<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadHasilRequest extends FormRequest
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
            'file_laporan' => [
                'nullable',
                'file',
                'mimes:pdf',
                'max:51200', // 50MB
                $pdfChecker
            ],
            'file_sertifikat' => [
                'nullable',
                'file',
                'mimes:pdf',
                'max:51200', // 50MB
                $pdfChecker
            ],
            'nomor_pengujian' => 'nullable|string|max:100',
            'nama_pemohon' => 'nullable|string|max:255',
            'email_pemohon' => 'nullable|email|max:255',
            'jenis_pengujian' => 'nullable|string|max:1000',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'file_laporan.file' => 'Berkas laporan harus berupa file.',
            'file_laporan.mimes' => 'Berkas laporan harus berformat PDF.',
            'file_laporan.max' => 'Ukuran berkas laporan maksimal adalah 50MB.',
            'file_sertifikat.file' => 'Berkas sertifikat harus berupa file.',
            'file_sertifikat.mimes' => 'Berkas sertifikat harus berformat PDF.',
            'file_sertifikat.max' => 'Ukuran berkas sertifikat maksimal adalah 50MB.',
        ];
    }
}
