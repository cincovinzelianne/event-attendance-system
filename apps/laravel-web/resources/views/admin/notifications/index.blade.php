<x-admin-layout title="Notifications" subtitle="Review delivery activity and manage communication to users.">
    <x-slot name="actions">
        <a href="{{ route('admin.notifications.create') }}" class="inline-flex items-center rounded-xl bg-gradient-to-r from-blue-700 to-yellow-500 px-5 py-3 text-sm font-semibold text-slate-950 shadow-[0_14px_40px_rgba(234,179,8,0.28)] transition hover:-translate-y-0.5">
            {{ __('Create Notification') }}
        </a>
    </x-slot>

    <div class="overflow-hidden rounded-3xl border border-white/10 bg-blue-950/75 shadow-2xl shadow-black/30 backdrop-blur-xl">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-white/10 text-sm">
                <thead class="bg-white/5">
                            <tr>
                                <th class="px-4 py-3 text-left font-medium text-slate-200">User</th>
                                <th class="px-4 py-3 text-left font-medium text-slate-200">Title</th>
                                <th class="px-4 py-3 text-left font-medium text-slate-200">Type</th>
                                <th class="px-4 py-3 text-left font-medium text-slate-200">Audience</th>
                                <th class="px-4 py-3 text-left font-medium text-slate-200">Status</th>
                                <th class="px-4 py-3 text-left font-medium text-slate-200">Created</th>
                            </tr>
                </thead>
                <tbody class="divide-y divide-white/10 bg-transparent">
                    @forelse ($notifications as $notification)
                        <tr>
                            <td class="px-4 py-3 text-slate-300">{{ $notification->user?->name ?? 'N/A' }}</td>
                            <td class="px-4 py-3 font-medium text-white">{{ $notification->title }}</td>
                            <td class="px-4 py-3 text-slate-300">{{ $notification->type }}</td>
                            <td class="px-4 py-3 text-slate-300">
                                {{ data_get($notification->meta, 'target_role', 'n/a') }}
                                @if(data_get($notification->meta, 'target_department'))
                                    <span class="text-xs text-yellow-300">/ {{ data_get($notification->meta, 'target_department') }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-slate-300">{{ ucfirst($notification->status) }}</td>
                            <td class="px-4 py-3 text-slate-300">{{ $notification->created_at?->format('Y-m-d H:i') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-slate-400">No notifications yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-white/10 px-4 py-3 text-slate-300">
            {{ $notifications->links() }}
        </div>
    </div>
</x-admin-layout>
