<?php

namespace App\Repositories;

use App\Models\Event;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class EventRepository
{
    public function paginateForAdmin(int $perPage = 15): LengthAwarePaginator
    {
        return Event::query()
            ->orderBy('starts_at', 'desc')
            ->paginate($perPage);
    }

    public function create(array $data): Event
    {
        return Event::query()->create($data);
    }

    public function update(Event $event, array $data): Event
    {
        $event->update($data);

        return $event->refresh();
    }

    public function delete(Event $event): void
    {
        $event->delete();
    }
}
