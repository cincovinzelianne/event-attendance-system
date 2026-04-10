<x-admin-layout title="Diagnostics" subtitle="Inspect system health, queues, and attendance integrity signals.">
    <div class="space-y-6">
        <div class="rounded-xl border {{ $dbConnection === 'ok' ? 'border-blue-300/30 bg-blue-700/25 text-blue-100' : 'border-yellow-300/30 bg-yellow-500/20 text-yellow-100' }} p-4 text-sm">
            <strong>Database Connection:</strong>
            <span class="uppercase">{{ $dbConnection }}</span>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="rounded-2xl border border-white/10 bg-white/5 p-4"><p class="text-xs text-slate-400">Users</p><p class="mt-1 text-xl font-semibold text-white">{{ $stats['users'] }}</p></div>
            <div class="rounded-2xl border border-white/10 bg-white/5 p-4"><p class="text-xs text-slate-400">Events</p><p class="mt-1 text-xl font-semibold text-white">{{ $stats['events'] }}</p></div>
            <div class="rounded-2xl border border-white/10 bg-white/5 p-4"><p class="text-xs text-slate-400">Attendances</p><p class="mt-1 text-xl font-semibold text-white">{{ $stats['attendances'] }}</p></div>
            <div class="rounded-2xl border border-white/10 bg-white/5 p-4"><p class="text-xs text-slate-400">Notifications</p><p class="mt-1 text-xl font-semibold text-white">{{ $stats['notifications'] }}</p></div>
            <div class="rounded-2xl border border-white/10 bg-white/5 p-4"><p class="text-xs text-slate-400">Locked Events</p><p class="mt-1 text-xl font-semibold text-white">{{ $stats['locked_events'] }}</p></div>
            <div class="rounded-2xl border border-white/10 bg-white/5 p-4"><p class="text-xs text-slate-400">Pending Jobs</p><p class="mt-1 text-xl font-semibold text-white">{{ $stats['pending_jobs'] }}</p></div>
            <div class="rounded-2xl border border-white/10 bg-white/5 p-4"><p class="text-xs text-slate-400">Failed Jobs</p><p class="mt-1 text-xl font-semibold text-white">{{ $stats['failed_jobs'] }}</p></div>
        </div>

        <div class="rounded-3xl border border-white/10 bg-blue-950/80 p-5 shadow-2xl shadow-black/30 backdrop-blur-xl">
            <h3 class="mb-2 font-medium text-white">Attendance Integrity</h3>
            <p class="mb-4 text-sm text-slate-300">Run command: <span class="font-mono">php artisan app:verify-attendance-integrity</span></p>
            @if ($integrityIssues->isEmpty())
                <p class="text-sm text-emerald-300">No duplicate attendance entries detected.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-white/10 text-sm">
                        <thead class="bg-white/5">
                            <tr>
                                <th class="px-3 py-2 text-left font-medium text-slate-200">Event ID</th>
                                <th class="px-3 py-2 text-left font-medium text-slate-200">User ID</th>
                                <th class="px-3 py-2 text-left font-medium text-slate-200">Duplicates</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/10">
                            @foreach ($integrityIssues as $issue)
                                <tr>
                                    <td class="px-3 py-2 text-slate-300">{{ $issue->event_id }}</td>
                                    <td class="px-3 py-2 text-slate-300">{{ $issue->user_id }}</td>
                                    <td class="px-3 py-2 text-slate-300">{{ $issue->total }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</x-admin-layout>
