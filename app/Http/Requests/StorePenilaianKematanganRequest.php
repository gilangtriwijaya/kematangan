<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePenilaianKematanganRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Ubah sesuai kebutuhan autentikasi
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'bukti'   => 'required|array',
            'bukti.*' => 'required|file|mimes:pdf|max:51200', 
            // max dalam KB → 51200 KB = 50 MB
        ];
    }

    public function messages(): array
    {
        return [
            'bukti.required'      => 'Harap unggah minimal 1 file bukti.',
            'bukti.array'         => 'Format unggahan tidak sesuai.',
            'bukti.*.required'    => 'File bukti tidak boleh kosong.',
            'bukti.*.file'        => 'Setiap bukti harus berupa file.',
            'bukti.*.mimes'       => 'File bukti harus berupa PDF.',
            'bukti.*.max'         => 'Ukuran file maksimal 50 MB per bukti.',
        ];
    }
}
