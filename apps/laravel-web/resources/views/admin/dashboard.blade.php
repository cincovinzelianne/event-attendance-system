<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Admin Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <p class="mb-4">{{ __('Welcome to the admin dashboard.') }}</p>
                    <div class="flex flex-wrap gap-3">
                        <a href="{{ route('admin.events.index') }}" class="inline-flex rounded-md bg-gray-900 px-4 py-2 text-sm text-white hover:bg-gray-700">
                        {{ __('Manage Events') }}
                        </a>
                        <a href="{{ route('admin.attendance.index') }}" class="inline-flex rounded-md bg-blue-700 px-4 py-2 text-sm text-white hover:bg-blue-600">
                            {{ __('Attendance Dashboard') }}
                        </a>
                        <a href="{{ route('admin.qr-scanner.index') }}" class="inline-flex rounded-md bg-emerald-700 px-4 py-2 text-sm text-white hover:bg-emerald-600">
                            {{ __('QR Scanner') }}
                        </a>
                        <a href="{{ route('admin.notifications.index') }}" class="inline-flex rounded-md bg-amber-700 px-4 py-2 text-sm text-white hover:bg-amber-600">
                            {{ __('Notifications') }}
                        </a>
                        <a href="{{ route('admin.analytics.index') }}" class="inline-flex rounded-md bg-indigo-700 px-4 py-2 text-sm text-white hover:bg-indigo-600">
                            {{ __('Analytics') }}
                        </a>
                        <a href="{{ route('admin.diagnostics.index') }}" class="inline-flex rounded-md bg-slate-700 px-4 py-2 text-sm text-white hover:bg-slate-600">
                            {{ __('Diagnostics') }}
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
