<?php

namespace App\Http\Requests\Petugas;

use App\Models\KonfigurasiImport;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class KonfigurasiImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isPetugas() ?? false;
    }

    public function rules(): array
    {
        $id = $this->route('konfigurasi_import')?->id;

        return [
            'nama_profil'     => ['required', 'string', 'max:100'],
            'nama_sheet'      => ['required', 'string', 'max:100'],
            'alias_sheet'     => ['nullable', 'string', 'max:100'],
            'jenis_indikator' => ['required', 'string', 'max:50', 'alpha_dash'],
            'label'           => [
                'required', 'string', 'max:100',
                Rule::unique('konfigurasi_import', 'label')
                    ->where('nama_profil', $this->input('nama_profil'))
                    ->where('nama_sheet', $this->input('nama_sheet'))
                    ->where('jenis_indikator', $this->input('jenis_indikator'))
                    ->ignore($id),
            ],
            'teks_header'                  => ['required', 'string', 'max:255'],
            'offset_kolom'                 => ['required', 'integer', 'min:0', 'max:200'],
            'orientasi'                    => ['required', Rule::in([
                KonfigurasiImport::ORIENTASI_BARIS,
                KonfigurasiImport::ORIENTASI_KOLOM,
                KonfigurasiImport::ORIENTASI_BLOK,
            ])],
            'teks_header_wilayah'          => ['required', 'string', 'max:100'],
            'baris_maks_pencarian_header'  => ['required', 'integer', 'min:1', 'max:200'],
            'baris_mulai_pencarian_header' => ['required', 'integer', 'min:0', 'max:200'],
            'aktif'                        => ['nullable', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'nama_profil'     => 'nama profil',
            'nama_sheet'      => 'nama sheet',
            'alias_sheet'     => 'nama alternatif sheet',
            'jenis_indikator' => 'jenis indikator',
            'label'           => 'label',
            'teks_header'     => 'teks header yang dicari',
            'offset_kolom'    => 'offset kolom',
            'orientasi'       => 'orientasi sheet',
            'teks_header_wilayah'          => 'teks header kolom wilayah',
            'baris_maks_pencarian_header'  => 'baris maksimum pencarian header',
            'baris_mulai_pencarian_header' => 'baris mulai pencarian header',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['aktif' => $this->boolean('aktif')]);
    }
}
