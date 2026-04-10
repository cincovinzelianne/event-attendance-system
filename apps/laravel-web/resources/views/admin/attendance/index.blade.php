<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Attendance Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="mb-4 rounded-md border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
                    {{ session('status') }}
                </div>
            @endif

            <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left font-medium text-gray-700">Event</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-700">Start</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-700">Attendance Count</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-700">Lock Status</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-700">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            @forelse ($events as $event)
                                <tr>
                                    <td class="px-4 py-3 font-medium text-gray-900">{{ $event->title }}</td>
                                    <td class="px-4 py-3 text-gray-700">{{ $event->starts_at?->format('Y-m-d H:i') }}</td>
                                    <td class="px-4 py-3 text-gray-700">{{ $event->attendances_count }}</td>
                                    <td class="px-4 py-3 text-gray-700">{{ $event->attendance_locked ? 'Locked' : 'Open' }}</td>
                                    <td class="px-4 py-3">
                                        @if ($event->attendance_locked)
                                            <form method="POST" action="{{ route('admin.attendance.unlock', $event) }}">
                                                @csrf
                                                <button type="submit" class="rounded-md bg-green-600 px-3 py-1.5 text-xs text-white hover:bg-green-500">Unlock</button>
                                            </form>
                                        @else
                                            <form method="POST" action="{{ route('admin.attendance.lock', $event) }}">
                                                @csrf
                                                <button type="submit" class="rounded-md bg-red-600 px-3 py-1.5 text-xs text-white hover:bg-red-500">Lock</button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-8 text-center text-gray-500">No events available.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
