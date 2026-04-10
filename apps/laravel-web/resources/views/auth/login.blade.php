<x-guest-layout>
    <div class="mb-8">
        <p class="text-sm font-semibold uppercase tracking-[0.28em] text-amber-200/80">Welcome back</p>
        <h2 class="mt-3 font-['Fraunces'] text-4xl leading-tight text-white">Sign in to continue</h2>
        <p class="mt-3 max-w-md text-sm leading-6 text-slate-300">
            Access the admin or student dashboard from one secure portal.
        </p>
    </div>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="space-y-5">
        @csrf

        <div>
            <x-input-label for="email" :value="__('Email')" class="text-sm font-medium text-slate-200" />
            <x-text-input id="email" class="mt-2 block w-full rounded-2xl border-white/10 bg-white/5 px-4 py-3 text-white placeholder:text-slate-400 focus:border-amber-300 focus:ring-amber-300" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" placeholder="name@example.com" />
            <x-input-error :messages="$errors->get('email')" class="mt-2 text-rose-300" />
        </div>

        <div>
            <x-input-label for="password" :value="__('Password')" class="text-sm font-medium text-slate-200" />
            <x-text-input id="password" class="mt-2 block w-full rounded-2xl border-white/10 bg-white/5 px-4 py-3 text-white placeholder:text-slate-400 focus:border-amber-300 focus:ring-amber-300" type="password" name="password" required autocomplete="current-password" placeholder="••••••••" />
            <x-input-error :messages="$errors->get('password')" class="mt-2 text-rose-300" />
        </div>

        <div class="flex items-center justify-between gap-4">
            <label for="remember_me" class="inline-flex items-center gap-2 text-sm text-slate-300">
                <input id="remember_me" type="checkbox" class="rounded border-white/20 bg-white/5 text-amber-300 shadow-sm focus:ring-amber-300" name="remember">
                <span>{{ __('Remember me') }}</span>
            </label>

            @if (Route::has('password.request'))
                <a class="text-sm font-medium text-amber-200 transition hover:text-amber-100" href="{{ route('password.request') }}">
                    {{ __('Forgot password?') }}
                </a>
            @endif
        </div>

        <button type="submit" class="inline-flex w-full items-center justify-center rounded-2xl bg-amber-300 px-5 py-3 text-sm font-semibold text-slate-950 shadow-xl shadow-amber-950/30 transition hover:-translate-y-0.5 hover:bg-amber-200">
            {{ __('Log in') }}
        </button>

        <p class="text-center text-sm text-slate-300">
            {{ __('Need an account?') }}
            <a href="{{ route('register') }}" class="font-semibold text-amber-200 hover:text-amber-100">{{ __('Register here') }}</a>
        </p>
    </form>
</x-guest-layout>
