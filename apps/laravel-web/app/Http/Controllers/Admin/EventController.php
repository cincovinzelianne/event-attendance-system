<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEventRequest;
use App\Http\Requests\UpdateEventRequest;
use App\Models\Event;
use App\Repositories\EventRepository;
use App\Services\EventService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class EventController extends Controller
{
    public function __construct(
        private readonly EventRepository $events,
        private readonly EventService $eventService,
    ) {
    }

    public function index(): View
    {
        return view('admin.events.index', [
            'events' => $this->events->paginateForAdmin(),
        ]);
    }

    public function create(): View
    {
        return view('admin.events.create');
    }

    public function store(StoreEventRequest $request): RedirectResponse
    {
        $this->eventService->create($request->validated(), (int) $request->user()->id);

        return redirect()->route('admin.events.index')->with('status', 'Event created successfully.');
    }

    public function edit(Event $event): View
    {
        return view('admin.events.edit', [
            'event' => $event,
        ]);
    }

    public function update(UpdateEventRequest $request, Event $event): RedirectResponse
    {
        $this->eventService->update($event, $request->validated());

        return redirect()->route('admin.events.index')->with('status', 'Event updated successfully.');
    }

    public function destroy(Event $event): RedirectResponse
    {
        $this->events->delete($event);

        return redirect()->route('admin.events.index')->with('status', 'Event deleted successfully.');
    }
}
