<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('QR Scanner') }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="mb-4 rounded-md border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
                    {{ session('status') }}
                </div>
            @endif

            <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                <form method="POST" action="{{ route('admin.qr-scanner.store') }}" class="space-y-4">
                    @csrf

                    <div>
                        <label for="event_id" class="block text-sm font-medium text-gray-700">Event</label>
                        <select id="event_id" name="event_id" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-gray-500 focus:ring-gray-500">
                            <option value="">-- Select Event --</option>
                            @foreach ($events as $event)
                                <option value="{{ $event->id }}" @selected(old('event_id') == $event->id)>
                                    {{ $event->title }} ({{ $event->starts_at?->format('Y-m-d H:i') }})
                                </option>
                            @endforeach
                        </select>
                        @error('event_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="token" class="block text-sm font-medium text-gray-700">Scanned QR Token</label>
                        <textarea id="token" name="token" rows="5" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-gray-500 focus:ring-gray-500">{{ old('token') }}</textarea>
                        @error('token')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <button type="submit" class="rounded-md bg-gray-900 px-4 py-2 text-sm text-white hover:bg-gray-700">Process Scan</button>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
