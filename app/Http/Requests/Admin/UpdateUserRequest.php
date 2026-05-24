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
            'email'   => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
            'roles'   => ['required', 'array', 'min:1'],
            'roles.*' => ['string', 'exists:roles,name'],
        ];
    }
}
