<x-admin-layout title="Edit Event" subtitle="Update schedule, location, and lock settings for this event.">
    <div class="max-w-4xl rounded-3xl border border-white/10 bg-slate-900/75 p-6 shadow-2xl shadow-black/30 backdrop-blur-xl">
        <form method="POST" action="{{ route('admin.events.update', $event) }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            @include('admin.events._form', ['submitLabel' => 'Update Event'])
        </form>
    </div>
</x-admin-layout>
