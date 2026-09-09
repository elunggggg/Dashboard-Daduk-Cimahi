<?php

namespace App\Http\Requests\Petugas;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndikatorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isPetugas() ?? false;
    }

    public function rules(): array
    {
        $id = $this->route('indikator')?->id;

        return [
            'jenis_indikator' => ['required', 'string', 'max:50', 'alpha_dash'],
            'label' => [
                'required', 'string', 'max:100',
                Rule::unique('dim_kategori', 'label')
                    ->where('jenis_indikator', $this->input('jenis_indikator'))
                    ->ignore($id),
            ],
            'urutan' => ['required', 'integer', 'min:0', 'max:9999'],
            'aktif'  => ['nullable', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'jenis_indikator' => 'kelompok indikator',
            'label'           => 'nama kategori',
            'urutan'          => 'urutan tampil',
            'aktif'           => 'status aktif',
        ];
    }

    public function messages(): array
    {
        return [
            'jenis_indikator.alpha_dash' => 'Kelompok indikator hanya boleh berisi huruf, angka, garis bawah (_), dan strip (-) — tanpa spasi. Contoh: kepemilikan_ktp.',
            'label.unique' => 'Nama kategori ini sudah ada di kelompok indikator yang sama.',
        ];
    }

    /** Checkbox yang tidak dicentang tidak ikut terkirim — disetel eksplisit ke false. */
    protected function prepareForValidation(): void
    {
        $this->merge(['aktif' => $this->boolean('aktif')]);
    }
}
