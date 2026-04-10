<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Analytics') }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto space-y-6 sm:px-6 lg:px-8">
            <div class="grid gap-4 sm:grid-cols-3">
                <div class="rounded-lg bg-white p-5 shadow-sm">
                    <p class="text-xs uppercase tracking-wide text-gray-500">Students</p>
                    <p class="mt-2 text-2xl font-semibold text-gray-900">{{ $totalStudents }}</p>
                </div>
                <div class="rounded-lg bg-white p-5 shadow-sm">
                    <p class="text-xs uppercase tracking-wide text-gray-500">Events</p>
                    <p class="mt-2 text-2xl font-semibold text-gray-900">{{ $totalEvents }}</p>
                </div>
                <div class="rounded-lg bg-white p-5 shadow-sm">
                    <p class="text-xs uppercase tracking-wide text-gray-500">Attendance Records</p>
                    <p class="mt-2 text-2xl font-semibold text-gray-900">{{ $totalAttendance }}</p>
                </div>
            </div>

            <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                <div class="border-b border-gray-100 px-5 py-4">
                    <h3 class="font-medium text-gray-900">Latest Event Attendance</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left font-medium text-gray-700">Event</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-700">Start</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-700">Attendance</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-700">Rate</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            @forelse ($events as $event)
                                <tr>
                                    <td class="px-4 py-3 font-medium text-gray-900">{{ $event['title'] }}</td>
                                    <td class="px-4 py-3 text-gray-700">{{ $event['starts_at']?->format('Y-m-d H:i') }}</td>
                                    <td class="px-4 py-3 text-gray-700">{{ $event['attendances_count'] }}</td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-3">
                                            <div class="h-2 w-36 rounded-full bg-gray-200">
                                                <div class="h-2 rounded-full bg-blue-600" style="width: {{ min(100, $event['attendance_rate']) }}%"></div>
                                            </div>
                                            <span class="text-gray-700">{{ $event['attendance_rate'] }}%</span>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-4 py-8 text-center text-gray-500">No analytics data yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
