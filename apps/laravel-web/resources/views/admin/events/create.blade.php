<x-admin-layout title="Create Event" subtitle="Set up a new event schedule, location, and attendance behavior.">
    <div class="max-w-4xl rounded-3xl border border-white/10 bg-slate-900/75 p-6 shadow-2xl shadow-black/30 backdrop-blur-xl">
        <form method="POST" action="{{ route('admin.events.store') }}">
            @include('admin.events._form', ['submitLabel' => 'Save Event'])
        </form>
    </div>
</x-admin-layout>
