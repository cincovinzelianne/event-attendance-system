<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('My Notifications') }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            <div class="space-y-3">
                @forelse ($notifications as $notification)
                    <div class="rounded-lg border {{ $notification->status === 'unread' ? 'border-blue-200 bg-blue-50' : 'border-gray-200 bg-white' }} p-4 shadow-sm">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <h3 class="font-semibold text-gray-900">{{ $notification->title }}</h3>
                                <p class="mt-1 text-sm text-gray-700">{{ $notification->message }}</p>
                                <p class="mt-2 text-xs text-gray-500">{{ $notification->created_at?->format('Y-m-d H:i') }}</p>
                            </div>
                            <div>
                                @if ($notification->status === 'unread')
                                    <form method="POST" action="{{ route('student.notifications.read', $notification) }}">
                                        @csrf
                                        <button type="submit" class="rounded-md bg-gray-900 px-3 py-1.5 text-xs text-white hover:bg-gray-700">Mark Read</button>
                                    </form>
                                @else
                                    <span class="text-xs text-gray-500">Read</span>
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="rounded-lg border border-gray-200 bg-white p-8 text-center text-gray-500">No notifications available.</div>
                @endforelse
            </div>

            <div class="mt-4">
                {{ $notifications->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
