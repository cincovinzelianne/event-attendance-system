<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Event Attendance System') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=manrope:400,500,600,700|fraunces:400,600,700" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-[#07111f] font-sans text-white antialiased">
        <div class="relative overflow-hidden">
            <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_left,_rgba(217,175,66,0.18),_transparent_28%),radial-gradient(circle_at_85%_20%,_rgba(31,78,121,0.45),_transparent_24%),linear-gradient(180deg,#08111f_0%,#0b1528_58%,#07111f_100%)]"></div>
            <div class="absolute inset-0 opacity-[0.18]" style="background-image: linear-gradient(rgba(255,255,255,0.12) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,0.12) 1px, transparent 1px); background-size: 72px 72px;"></div>

            <div class="relative mx-auto grid min-h-screen max-w-7xl lg:grid-cols-[0.92fr_1.08fr]">
                <aside class="hidden lg:flex flex-col justify-between px-10 py-10">
                    <a href="{{ route('landing') }}" class="inline-flex items-center gap-3">
                        <div class="flex h-11 w-11 items-center justify-center rounded-2xl border border-white/15 bg-white/10 shadow-2xl backdrop-blur">
                            <span class="font-semibold tracking-[0.22em] text-amber-300">EA</span>
                        </div>
                        <div>
                            <p class="text-sm font-semibold tracking-[0.3em] text-white/70 uppercase">Event Attendance System</p>
                            <p class="text-xs text-white/45">Web platform for campus attendance</p>
                        </div>
                    </a>

                    <div class="max-w-xl">
                        <p class="inline-flex rounded-full border border-emerald-400/20 bg-emerald-400/10 px-4 py-1 text-sm font-medium text-emerald-200">Secure access for admins and students</p>
                        <h1 class="mt-6 font-['Fraunces'] text-5xl leading-[0.95] tracking-tight text-white">
                            A polished command center for attendance, events, and notifications.
                        </h1>
                        <p class="mt-5 max-w-lg text-base leading-7 text-slate-300">
                            Centralize the campus workflow in a refined browser experience with QR scanning, role-aware dashboards, and clean operational control.
                        </p>
                        <div class="mt-8 grid gap-4 sm:grid-cols-3">
                            <div class="rounded-2xl border border-white/10 bg-white/6 p-4">
                                <p class="text-2xl font-semibold text-white">P0</p>
                                <p class="mt-1 text-sm text-slate-300">Auth and attendance</p>
                            </div>
                            <div class="rounded-2xl border border-white/10 bg-white/6 p-4">
                                <p class="text-2xl font-semibold text-white">QR</p>
                                <p class="mt-1 text-sm text-slate-300">Scan and lock flow</p>
                            </div>
                            <div class="rounded-2xl border border-white/10 bg-white/6 p-4">
                                <p class="text-2xl font-semibold text-white">MySQL</p>
                                <p class="mt-1 text-sm text-slate-300">Stable data model</p>
                            </div>
                        </div>
                    </div>
                </aside>

                <main class="flex items-center justify-center px-6 py-10 lg:px-10">
                    <div class="w-full max-w-lg rounded-[2rem] border border-white/10 bg-slate-950/80 p-8 shadow-2xl shadow-black/40 backdrop-blur-2xl sm:p-10">
                        <div class="mb-8 lg:hidden">
                            <a href="{{ route('landing') }}" class="inline-flex items-center gap-3">
                                <div class="flex h-11 w-11 items-center justify-center rounded-2xl border border-white/15 bg-white/10 shadow-2xl backdrop-blur">
                                    <span class="font-semibold tracking-[0.22em] text-amber-300">EA</span>
                                </div>
                                <div>
                                    <p class="text-sm font-semibold tracking-[0.3em] text-white/70 uppercase">Event Attendance System</p>
                                    <p class="text-xs text-white/45">Web platform for campus attendance</p>
                                </div>
                            </a>
                        </div>

                        {{ $slot }}
                    </div>
                </main>
            </div>
        </div>
    </body>
</html>
