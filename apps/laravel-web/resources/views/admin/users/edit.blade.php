<x-admin-layout title="Edit User" subtitle="Update role, account status, and credentials.">
    <div class="max-w-3xl rounded-3xl border border-white/10 bg-slate-900/75 p-6 shadow-2xl shadow-black/30 backdrop-blur-xl">
        <form method="POST" action="{{ route('admin.users.update', $user) }}" class="grid gap-4 sm:grid-cols-2">
            @csrf
            @method('PUT')

            <div class="sm:col-span-2">
                <label for="name" class="block text-sm font-medium text-slate-200">Name</label>
                <input id="name" name="name" type="text" value="{{ old('name', $user->name) }}" required class="mt-1 block w-full rounded-xl border-white/10 bg-white/5 text-white shadow-sm focus:border-cyan-300 focus:ring-cyan-300">
                @error('name')<p class="mt-1 text-sm text-rose-300">{{ $message }}</p>@enderror
            </div>

            <div class="sm:col-span-2">
                <label for="email" class="block text-sm font-medium text-slate-200">Email</label>
                <input id="email" name="email" type="email" value="{{ old('email', $user->email) }}" required class="mt-1 block w-full rounded-xl border-white/10 bg-white/5 text-white shadow-sm focus:border-cyan-300 focus:ring-cyan-300">
                @error('email')<p class="mt-1 text-sm text-rose-300">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="role" class="block text-sm font-medium text-slate-200">Role</label>
                <select id="role" name="role" required class="mt-1 block w-full rounded-xl border-white/10 bg-white/5 text-white shadow-sm focus:border-cyan-300 focus:ring-cyan-300">
                    <option value="student" @selected(old('role', $user->role) === 'student')>Student</option>
                    <option value="admin" @selected(old('role', $user->role) === 'admin')>Admin</option>
                </select>
                @error('role')<p class="mt-1 text-sm text-rose-300">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-slate-200">New Password (Optional)</label>
                <input id="password" name="password" type="password" class="mt-1 block w-full rounded-xl border-white/10 bg-white/5 text-white shadow-sm focus:border-cyan-300 focus:ring-cyan-300">
                @error('password')<p class="mt-1 text-sm text-rose-300">{{ $message }}</p>@enderror
            </div>

            <div class="sm:col-span-2">
                <label class="inline-flex items-center gap-2 text-sm text-slate-200">
                    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $user->is_active)) class="rounded border-white/20 bg-white/5 text-cyan-300 focus:ring-cyan-300">
                    Account is active
                </label>
            </div>

            <div class="sm:col-span-2 flex items-center gap-3">
                <button type="submit" class="rounded-xl bg-gradient-to-r from-cyan-400 to-blue-500 px-4 py-2 text-sm font-semibold text-slate-950 transition hover:-translate-y-0.5">Update User</button>
                <a href="{{ route('admin.users.index') }}" class="rounded-xl border border-white/20 px-4 py-2 text-sm text-slate-200 transition hover:bg-white/10">Cancel</a>
            </div>
        </form>
    </div>
</x-admin-layout>
