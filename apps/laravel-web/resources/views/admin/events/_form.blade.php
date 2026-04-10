@csrf

<div class="grid gap-4 sm:grid-cols-2">
    <div class="sm:col-span-2">
        <label for="title" class="block text-sm font-medium text-slate-200">Title</label>
        <input id="title" name="title" type="text" value="{{ old('title', $event->title ?? '') }}" required class="mt-1 block w-full rounded-xl border-white/10 bg-white/5 text-white shadow-sm focus:border-yellow-300 focus:ring-yellow-300">
        @error('title')<p class="mt-1 text-sm text-yellow-200">{{ $message }}</p>@enderror
    </div>

    <div class="sm:col-span-2">
        <label for="description" class="block text-sm font-medium text-slate-200">Description</label>
        <textarea id="description" name="description" rows="4" class="mt-1 block w-full rounded-xl border-white/10 bg-white/5 text-white shadow-sm focus:border-yellow-300 focus:ring-yellow-300">{{ old('description', $event->description ?? '') }}</textarea>
        @error('description')<p class="mt-1 text-sm text-yellow-200">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="starts_at" class="block text-sm font-medium text-slate-200">Starts At</label>
        <input id="starts_at" name="starts_at" type="datetime-local" value="{{ old('starts_at', isset($event) && $event->starts_at ? $event->starts_at->format('Y-m-d\\TH:i') : '') }}" required class="mt-1 block w-full rounded-xl border-white/10 bg-white/5 text-white shadow-sm focus:border-yellow-300 focus:ring-yellow-300">
        @error('starts_at')<p class="mt-1 text-sm text-yellow-200">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="checkin_start_at" class="block text-sm font-medium text-slate-200">Check-in Opens</label>
        <input id="checkin_start_at" name="checkin_start_at" type="datetime-local" value="{{ old('checkin_start_at', isset($event) && $event->checkin_start_at ? $event->checkin_start_at->format('Y-m-d\\TH:i') : '') }}" class="mt-1 block w-full rounded-xl border-white/10 bg-white/5 text-white shadow-sm focus:border-yellow-300 focus:ring-yellow-300">
        @error('checkin_start_at')<p class="mt-1 text-sm text-yellow-200">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="checkin_end_at" class="block text-sm font-medium text-slate-200">Check-in Closes</label>
        <input id="checkin_end_at" name="checkin_end_at" type="datetime-local" value="{{ old('checkin_end_at', isset($event) && $event->checkin_end_at ? $event->checkin_end_at->format('Y-m-d\\TH:i') : '') }}" class="mt-1 block w-full rounded-xl border-white/10 bg-white/5 text-white shadow-sm focus:border-yellow-300 focus:ring-yellow-300">
        @error('checkin_end_at')<p class="mt-1 text-sm text-yellow-200">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="ends_at" class="block text-sm font-medium text-slate-200">Ends At</label>
        <input id="ends_at" name="ends_at" type="datetime-local" value="{{ old('ends_at', isset($event) && $event->ends_at ? $event->ends_at->format('Y-m-d\\TH:i') : '') }}" class="mt-1 block w-full rounded-xl border-white/10 bg-white/5 text-white shadow-sm focus:border-yellow-300 focus:ring-yellow-300">
        @error('ends_at')<p class="mt-1 text-sm text-yellow-200">{{ $message }}</p>@enderror
    </div>

    <div class="sm:col-span-2">
        <label for="location" class="block text-sm font-medium text-slate-200">Location</label>
        <input id="location" name="location" type="text" value="{{ old('location', $event->location ?? '') }}" class="mt-1 block w-full rounded-xl border-white/10 bg-white/5 text-white shadow-sm focus:border-yellow-300 focus:ring-yellow-300">
        @error('location')<p class="mt-1 text-sm text-yellow-200">{{ $message }}</p>@enderror
    </div>

    <div class="sm:col-span-2">
        <label for="google_form_url" class="block text-sm font-medium text-slate-200">Google Form Link (Optional)</label>
        <input id="google_form_url" name="google_form_url" type="url" value="{{ old('google_form_url', $event->google_form_url ?? '') }}" class="mt-1 block w-full rounded-xl border-white/10 bg-white/5 text-white shadow-sm focus:border-yellow-300 focus:ring-yellow-300">
        @error('google_form_url')<p class="mt-1 text-sm text-yellow-200">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="target_department" class="block text-sm font-medium text-slate-200">Target Department</label>
        <input id="target_department" name="target_department" type="text" value="{{ old('target_department', $event->target_department ?? '') }}" class="mt-1 block w-full rounded-xl border-white/10 bg-white/5 text-white shadow-sm focus:border-yellow-300 focus:ring-yellow-300">
        @error('target_department')<p class="mt-1 text-sm text-yellow-200">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="target_course" class="block text-sm font-medium text-slate-200">Target Course</label>
        <input id="target_course" name="target_course" type="text" value="{{ old('target_course', $event->target_course ?? '') }}" class="mt-1 block w-full rounded-xl border-white/10 bg-white/5 text-white shadow-sm focus:border-yellow-300 focus:ring-yellow-300">
        @error('target_course')<p class="mt-1 text-sm text-yellow-200">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="target_year_level" class="block text-sm font-medium text-slate-200">Target Year Level</label>
        <input id="target_year_level" name="target_year_level" type="text" value="{{ old('target_year_level', $event->target_year_level ?? '') }}" class="mt-1 block w-full rounded-xl border-white/10 bg-white/5 text-white shadow-sm focus:border-yellow-300 focus:ring-yellow-300">
        @error('target_year_level')<p class="mt-1 text-sm text-yellow-200">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="status" class="block text-sm font-medium text-slate-200">Status</label>
        <select id="status" name="status" class="mt-1 block w-full rounded-xl border-white/10 bg-white/5 text-white shadow-sm focus:border-yellow-300 focus:ring-yellow-300">
            <option value="upcoming" @selected(old('status', $event->status ?? 'upcoming') === 'upcoming')>Upcoming</option>
            <option value="ongoing" @selected(old('status', $event->status ?? '') === 'ongoing')>Ongoing</option>
            <option value="completed" @selected(old('status', $event->status ?? '') === 'completed')>Completed</option>
        </select>
        @error('status')<p class="mt-1 text-sm text-yellow-200">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="poster" class="block text-sm font-medium text-slate-200">Event Poster</label>
        <input id="poster" name="poster" type="file" accept=".jpg,.jpeg,.png,.webp" class="mt-1 block w-full rounded-xl border-white/10 bg-white/5 text-sm text-slate-200 shadow-sm focus:border-yellow-300 focus:ring-yellow-300">
        @if (! empty($event?->poster_path))
            <p class="mt-1 text-xs text-slate-400">Current: {{ $event->poster_path }}</p>
        @endif
        @error('poster')<p class="mt-1 text-sm text-yellow-200">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="attachment" class="block text-sm font-medium text-slate-200">Attachment File</label>
        <input id="attachment" name="attachment" type="file" class="mt-1 block w-full rounded-xl border-white/10 bg-white/5 text-sm text-slate-200 shadow-sm focus:border-yellow-300 focus:ring-yellow-300">
        @if (! empty($event?->attachment_path))
            <p class="mt-1 text-xs text-slate-400">Current: {{ $event->attachment_path }}</p>
        @endif
        @error('attachment')<p class="mt-1 text-sm text-yellow-200">{{ $message }}</p>@enderror
    </div>

    <div class="sm:col-span-2">
        <label class="inline-flex items-center gap-2 text-sm text-slate-200">
            <input type="checkbox" name="attendance_locked" value="1" @checked(old('attendance_locked', $event->attendance_locked ?? false)) class="rounded border-white/20 bg-white/5 text-yellow-300 focus:ring-yellow-300">
            Attendance Locked
        </label>
    </div>
</div>

<div class="mt-6 flex items-center gap-3">
    <button type="submit" class="rounded-xl bg-gradient-to-r from-blue-700 to-yellow-500 px-4 py-2 text-sm font-semibold text-slate-950 transition hover:-translate-y-0.5">
        {{ $submitLabel }}
    </button>
    <a href="{{ route('admin.events.index') }}" class="rounded-xl border border-white/20 px-4 py-2 text-sm text-slate-200 transition hover:bg-white/10">
        Cancel
    </a>
</div>
