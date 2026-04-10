<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\ScanAttendanceRequest;
use App\Models\Event;
use App\Services\AttendanceService;
use DomainException;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class AttendanceController extends Controller
{
    public function __construct(private readonly AttendanceService $attendanceService)
    {
    }

    public function index(): View
    {
        return view('student.attendance.index', [
            'events' => Event::query()
                ->orderBy('starts_at', 'desc')
                ->limit(20)
                ->get(),
        ]);
    }

    public function store(ScanAttendanceRequest $request): RedirectResponse
    {
        $event = Event::query()->findOrFail($request->integer('event_id'));

        try {
            $attendance = $this->attendanceService->checkIn($event, $request->user(), 'student-web');

            return redirect()->route('student.attendance.index')->with(
                'status',
                "Attendance recorded at {$attendance->checked_in_at?->format('Y-m-d H:i:s')}"
            );
        } catch (DomainException $exception) {
            return redirect()->route('student.attendance.index')->withErrors([
                'event_id' => $exception->getMessage(),
            ]);
        }
    }
}
