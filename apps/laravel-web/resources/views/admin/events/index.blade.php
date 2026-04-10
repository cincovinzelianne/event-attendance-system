<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Event Management') }}
            </h2>
            <a href="{{ route('admin.events.create') }}" class="rounded-md bg-gray-900 px-4 py-2 text-sm text-white hover:bg-gray-700">
                {{ __('Create Event') }}
            </a>
        </div>
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
                                <th class="px-4 py-3 text-left font-medium text-gray-700">Title</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-700">Start</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-700">End</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-700">Location</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-700">Locked</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-700">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            @forelse ($events as $event)
                                <tr>
                                    <td class="px-4 py-3 font-medium text-gray-900">{{ $event->title }}</td>
                                    <td class="px-4 py-3 text-gray-700">{{ $event->starts_at?->format('Y-m-d H:i') }}</td>
                                    <td class="px-4 py-3 text-gray-700">{{ $event->ends_at?->format('Y-m-d H:i') ?? '-' }}</td>
                                    <td class="px-4 py-3 text-gray-700">{{ $event->location ?? '-' }}</td>
                                    <td class="px-4 py-3 text-gray-700">{{ $event->attendance_locked ? 'Yes' : 'No' }}</td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-3">
                                            <a href="{{ route('admin.events.edit', $event) }}" class="text-blue-600 hover:text-blue-500">Edit</a>
                                            <form method="POST" action="{{ route('admin.events.destroy', $event) }}" onsubmit="return confirm('Delete this event?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-red-600 hover:text-red-500">Delete</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-8 text-center text-gray-500">No events found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-gray-100 px-4 py-3">
                    {{ $events->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
