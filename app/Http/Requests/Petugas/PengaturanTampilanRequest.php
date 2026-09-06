<?php

namespace App\Http\Requests\Petugas;

use Illuminate\Foundation\Http\FormRequest;

class PengaturanTampilanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isPetugas() ?? false;
    }

    public function rules(): array
    {
        return [
            'latar_belakang' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'hapus_latar'    => ['nullable', 'boolean'],
            'logo'           => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'hapus_logo'     => ['nullable', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'latar_belakang' => 'gambar latar belakang header',
            'logo'           => 'logo aplikasi',
        ];
    }
}
