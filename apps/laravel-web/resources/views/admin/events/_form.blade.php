@csrf

<div class="grid gap-4 sm:grid-cols-2">
    <div class="sm:col-span-2">
        <label for="title" class="block text-sm font-medium text-gray-700">Title</label>
        <input id="title" name="title" type="text" value="{{ old('title', $event->title ?? '') }}" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-gray-500 focus:ring-gray-500">
        @error('title')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>

    <div class="sm:col-span-2">
        <label for="description" class="block text-sm font-medium text-gray-700">Description</label>
        <textarea id="description" name="description" rows="4" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-gray-500 focus:ring-gray-500">{{ old('description', $event->description ?? '') }}</textarea>
        @error('description')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="starts_at" class="block text-sm font-medium text-gray-700">Starts At</label>
        <input id="starts_at" name="starts_at" type="datetime-local" value="{{ old('starts_at', isset($event) && $event->starts_at ? $event->starts_at->format('Y-m-d\\TH:i') : '') }}" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-gray-500 focus:ring-gray-500">
        @error('starts_at')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="ends_at" class="block text-sm font-medium text-gray-700">Ends At</label>
        <input id="ends_at" name="ends_at" type="datetime-local" value="{{ old('ends_at', isset($event) && $event->ends_at ? $event->ends_at->format('Y-m-d\\TH:i') : '') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-gray-500 focus:ring-gray-500">
        @error('ends_at')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>

    <div class="sm:col-span-2">
        <label for="location" class="block text-sm font-medium text-gray-700">Location</label>
        <input id="location" name="location" type="text" value="{{ old('location', $event->location ?? '') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-gray-500 focus:ring-gray-500">
        @error('location')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>

    <div class="sm:col-span-2">
        <label class="inline-flex items-center gap-2 text-sm text-gray-700">
            <input type="checkbox" name="attendance_locked" value="1" @checked(old('attendance_locked', $event->attendance_locked ?? false)) class="rounded border-gray-300 text-gray-900 focus:ring-gray-500">
            Attendance Locked
        </label>
    </div>
</div>

<div class="mt-6 flex items-center gap-3">
    <button type="submit" class="rounded-md bg-gray-900 px-4 py-2 text-sm text-white hover:bg-gray-700">
        {{ $submitLabel }}
    </button>
    <a href="{{ route('admin.events.index') }}" class="rounded-md border border-gray-300 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
        Cancel
    </a>
</div>
