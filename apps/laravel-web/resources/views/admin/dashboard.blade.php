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
            <p class="text-xs uppercase tracking-[0.2em] text-slate-400">Total Events</p>
            <p class="mt-2 text-3xl font-semibold text-white">{{ number_format($metrics['events']) }}</p>
        </article>
        <article class="rounded-2xl border border-yellow-300/30 bg-blue-700/25 p-5 backdrop-blur-xl">
            <p class="text-xs uppercase tracking-[0.2em] text-yellow-100/85">Active Now</p>
            <p class="mt-2 text-3xl font-semibold text-white">{{ number_format($metrics['active_events']) }}</p>
        </article>
        <article class="rounded-2xl border border-white/10 bg-white/5 p-5 backdrop-blur-xl">
            <p class="text-xs uppercase tracking-[0.2em] text-slate-400">Today Check-ins</p>
            <p class="mt-2 text-3xl font-semibold text-white">{{ number_format($metrics['today_attendance']) }}</p>
        </article>
        <article class="rounded-2xl border border-yellow-300/30 bg-yellow-500/20 p-5 backdrop-blur-xl">
            <p class="text-xs uppercase tracking-[0.2em] text-yellow-100/85">Unread Notices</p>
            <p class="mt-2 text-3xl font-semibold text-white">{{ number_format($metrics['unread_notifications']) }}</p>
        </article>
    </section>

    <section class="mt-6 grid gap-6 xl:grid-cols-[1.45fr_0.55fr]">
        <article class="rounded-3xl border border-white/10 bg-blue-950/80 p-6 shadow-2xl shadow-black/30 backdrop-blur-xl">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="text-lg font-semibold text-white">Analytics Summary</h2>
                    <p class="mt-1 text-sm text-slate-300">Operational snapshot and trend performance for quick decision making.</p>
                </div>
                <span class="rounded-full border border-yellow-300/30 bg-yellow-500/20 px-3 py-1 text-xs font-semibold text-yellow-100">Last 30 Days: {{ $attendanceRateLast30Days }}%</span>
            </div>

            <div class="mt-5 grid gap-4 sm:grid-cols-3">
                <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
                    <p class="text-xs uppercase tracking-[0.14em] text-slate-400">Weekly Check-ins</p>
                    <p class="mt-2 text-2xl font-semibold text-white">{{ number_format($metrics['week_attendance']) }}</p>
                </div>
                <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
                    <p class="text-xs uppercase tracking-[0.14em] text-slate-400">Monthly Check-ins</p>
                    <p class="mt-2 text-2xl font-semibold text-white">{{ number_format($metrics['month_attendance']) }}</p>
                </div>
                <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
                    <p class="text-xs uppercase tracking-[0.14em] text-slate-400">Top Event</p>
                    <p class="mt-2 truncate text-sm font-semibold text-white">{{ $topEvent?->title ?? 'No events yet' }}</p>
                    <p class="mt-1 text-xs text-slate-300">{{ number_format($topEvent?->attendances_count ?? 0) }} check-ins</p>
                </div>
            </div>

            <div class="mt-6">
                <div class="mb-3 flex items-center justify-between">
                    <h3 class="text-sm font-semibold uppercase tracking-[0.2em] text-slate-300">Attendance Trend</h3>
                    <span class="text-xs text-slate-400">6-month activity</span>
                </div>
                @php
                    $maxTrend = max(1, collect($monthlyTrend)->max('count'));
                @endphp
                <div class="grid grid-cols-6 gap-2">
                    @foreach ($monthlyTrend as $point)
                        @php
                            $height = max(10, (int) round(($point['count'] / $maxTrend) * 100));
                        @endphp
                        <div class="rounded-xl border border-white/10 bg-white/5 p-2 text-center">
                            <div class="mx-auto flex h-24 items-end justify-center">
                                <div class="w-full rounded-md bg-gradient-to-t from-blue-700 to-yellow-500" style="height: {{ $height }}%"></div>
                            </div>
                            <p class="mt-2 text-[11px] font-semibold uppercase tracking-[0.08em] text-slate-300">{{ $point['month'] }}</p>
                            <p class="text-xs text-slate-400">{{ number_format($point['count']) }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </article>

        <article class="rounded-3xl border border-white/10 bg-blue-950/80 p-6 shadow-2xl shadow-black/30 backdrop-blur-xl">
            <h2 class="text-lg font-semibold text-white">Department Engagement</h2>
            <p class="mt-1 text-sm text-slate-300">Top departments based on attendance activity.</p>

            <div class="mt-5 space-y-3">
                @php
                    $maxDepartment = max(1, collect($departmentSummary)->max('attendance_count'));
                @endphp

                @forelse ($departmentSummary as $row)
                    <div class="rounded-xl border border-white/10 bg-white/5 p-3">
                        <div class="flex items-center justify-between gap-3">
                            <p class="truncate text-sm font-semibold text-white">{{ $row['department'] }}</p>
                            <p class="text-xs text-slate-300">{{ number_format($row['attendance_count']) }} logs</p>
                        </div>
                        <div class="mt-2 h-2 w-full overflow-hidden rounded-full bg-white/10">
                            <div class="h-full rounded-full bg-gradient-to-r from-blue-700 to-yellow-500" style="width: {{ (int) round(($row['attendance_count'] / $maxDepartment) * 100) }}%"></div>
                        </div>
                        <p class="mt-2 text-xs text-slate-400">{{ number_format($row['students_count']) }} students</p>
                    </div>
                @empty
                    <div class="rounded-xl border border-dashed border-white/20 bg-white/5 p-4 text-sm text-slate-300">
                        No department profile data available yet.
                    </div>
                @endforelse
            </div>
        </article>
    </section>

    <section class="mt-6 grid gap-6 xl:grid-cols-[1.3fr_0.7fr]">
        <article class="rounded-3xl border border-white/10 bg-blue-950/80 p-6 shadow-2xl shadow-black/30 backdrop-blur-xl">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold text-white">Recent Events</h2>
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
            <h2 class="text-lg font-semibold text-white">Quick Actions</h2>
            <p class="mt-2 text-sm leading-6 text-slate-300">Jump directly into the tools admins use most throughout the day.</p>

            <div class="mt-5 grid gap-3">
                <a href="{{ route('admin.events.create') }}" class="rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm font-medium text-slate-100 transition hover:border-yellow-300/50 hover:bg-blue-700/25">Create new event</a>
                <a href="{{ route('admin.attendance.index') }}" class="rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm font-medium text-slate-100 transition hover:border-yellow-300/50 hover:bg-blue-700/25">Open attendance dashboard</a>
                <a href="{{ route('admin.qr-scanner.index') }}" class="rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm font-medium text-slate-100 transition hover:border-yellow-300/50 hover:bg-blue-700/25">Launch QR scanner</a>
                <a href="{{ route('admin.notifications.create') }}" class="rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm font-medium text-slate-100 transition hover:border-yellow-300/50 hover:bg-blue-700/25">Send notification</a>
                <a href="{{ route('admin.users.index') }}" class="rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm font-medium text-slate-100 transition hover:border-yellow-300/50 hover:bg-blue-700/25">Manage users</a>
                <a href="{{ route('admin.audit-logs.index') }}" class="rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm font-medium text-slate-100 transition hover:border-yellow-300/50 hover:bg-blue-700/25">View audit logs</a>
            </div>
        </article>
    </section>
</x-admin-layout>
