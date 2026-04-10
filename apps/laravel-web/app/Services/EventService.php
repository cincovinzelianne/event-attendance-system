<?php

namespace App\Services;

use App\Models\Event;
use App\Repositories\EventRepository;

class EventService
{
    public function __construct(private readonly EventRepository $events)
    {
    }

    public function create(array $validated, int $adminUserId): Event
    {
        $payload = $this->normalizePayload($validated);
        $payload['created_by'] = $adminUserId;

        return $this->events->create($payload);
    }

    public function update(Event $event, array $validated): Event
    {
        $payload = $this->normalizePayload($validated);

        return $this->events->update($event, $payload);
    }

    private function normalizePayload(array $validated): array
    {
        $validated['attendance_locked'] = (bool) ($validated['attendance_locked'] ?? false);

        return $validated;
    }
}
