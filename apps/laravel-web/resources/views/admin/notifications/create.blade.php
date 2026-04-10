<x-admin-layout title="Create Notification" subtitle="Compose and queue a notification for admins, students, or everyone.">
    <div class="max-w-4xl rounded-3xl border border-white/10 bg-blue-950/80 p-6 shadow-2xl shadow-black/30 backdrop-blur-xl">
        <form method="POST" action="{{ route('admin.notifications.store') }}" class="space-y-4">
            @csrf

            <div>
                <label for="title" class="block text-sm font-medium text-slate-200">Title</label>
                <input id="title" name="title" type="text" value="{{ old('title') }}" required class="mt-1 block w-full rounded-xl border-white/10 bg-white/5 text-white shadow-sm focus:border-yellow-300 focus:ring-yellow-300">
                @error('title')<p class="mt-1 text-sm text-yellow-200">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="message" class="block text-sm font-medium text-slate-200">Message</label>
                <textarea id="message" name="message" rows="5" required class="mt-1 block w-full rounded-xl border-white/10 bg-white/5 text-white shadow-sm focus:border-yellow-300 focus:ring-yellow-300">{{ old('message') }}</textarea>
                @error('message')<p class="mt-1 text-sm text-yellow-200">{{ $message }}</p>@enderror
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="type" class="block text-sm font-medium text-slate-200">Type</label>
                    <input id="type" name="type" type="text" value="{{ old('type', 'general') }}" class="mt-1 block w-full rounded-xl border-white/10 bg-white/5 text-white shadow-sm focus:border-yellow-300 focus:ring-yellow-300">
                    @error('type')<p class="mt-1 text-sm text-yellow-200">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="target_role" class="block text-sm font-medium text-slate-200">Target</label>
                    <select id="target_role" name="target_role" required class="mt-1 block w-full rounded-xl border-white/10 bg-white/5 text-white shadow-sm focus:border-yellow-300 focus:ring-yellow-300">
                        <option value="all" @selected(old('target_role') === 'all')>All Users</option>
                        <option value="student" @selected(old('target_role') === 'student')>Students</option>
                        <option value="admin" @selected(old('target_role') === 'admin')>Admins</option>
                    </select>
                    @error('target_role')<p class="mt-1 text-sm text-yellow-200">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="target_department" class="block text-sm font-medium text-slate-200">Target Department (Optional)</label>
                    <select id="target_department" name="target_department" class="mt-1 block w-full rounded-xl border-white/10 bg-white/5 text-white shadow-sm focus:border-yellow-300 focus:ring-yellow-300">
                        <option value="">All Departments</option>
                        @foreach ($departments as $department)
                            <option value="{{ $department }}" @selected(old('target_department') === $department)>{{ $department }}</option>
                        @endforeach
                    </select>
                    @error('target_department')<p class="mt-1 text-sm text-yellow-200">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="scheduled_for" class="block text-sm font-medium text-slate-200">Schedule (Optional)</label>
                    <input id="scheduled_for" name="scheduled_for" type="datetime-local" value="{{ old('scheduled_for') }}" class="mt-1 block w-full rounded-xl border-white/10 bg-white/5 text-white shadow-sm focus:border-yellow-300 focus:ring-yellow-300">
                    @error('scheduled_for')<p class="mt-1 text-sm text-yellow-200">{{ $message }}</p>@enderror
                </div>
            </div>

            <div>
                <label for="target_user_ids" class="block text-sm font-medium text-slate-200">Specific Students (Optional)</label>
                <select id="target_user_ids" name="target_user_ids[]" multiple class="mt-1 block min-h-44 w-full rounded-xl border-white/10 bg-white/5 text-white shadow-sm focus:border-yellow-300 focus:ring-yellow-300">
                    @foreach ($students as $student)
                        <option value="{{ $student->id }}" @selected(in_array($student->id, old('target_user_ids', []), true))>
                            {{ $student->name }} ({{ $student->email }})
                        </option>
                    @endforeach
                </select>
                <p class="mt-1 text-xs text-slate-400">Hold Ctrl/Cmd to select multiple students. If selected, these users are prioritized as recipients.</p>
                @error('target_user_ids')<p class="mt-1 text-sm text-yellow-200">{{ $message }}</p>@enderror
                @error('target_user_ids.*')<p class="mt-1 text-sm text-yellow-200">{{ $message }}</p>@enderror
            </div>

            <div class="flex items-center gap-3">
                <button type="submit" class="rounded-xl bg-gradient-to-r from-blue-700 to-yellow-500 px-4 py-2 text-sm font-semibold text-slate-950 transition hover:-translate-y-0.5">Queue Notification</button>
                <a href="{{ route('admin.notifications.index') }}" class="rounded-xl border border-white/20 px-4 py-2 text-sm text-slate-200 transition hover:bg-white/10">Cancel</a>
            </div>
        </form>
    </div>
</x-admin-layout>
