<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreNotificationRequest;
use App\Jobs\DispatchNotificationJob;
use App\Models\Notification;
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
        return view('admin.notifications.create');
    }

    public function store(StoreNotificationRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        DispatchNotificationJob::dispatch(
            payload: [
                'title' => $validated['title'],
                'message' => $validated['message'],
                'type' => $validated['type'] ?? 'general',
            ],
            targetRole: $validated['target_role'],
            createdBy: (int) $request->user()->id,
        );

        return redirect()
            ->route('admin.notifications.index')
            ->with('status', 'Notification dispatch job queued. Start queue worker to process.');
    }
}
