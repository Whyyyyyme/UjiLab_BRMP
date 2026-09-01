<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PengujianRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $id = $this->route('id') ?: $this->route('pengujian'); // Support both id or model binding path params

        $pdfChecker = function ($attribute, $value, $fail) {
            if ($value && $value->isValid()) {
                $handle = fopen($value->getPathname(), 'r');
                if ($handle) {
                    $firstBytes = fread($handle, 4);
                    fclose($handle);
                    if ($firstBytes !== '%PDF') {
                        $label = $attribute === 'file_laporan' ? 'Laporan Hasil Pengujian' : 'Sertifikat';
                        $fail("Konten berkas {$label} bukan PDF asli.");
                    }
                } else {
                    $fail("Tidak dapat membaca berkas.");
                }
            }
        };

        return [
            'nomor_pengujian' => 'required|string|unique:pengujian,nomor_pengujian,' . $id,
            'nama_pemohon' => 'required|string|max:255',
            'email_pemohon' => 'required|email|max:255',
            'jenis_pengujian' => 'required|string|in:' . implode(',', [
                'Analisis SSR/RAPD',
                'Deteksi GMO',
                'Deteksi Virus secara Molekuler',
                'Analisis Ploidi Level',
                'Uji Mutu Benih (ISTA)',
                'Liofilisasi',
                'Enumerasi Total Mikroba Bakteri/Cendawan',
                'Deteksi Mikroba secara Molekuler (Bakteri/Cendawan)',
                'Uji Sensitivitas Bakteri'
            ]),
            'file_laporan' => [
                'nullable',
                'file',
                'mimes:pdf',
                'max:10240', // 10MB
                $pdfChecker
            ],
            'file_sertifikat' => [
                'nullable',
                'file',
                'mimes:pdf',
                'max:10240', // 10MB
                $pdfChecker
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'nomor_pengujian.required' => 'Nomor pengujian wajib diisi.',
            'nomor_pengujian.unique' => 'Nomor pengujian sudah terdaftar di sistem.',
            'nama_pemohon.required' => 'Nama pemohon wajib diisi.',
            'email_pemohon.required' => 'Email pemohon wajib diisi.',
            'email_pemohon.email' => 'Format email pemohon tidak valid.',
            'jenis_pengujian.required' => 'Jenis pengujian wajib dipilih.',
            'jenis_pengujian.in' => 'Jenis pengujian tidak valid.',
            'file_laporan.file' => 'Berkas laporan harus berupa file.',
            'file_laporan.mimes' => 'Berkas laporan harus berformat PDF.',
            'file_laporan.max' => 'Ukuran berkas laporan maksimal adalah 10MB.',
            'file_sertifikat.file' => 'Berkas sertifikat harus berupa file.',
            'file_sertifikat.mimes' => 'Berkas sertifikat harus berformat PDF.',
            'file_sertifikat.max' => 'Ukuran berkas sertifikat maksimal adalah 10MB.',
        ];
    }
}