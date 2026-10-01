<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SkmRequest extends FormRequest
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
        $rules = [
            'nama' => 'required|string|max:100',
            'jenis_kelamin' => 'required|in:Laki-laki,Perempuan',
            'pendidikan' => 'required|string',
            'usia' => 'required|string',
            'pekerjaan' => 'required|string',
            'disabilitas' => 'required|in:Ya,Tidak',
        ];
        
        for ($i = 1; $i <= 16; $i++) {
            $rules["u{$i}"] = 'required|integer|between:1,4';
        }
        return $rules;
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        $messages = [
            'nama.required' => 'Nama wajib diisi.',
            'jenis_kelamin.required' => 'Jenis kelamin wajib dipilih.',
            'jenis_kelamin.in' => 'Pilihan jenis kelamin tidak valid.',
            'pendidikan.required' => 'Pendidikan wajib dipilih.',
            'usia.required' => 'Rentang usia wajib dipilih.',
            'pekerjaan.required' => 'Pekerjaan wajib dipilih.',
            'disabilitas.required' => 'Status disabilitas wajib dipilih.',
            'disabilitas.in' => 'Pilihan status disabilitas tidak valid.',
        ];
        
        for ($i = 1; $i <= 16; $i++) {
            $messages["u{$i}.required"] = "Pilihan unsur pelayanan U{$i} wajib diisi.";
            $messages["u{$i}.integer"] = "Nilai unsur U{$i} harus berupa angka bulat.";
            $messages["u{$i}.between"] = "Nilai unsur U{$i} harus berada di antara 1 dan 4.";
        }

        return $messages;
    }
}
