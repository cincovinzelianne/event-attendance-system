<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Event;
use App\Models\User;
use App\Repositories\AttendanceRepository;
use DomainException;

class AttendanceService
{
    public function __construct(private readonly AttendanceRepository $attendances)
    {
    }

    public function checkIn(Event $event, User $student): Attendance
    {
        if ($event->attendance_locked) {
            throw new DomainException('Attendance is locked for this event.');
        }

        $existing = $this->attendances->findForEventAndUser((int) $event->id, (int) $student->id);

        if ($existing) {
            return $existing;
        }

        return $this->attendances->create([
            'event_id' => $event->id,
            'user_id' => $student->id,
            'checked_in_at' => now(),
            'status' => 'present',
            'scan_source' => 'web',
        ]);
    }

    public function setLock(Event $event, bool $locked): Event
    {
        $event->update(['attendance_locked' => $locked]);

        return $event->refresh();
    }
}
