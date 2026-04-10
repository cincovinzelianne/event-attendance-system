<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('My QR Token') }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                <p class="mb-3 text-sm text-gray-600">Use this token at the scanner page to record attendance.</p>
                <textarea readonly rows="6" class="block w-full rounded-md border-gray-300 bg-gray-50 text-xs text-gray-800">{{ $token }}</textarea>
            </div>
        </div>
    </div>
</x-app-layout>
