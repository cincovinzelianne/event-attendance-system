<?php

namespace App\Repositories;

use App\Models\Attendance;
use App\Models\Event;
use Illuminate\Support\Collection;

class AttendanceRepository
{
    public function findForEventAndUser(int $eventId, int $userId): ?Attendance
    {
        return Attendance::query()
            ->where('event_id', $eventId)
            ->where('user_id', $userId)
            ->first();
    }

    public function create(array $data): Attendance
    {
        return Attendance::query()->create($data);
    }

    public function eventSummary(): Collection
    {
        return Event::query()
            ->withCount('attendances')
            ->orderBy('starts_at', 'desc')
            ->get();
    }
}
