<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Event;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;

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

        $topAttendees = User::query()
            ->select(['users.id', 'users.name', DB::raw('COUNT(attendances.id) as attendance_count')])
            ->leftJoin('attendances', 'attendances.user_id', '=', 'users.id')
            ->where('users.role', 'student')
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('attendance_count')
            ->limit(5)
            ->get();

        $frequentAbsentees = User::query()
            ->select(['users.id', 'users.name', DB::raw('COUNT(attendances.id) as attendance_count')])
            ->leftJoin('attendances', 'attendances.user_id', '=', 'users.id')
            ->where('users.role', 'student')
            ->groupBy('users.id', 'users.name')
            ->orderBy('attendance_count')
            ->limit(5)
            ->get();

        $departmentEngagement = DB::table('student_profiles')
            ->leftJoin('attendances', 'attendances.user_id', '=', 'student_profiles.user_id')
            ->select([
                'student_profiles.department',
                DB::raw('COUNT(DISTINCT student_profiles.user_id) as total_students'),
                DB::raw('COUNT(attendances.id) as total_attendance'),
            ])
            ->whereNotNull('student_profiles.department')
            ->where('student_profiles.department', '!=', '')
            ->groupBy('student_profiles.department')
            ->orderByDesc('total_attendance')
            ->get();

        return view('admin.analytics.index', [
            'totalStudents' => $totalStudents,
            'totalEvents' => $totalEvents,
            'totalAttendance' => $totalAttendance,
            'events' => $events,
            'topAttendees' => $topAttendees,
            'frequentAbsentees' => $frequentAbsentees,
            'departmentEngagement' => $departmentEngagement,
        ]);
    }
}
