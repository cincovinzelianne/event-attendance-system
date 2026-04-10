<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->isAdmin();
    }

    public function rules(): array
    {
        return [
            'attendance_grace_minutes' => ['required', 'integer', 'min:0', 'max:180'],
            'default_checkin_window_minutes' => ['required', 'integer', 'min:5', 'max:720'],
            'qr_expiry_minutes' => ['required', 'integer', 'min:1', 'max:1440'],
            'notification_email_enabled' => ['nullable', 'boolean'],
            'notification_sms_enabled' => ['nullable', 'boolean'],
            'school_name' => ['nullable', 'string', 'max:255'],
        ];
    }
}
