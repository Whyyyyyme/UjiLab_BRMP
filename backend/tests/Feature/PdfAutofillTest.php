<?php

namespace Tests\Feature;

use App\Services\PdfExtractionService;
use Smalot\PdfParser\Parser;
use Smalot\PdfParser\Document;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PdfAutofillTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Reset gemini config key to null by default for other tests
        config(['services.gemini.api_key' => null]);
    }

    /**
     * Test autofill PDF berhasil mendeteksi label-label yang sesuai menggunakan Regex.
     */
    public function test_autofill_pdf_berhasil_ekstraksi_data(): void
    {
        // Mocking parser dan document PDF
        $mockParser = $this->createMock(Parser::class);
        $mockDocument = $this->createMock(Document::class);
        
        $pdfText = "Laporan Hasil Uji\n" .
                   "Nomor Pengujian: UJI-2026-99\n" .
                   "Nama Pemohon: John Doe\n" .
                   "Email: john.doe@example.com\n" .
                   "Jenis Pengujian: Deteksi GMO\n" .
                   "Tanggal: 13-08-2026";
                   
        $mockParser->method('parseFile')->willReturn($mockDocument);
        $mockDocument->method('getText')->willReturn($pdfText);

        $service = new PdfExtractionService($mockParser);
        $hasil = $service->extract('mock_path.pdf');

        $this->assertEquals('UJI-2026-99', $hasil['nomor_pengujian']);
        $this->assertEquals('John Doe', $hasil['nama_pemohon']);
        $this->assertEquals('john.doe@example.com', $hasil['email_pemohon']);
        $this->assertEquals('Deteksi GMO', $hasil['jenis_pengujian']);
        $this->assertEquals('regex', $hasil['method']);
    }

    /**
     * Test autofill PDF mengembalikan null jika label tidak dikenali dengan Regex.
     */
    public function test_autofill_pdf_kosong_jika_label_tidak_ditemukan(): void
    {
        $mockParser = $this->createMock(Parser::class);
        $mockDocument = $this->createMock(Document::class);
        
        // Teks acak tanpa label yang terdaftar
        $pdfText = "Halo Dunia, ini teks biasa tanpa format label.";
                   
        $mockParser->method('parseFile')->willReturn($mockDocument);
        $mockDocument->method('getText')->willReturn($pdfText);

        $service = new PdfExtractionService($mockParser);
        $hasil = $service->extract('mock_path.pdf');

        // Harus bernilai null, tidak boleh ditebak (keamanan data)
        $this->assertNull($hasil['nomor_pengujian']);
        $this->assertNull($hasil['nama_pemohon']);
        $this->assertNull($hasil['email_pemohon']);
        $this->assertNull($hasil['jenis_pengujian']);
        $this->assertEquals('regex', $hasil['method']);
    }

    /**
     * Test ekstraksi PDF menggunakan Gemini AI ketika API Key tersedia.
     */
    public function test_autofill_pdf_dengan_gemini_api_sukses(): void
    {
        config(['services.gemini.api_key' => 'dummy-api-key']);

        Http::fake([
            'https://generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                [
                                    'text' => json_encode([
                                        'nomor_pengujian' => 'UJI-GEMINI-88',
                                        'nama_pemohon' => 'Budi Gemini',
                                        'email_pemohon' => 'budi.gemini@example.com',
                                        'jenis_pengujian' => 'Deteksi GMO',
                                    ])
                                ]
                            ]
                        ]
                    ]
                ]
            ], 200)
        ]);

        $mockParser = $this->createMock(Parser::class);
        $mockDocument = $this->createMock(Document::class);
        
        $mockParser->method('parseFile')->willReturn($mockDocument);
        $mockDocument->method('getText')->willReturn('Teks Laporan Hasil Uji Lab');

        $service = new PdfExtractionService($mockParser);
        $hasil = $service->extract('mock_path.pdf');

        $this->assertEquals('UJI-GEMINI-88', $hasil['nomor_pengujian']);
        $this->assertEquals('Budi Gemini', $hasil['nama_pemohon']);
        $this->assertEquals('budi.gemini@example.com', $hasil['email_pemohon']);
        $this->assertEquals('Deteksi GMO', $hasil['jenis_pengujian']);
        $this->assertEquals('ai', $hasil['method']);
    }

    /**
     * Test fallback ke Regex ketika API Key tersedia tapi panggilan API Gemini gagal (500 Error).
     */
    public function test_autofill_pdf_dengan_gemini_api_gagal_dan_fallback_ke_regex(): void
    {
        config(['services.gemini.api_key' => 'dummy-api-key']);

        Http::fake([
            'https://generativelanguage.googleapis.com/*' => Http::response([
                'error' => 'Internal Server Error'
            ], 500)
        ]);

        $mockParser = $this->createMock(Parser::class);
        $mockDocument = $this->createMock(Document::class);
        
        $pdfText = "Laporan Hasil Uji\n" .
                   "Nomor Pengujian: UJI-FALLBACK-77\n" .
                   "Nama Pemohon: John Fallback\n" .
                   "Email: fallback@example.com\n" .
                   "Jenis Pengujian: Deteksi GMO\n";
                   
        $mockParser->method('parseFile')->willReturn($mockDocument);
        $mockDocument->method('getText')->willReturn($pdfText);

        $service = new PdfExtractionService($mockParser);
        $hasil = $service->extract('mock_path.pdf');

        // Hasil harus diperoleh dari Regex pencarian fallback
        $this->assertEquals('UJI-FALLBACK-77', $hasil['nomor_pengujian']);
        $this->assertEquals('John Fallback', $hasil['nama_pemohon']);
        $this->assertEquals('fallback@example.com', $hasil['email_pemohon']);
        $this->assertEquals('Deteksi GMO', $hasil['jenis_pengujian']);
        $this->assertEquals('regex', $hasil['method']);
    }
}