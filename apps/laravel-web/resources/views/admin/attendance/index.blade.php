<x-admin-layout title="Attendance Dashboard" subtitle="Review attendance volume and control lock status for each event.">
    <x-slot name="actions">
        <a href="{{ route('admin.attendance.export.csv') }}" class="inline-flex items-center rounded-xl bg-gradient-to-r from-blue-700 to-yellow-500 px-5 py-3 text-sm font-semibold text-slate-950 shadow-[0_14px_40px_rgba(234,179,8,0.28)] transition hover:-translate-y-0.5">
            Export CSV
        </a>
    </x-slot>

    <div class="overflow-hidden rounded-3xl border border-white/10 bg-blue-950/75 shadow-2xl shadow-black/30 backdrop-blur-xl">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-white/10 text-sm">
                <thead class="bg-white/5">
                            <tr>
                                <th class="px-4 py-3 text-left font-medium text-slate-200">Event</th>
                                <th class="px-4 py-3 text-left font-medium text-slate-200">Start</th>
                                <th class="px-4 py-3 text-left font-medium text-slate-200">Attendance Count</th>
                                <th class="px-4 py-3 text-left font-medium text-slate-200">Lock Status</th>
                                <th class="px-4 py-3 text-left font-medium text-slate-200">Actions</th>
                            </tr>
                </thead>
                <tbody class="divide-y divide-white/10 bg-transparent">
                    @forelse ($events as $event)
                        <tr>
                            <td class="px-4 py-3 font-medium text-white">{{ $event->title }}</td>
                            <td class="px-4 py-3 text-slate-300">{{ $event->starts_at?->format('Y-m-d H:i') }}</td>
                            <td class="px-4 py-3 text-slate-300">{{ $event->attendances_count }}</td>
                            <td class="px-4 py-3">
                                <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $event->attendance_locked ? 'bg-yellow-500/25 text-yellow-100' : 'bg-blue-600/30 text-blue-100' }}">
                                    {{ $event->attendance_locked ? 'Locked' : 'Open' }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                @if ($event->attendance_locked)
                                    <form method="POST" action="{{ route('admin.attendance.unlock', $event) }}">
                                        @csrf
                                        <button type="submit" class="rounded-lg bg-blue-700/90 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-blue-700">Unlock</button>
                                    </form>
                                @else
                                    <form method="POST" action="{{ route('admin.attendance.lock', $event) }}">
                                        @csrf
                                        <button type="submit" class="rounded-lg bg-yellow-500/90 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-yellow-500">Lock</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-slate-400">No events available.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-admin-layout>
