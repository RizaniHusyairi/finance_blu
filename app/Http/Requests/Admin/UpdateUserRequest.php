<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('Super Admin') === true;
    }

    public function rules(): array
    {
        $userId = $this->route('user')?->id;

        return [
            'email'        => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
            'roles'        => ['required', 'array', 'min:1'],
            'roles.*'      => ['string', 'exists:roles,name'],
            'batasi_masa_aktif' => ['nullable', 'boolean'],
            'active_from'  => [Rule::requiredIf(fn () => $this->limitsActivePeriod()), 'nullable', 'date'],
            'active_until' => [
                Rule::requiredIf(fn () => $this->limitsActivePeriod()),
                'nullable',
                'date',
                'after_or_equal:active_from',
                'after_or_equal:today',
            ],
        ];
    }

    public function attributes(): array
    {
        return [
            'roles' => 'role',
            'active_from' => 'tanggal mulai aktif',
            'active_until' => 'tanggal selesai aktif',
        ];
    }

    private function hasTemporaryRole(): bool
    {
        return in_array('PLT/PLH', (array) $this->input('roles', []), true);
    }

    /**
     * Masa aktif hanya wajib ketika role PLT/PLH dipilih DAN toggle masa aktif aktif.
     */
    private function limitsActivePeriod(): bool
    {
        return $this->hasTemporaryRole() && $this->boolean('batasi_masa_aktif');
    }
}
