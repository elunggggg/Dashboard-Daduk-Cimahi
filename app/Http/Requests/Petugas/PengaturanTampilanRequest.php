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
            'nama_sistem'    => ['nullable', 'string', 'max:60'],
            'nama_instansi'  => ['nullable', 'string', 'max:120'],
            'latar_belakang' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'hapus_latar'    => ['nullable', 'boolean'],
            'logo'           => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'hapus_logo'     => ['nullable', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'nama_sistem'    => 'nama sistem',
            'nama_instansi'  => 'nama instansi',
            'latar_belakang' => 'gambar latar belakang header',
            'logo'           => 'logo aplikasi',
        ];
    }
}
