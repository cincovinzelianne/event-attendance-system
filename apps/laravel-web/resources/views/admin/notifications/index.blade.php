<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Notifications') }}
            </h2>
            <a href="{{ route('admin.notifications.create') }}" class="rounded-md bg-gray-900 px-4 py-2 text-sm text-white hover:bg-gray-700">
                {{ __('Create Notification') }}
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
                                <th class="px-4 py-3 text-left font-medium text-gray-700">User</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-700">Title</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-700">Type</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-700">Status</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-700">Created</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            @forelse ($notifications as $notification)
                                <tr>
                                    <td class="px-4 py-3 text-gray-700">{{ $notification->user?->name ?? 'N/A' }}</td>
                                    <td class="px-4 py-3 font-medium text-gray-900">{{ $notification->title }}</td>
                                    <td class="px-4 py-3 text-gray-700">{{ $notification->type }}</td>
                                    <td class="px-4 py-3 text-gray-700">{{ ucfirst($notification->status) }}</td>
                                    <td class="px-4 py-3 text-gray-700">{{ $notification->created_at?->format('Y-m-d H:i') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-8 text-center text-gray-500">No notifications yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-gray-100 px-4 py-3">
                    {{ $notifications->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
