<x-admin-layout title="Role & Permission Management" subtitle="Assign advanced capabilities per role for secure module access.">
    <form method="POST" action="{{ route('admin.permissions.update') }}" class="space-y-6">
        @csrf
        @method('PUT')

        <div class="overflow-hidden rounded-3xl border border-white/10 bg-blue-950/75 shadow-2xl shadow-black/30 backdrop-blur-xl">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-white/10 text-sm">
                    <thead class="bg-white/5">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium text-slate-200">Permission</th>
                            @foreach ($roles as $role)
                                <th class="px-4 py-3 text-left font-medium text-slate-200">{{ ucfirst($role) }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/10 bg-transparent">
                        @foreach ($permissions as $permission)
                            <tr>
                                <td class="px-4 py-3 text-white">{{ $permission->label }}</td>
                                @foreach ($roles as $role)
                                    <td class="px-4 py-3 text-slate-300">
                                        <input type="checkbox" name="permissions[{{ $role }}][]" value="{{ $permission->permission_key }}" @checked(in_array($permission->permission_key, $assigned[$role] ?? [], true)) class="rounded border-white/20 bg-white/5 text-yellow-300 focus:ring-yellow-300">
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <button type="submit" class="rounded-xl bg-gradient-to-r from-blue-700 to-yellow-500 px-4 py-2 text-sm font-semibold text-slate-950 transition hover:-translate-y-0.5">Update Permissions</button>
    </form>
</x-admin-layout>
