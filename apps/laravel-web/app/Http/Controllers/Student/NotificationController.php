<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request): View
    {
        return view('student.notifications.index', [
            'notifications' => Notification::query()
                ->where('user_id', $request->user()->id)
                ->latest()
                ->paginate(20),
        ]);
    }

    public function markRead(Request $request, Notification $notification): RedirectResponse
    {
        abort_if($notification->user_id !== $request->user()->id, 403);

        if ($notification->status !== 'read') {
            $notification->update([
                'status' => 'read',
                'read_at' => now(),
            ]);
        }

        return redirect()->route('student.notifications.index');
    }
}
