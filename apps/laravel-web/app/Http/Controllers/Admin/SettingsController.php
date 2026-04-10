<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateSettingsRequest;
use App\Services\SettingsService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class SettingsController extends Controller
{
    public function __construct(private readonly SettingsService $settings)
    {
    }

    public function index(): View
    {
        return view('admin.settings.index', [
            'settings' => [
                'attendance_grace_minutes' => (int) $this->settings->get('attendance.grace_minutes', 15),
                'default_checkin_window_minutes' => (int) $this->settings->get('attendance.default_checkin_window_minutes', 180),
                'qr_expiry_minutes' => (int) $this->settings->get('qr.expiry_minutes', 60),
                'notification_email_enabled' => (bool) $this->settings->get('notification.email_enabled', false),
                'notification_sms_enabled' => (bool) $this->settings->get('notification.sms_enabled', false),
                'school_name' => (string) $this->settings->get('system.school_name', 'Event Attendance System'),
            ],
        ]);
    }

    public function update(UpdateSettingsRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $this->settings->set('attendance.grace_minutes', (int) $validated['attendance_grace_minutes'], 'attendance');
        $this->settings->set('attendance.default_checkin_window_minutes', (int) $validated['default_checkin_window_minutes'], 'attendance');
        $this->settings->set('qr.expiry_minutes', (int) $validated['qr_expiry_minutes'], 'qr');
        $this->settings->set('notification.email_enabled', (bool) ($validated['notification_email_enabled'] ?? false), 'notification');
        $this->settings->set('notification.sms_enabled', (bool) ($validated['notification_sms_enabled'] ?? false), 'notification');
        $this->settings->set('system.school_name', (string) ($validated['school_name'] ?? ''), 'system');

        return redirect()->route('admin.settings.index')->with('status', 'System settings updated.');
    }
}
