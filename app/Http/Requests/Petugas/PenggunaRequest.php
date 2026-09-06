<?php

namespace App\Http\Requests\Petugas;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class PenggunaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isPetugas() ?? false;
    }

    public function rules(): array
    {
        $pengguna = $this->route('pengguna');
        $isUpdate = $pengguna !== null;

        return [
            'name'  => ['required', 'string', 'max:255'],
            'email' => [
                'required', 'string', 'email', 'max:255',
                Rule::unique('users', 'email')->ignore($pengguna?->id),
            ],
            // Saat update, password opsional — kosongkan berarti tidak diubah
            'password' => [
                $isUpdate ? 'nullable' : 'required',
                'confirmed',
                Password::min(8)->letters()->numbers(),
            ],
        ];
    }

    public function attributes(): array
    {
        return [
            'name'  => 'nama',
            'email' => 'email',
        ];
    }
}
