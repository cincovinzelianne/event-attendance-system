<?php

namespace App\Http\Controllers\Admin;

use App\Models\Attendance;
use App\Models\Event;
use App\Models\Notification;
use App\Models\User;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $metrics = [
            'students' => User::query()->where('role', 'student')->count(),
            'events' => Event::query()->count(),
            'upcoming_events' => Event::query()->where('starts_at', '>=', now())->count(),
            'attendance_logs' => Attendance::query()->count(),
            'unread_notifications' => Notification::query()->where('status', 'unread')->count(),
        ];

        $recentEvents = Event::query()
            ->latest('starts_at')
            ->limit(5)
            ->get(['id', 'title', 'starts_at', 'location', 'attendance_locked']);

        return view('admin.dashboard', [
            'metrics' => $metrics,
            'recentEvents' => $recentEvents,
        ]);
    }
}
