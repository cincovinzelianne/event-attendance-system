<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ImportUsersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->isAdmin();
    }

    public function rules(): array
    {
        return [
            'csv_file' => ['required', 'file', 'mimes:csv,txt', 'max:10240'],
            'default_role' => ['nullable', 'in:admin,student'],
        ];
    }
}
