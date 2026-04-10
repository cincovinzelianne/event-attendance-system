<?php

namespace App\Http\Controllers\Admin;

use App\Models\Attendance;
use App\Models\Event;
use App\Models\Notification;
use App\Models\User;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $todayStart = now()->startOfDay();
        $weekStart = now()->copy()->subDays(6)->startOfDay();
        $monthStart = now()->copy()->startOfMonth();
        $thirtyDaysAgo = now()->copy()->subDays(30)->startOfDay();

        $metrics = [
            'students' => User::query()->where('role', 'student')->count(),
            'events' => Event::query()->count(),
            'upcoming_events' => Event::query()->where('starts_at', '>=', now())->count(),
            'attendance_logs' => Attendance::query()->count(),
            'unread_notifications' => Notification::query()->where('status', 'unread')->count(),
            'today_attendance' => Attendance::query()->where('checked_in_at', '>=', $todayStart)->count(),
            'week_attendance' => Attendance::query()->where('checked_in_at', '>=', $weekStart)->count(),
            'month_attendance' => Attendance::query()->where('checked_in_at', '>=', $monthStart)->count(),
            'active_events' => Event::query()
                ->where('starts_at', '<=', now())
                ->where(function ($query): void {
                    $query->whereNull('ends_at')->orWhere('ends_at', '>=', now());
                })
                ->count(),
        ];

        $eventsLast30Days = Event::query()->where('starts_at', '>=', $thirtyDaysAgo)->count();
        $denominator = max(1, $metrics['students'] * max(1, $eventsLast30Days));
        $attendanceRateLast30Days = round((Attendance::query()->where('checked_in_at', '>=', $thirtyDaysAgo)->count() / $denominator) * 100, 2);

        $recentEvents = Event::query()
            ->latest('starts_at')
            ->limit(5)
            ->get(['id', 'title', 'starts_at', 'location', 'attendance_locked']);

        $topEvent = Event::query()
            ->withCount('attendances')
            ->orderByDesc('attendances_count')
            ->first(['id', 'title']);

        $monthKeys = collect(range(5, 0))->map(
            fn (int $offset): string => now()->copy()->subMonths($offset)->format('Y-m')
        );

        $trendSource = Attendance::query()
            ->where('checked_in_at', '>=', now()->copy()->subMonths(5)->startOfMonth())
            ->get(['checked_in_at'])
            ->groupBy(fn (Attendance $attendance): string => Carbon::parse($attendance->checked_in_at)->format('Y-m'));

        $monthlyTrend = $monthKeys->map(function (string $key) use ($trendSource): array {
            $label = Carbon::createFromFormat('Y-m', $key)->format('M');

            return [
                'month' => $label,
                'count' => (int) ($trendSource->get($key)?->count() ?? 0),
            ];
        })->all();

        $departmentSummary = [];

        if (Schema::hasTable('student_profiles')) {
            $departmentSummary = DB::table('student_profiles')
                ->leftJoin('attendances', 'attendances.user_id', '=', 'student_profiles.user_id')
                ->select([
                    'student_profiles.department',
                    DB::raw('COUNT(DISTINCT student_profiles.user_id) as students_count'),
                    DB::raw('COUNT(attendances.id) as attendance_count'),
                ])
                ->whereNotNull('student_profiles.department')
                ->where('student_profiles.department', '!=', '')
                ->groupBy('student_profiles.department')
                ->orderByDesc('attendance_count')
                ->limit(5)
                ->get()
                ->map(function ($row): array {
                    return [
                        'department' => (string) $row->department,
                        'students_count' => (int) $row->students_count,
                        'attendance_count' => (int) $row->attendance_count,
                    ];
                })
                ->all();
        }

        return view('admin.dashboard', [
            'metrics' => $metrics,
            'recentEvents' => $recentEvents,
            'attendanceRateLast30Days' => $attendanceRateLast30Days,
            'topEvent' => $topEvent,
            'monthlyTrend' => $monthlyTrend,
            'departmentSummary' => $departmentSummary,
        ]);
    }
}
