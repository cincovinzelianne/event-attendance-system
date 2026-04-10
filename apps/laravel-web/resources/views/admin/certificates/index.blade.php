<x-admin-layout title="Certificates" subtitle="Generate and download participation certificates for event attendees.">
    <div class="mb-6 rounded-2xl border border-white/10 bg-slate-900/70 p-5">
        <form method="POST" action="{{ route('admin.certificates.generate') }}" class="grid gap-4 sm:grid-cols-[1fr_auto] sm:items-end">
            @csrf
            <div>
                <label for="event_id" class="block text-sm font-medium text-slate-200">Event</label>
                <select id="event_id" name="event_id" required class="mt-1 block w-full rounded-xl border-white/10 bg-white/5 text-white shadow-sm focus:border-cyan-300 focus:ring-cyan-300">
                    <option value="">Select an event</option>
                    @foreach ($events as $event)
                        <option value="{{ $event->id }}">{{ $event->title }} ({{ $event->starts_at?->format('Y-m-d') }})</option>
                    @endforeach
                </select>
                @error('event_id')<p class="mt-1 text-sm text-rose-300">{{ $message }}</p>@enderror
            </div>
            <button type="submit" class="rounded-xl bg-cyan-400/90 px-4 py-2 text-sm font-semibold text-slate-950 transition hover:bg-cyan-300">Generate</button>
        </form>
    </div>

    <div class="overflow-hidden rounded-3xl border border-white/10 bg-slate-900/70 shadow-2xl shadow-black/30 backdrop-blur-xl">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-white/10 text-sm">
                <thead class="bg-white/5">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium text-slate-200">Certificate No.</th>
                        <th class="px-4 py-3 text-left font-medium text-slate-200">Student</th>
                        <th class="px-4 py-3 text-left font-medium text-slate-200">Event</th>
                        <th class="px-4 py-3 text-left font-medium text-slate-200">Issued At</th>
                        <th class="px-4 py-3 text-left font-medium text-slate-200">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/10 bg-transparent">
                    @forelse ($certificates as $certificate)
                        <tr>
                            <td class="px-4 py-3 text-white">{{ $certificate->certificate_no }}</td>
                            <td class="px-4 py-3 text-slate-300">{{ $certificate->user?->name }}</td>
                            <td class="px-4 py-3 text-slate-300">{{ $certificate->event?->title }}</td>
                            <td class="px-4 py-3 text-slate-300">{{ $certificate->issued_at?->format('Y-m-d H:i') }}</td>
                            <td class="px-4 py-3">
                                <a href="{{ route('admin.certificates.download', $certificate) }}" class="text-cyan-300 transition hover:text-cyan-100">Download</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-slate-400">No certificates generated yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-white/10 px-4 py-3 text-slate-300">
            {{ $certificates->links() }}
        </div>
    </div>
</x-admin-layout>
