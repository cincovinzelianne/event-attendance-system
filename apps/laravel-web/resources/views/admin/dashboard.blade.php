<x-admin-layout
    title="Admin Dashboard"
    subtitle="Manage event operations, monitor attendance health, and track student communication from one premium command view."
>
    <x-slot name="actions">
        <a href="{{ route('admin.events.create') }}" class="inline-flex items-center rounded-xl bg-gradient-to-r from-blue-700 to-yellow-500 px-5 py-3 text-sm font-semibold text-slate-950 shadow-[0_14px_40px_rgba(234,179,8,0.28)] transition hover:-translate-y-0.5">
            Create Event
        </a>
    </x-slot>

    <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
                    <article class="rounded-2xl border border-white/10 bg-white/5 p-5 backdrop-blur-xl">
                        <p class="text-xs uppercase tracking-[0.2em] text-slate-400">Students</p>
                        <p class="mt-2 text-3xl font-semibold text-white">{{ number_format($metrics['students']) }}</p>
                    </article>
                    <article class="rounded-2xl border border-white/10 bg-white/5 p-5 backdrop-blur-xl">
                        <p class="text-xs uppercase tracking-[0.2em] text-slate-400">Total events</p>
                        <p class="mt-2 text-3xl font-semibold text-white">{{ number_format($metrics['events']) }}</p>
                    </article>
                    <article class="rounded-2xl border border-yellow-300/30 bg-blue-700/25 p-5 backdrop-blur-xl">
                        <p class="text-xs uppercase tracking-[0.2em] text-yellow-100/85">Upcoming</p>
                        <p class="mt-2 text-3xl font-semibold text-white">{{ number_format($metrics['upcoming_events']) }}</p>
                    </article>
                    <article class="rounded-2xl border border-white/10 bg-white/5 p-5 backdrop-blur-xl">
                        <p class="text-xs uppercase tracking-[0.2em] text-slate-400">Attendance logs</p>
                        <p class="mt-2 text-3xl font-semibold text-white">{{ number_format($metrics['attendance_logs']) }}</p>
                    </article>
                    <article class="rounded-2xl border border-amber-300/20 bg-amber-300/10 p-5 backdrop-blur-xl">
                        <p class="text-xs uppercase tracking-[0.2em] text-amber-100/85">Unread notices</p>
                        <p class="mt-2 text-3xl font-semibold text-white">{{ number_format($metrics['unread_notifications']) }}</p>
                    </article>
                </section>

    <section class="mt-8 grid gap-6 xl:grid-cols-[1.3fr_0.7fr]">
                    <article class="rounded-3xl border border-white/10 bg-blue-950/80 p-6 shadow-2xl shadow-black/30 backdrop-blur-xl">
                        <div class="flex items-center justify-between">
                            <h2 class="text-lg font-semibold text-white">Recent events</h2>
                            <a href="{{ route('admin.events.index') }}" class="text-sm font-medium text-yellow-200 transition hover:text-yellow-100">View all</a>
                        </div>

                        <div class="mt-5 space-y-3">
                            @forelse ($recentEvents as $event)
                                <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
                                    <div class="flex flex-wrap items-start justify-between gap-3">
                                        <div>
                                            <p class="text-sm font-semibold text-white">{{ $event->title }}</p>
                                            <p class="mt-1 text-xs text-slate-300">
                                                {{ $event->starts_at?->format('M d, Y h:i A') }}
                                                @if($event->location)
                                                    • {{ $event->location }}
                                                @endif
                                            </p>
                                        </div>
                                        <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $event->attendance_locked ? 'bg-yellow-500/25 text-yellow-100' : 'bg-blue-600/30 text-blue-100' }}">
                                            {{ $event->attendance_locked ? 'Locked' : 'Open' }}
                                        </span>
                                    </div>
                                </div>
                            @empty
                                <div class="rounded-2xl border border-dashed border-white/20 bg-white/5 p-6 text-sm text-slate-300">
                                    No events yet. Create your first event to start collecting attendance.
                                </div>
                            @endforelse
                        </div>
                    </article>

                    <article class="rounded-3xl border border-white/10 bg-blue-950/80 p-6 shadow-2xl shadow-black/30 backdrop-blur-xl">
                        <h2 class="text-lg font-semibold text-white">Quick actions</h2>
                        <p class="mt-2 text-sm leading-6 text-slate-300">
                            Jump directly into the tools admins use most throughout the day.
                        </p>

                        <div class="mt-5 grid gap-3">
                            <a href="{{ route('admin.events.create') }}" class="rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm font-medium text-slate-100 transition hover:border-yellow-300/50 hover:bg-blue-700/25">Create new event</a>
                            <a href="{{ route('admin.attendance.index') }}" class="rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm font-medium text-slate-100 transition hover:border-yellow-300/50 hover:bg-blue-700/25">Open attendance dashboard</a>
                            <a href="{{ route('admin.qr-scanner.index') }}" class="rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm font-medium text-slate-100 transition hover:border-yellow-300/50 hover:bg-blue-700/25">Launch QR scanner</a>
                            <a href="{{ route('admin.notifications.create') }}" class="rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm font-medium text-slate-100 transition hover:border-yellow-300/50 hover:bg-blue-700/25">Send notification</a>
                            <a href="{{ route('admin.users.index') }}" class="rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm font-medium text-slate-100 transition hover:border-yellow-300/50 hover:bg-blue-700/25">Manage users</a>
                            <a href="{{ route('admin.audit-logs.index') }}" class="rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm font-medium text-slate-100 transition hover:border-yellow-300/50 hover:bg-blue-700/25">View audit logs</a>
                            <a href="{{ route('admin.settings.index') }}" class="rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm font-medium text-slate-100 transition hover:border-yellow-300/50 hover:bg-blue-700/25">System settings</a>
                        </div>
                    </article>
    </section>
</x-admin-layout>
