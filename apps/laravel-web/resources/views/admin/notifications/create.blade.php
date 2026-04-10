<x-admin-layout title="Create Notification" subtitle="Compose and queue a notification for admins, students, or everyone.">
    <div class="max-w-4xl rounded-3xl border border-white/10 bg-slate-900/75 p-6 shadow-2xl shadow-black/30 backdrop-blur-xl">
        <form method="POST" action="{{ route('admin.notifications.store') }}" class="space-y-4">
            @csrf

            <div>
                <label for="title" class="block text-sm font-medium text-slate-200">Title</label>
                <input id="title" name="title" type="text" value="{{ old('title') }}" required class="mt-1 block w-full rounded-xl border-white/10 bg-white/5 text-white shadow-sm focus:border-cyan-300 focus:ring-cyan-300">
                @error('title')<p class="mt-1 text-sm text-rose-300">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="message" class="block text-sm font-medium text-slate-200">Message</label>
                <textarea id="message" name="message" rows="5" required class="mt-1 block w-full rounded-xl border-white/10 bg-white/5 text-white shadow-sm focus:border-cyan-300 focus:ring-cyan-300">{{ old('message') }}</textarea>
                @error('message')<p class="mt-1 text-sm text-rose-300">{{ $message }}</p>@enderror
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="type" class="block text-sm font-medium text-slate-200">Type</label>
                    <input id="type" name="type" type="text" value="{{ old('type', 'general') }}" class="mt-1 block w-full rounded-xl border-white/10 bg-white/5 text-white shadow-sm focus:border-cyan-300 focus:ring-cyan-300">
                    @error('type')<p class="mt-1 text-sm text-rose-300">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="target_role" class="block text-sm font-medium text-slate-200">Target</label>
                    <select id="target_role" name="target_role" required class="mt-1 block w-full rounded-xl border-white/10 bg-white/5 text-white shadow-sm focus:border-cyan-300 focus:ring-cyan-300">
                        <option value="all" @selected(old('target_role') === 'all')>All Users</option>
                        <option value="student" @selected(old('target_role') === 'student')>Students</option>
                        <option value="admin" @selected(old('target_role') === 'admin')>Admins</option>
                    </select>
                    @error('target_role')<p class="mt-1 text-sm text-rose-300">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="flex items-center gap-3">
                <button type="submit" class="rounded-xl bg-gradient-to-r from-cyan-400 to-blue-500 px-4 py-2 text-sm font-semibold text-slate-950 transition hover:-translate-y-0.5">Queue Notification</button>
                <a href="{{ route('admin.notifications.index') }}" class="rounded-xl border border-white/20 px-4 py-2 text-sm text-slate-200 transition hover:bg-white/10">Cancel</a>
            </div>
        </form>
    </div>
</x-admin-layout>
