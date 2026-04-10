<x-admin-layout title="Event Management" subtitle="Create and manage attendance events from a single operational queue.">
    <x-slot name="actions">
        <a href="{{ route('admin.events.create') }}" class="inline-flex items-center rounded-xl bg-gradient-to-r from-cyan-400 to-blue-500 px-5 py-3 text-sm font-semibold text-slate-950 shadow-[0_14px_40px_rgba(34,211,238,0.25)] transition hover:-translate-y-0.5">
            {{ __('Create Event') }}
        </a>
    </x-slot>

    <div class="overflow-hidden rounded-3xl border border-white/10 bg-slate-900/70 shadow-2xl shadow-black/30 backdrop-blur-xl">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-white/10 text-sm">
                <thead class="bg-white/5">
                            <tr>
                                <th class="px-4 py-3 text-left font-medium text-slate-200">Title</th>
                                <th class="px-4 py-3 text-left font-medium text-slate-200">Start</th>
                                <th class="px-4 py-3 text-left font-medium text-slate-200">End</th>
                                <th class="px-4 py-3 text-left font-medium text-slate-200">Location</th>
                                <th class="px-4 py-3 text-left font-medium text-slate-200">Locked</th>
                                <th class="px-4 py-3 text-left font-medium text-slate-200">Actions</th>
                            </tr>
                </thead>
                <tbody class="divide-y divide-white/10 bg-transparent">
                    @forelse ($events as $event)
                        <tr>
                            <td class="px-4 py-3 font-medium text-white">{{ $event->title }}</td>
                            <td class="px-4 py-3 text-slate-300">{{ $event->starts_at?->format('Y-m-d H:i') }}</td>
                            <td class="px-4 py-3 text-slate-300">{{ $event->ends_at?->format('Y-m-d H:i') ?? '-' }}</td>
                            <td class="px-4 py-3 text-slate-300">{{ $event->location ?? '-' }}</td>
                            <td class="px-4 py-3">
                                <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $event->attendance_locked ? 'bg-rose-400/20 text-rose-200' : 'bg-emerald-400/20 text-emerald-200' }}">
                                    {{ $event->attendance_locked ? 'Yes' : 'No' }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <a href="{{ route('admin.events.edit', $event) }}" class="text-cyan-300 transition hover:text-cyan-100">Edit</a>
                                    <form method="POST" action="{{ route('admin.events.destroy', $event) }}" onsubmit="return confirm('Delete this event?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-rose-300 transition hover:text-rose-200">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-slate-400">No events found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-white/10 px-4 py-3 text-slate-300">
            {{ $events->links() }}
        </div>
    </div>
</x-admin-layout>
