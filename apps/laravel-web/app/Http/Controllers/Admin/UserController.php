<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ImportUsersRequest;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\StreamedResponse;

class UserController extends Controller
{
    public function index(): View
    {
        return view('admin.users.index', [
            'users' => User::query()->latest()->paginate(20),
        ]);
    }

    public function create(): View
    {
        return view('admin.users.create');
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        User::query()->create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $validated['role'],
            'is_active' => (bool) ($validated['is_active'] ?? true),
            'password' => Hash::make($validated['password']),
            'email_verified_at' => now(),
        ]);

        return redirect()->route('admin.users.index')->with('status', 'User created successfully.');
    }

    public function edit(User $user): View
    {
        return view('admin.users.edit', ['user' => $user]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $validated = $request->validated();

        $payload = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $validated['role'],
            'is_active' => (bool) ($validated['is_active'] ?? false),
        ];

        if (! empty($validated['password'])) {
            $payload['password'] = Hash::make($validated['password']);
        }

        $user->update($payload);

        return redirect()->route('admin.users.index')->with('status', 'User updated successfully.');
    }

    public function destroy(User $user): RedirectResponse
    {
        if ((int) $user->id === (int) request()->user()->id) {
            return redirect()->route('admin.users.index')->withErrors(['user' => 'You cannot delete your own account.']);
        }

        $user->delete();

        return redirect()->route('admin.users.index')->with('status', 'User deleted successfully.');
    }

    public function import(ImportUsersRequest $request): RedirectResponse
    {
        $file = $request->file('csv_file');
        $defaultRole = $request->string('default_role')->toString() ?: 'student';

        $handle = fopen($file->getRealPath(), 'r');

        if ($handle === false) {
            return redirect()->route('admin.users.index')->withErrors(['csv_file' => 'Unable to read uploaded file.']);
        }

        $created = 0;
        $row = 0;

        while (($data = fgetcsv($handle)) !== false) {
            $row++;

            if ($row === 1 && isset($data[0]) && strtolower((string) $data[0]) === 'name') {
                continue;
            }

            $name = trim((string) ($data[0] ?? ''));
            $email = trim((string) ($data[1] ?? ''));
            $role = trim((string) ($data[2] ?? $defaultRole));

            if ($name === '' || $email === '') {
                continue;
            }

            if (! in_array($role, ['admin', 'student'], true)) {
                $role = $defaultRole;
            }

            User::query()->updateOrCreate(
                ['email' => $email],
                [
                    'name' => $name,
                    'role' => $role,
                    'is_active' => true,
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                ]
            );

            $created++;
        }

        fclose($handle);

        return redirect()->route('admin.users.index')->with('status', "User import completed. {$created} record(s) processed.");
    }

    public function exportCsv(): StreamedResponse
    {
        $filename = 'users-export-'.now()->format('Ymd-His').'.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ];

        return response()->stream(function (): void {
            $handle = fopen('php://output', 'w');

            if ($handle === false) {
                return;
            }

            fputcsv($handle, ['Name', 'Email', 'Role', 'Active', 'Created At']);

            User::query()
                ->orderBy('name')
                ->chunk(500, function ($users) use ($handle): void {
                    foreach ($users as $user) {
                        fputcsv($handle, [
                            $user->name,
                            $user->email,
                            $user->role,
                            $user->is_active ? 'Yes' : 'No',
                            $user->created_at?->format('Y-m-d H:i:s'),
                        ]);
                    }
                });

            fclose($handle);
        }, 200, $headers);
    }
}
