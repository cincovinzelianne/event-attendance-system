<?php

namespace App\Services;

use App\Models\Event;
use App\Repositories\EventRepository;
use Illuminate\Http\UploadedFile;

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
        $validated['status'] = $validated['status'] ?? 'upcoming';

        if (isset($validated['poster']) && $validated['poster'] instanceof UploadedFile) {
            $validated['poster_path'] = $validated['poster']->store('events/posters', 'public');
            unset($validated['poster']);
        }

        if (isset($validated['attachment']) && $validated['attachment'] instanceof UploadedFile) {
            $validated['attachment_path'] = $validated['attachment']->store('events/attachments', 'public');
            unset($validated['attachment']);
        }

        return $validated;
    }
}
