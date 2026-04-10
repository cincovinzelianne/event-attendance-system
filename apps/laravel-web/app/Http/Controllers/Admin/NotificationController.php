<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreNotificationRequest;
use App\Jobs\DispatchNotificationJob;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class NotificationController extends Controller
{
    public function index(): View
    {
        return view('admin.notifications.index', [
            'notifications' => Notification::query()
                ->with('user:id,name')
                ->latest()
                ->paginate(20),
        ]);
    }

    public function create(): View
    {
        return view('admin.notifications.create', [
            'departments' => DB::table('student_profiles')
                ->whereNotNull('department')
                ->where('department', '!=', '')
                ->distinct()
                ->orderBy('department')
                ->pluck('department')
                ->all(),
            'students' => User::query()
                ->where('role', 'student')
                ->orderBy('name')
                ->get(['id', 'name', 'email']),
        ]);
    }

    public function store(StoreNotificationRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $job = DispatchNotificationJob::dispatch(
            payload: [
                'title' => $validated['title'],
                'message' => $validated['message'],
                'type' => $validated['type'] ?? 'general',
            ],
            targetRole: $validated['target_role'],
            createdBy: (int) $request->user()->id,
            scheduledFor: $validated['scheduled_for'] ?? null,
            targetDepartment: $validated['target_department'] ?? null,
            targetUserIds: array_values(array_unique(array_map('intval', (array) ($validated['target_user_ids'] ?? [])))),
        );

        if (! empty($validated['scheduled_for'])) {
            $delayUntil = \Illuminate\Support\Carbon::parse($validated['scheduled_for']);
            $job->delay($delayUntil);
        }

        return redirect()
            ->route('admin.notifications.index')
            ->with('status', 'Notification dispatch job queued. Start queue worker to process.');
    }
}
