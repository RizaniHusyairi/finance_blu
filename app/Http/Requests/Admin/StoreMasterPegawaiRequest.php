<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreMasterPegawaiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('Super Admin') === true;
    }

    public function rules(): array
    {
        return [
            'nama_lengkap'   => ['required', 'string', 'max:255'],
            'nip'            => ['nullable', 'string', 'max:30', 'unique:master_pegawai,nip'],
            'nik'            => ['nullable', 'string', 'max:16', 'unique:master_pegawai,nik'],
            'jabatan'        => ['nullable', 'string', 'max:255'],
            'nomor_hp'       => ['nullable', 'string', 'max:30'],
            'nomor_rekening' => ['nullable', 'string', 'max:50'],
            'nama_rekening'  => ['nullable', 'string', 'max:255'],
            'nama_bank'      => ['nullable', 'string', 'max:50'],
            'status_aktif'   => ['nullable', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'nama_lengkap'   => 'nama lengkap',
            'nip'            => 'NIP',
            'nik'            => 'NIK',
            'jabatan'        => 'jabatan',
            'nomor_hp'       => 'nomor HP',
            'nomor_rekening' => 'nomor rekening',
            'nama_rekening'  => 'nama rekening',
            'nama_bank'      => 'nama bank',
            'status_aktif'   => 'status aktif',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'status_aktif' => $this->boolean('status_aktif', true),
        ]);
    }
}
