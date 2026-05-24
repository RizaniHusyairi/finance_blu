<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMasterPegawaiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('Super Admin') === true;
    }

    public function rules(): array
    {
        $pegawaiId = $this->route('pegawai')?->id;

        return [
            'nama_lengkap'   => ['required', 'string', 'max:255'],
            'nip'            => ['nullable', 'string', 'max:30', Rule::unique('master_pegawai', 'nip')->ignore($pegawaiId)],
            'nik'            => ['nullable', 'string', 'max:16', Rule::unique('master_pegawai', 'nik')->ignore($pegawaiId)],
            'jabatan'        => ['nullable', 'string', 'max:255'],
            'nomor_hp'       => ['nullable', 'string', 'max:30'],
            'nomor_rekening' => ['nullable', 'string', 'max:50'],
            'nama_rekening'  => ['nullable', 'string', 'max:255'],
            'nama_bank'      => ['nullable', 'string', 'max:50'],
            'status_aktif'   => ['nullable', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'status_aktif' => $this->boolean('status_aktif'),
        ]);
    }
}
