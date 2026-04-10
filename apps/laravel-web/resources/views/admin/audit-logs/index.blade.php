<x-admin-layout title="Audit Logs" subtitle="Track create, update, delete, and module-level admin actions.">
    <div class="mb-6 rounded-2xl border border-white/10 bg-blue-950/75 p-5">
        <form method="GET" action="{{ route('admin.audit-logs.index') }}" class="grid gap-4 sm:grid-cols-3">
            <div>
                <label for="action_type" class="block text-sm font-medium text-slate-200">Action Type</label>
                <input id="action_type" name="action_type" type="text" value="{{ request('action_type') }}" placeholder="post:admin/events" class="mt-1 block w-full rounded-xl border-white/10 bg-white/5 text-white shadow-sm focus:border-yellow-300 focus:ring-yellow-300">
            </div>
            <div>
                <label for="user_id" class="block text-sm font-medium text-slate-200">User ID</label>
                <input id="user_id" name="user_id" type="number" value="{{ request('user_id') }}" class="mt-1 block w-full rounded-xl border-white/10 bg-white/5 text-white shadow-sm focus:border-yellow-300 focus:ring-yellow-300">
            </div>
            <div class="self-end">
                <button type="submit" class="rounded-xl bg-yellow-500/90 px-4 py-2 text-sm font-semibold text-slate-950 transition hover:bg-yellow-400">Filter</button>
            </div>
        </form>
    </div>

    <div class="overflow-hidden rounded-3xl border border-white/10 bg-blue-950/75 shadow-2xl shadow-black/30 backdrop-blur-xl">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-white/10 text-sm">
                <thead class="bg-white/5">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium text-slate-200">Time</th>
                        <th class="px-4 py-3 text-left font-medium text-slate-200">Admin</th>
                        <th class="px-4 py-3 text-left font-medium text-slate-200">Action</th>
                        <th class="px-4 py-3 text-left font-medium text-slate-200">IP</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/10 bg-transparent">
                    @forelse ($logs as $log)
                        <tr>
                            <td class="px-4 py-3 text-slate-300">{{ $log->created_at?->format('Y-m-d H:i:s') }}</td>
                            <td class="px-4 py-3 text-white">{{ $log->user?->name ?? 'Unknown' }}</td>
                            <td class="px-4 py-3 text-slate-300">{{ $log->action_type }}</td>
                            <td class="px-4 py-3 text-slate-300">{{ $log->ip_address ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-8 text-center text-slate-400">No activity logs found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-white/10 px-4 py-3 text-slate-300">
            {{ $logs->links() }}
        </div>
    </div>
</x-admin-layout>
