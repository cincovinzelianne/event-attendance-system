<x-admin-layout title="Analytics" subtitle="Track participation levels and measure event attendance performance.">
    <div class="space-y-6">
        <div class="grid gap-4 sm:grid-cols-3">
            <div class="rounded-2xl border border-white/10 bg-white/5 p-5 backdrop-blur-xl">
                <p class="text-xs uppercase tracking-wide text-slate-400">Students</p>
                <p class="mt-2 text-2xl font-semibold text-white">{{ $totalStudents }}</p>
            </div>
            <div class="rounded-2xl border border-white/10 bg-white/5 p-5 backdrop-blur-xl">
                <p class="text-xs uppercase tracking-wide text-slate-400">Events</p>
                <p class="mt-2 text-2xl font-semibold text-white">{{ $totalEvents }}</p>
            </div>
            <div class="rounded-2xl border border-yellow-300/30 bg-blue-700/25 p-5 backdrop-blur-xl">
                <p class="text-xs uppercase tracking-wide text-yellow-100">Attendance Records</p>
                <p class="mt-2 text-2xl font-semibold text-white">{{ $totalAttendance }}</p>
            </div>
        </div>

        <div class="overflow-hidden rounded-3xl border border-white/10 bg-blue-950/75 shadow-2xl shadow-black/30 backdrop-blur-xl">
            <div class="border-b border-white/10 px-5 py-4">
                <h3 class="font-medium text-white">Latest Event Attendance</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-white/10 text-sm">
                    <thead class="bg-white/5">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium text-slate-200">Event</th>
                            <th class="px-4 py-3 text-left font-medium text-slate-200">Start</th>
                            <th class="px-4 py-3 text-left font-medium text-slate-200">Attendance</th>
                            <th class="px-4 py-3 text-left font-medium text-slate-200">Rate</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/10 bg-transparent">
                        @forelse ($events as $event)
                            <tr>
                                <td class="px-4 py-3 font-medium text-white">{{ $event['title'] }}</td>
                                <td class="px-4 py-3 text-slate-300">{{ $event['starts_at']?->format('Y-m-d H:i') }}</td>
                                <td class="px-4 py-3 text-slate-300">{{ $event['attendances_count'] }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        <div class="h-2 w-36 rounded-full bg-slate-700">
                                            <div class="h-2 rounded-full bg-gradient-to-r from-blue-700 to-yellow-500" style="width: {{ min(100, $event['attendance_rate']) }}%"></div>
                                        </div>
                                        <span class="text-slate-300">{{ $event['attendance_rate'] }}%</span>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-4 py-8 text-center text-slate-400">No analytics data yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-2">
            <div class="overflow-hidden rounded-3xl border border-white/10 bg-blue-950/75 shadow-2xl shadow-black/30 backdrop-blur-xl">
                <div class="border-b border-white/10 px-5 py-4">
                    <h3 class="font-medium text-white">Top Attendees</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-white/10 text-sm">
                        <thead class="bg-white/5">
                            <tr>
                                <th class="px-4 py-3 text-left font-medium text-slate-200">Student</th>
                                <th class="px-4 py-3 text-left font-medium text-slate-200">Attendance</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/10 bg-transparent">
                            @forelse ($topAttendees as $attendee)
                                <tr>
                                    <td class="px-4 py-3 text-white">{{ $attendee->name }}</td>
                                    <td class="px-4 py-3 text-slate-300">{{ $attendee->attendance_count }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="2" class="px-4 py-8 text-center text-slate-400">No attendance data yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="overflow-hidden rounded-3xl border border-white/10 bg-blue-950/75 shadow-2xl shadow-black/30 backdrop-blur-xl">
                <div class="border-b border-white/10 px-5 py-4">
                    <h3 class="font-medium text-white">Frequent Absentees</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-white/10 text-sm">
                        <thead class="bg-white/5">
                            <tr>
                                <th class="px-4 py-3 text-left font-medium text-slate-200">Student</th>
                                <th class="px-4 py-3 text-left font-medium text-slate-200">Attendance</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/10 bg-transparent">
                            @forelse ($frequentAbsentees as $attendee)
                                <tr>
                                    <td class="px-4 py-3 text-white">{{ $attendee->name }}</td>
                                    <td class="px-4 py-3 text-slate-300">{{ $attendee->attendance_count }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="2" class="px-4 py-8 text-center text-slate-400">No attendance data yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="overflow-hidden rounded-3xl border border-white/10 bg-blue-950/75 shadow-2xl shadow-black/30 backdrop-blur-xl">
            <div class="border-b border-white/10 px-5 py-4">
                <h3 class="font-medium text-white">Department Engagement</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-white/10 text-sm">
                    <thead class="bg-white/5">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium text-slate-200">Department</th>
                            <th class="px-4 py-3 text-left font-medium text-slate-200">Students</th>
                            <th class="px-4 py-3 text-left font-medium text-slate-200">Attendance Logs</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/10 bg-transparent">
                        @forelse ($departmentEngagement as $row)
                            <tr>
                                <td class="px-4 py-3 text-white">{{ $row->department }}</td>
                                <td class="px-4 py-3 text-slate-300">{{ $row->total_students }}</td>
                                <td class="px-4 py-3 text-slate-300">{{ $row->total_attendance }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-4 py-8 text-center text-slate-400">No department profiles found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-admin-layout>
