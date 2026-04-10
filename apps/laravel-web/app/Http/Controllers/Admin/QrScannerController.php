<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProcessQrScanRequest;
use App\Models\Event;
use App\Models\User;
use App\Services\AttendanceService;
use App\Services\QrTokenService;
use DomainException;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use RuntimeException;

class QrScannerController extends Controller
{
    public function __construct(
        private readonly QrTokenService $qrTokenService,
        private readonly AttendanceService $attendanceService,
    ) {
    }

    public function index(): View
    {
        return view('admin.qr-scanner.index', [
            'events' => Event::query()->orderBy('starts_at', 'desc')->get(),
        ]);
    }

    public function store(ProcessQrScanRequest $request): RedirectResponse
    {
        try {
            $userId = $this->qrTokenService->resolveUserId($request->string('token')->toString());
            $student = User::query()->findOrFail($userId);
            $event = Event::query()->findOrFail($request->integer('event_id'));

            if (! $student->isStudent()) {
                throw new RuntimeException('Scanned token is not for a student account.');
            }

            $attendance = $this->attendanceService->checkIn($event, $student, 'qr-scanner');

            return redirect()->route('admin.qr-scanner.index')->with(
                'status',
                "Attendance recorded for {$student->name} at {$attendance->checked_in_at?->format('Y-m-d H:i:s')}"
            );
        } catch (RuntimeException|DomainException $exception) {
            return redirect()->route('admin.qr-scanner.index')->withErrors([
                'token' => $exception->getMessage(),
            ]);
        }
    }
}
