<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ config('app.name', 'LLCC Event Attendance System') }}</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=manrope:400,500,600,700|fraunces:400,600,700" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-[#07111f] text-white antialiased">
        <div class="relative overflow-hidden">
            <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_left,_rgba(217,175,66,0.18),_transparent_28%),radial-gradient(circle_at_85%_20%,_rgba(31,78,121,0.45),_transparent_24%),linear-gradient(180deg,#08111f_0%,#0b1528_58%,#07111f_100%)]"></div>
            <div class="absolute inset-0 opacity-[0.18]" style="background-image: linear-gradient(rgba(255,255,255,0.12) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,0.12) 1px, transparent 1px); background-size: 72px 72px;"></div>

            <header class="relative z-10 mx-auto flex w-full max-w-7xl items-center justify-between px-6 py-6 lg:px-8">
                <a href="{{ route('landing') }}" class="flex items-center gap-3">
                    <div class="flex h-11 w-11 items-center justify-center rounded-2xl border border-white/15 bg-white/10 shadow-2xl backdrop-blur">
                        <span class="font-semibold tracking-[0.22em] text-amber-300">EA</span>
                    </div>
                    <div>
                        <p class="text-sm font-semibold tracking-[0.3em] text-white/70 uppercase">LLCC Event Attendance System</p>
                        <p class="text-xs text-white/45">Web platform for campus attendance</p>
                    </div>
                </a>

                <nav class="flex items-center gap-3 text-sm font-medium">
                    <a href="#features" class="rounded-full border border-white/10 bg-white/5 px-4 py-2 text-white/80 transition hover:border-white/20 hover:bg-white/10">Features</a>
                    <a href="{{ route('login') }}" class="rounded-full border border-white/10 bg-white/5 px-4 py-2 text-white/80 transition hover:border-white/20 hover:bg-white/10">Log in</a>
                    @if (Route::has('register'))
                        <a href="{{ route('register') }}" class="rounded-full bg-amber-300 px-4 py-2 font-semibold text-slate-950 transition hover:bg-amber-200">Register</a>
                    @endif
                </nav>
            </header>

            <main class="relative z-10 mx-auto grid w-full max-w-7xl gap-12 px-6 pb-20 pt-10 lg:grid-cols-[1.1fr_0.9fr] lg:px-8 lg:pt-14">
                <section class="max-w-3xl">
                    <span class="inline-flex items-center rounded-full border border-emerald-400/20 bg-emerald-400/10 px-4 py-1 text-sm font-medium text-emerald-200 shadow-lg shadow-emerald-950/20">
                        Premium web platform for campus operations
                    </span>
                    <h1 class="mt-6 max-w-2xl font-['Fraunces'] text-5xl leading-[0.95] tracking-tight text-white sm:text-6xl lg:text-7xl">
                        Attendance management, elevated for the modern campus.
                    </h1>
                    <p class="mt-6 max-w-2xl text-lg leading-8 text-slate-300">
                        Centralize events, QR scanning, student notifications, and real-time attendance control in one polished web experience built for admins and students.
                    </p>

                    <div class="mt-10 flex flex-wrap gap-4">
                        <a href="{{ route('login') }}" class="inline-flex items-center rounded-full bg-white px-6 py-3 text-sm font-semibold text-slate-950 shadow-xl shadow-black/20 transition hover:-translate-y-0.5 hover:bg-slate-100">
                            Access the system
                        </a>
                        @if (Route::has('register'))
                            <a href="{{ route('register') }}" class="inline-flex items-center rounded-full border border-white/15 bg-white/5 px-6 py-3 text-sm font-semibold text-white transition hover:-translate-y-0.5 hover:bg-white/10">
                                Create account
                            </a>
                        @endif
                    </div>

                    <div class="mt-12 grid gap-4 sm:grid-cols-3">
                        <div class="rounded-3xl border border-white/10 bg-white/6 p-5 backdrop-blur-xl">
                            <p class="text-3xl font-semibold text-white">P0</p>
                            <p class="mt-1 text-sm text-slate-300">Auth, events, attendance, QR</p>
                        </div>
                        <div class="rounded-3xl border border-white/10 bg-white/6 p-5 backdrop-blur-xl">
                            <p class="text-3xl font-semibold text-white">100%</p>
                            <p class="mt-1 text-sm text-slate-300">Web-based platform only</p>
                        </div>
                        <div class="rounded-3xl border border-white/10 bg-white/6 p-5 backdrop-blur-xl">
                            <p class="text-3xl font-semibold text-white">MySQL</p>
                            <p class="mt-1 text-sm text-slate-300">Structured, auditable storage</p>
                        </div>
                    </div>
                </section>

                <aside id="features" class="lg:pl-8">
                    <div class="overflow-hidden rounded-[2rem] border border-white/10 bg-slate-950/70 shadow-2xl shadow-black/40 backdrop-blur-2xl">
                        <div class="border-b border-white/10 px-6 py-5">
                            <p class="text-xs font-semibold uppercase tracking-[0.3em] text-amber-200/80">What it supports</p>
                            <h2 class="mt-2 text-2xl font-semibold text-white">Built for the whole attendance workflow</h2>
                        </div>
                        <div class="grid gap-4 p-6">
                            <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
                                <p class="font-semibold text-white">Admin dashboard</p>
                                <p class="mt-2 text-sm leading-6 text-slate-300">Create events, lock attendance, inspect analytics, and manage notifications from one place.</p>
                            </div>
                            <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
                                <p class="font-semibold text-white">Student experience</p>
                                <p class="mt-2 text-sm leading-6 text-slate-300">Log in, show QR credentials, record attendance, and track notifications in a simple browser flow.</p>
                            </div>
                            <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
                                <p class="font-semibold text-white">Operational control</p>
                                <p class="mt-2 text-sm leading-6 text-slate-300">Diagnostics, audit trails, and migration-ready MySQL tables keep the platform maintainable.</p>
                            </div>
                        </div>
                    </div>
                </aside>
            </main>
        </div>
    </body>
</html>
