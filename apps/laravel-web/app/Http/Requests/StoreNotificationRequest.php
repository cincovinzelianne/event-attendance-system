<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreNotificationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return (bool) $this->user()?->isAdmin();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:2000'],
            'type' => ['nullable', 'string', 'max:30'],
            'target_role' => ['required', 'in:all,admin,student'],
            'target_department' => ['nullable', 'string', 'max:120'],
            'target_user_ids' => ['nullable', 'array'],
            'target_user_ids.*' => ['integer', 'exists:users,id'],
            'scheduled_for' => ['nullable', 'date', 'after:now'],
        ];
    }
}
