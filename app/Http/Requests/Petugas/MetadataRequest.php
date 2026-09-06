<?php

namespace App\Http\Requests\Petugas;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MetadataRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isPetugas() ?? false;
    }

    public function rules(): array
    {
        $id = $this->route('metadata')?->id;

        return [
            'jenis_indikator' => [
                'required', 'string', 'max:50', 'alpha_dash',
                Rule::unique('metadatas', 'jenis_indikator')->ignore($id),
            ],
            'nama'           => ['required', 'string', 'max:100'],
            'modul'          => ['required', 'string', 'max:50'],
            'definisi'       => ['required', 'string'],
            'satuan'         => ['required', 'string', 'max:50'],
            'sumber'         => ['required', 'string', 'max:100'],
            'periode_update' => ['required', 'string', 'max:30'],
        ];
    }

    public function attributes(): array
    {
        return [
            'jenis_indikator' => 'kode indikator',
            'nama'            => 'nama indikator',
            'modul'           => 'modul',
            'definisi'        => 'definisi',
            'satuan'          => 'satuan',
            'sumber'          => 'sumber data',
            'periode_update'  => 'periode pembaruan',
        ];
    }

    public function messages(): array
    {
        return [
            'jenis_indikator.alpha_dash' => 'Kode indikator hanya boleh huruf, angka, garis bawah, dan tanda hubung (mis. kepemilikan_ktp).',
        ];
    }
}
