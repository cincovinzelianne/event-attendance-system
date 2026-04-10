<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PermissionController extends Controller
{
    public function index(): View
    {
        $this->seedDefaults();

        $permissions = Permission::query()->orderBy('label')->get();
        $assigned = DB::table('role_permissions')
            ->join('permissions', 'permissions.id', '=', 'role_permissions.permission_id')
            ->select(['role_permissions.role', 'permissions.permission_key'])
            ->get()
            ->groupBy('role')
            ->map(fn ($rows) => $rows->pluck('permission_key')->all())
            ->all();

        return view('admin.permissions.index', [
            'permissions' => $permissions,
            'roles' => ['admin', 'student'],
            'assigned' => $assigned,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $this->seedDefaults();

        $payload = $request->input('permissions', []);
        $permissionMap = Permission::query()->pluck('id', 'permission_key');

        DB::table('role_permissions')->delete();

        foreach (['admin', 'student'] as $role) {
            $keys = array_values(array_filter((array) ($payload[$role] ?? [])));

            foreach ($keys as $key) {
                if (! $permissionMap->has($key)) {
                    continue;
                }

                DB::table('role_permissions')->insert([
                    'role' => $role,
                    'permission_id' => $permissionMap[$key],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        return redirect()->route('admin.permissions.index')->with('status', 'Role permissions updated.');
    }

    private function seedDefaults(): void
    {
        $defaults = [
            'events.manage' => 'Manage events',
            'attendance.manage' => 'Manage attendance',
            'qr.scan' => 'Scan QR codes',
            'notifications.manage' => 'Manage notifications',
            'analytics.view' => 'View analytics',
            'diagnostics.view' => 'View diagnostics',
            'users.manage' => 'Manage users',
            'settings.manage' => 'Manage settings',
            'logs.view' => 'View audit logs',
            'certificates.manage' => 'Manage certificates',
            'evaluations.manage' => 'Manage evaluations',
            'imports.manage' => 'Manage imports/exports',
        ];

        foreach ($defaults as $key => $label) {
            Permission::query()->updateOrCreate(
                ['permission_key' => $key],
                ['label' => $label],
            );
        }

        if (DB::table('role_permissions')->where('role', 'admin')->doesntExist()) {
            $ids = Permission::query()->pluck('id')->all();

            foreach ($ids as $id) {
                DB::table('role_permissions')->insert([
                    'role' => 'admin',
                    'permission_id' => $id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}
