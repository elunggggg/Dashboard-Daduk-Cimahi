<?php

namespace App\Http\Requests\Petugas;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PengaturanExportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isPetugas() ?? false;
    }

    public function rules(): array
    {
        return [
            'format_bawaan'   => ['required', Rule::in(['excel', 'pdf'])],
            'batas_baris_pdf' => ['required', 'integer', 'min:100', 'max:100000'],
            'orientasi_pdf'   => ['required', Rule::in(['potrait', 'landscape'])],
            'kop_judul'       => ['nullable', 'string', 'max:150'],
            'kop_subjudul'    => ['nullable', 'string', 'max:200'],
        ];
    }

    public function attributes(): array
    {
        return [
            'format_bawaan'   => 'format bawaan',
            'batas_baris_pdf' => 'batas baris PDF',
            'orientasi_pdf'   => 'orientasi PDF',
            'kop_judul'       => 'judul kop',
            'kop_subjudul'    => 'subjudul kop',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'kop_judul'    => trim((string) $this->kop_judul) ?: null,
            'kop_subjudul' => trim((string) $this->kop_subjudul) ?: null,
        ]);
    }
}
