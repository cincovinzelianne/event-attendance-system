<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreEventRequest extends FormRequest
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
            'description' => ['nullable', 'string'],
            'starts_at' => ['required', 'date'],
            'checkin_start_at' => ['nullable', 'date'],
            'checkin_end_at' => ['nullable', 'date', 'after_or_equal:checkin_start_at'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'location' => ['nullable', 'string', 'max:255'],
            'google_form_url' => ['nullable', 'url', 'max:500'],
            'target_department' => ['nullable', 'string', 'max:120'],
            'target_course' => ['nullable', 'string', 'max:120'],
            'target_year_level' => ['nullable', 'string', 'max:40'],
            'status' => ['nullable', 'in:upcoming,ongoing,completed'],
            'poster' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'attachment' => ['nullable', 'file', 'max:10240'],
            'attendance_locked' => ['nullable', 'boolean'],
        ];
    }
}
