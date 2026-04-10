<x-admin-layout title="User Management" subtitle="Create, update, import, and export user accounts with role controls.">
    <x-slot name="actions">
        <a href="{{ route('admin.users.create') }}" class="inline-flex items-center rounded-xl bg-gradient-to-r from-cyan-400 to-blue-500 px-5 py-3 text-sm font-semibold text-slate-950 shadow-[0_14px_40px_rgba(34,211,238,0.25)] transition hover:-translate-y-0.5">
            Add User
        </a>
        <a href="{{ route('admin.users.export.csv') }}" class="inline-flex items-center rounded-xl border border-white/15 bg-white/5 px-5 py-3 text-sm font-semibold text-slate-100 transition hover:bg-white/10">
            Export CSV
        </a>
    </x-slot>

    <div class="mb-6 rounded-2xl border border-white/10 bg-slate-900/70 p-5">
        <h2 class="text-sm font-semibold uppercase tracking-[0.2em] text-slate-300">Import Users</h2>
        <form method="POST" action="{{ route('admin.users.import') }}" enctype="multipart/form-data" class="mt-4 grid gap-4 sm:grid-cols-[1fr_auto_auto] sm:items-end">
            @csrf
            <div>
                <label for="csv_file" class="block text-sm font-medium text-slate-200">CSV File</label>
                <input id="csv_file" name="csv_file" type="file" accept=".csv,.txt" required class="mt-1 block w-full rounded-xl border-white/10 bg-white/5 text-sm text-slate-200 shadow-sm focus:border-cyan-300 focus:ring-cyan-300">
                @error('csv_file')<p class="mt-1 text-sm text-rose-300">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="default_role" class="block text-sm font-medium text-slate-200">Default Role</label>
                <select id="default_role" name="default_role" class="mt-1 block w-full rounded-xl border-white/10 bg-white/5 text-white shadow-sm focus:border-cyan-300 focus:ring-cyan-300">
                    <option value="student">Student</option>
                    <option value="admin">Admin</option>
                </select>
            </div>
            <button type="submit" class="rounded-xl bg-cyan-400/90 px-4 py-2 text-sm font-semibold text-slate-950 transition hover:bg-cyan-300">Import</button>
        </form>
    </div>

    <div class="overflow-hidden rounded-3xl border border-white/10 bg-slate-900/70 shadow-2xl shadow-black/30 backdrop-blur-xl">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-white/10 text-sm">
                <thead class="bg-white/5">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium text-slate-200">Name</th>
                        <th class="px-4 py-3 text-left font-medium text-slate-200">Email</th>
                        <th class="px-4 py-3 text-left font-medium text-slate-200">Role</th>
                        <th class="px-4 py-3 text-left font-medium text-slate-200">Status</th>
                        <th class="px-4 py-3 text-left font-medium text-slate-200">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/10 bg-transparent">
                    @forelse ($users as $user)
                        <tr>
                            <td class="px-4 py-3 font-medium text-white">{{ $user->name }}</td>
                            <td class="px-4 py-3 text-slate-300">{{ $user->email }}</td>
                            <td class="px-4 py-3 text-slate-300">{{ ucfirst($user->role) }}</td>
                            <td class="px-4 py-3 text-slate-300">{{ $user->is_active ? 'Active' : 'Inactive' }}</td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <a href="{{ route('admin.users.edit', $user) }}" class="text-cyan-300 transition hover:text-cyan-100">Edit</a>
                                    <form method="POST" action="{{ route('admin.users.destroy', $user) }}" onsubmit="return confirm('Delete this user?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-rose-300 transition hover:text-rose-200">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-slate-400">No users found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-white/10 px-4 py-3 text-slate-300">
            {{ $users->links() }}
        </div>
    </div>
</x-admin-layout>
