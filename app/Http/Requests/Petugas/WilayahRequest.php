<?php

namespace App\Http\Requests\Petugas;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class WilayahRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isPetugas() ?? false;
    }

    public function rules(): array
    {
        $id = $this->route('wilayah')?->id;

        return [
            'kode_kemendagri' => [
                'required', 'string', 'max:20',
                'regex:/^\d{2}\.\d{2}\.\d{2}\.\d{4}$/',
                Rule::unique('dim_wilayah', 'kode_kemendagri')->ignore($id),
            ],
            'nama_kelurahan' => ['required', 'string', 'max:100'],
            'nama_kecamatan' => ['required', 'string', 'max:100'],
            'luas_km2'       => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
        ];
    }

    public function attributes(): array
    {
        return [
            'kode_kemendagri' => 'kode Kemendagri',
            'nama_kelurahan'  => 'nama kelurahan',
            'nama_kecamatan'  => 'nama kecamatan',
            'luas_km2'        => 'luas wilayah',
        ];
    }

    public function messages(): array
    {
        return [
            'kode_kemendagri.regex' => 'Format kode Kemendagri harus xx.xx.xx.xxxx (contoh: 32.77.01.1001).',
        ];
    }
}
