<x-admin-layout title="System Settings" subtitle="Configure attendance, QR, notification, and organization defaults.">
    <div class="max-w-4xl rounded-3xl border border-white/10 bg-blue-950/80 p-6 shadow-2xl shadow-black/30 backdrop-blur-xl">
        <form method="POST" action="{{ route('admin.settings.update') }}" class="grid gap-4 sm:grid-cols-2">
            @csrf
            @method('PUT')

            <div>
                <label for="school_name" class="block text-sm font-medium text-slate-200">School / Organization Name</label>
                <input id="school_name" name="school_name" type="text" value="{{ old('school_name', $settings['school_name']) }}" class="mt-1 block w-full rounded-xl border-white/10 bg-white/5 text-white shadow-sm focus:border-yellow-300 focus:ring-yellow-300">
                @error('school_name')<p class="mt-1 text-sm text-yellow-200">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="attendance_grace_minutes" class="block text-sm font-medium text-slate-200">Late Grace Minutes</label>
                <input id="attendance_grace_minutes" name="attendance_grace_minutes" type="number" min="0" max="180" value="{{ old('attendance_grace_minutes', $settings['attendance_grace_minutes']) }}" required class="mt-1 block w-full rounded-xl border-white/10 bg-white/5 text-white shadow-sm focus:border-yellow-300 focus:ring-yellow-300">
                @error('attendance_grace_minutes')<p class="mt-1 text-sm text-yellow-200">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="default_checkin_window_minutes" class="block text-sm font-medium text-slate-200">Default Check-in Window (Minutes)</label>
                <input id="default_checkin_window_minutes" name="default_checkin_window_minutes" type="number" min="5" max="720" value="{{ old('default_checkin_window_minutes', $settings['default_checkin_window_minutes']) }}" required class="mt-1 block w-full rounded-xl border-white/10 bg-white/5 text-white shadow-sm focus:border-yellow-300 focus:ring-yellow-300">
                @error('default_checkin_window_minutes')<p class="mt-1 text-sm text-yellow-200">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="qr_expiry_minutes" class="block text-sm font-medium text-slate-200">QR Expiry (Minutes)</label>
                <input id="qr_expiry_minutes" name="qr_expiry_minutes" type="number" min="1" max="1440" value="{{ old('qr_expiry_minutes', $settings['qr_expiry_minutes']) }}" required class="mt-1 block w-full rounded-xl border-white/10 bg-white/5 text-white shadow-sm focus:border-yellow-300 focus:ring-yellow-300">
                @error('qr_expiry_minutes')<p class="mt-1 text-sm text-yellow-200">{{ $message }}</p>@enderror
            </div>

            <div class="sm:col-span-2 grid gap-3 sm:grid-cols-2">
                <label class="inline-flex items-center gap-2 rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm text-slate-200">
                    <input type="checkbox" name="notification_email_enabled" value="1" @checked(old('notification_email_enabled', $settings['notification_email_enabled'])) class="rounded border-white/20 bg-white/5 text-yellow-300 focus:ring-yellow-300">
                    Enable Email Notifications
                </label>
                <label class="inline-flex items-center gap-2 rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm text-slate-200">
                    <input type="checkbox" name="notification_sms_enabled" value="1" @checked(old('notification_sms_enabled', $settings['notification_sms_enabled'])) class="rounded border-white/20 bg-white/5 text-yellow-300 focus:ring-yellow-300">
                    Enable SMS Notifications
                </label>
            </div>

            <div class="sm:col-span-2 flex items-center gap-3">
                <button type="submit" class="rounded-xl bg-gradient-to-r from-blue-700 to-yellow-500 px-4 py-2 text-sm font-semibold text-slate-950 transition hover:-translate-y-0.5">Save Settings</button>
            </div>
        </form>
    </div>
</x-admin-layout>
