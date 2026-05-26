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
            'active_from'  => [Rule::requiredIf(fn () => $this->hasTemporaryRole()), 'nullable', 'date'],
            'active_until' => [
                Rule::requiredIf(fn () => $this->hasTemporaryRole()),
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
}
