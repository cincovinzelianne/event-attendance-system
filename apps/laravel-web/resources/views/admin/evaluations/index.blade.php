<x-admin-layout title="Feedback & Evaluation Tracking" subtitle="Monitor evaluation completion and connect submissions to event attendance.">
    <div class="mb-6 rounded-2xl border border-white/10 bg-blue-950/75 p-5">
        <form method="POST" action="{{ route('admin.evaluations.seed') }}" class="grid gap-4 sm:grid-cols-[1fr_auto] sm:items-end">
            @csrf
            <div>
                <label for="event_id" class="block text-sm font-medium text-slate-200">Prepare Tracking for Event</label>
                <select id="event_id" name="event_id" required class="mt-1 block w-full rounded-xl border-white/10 bg-white/5 text-white shadow-sm focus:border-yellow-300 focus:ring-yellow-300">
                    <option value="">Select an event</option>
                    @foreach ($events as $event)
                        <option value="{{ $event->id }}" @selected((int) old('event_id', $selectedEventId) === (int) $event->id)>{{ $event->title }}</option>
                    @endforeach
                </select>
                @error('event_id')<p class="mt-1 text-sm text-yellow-200">{{ $message }}</p>@enderror
            </div>
            <button type="submit" class="rounded-xl bg-yellow-500/90 px-4 py-2 text-sm font-semibold text-slate-950 transition hover:bg-yellow-400">Seed From Attendance</button>
        </form>
    </div>

    <div class="overflow-hidden rounded-3xl border border-white/10 bg-blue-950/75 shadow-2xl shadow-black/30 backdrop-blur-xl">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-white/10 text-sm">
                <thead class="bg-white/5">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium text-slate-200">Event</th>
                        <th class="px-4 py-3 text-left font-medium text-slate-200">Student</th>
                        <th class="px-4 py-3 text-left font-medium text-slate-200">Status</th>
                        <th class="px-4 py-3 text-left font-medium text-slate-200">Submitted At</th>
                        <th class="px-4 py-3 text-left font-medium text-slate-200">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/10 bg-transparent">
                    @forelse ($evaluations as $evaluation)
                        <tr>
                            <td class="px-4 py-3 text-white">{{ $evaluation->event?->title }}</td>
                            <td class="px-4 py-3 text-slate-300">{{ $evaluation->user?->name }}</td>
                            <td class="px-4 py-3 text-slate-300">{{ str_replace('_', ' ', ucfirst($evaluation->status)) }}</td>
                            <td class="px-4 py-3 text-slate-300">{{ $evaluation->submitted_at?->format('Y-m-d H:i') ?? '-' }}</td>
                            <td class="px-4 py-3">
                                @if ($evaluation->status !== 'submitted')
                                    <form method="POST" action="{{ route('admin.evaluations.mark-submitted', $evaluation) }}">
                                        @csrf
                                        <button type="submit" class="text-yellow-300 transition hover:text-yellow-100">Mark Submitted</button>
                                    </form>
                                @else
                                    <span class="text-emerald-300">Complete</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-slate-400">No evaluation records yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-white/10 px-4 py-3 text-slate-300">
            {{ $evaluations->links() }}
        </div>
    </div>
</x-admin-layout>
