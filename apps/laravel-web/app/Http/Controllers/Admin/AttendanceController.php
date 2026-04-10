<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Repositories\AttendanceRepository;
use App\Services\AttendanceService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

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
}
