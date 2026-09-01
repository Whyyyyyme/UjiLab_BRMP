<?php

namespace App\Services;

use Smalot\PdfParser\Parser;
use Exception;

class PdfExtractionService
{
    protected Parser $parser;

    public function __construct(Parser $parser = null)
    {
        $this->parser = $parser ?: new Parser();
    }

    /**
     * Ekstraksi data dari file PDF menggunakan Gemini AI (jika API Key dikonfigurasi) atau Fallback ke Regex.
     *
     * @param string $filePath
     * @return array
     */
    public function extract(string $filePath): array
    {
        $apiKey = config('services.gemini.api_key');
        
        try {
            $pdf = $this->parser->parseFile($filePath);
            $text = trim($pdf->getText());

            if (empty($text)) {
                return [
                    'nomor_pengujian' => null,
                    'nama_pemohon' => null,
                    'email_pemohon' => null,
                    'jenis_pengujian' => null,
                    'method' => 'regex',
                ];
            }

            if ($apiKey) {
                // Kirim HTTP request ke Google Gemini API
                $response = \Illuminate\Support\Facades\Http::timeout(10)
                    ->post("https://generativelanguage.googleapis.com/v1/models/gemini-3.6-flash:generateContent?key={$apiKey}", [
                        'contents' => [
                            [
                                'parts' => [
                                    [
                                        'text' => $this->getGeminiPrompt($text)
                                    ]
                                ]
                            ]
                        ],
                        'generationConfig' => [
                            'responseMimeType' => 'application/json'
                        ]
                    ]);

                if ($response->successful()) {
                    $resJson = $response->json();
                    $aiText = $resJson['candidates'][0]['content']['parts'][0]['text'] ?? '';
                    $data = json_decode(trim($aiText), true);

                    if (is_array($data)) {
                        $hasil = [
                            'nomor_pengujian' => $data['nomor_pengujian'] ?? null,
                            'nama_pemohon' => $data['nama_pemohon'] ?? null,
                            'email_pemohon' => $data['email_pemohon'] ?? null,
                            'jenis_pengujian' => $data['jenis_pengujian'] ?? null,
                            'method' => 'ai',
                        ];

                        // Pastikan jenis_pengujian divalidasi ke daftar resmi
                        $hasil['jenis_pengujian'] = $this->validateJenisPengujian($hasil['jenis_pengujian']);

                        return $hasil;
                    }
                }
                
                logger()->warning('Gemini API call failed, falling back to regex. Status: ' . $response->status());
            }
        } catch (Exception $exception) {
            logger()->error('PDF AI Extraction Error (falling back to regex): ' . $exception->getMessage());
        }

        // Fallback ke Regex
        return $this->extractViaRegex($filePath);
    }

    /**
     * Pola prompt instruksi Gemini untuk pengekstrakan dokumen hasil uji lab.
     */
    protected function getGeminiPrompt(string $text): string
    {
        $jenisList = implode("\n- ", [
            'Analisis SSR/RAPD',
            'Deteksi GMO',
            'Deteksi Virus secara Molekuler',
            'Analisis Ploidi Level',
            'Uji Mutu Benih (ISTA)',
            'Liofilisasi',
            'Enumerasi Total Mikroba Bakteri/Cendawan',
            'Deteksi Mikroba secara Molekuler (Bakteri/Cendawan)',
            'Uji Sensitivitas Bakteri'
        ]);

        return "Anda adalah asisten AI yang bertugas mengekstrak metadata dari dokumen hasil uji lab.
Harap analisis teks laporan berikut dan kembalikan JSON objek dengan format tepat:
{
  \"nomor_pengujian\": \"nomor pengujian/uji/sampel yang ditemukan, atau null jika tidak ada\",
  \"nama_pemohon\": \"nama pemohon/instansi/pelanggan yang ditemukan, atau null jika tidak ada\",
  \"email_pemohon\": \"alamat email pemohon/pelanggan yang ditemukan, atau null jika tidak ada\",
  \"jenis_pengujian\": \"salah satu dari daftar jenis pengujian resmi di bawah, atau null jika tidak ada\"
}

Daftar jenis pengujian resmi yang diperbolehkan (pilih salah satu yang paling mendekati, atau null jika tidak cocok sama sekali):
- {$jenisList}

Teks Laporan:
\"\"\"
{$text}
\"\"\"";
    }

    /**
     * Memastikan jenis pengujian cocok dengan daftar resmi.
     */
    protected function validateJenisPengujian(?string $jenis): ?string
    {
        if (!$jenis) {
            return null;
        }

        $list = [
            'Analisis SSR/RAPD',
            'Deteksi GMO',
            'Deteksi Virus secara Molekuler',
            'Analisis Ploidi Level',
            'Uji Mutu Benih (ISTA)',
            'Liofilisasi',
            'Enumerasi Total Mikroba Bakteri/Cendawan',
            'Deteksi Mikroba secara Molekuler (Bakteri/Cendawan)',
            'Uji Sensitivitas Bakteri'
        ];

        // Pencocokan ketat (exact match)
        if (in_array($jenis, $list)) {
            return $jenis;
        }

        // Pencocokan longgar (case-insensitive)
        foreach ($list as $item) {
            if (strcasecmp($jenis, $item) === 0) {
                return $item;
            }
        }

        return null;
    }

    /**
     * Fallback ekstraksi berbasis Regular Expression.
     */
    protected function extractViaRegex(string $filePath): array
    {
        $hasil = [
            'nomor_pengujian' => null,
            'nama_pemohon' => null,
            'email_pemohon' => null,
            'jenis_pengujian' => null,
            'method' => 'regex',
        ];

        try {
            $pdf = $this->parser->parseFile($filePath);
            $text = $pdf->getText();

            $labelVariasi = [
                'nomor_pengujian' => ['Nomor Pengujian', 'No. Pengujian', 'Nomor Uji', 'No Sampel'],
                'nama_pemohon'    => ['Nama Pemohon', 'Nama Pengguna Jasa', 'Diajukan oleh', 'Nama Penguji'],
                'jenis_pengujian' => ['Jenis Pengujian', 'Jenis Uji', 'Pengujian'],
            ];

            foreach ($labelVariasi as $field => $variasiLabel) {
                foreach ($variasiLabel as $label) {
                    if (preg_match('/' . preg_quote($label, '/') . '\s*:?\s*(.+)/i', $text, $match)) {
                        $hasil[$field] = trim($match[1]);
                        break;
                    }
                }
            }

            // Cari email pemohon menggunakan pencarian email
            if (preg_match('/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/', $text, $match)) {
                $hasil['email_pemohon'] = trim($match[0]);
            }

            $hasil['jenis_pengujian'] = $this->validateJenisPengujian($hasil['jenis_pengujian']);
        } catch (Exception $exception) {
            logger()->error('Regex PDF Extraction Error: ' . $exception->getMessage());
        }

        return $hasil;
    }
}