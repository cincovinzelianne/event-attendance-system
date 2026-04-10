<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Event;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DiagnosticsController extends Controller
{
    public function index(): View
    {
        $dbConnection = 'ok';

        try {
            DB::select('select 1 as healthy');
        } catch (\Throwable) {
            $dbConnection = 'failed';
        }

        $stats = [
            'users' => User::query()->count(),
            'events' => Event::query()->count(),
            'attendances' => Attendance::query()->count(),
            'notifications' => Notification::query()->count(),
            'locked_events' => Event::query()->where('attendance_locked', true)->count(),
            'pending_jobs' => Schema::hasTable('jobs') ? DB::table('jobs')->count() : 0,
            'failed_jobs' => Schema::hasTable('failed_jobs') ? DB::table('failed_jobs')->count() : 0,
        ];

        $integrityIssues = DB::table('attendances')
            ->select('event_id', 'user_id', DB::raw('COUNT(*) as total'))
            ->groupBy('event_id', 'user_id')
            ->having('total', '>', 1)
            ->get();

        return view('admin.diagnostics.index', [
            'dbConnection' => $dbConnection,
            'stats' => $stats,
            'integrityIssues' => $integrityIssues,
        ]);
    }
}
