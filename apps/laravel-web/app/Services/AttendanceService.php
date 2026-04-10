<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Event;
use App\Models\User;
use App\Repositories\AttendanceRepository;
use DomainException;

class AttendanceService
{
    public function __construct(
        private readonly AttendanceRepository $attendances,
        private readonly SettingsService $settings,
    ) {
    }

    public function checkIn(Event $event, User $student, string $scanSource = 'web'): Attendance
    {
        if ($event->attendance_locked) {
            throw new DomainException('Attendance is locked for this event.');
        }

        $now = now();
        $windowStart = $event->checkin_start_at ?? $event->starts_at;

        $windowGraceMinutes = (int) $this->settings->get('attendance.grace_minutes', 15);
        $windowEnd = $event->checkin_end_at ?? ($event->ends_at ?? $event->starts_at?->copy()->addHours(3));

        if ($windowStart && $now->lt($windowStart)) {
            throw new DomainException('Check-in is not yet open for this event.');
        }

        if ($windowEnd && $now->gt($windowEnd)) {
            throw new DomainException('Check-in window is already closed for this event.');
        }

        $existing = $this->attendances->findForEventAndUser((int) $event->id, (int) $student->id);

        if ($existing) {
            return $existing;
        }

        $status = 'on_time';

        if ($event->starts_at && $now->gt($event->starts_at->copy()->addMinutes($windowGraceMinutes))) {
            $status = 'late';
        }

        return $this->attendances->create([
            'event_id' => $event->id,
            'user_id' => $student->id,
            'checked_in_at' => $now,
            'status' => $status,
            'scan_source' => $scanSource,
        ]);
    }

    public function setLock(Event $event, bool $locked): Event
    {
        $event->update(['attendance_locked' => $locked]);

        return $event->refresh();
    }
}
