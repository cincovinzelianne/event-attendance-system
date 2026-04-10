<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Event;
use App\Models\User;
use Illuminate\Contracts\View\View;

class AnalyticsController extends Controller
{
    public function index(): View
    {
        $totalStudents = User::query()->where('role', 'student')->count();
        $totalEvents = Event::query()->count();
        $totalAttendance = Attendance::query()->count();

        $events = Event::query()
            ->withCount('attendances')
            ->orderBy('starts_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function (Event $event) use ($totalStudents): array {
                $rate = $totalStudents > 0
                    ? round(($event->attendances_count / $totalStudents) * 100, 2)
                    : 0;

                return [
                    'id' => $event->id,
                    'title' => $event->title,
                    'starts_at' => $event->starts_at,
                    'attendances_count' => $event->attendances_count,
                    'attendance_rate' => $rate,
                ];
            });

        return view('admin.analytics.index', [
            'totalStudents' => $totalStudents,
            'totalEvents' => $totalEvents,
            'totalAttendance' => $totalAttendance,
            'events' => $events,
        ]);
    }
}
