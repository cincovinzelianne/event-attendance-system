<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function __invoke(Request $request): View
    {
        $query = ActivityLog::query()->with('user:id,name,email')->latest();

        if ($request->filled('action_type')) {
            $query->where('action_type', 'like', '%'.$request->string('action_type')->toString().'%');
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', (int) $request->integer('user_id'));
        }

        return view('admin.audit-logs.index', [
            'logs' => $query->paginate(25)->withQueryString(),
        ]);
    }
}
