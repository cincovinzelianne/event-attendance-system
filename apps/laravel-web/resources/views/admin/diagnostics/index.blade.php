<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Diagnostics') }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto space-y-6 sm:px-6 lg:px-8">
            <div class="rounded-lg border {{ $dbConnection === 'ok' ? 'border-green-200 bg-green-50' : 'border-red-200 bg-red-50' }} p-4 text-sm">
                <strong>Database Connection:</strong>
                <span class="uppercase">{{ $dbConnection }}</span>
            </div>

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div class="rounded-lg bg-white p-4 shadow-sm"><p class="text-xs text-gray-500">Users</p><p class="mt-1 text-xl font-semibold">{{ $stats['users'] }}</p></div>
                <div class="rounded-lg bg-white p-4 shadow-sm"><p class="text-xs text-gray-500">Events</p><p class="mt-1 text-xl font-semibold">{{ $stats['events'] }}</p></div>
                <div class="rounded-lg bg-white p-4 shadow-sm"><p class="text-xs text-gray-500">Attendances</p><p class="mt-1 text-xl font-semibold">{{ $stats['attendances'] }}</p></div>
                <div class="rounded-lg bg-white p-4 shadow-sm"><p class="text-xs text-gray-500">Notifications</p><p class="mt-1 text-xl font-semibold">{{ $stats['notifications'] }}</p></div>
                <div class="rounded-lg bg-white p-4 shadow-sm"><p class="text-xs text-gray-500">Locked Events</p><p class="mt-1 text-xl font-semibold">{{ $stats['locked_events'] }}</p></div>
                <div class="rounded-lg bg-white p-4 shadow-sm"><p class="text-xs text-gray-500">Pending Jobs</p><p class="mt-1 text-xl font-semibold">{{ $stats['pending_jobs'] }}</p></div>
                <div class="rounded-lg bg-white p-4 shadow-sm"><p class="text-xs text-gray-500">Failed Jobs</p><p class="mt-1 text-xl font-semibold">{{ $stats['failed_jobs'] }}</p></div>
            </div>

            <div class="rounded-lg bg-white p-5 shadow-sm">
                <h3 class="mb-2 font-medium text-gray-900">Attendance Integrity</h3>
                <p class="mb-4 text-sm text-gray-600">Run command: <span class="font-mono">php artisan app:verify-attendance-integrity</span></p>
                @if ($integrityIssues->isEmpty())
                    <p class="text-sm text-green-700">No duplicate attendance entries detected.</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-3 py-2 text-left font-medium text-gray-700">Event ID</th>
                                    <th class="px-3 py-2 text-left font-medium text-gray-700">User ID</th>
                                    <th class="px-3 py-2 text-left font-medium text-gray-700">Duplicates</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach ($integrityIssues as $issue)
                                    <tr>
                                        <td class="px-3 py-2">{{ $issue->event_id }}</td>
                                        <td class="px-3 py-2">{{ $issue->user_id }}</td>
                                        <td class="px-3 py-2">{{ $issue->total }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
