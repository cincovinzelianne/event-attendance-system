<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Student Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <p class="mb-4">{{ __('Welcome to the student dashboard.') }}</p>
                    <div class="flex flex-wrap gap-3">
                        <a href="{{ route('student.attendance.index') }}" class="inline-flex rounded-md bg-gray-900 px-4 py-2 text-sm text-white hover:bg-gray-700">
                            {{ __('Record Attendance') }}
                        </a>
                        <a href="{{ route('student.qr.show') }}" class="inline-flex rounded-md bg-blue-700 px-4 py-2 text-sm text-white hover:bg-blue-600">
                            {{ __('Show My QR Token') }}
                        </a>
                        <a href="{{ route('student.notifications.index') }}" class="inline-flex rounded-md bg-amber-700 px-4 py-2 text-sm text-white hover:bg-amber-600">
                            {{ __('My Notifications') }}
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
