<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Event;
use App\Repositories\AttendanceRepository;
use App\Services\AttendanceService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttendanceController extends Controller
{
    public function __construct(
        private readonly AttendanceRepository $attendances,
        private readonly AttendanceService $attendanceService,
    ) {
    }

    public function index(): View
    {
        return view('admin.attendance.index', [
            'events' => $this->attendances->eventSummary(),
        ]);
    }

    public function lock(Event $event): RedirectResponse
    {
        $this->attendanceService->setLock($event, true);

        return redirect()->route('admin.attendance.index')->with('status', 'Attendance locked.');
    }

    public function unlock(Event $event): RedirectResponse
    {
        $this->attendanceService->setLock($event, false);

        return redirect()->route('admin.attendance.index')->with('status', 'Attendance unlocked.');
    }

    public function exportCsv(): StreamedResponse
    {
        $filename = 'attendance-report-'.now()->format('Ymd-His').'.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ];

        return response()->stream(function (): void {
            $handle = fopen('php://output', 'w');

            if ($handle === false) {
                return;
            }

            fputcsv($handle, [
                'Event',
                'Student Name',
                'Student Email',
                'Checked In At',
                'Status',
                'Source',
            ]);

            Attendance::query()
                ->with(['event:id,title', 'user:id,name,email'])
                ->orderByDesc('checked_in_at')
                ->chunk(500, function ($rows) use ($handle): void {
                    foreach ($rows as $attendance) {
                        fputcsv($handle, [
                            $attendance->event?->title,
                            $attendance->user?->name,
                            $attendance->user?->email,
                            $attendance->checked_in_at?->format('Y-m-d H:i:s'),
                            $attendance->status,
                            $attendance->scan_source,
                        ]);
                    }
                });

            fclose($handle);
        }, 200, $headers);
    }
}
