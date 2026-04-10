@props([
    'title' => 'Admin Page',
    'subtitle' => null,
])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Event Attendance System') }} | {{ $title }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=manrope:400,500,600,700|fraunces:500,700" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-blue-950 font-sans text-slate-100 antialiased">
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_20%_10%,_rgba(56,189,248,0.15),_transparent_28%),radial-gradient(circle_at_80%_0%,_rgba(250,204,21,0.12),_transparent_22%),linear-gradient(180deg,#020617_0%,#0b1220_100%)]"></div>

        <div class="relative min-h-screen lg:grid lg:grid-cols-[290px_1fr]">
            <aside class="border-b border-white/10 bg-blue-950/85 backdrop-blur-xl lg:border-b-0 lg:border-r">
                <div class="flex h-full flex-col">
                    <div class="border-b border-white/10 px-6 py-6">
                        <a href="{{ route('admin.dashboard') }}" class="inline-flex items-center gap-3">
                            <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-yellow-400/20 text-sm font-semibold tracking-[0.2em] text-yellow-200">
                                EA
                            </div>
                            <div>
                                <p class="text-xs uppercase tracking-[0.3em] text-yellow-200/80">Admin Panel</p>
                                <p class="text-sm font-semibold text-white">Event Attendance System</p>
                            </div>
                        </a>
                    </div>

                    <nav class="px-4 py-5">
                        <p class="px-3 text-xs font-semibold uppercase tracking-[0.25em] text-slate-400">Navigation</p>
                        <div class="mt-3 space-y-1">
                            <a href="{{ route('admin.dashboard') }}" class="flex items-center rounded-xl px-3 py-2 text-sm transition {{ request()->routeIs('admin.dashboard') ? 'bg-blue-700/25 font-semibold text-yellow-200' : 'text-slate-300 hover:bg-white/5 hover:text-white' }}">
                                Dashboard
                            </a>
                            <a href="{{ route('admin.events.index') }}" class="flex items-center rounded-xl px-3 py-2 text-sm transition {{ request()->routeIs('admin.events.*') ? 'bg-blue-700/25 font-semibold text-yellow-200' : 'text-slate-300 hover:bg-white/5 hover:text-white' }}">
                                Events
                            </a>
                            <a href="{{ route('admin.attendance.index') }}" class="flex items-center rounded-xl px-3 py-2 text-sm transition {{ request()->routeIs('admin.attendance.*') ? 'bg-blue-700/25 font-semibold text-yellow-200' : 'text-slate-300 hover:bg-white/5 hover:text-white' }}">
                                Attendance
                            </a>
                            <a href="{{ route('admin.qr-scanner.index') }}" class="flex items-center rounded-xl px-3 py-2 text-sm transition {{ request()->routeIs('admin.qr-scanner.*') ? 'bg-blue-700/25 font-semibold text-yellow-200' : 'text-slate-300 hover:bg-white/5 hover:text-white' }}">
                                QR Scanner
                            </a>
                            <a href="{{ route('admin.notifications.index') }}" class="flex items-center rounded-xl px-3 py-2 text-sm transition {{ request()->routeIs('admin.notifications.*') ? 'bg-blue-700/25 font-semibold text-yellow-200' : 'text-slate-300 hover:bg-white/5 hover:text-white' }}">
                                Notifications
                            </a>
                            <a href="{{ route('admin.users.index') }}" class="flex items-center rounded-xl px-3 py-2 text-sm transition {{ request()->routeIs('admin.users.*') ? 'bg-blue-700/25 font-semibold text-yellow-200' : 'text-slate-300 hover:bg-white/5 hover:text-white' }}">
                                Users
                            </a>
                            <a href="{{ route('admin.settings.index') }}" class="flex items-center rounded-xl px-3 py-2 text-sm transition {{ request()->routeIs('admin.settings.*') ? 'bg-blue-700/25 font-semibold text-yellow-200' : 'text-slate-300 hover:bg-white/5 hover:text-white' }}">
                                Settings
                            </a>
                            <a href="{{ route('admin.permissions.index') }}" class="flex items-center rounded-xl px-3 py-2 text-sm transition {{ request()->routeIs('admin.permissions.*') ? 'bg-blue-700/25 font-semibold text-yellow-200' : 'text-slate-300 hover:bg-white/5 hover:text-white' }}">
                                Permissions
                            </a>
                            <a href="{{ route('admin.audit-logs.index') }}" class="flex items-center rounded-xl px-3 py-2 text-sm transition {{ request()->routeIs('admin.audit-logs.*') ? 'bg-blue-700/25 font-semibold text-yellow-200' : 'text-slate-300 hover:bg-white/5 hover:text-white' }}">
                                Audit Logs
                            </a>
                            <a href="{{ route('admin.certificates.index') }}" class="flex items-center rounded-xl px-3 py-2 text-sm transition {{ request()->routeIs('admin.certificates.*') ? 'bg-blue-700/25 font-semibold text-yellow-200' : 'text-slate-300 hover:bg-white/5 hover:text-white' }}">
                                Certificates
                            </a>
                            <a href="{{ route('admin.evaluations.index') }}" class="flex items-center rounded-xl px-3 py-2 text-sm transition {{ request()->routeIs('admin.evaluations.*') ? 'bg-blue-700/25 font-semibold text-yellow-200' : 'text-slate-300 hover:bg-white/5 hover:text-white' }}">
                                Evaluations
                            </a>
                            <a href="{{ route('admin.analytics.index') }}" class="flex items-center rounded-xl px-3 py-2 text-sm transition {{ request()->routeIs('admin.analytics.*') ? 'bg-blue-700/25 font-semibold text-yellow-200' : 'text-slate-300 hover:bg-white/5 hover:text-white' }}">
                                Analytics
                            </a>
                            <a href="{{ route('admin.diagnostics.index') }}" class="flex items-center rounded-xl px-3 py-2 text-sm transition {{ request()->routeIs('admin.diagnostics.*') ? 'bg-blue-700/25 font-semibold text-yellow-200' : 'text-slate-300 hover:bg-white/5 hover:text-white' }}">
                                Diagnostics
                            </a>
                        </div>
                    </nav>

                    <div class="mt-auto border-t border-white/10 p-4">
                        <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
                            <p class="text-xs uppercase tracking-[0.25em] text-slate-400">Signed in as</p>
                            <p class="mt-2 text-sm font-semibold text-white">{{ auth()->user()->name }}</p>
                            <p class="mt-1 text-xs text-slate-400">{{ auth()->user()->email }}</p>
                            <div class="mt-4 flex items-center gap-2">
                                <a href="{{ route('landing') }}" class="rounded-lg border border-white/15 px-3 py-2 text-xs font-medium text-slate-200 transition hover:bg-white/10">Landing</a>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="rounded-lg bg-yellow-500/90 px-3 py-2 text-xs font-semibold text-white transition hover:bg-yellow-500">
                                        Logout
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </aside>

            <main class="p-5 sm:p-8 lg:p-10">
                <header class="mb-8 flex flex-wrap items-end justify-between gap-5">
                    <div>
                        <p class="text-xs uppercase tracking-[0.3em] text-yellow-200/70">Control Center</p>
                        <h1 class="mt-2 font-['Fraunces'] text-4xl leading-tight text-white sm:text-5xl">{{ $title }}</h1>
                        @if ($subtitle)
                            <p class="mt-3 max-w-2xl text-sm leading-7 text-slate-300">{{ $subtitle }}</p>
                        @endif
                    </div>
                    @isset($actions)
                        <div class="flex flex-wrap gap-3">
                            {{ $actions }}
                        </div>
                    @endisset
                </header>

                @if (session('status'))
                    <div class="mb-6 rounded-xl border border-blue-300/30 bg-blue-700/25 px-4 py-3 text-sm text-blue-100">
                        {{ session('status') }}
                    </div>
                @endif

                {{ $slot }}
            </main>
        </div>
    </body>
</html>
