<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventEvaluation;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EvaluationController extends Controller
{
    public function index(Request $request): View
    {
        $eventId = $request->integer('event_id');

        $query = EventEvaluation::query()
            ->with(['event:id,title,google_form_url', 'user:id,name,email'])
            ->latest();

        if ($eventId > 0) {
            $query->where('event_id', $eventId);
        }

        return view('admin.evaluations.index', [
            'events' => Event::query()->orderBy('starts_at', 'desc')->get(['id', 'title']),
            'evaluations' => $query->paginate(20)->withQueryString(),
            'selectedEventId' => $eventId,
        ]);
    }

    public function seedFromAttendance(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'event_id' => ['required', 'integer', 'exists:events,id'],
        ]);

        $event = Event::query()->findOrFail((int) $validated['event_id']);

        $attendanceUserIds = $event->attendances()->pluck('user_id')->all();

        foreach ($attendanceUserIds as $userId) {
            EventEvaluation::query()->firstOrCreate(
                [
                    'event_id' => $event->id,
                    'user_id' => $userId,
                ],
                [
                    'status' => 'not_submitted',
                ]
            );
        }

        return redirect()->route('admin.evaluations.index', ['event_id' => $event->id])
            ->with('status', 'Evaluation tracking entries prepared from attendance records.');
    }

    public function markSubmitted(EventEvaluation $evaluation): RedirectResponse
    {
        $evaluation->update([
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);

        return back()->with('status', 'Evaluation marked as submitted.');
    }
}
