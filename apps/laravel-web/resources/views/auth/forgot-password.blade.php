<x-guest-layout>
    <div class="mx-auto max-w-6xl px-6 py-14 lg:px-8">
        <div class="grid gap-10 lg:grid-cols-[1.05fr_0.95fr] lg:items-center">
            <section class="space-y-8 text-white">
                <div class="inline-flex items-center gap-3 rounded-full border border-white/10 bg-white/5 px-4 py-2 text-xs font-semibold uppercase tracking-[0.35em] text-amber-200/90 backdrop-blur">
                    <span class="h-2 w-2 rounded-full bg-emerald-400 shadow-[0_0_18px_rgba(74,222,128,0.85)]"></span>
                    Account recovery
                </div>

                <div class="space-y-5">
                    <h1 class="max-w-2xl font-['Fraunces'] text-4xl leading-tight text-white sm:text-5xl lg:text-6xl">
                        Reset your password without breaking the flow.
                    </h1>
                    <p class="max-w-xl text-base leading-8 text-slate-300 sm:text-lg">
                        Enter your email address and we will send a secure reset link so you can get back into the attendance platform quickly.
                    </p>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="rounded-3xl border border-white/10 bg-white/5 p-5 shadow-[0_24px_80px_rgba(15,23,42,0.28)] backdrop-blur">
                        <p class="text-xs font-semibold uppercase tracking-[0.3em] text-amber-200/80">Secure workflow</p>
                        <p class="mt-3 text-sm leading-7 text-slate-300">Reset links are time-bound and designed to keep access recovery controlled.</p>
                    </div>
                    <div class="rounded-3xl border border-white/10 bg-white/5 p-5 shadow-[0_24px_80px_rgba(15,23,42,0.28)] backdrop-blur">
                        <p class="text-xs font-semibold uppercase tracking-[0.3em] text-amber-200/80">Fast return</p>
                        <p class="mt-3 text-sm leading-7 text-slate-300">Return to your dashboard, notifications, and attendance tools with minimal friction.</p>
                    </div>
                </div>
            </section>

            <section class="rounded-[2rem] border border-white/10 bg-slate-950/75 p-8 shadow-[0_32px_110px_rgba(15,23,42,0.45)] backdrop-blur-xl sm:p-10">
                <div class="mb-8">
                    <p class="text-sm font-semibold uppercase tracking-[0.3em] text-amber-200/80">Password reset</p>
                    <h2 class="mt-3 text-3xl font-semibold text-white">Request a reset link</h2>
                    <p class="mt-3 text-sm leading-7 text-slate-400">
                        We'll email you a link to choose a new password.
                    </p>
                </div>

                <x-auth-session-status class="mb-6" :status="session('status')" />

                <form method="POST" action="{{ route('password.email') }}" class="space-y-6">
                    @csrf

                    <div>
                        <x-input-label for="email" :value="__('Email address')" class="text-sm font-medium text-slate-200" />
                        <x-text-input
                            id="email"
                            class="mt-2 block w-full rounded-2xl border-white/10 bg-slate-900/90 px-4 py-3 text-white placeholder:text-slate-500 shadow-[0_12px_40px_rgba(15,23,42,0.2)] focus:border-amber-400 focus:ring-amber-400"
                            type="email"
                            name="email"
                            :value="old('email')"
                            required
                            autofocus
                            autocomplete="email"
                            placeholder="name@school.edu"
                        />
                        <x-input-error :messages="$errors->get('email')" class="mt-2" />
                    </div>

                    <x-primary-button class="inline-flex w-full justify-center rounded-2xl bg-gradient-to-r from-amber-400 via-orange-400 to-amber-500 px-6 py-3 text-sm font-semibold text-slate-950 shadow-[0_18px_40px_rgba(251,191,36,0.28)] transition hover:-translate-y-0.5 hover:shadow-[0_24px_60px_rgba(251,191,36,0.36)]">
                        {{ __('Email Password Reset Link') }}
                    </x-primary-button>
                </form>

                <div class="mt-8 rounded-2xl border border-white/10 bg-white/5 p-5 text-sm text-slate-300">
                    Remembered your password?
                    <a href="{{ route('login') }}" class="font-semibold text-amber-200 transition hover:text-amber-100">
                        Back to sign in
                    </a>
                </div>
            </section>
        </div>
    </div>
</x-guest-layout>
