<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Certificate;
use App\Models\Event;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CertificateController extends Controller
{
    public function index(): View
    {
        return view('admin.certificates.index', [
            'events' => Event::query()->orderBy('starts_at', 'desc')->limit(20)->get(),
            'certificates' => Certificate::query()
                ->with(['event:id,title', 'user:id,name,email'])
                ->latest()
                ->paginate(20),
        ]);
    }

    public function generate(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'event_id' => ['required', 'integer', 'exists:events,id'],
        ]);

        $eventId = (int) $validated['event_id'];

        $attendees = Attendance::query()
            ->where('event_id', $eventId)
            ->pluck('user_id')
            ->all();

        $created = 0;

        foreach ($attendees as $userId) {
            Certificate::query()->updateOrCreate(
                ['event_id' => $eventId, 'user_id' => $userId],
                [
                    'certificate_no' => 'CERT-'.strtoupper(substr(md5($eventId.'-'.$userId), 0, 10)),
                    'issued_at' => now(),
                    'status' => 'generated',
                ]
            );

            $created++;
        }

        return redirect()->route('admin.certificates.index')->with('status', "Generated {$created} certificate(s).");
    }

    public function download(Certificate $certificate)
    {
        $certificate->load(['event:id,title,starts_at', 'user:id,name']);

        $html = view('admin.certificates.template', [
            'certificate' => $certificate,
        ])->render();

        return response($html)
            ->header('Content-Type', 'text/html; charset=UTF-8')
            ->header('Content-Disposition', 'attachment; filename="certificate-'.$certificate->certificate_no.'.html"');
    }
}
