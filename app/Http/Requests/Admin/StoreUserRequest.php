<?php

namespace App\Http\Requests\Admin;

use App\Models\MasterPegawai;
use App\Models\MitraJasa;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('Super Admin') === true;
    }

    public function rules(): array
    {
        return [
            'tipe_akun'      => ['required', Rule::in(['pegawai', 'mitra', 'sistem'])],
            'email'          => ['required', 'email', 'max:255', 'unique:users,email'],
            'password'       => ['nullable', 'confirmed', Password::min(8)->numbers()],
            'roles'          => ['required', 'array', 'min:1'],
            'roles.*'        => ['string', 'exists:roles,name'],

            // Pegawai
            'pegawai_id'     => [
                Rule::requiredIf(fn () => $this->input('tipe_akun') === 'pegawai'),
                'nullable',
                'integer',
                Rule::exists('master_pegawai', 'id')->whereNull('deleted_at'),
            ],

            // Mitra
            'mitra_id'       => [
                Rule::requiredIf(fn () => $this->input('tipe_akun') === 'mitra'),
                'nullable',
                'integer',
                Rule::exists('mitra_jasa', 'id')->whereNull('deleted_at'),
            ],
        ];
    }

    public function attributes(): array
    {
        return [
            'tipe_akun'  => 'tipe akun',
            'email'      => 'email',
            'password'   => 'password',
            'roles'      => 'role',
            'pegawai_id' => 'pegawai',
            'mitra_id'   => 'mitra',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $tipe = $this->input('tipe_akun');
            $roles = (array) $this->input('roles', []);

            if ($tipe === 'sistem' && ! in_array('Super Admin', $roles, true)) {
                $validator->errors()->add('roles', 'Akun sistem hanya boleh diberi role Super Admin.');
            }

            if ($tipe === 'mitra' && ! in_array('Mitra Jasa', $roles, true) && ! in_array('Mitra', $roles, true)) {
                $validator->errors()->add('roles', 'Akun mitra minimal harus memiliki role Mitra Jasa.');
            }
        });
    }
}
