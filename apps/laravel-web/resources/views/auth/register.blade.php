<x-guest-layout>
    <div class="mb-8">
        <p class="text-sm font-semibold uppercase tracking-[0.28em] text-amber-200/80">Create account</p>
        <h2 class="mt-3 font-['Fraunces'] text-4xl leading-tight text-white">Start using the platform</h2>
        <p class="mt-3 max-w-md text-sm leading-6 text-slate-300">
            Register to manage events, scan attendance, and view notifications from your browser.
        </p>
    </div>

    <form method="POST" action="{{ route('register') }}" class="space-y-5">
        @csrf

        <div>
            <x-input-label for="name" :value="__('Name')" class="text-sm font-medium text-slate-200" />
            <x-text-input id="name" class="mt-2 block w-full rounded-2xl border-white/10 bg-white/5 px-4 py-3 text-white placeholder:text-slate-400 focus:border-amber-300 focus:ring-amber-300" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" placeholder="Full name" />
            <x-input-error :messages="$errors->get('name')" class="mt-2 text-rose-300" />
        </div>

        <div>
            <x-input-label for="email" :value="__('Email')" class="text-sm font-medium text-slate-200" />
            <x-text-input id="email" class="mt-2 block w-full rounded-2xl border-white/10 bg-white/5 px-4 py-3 text-white placeholder:text-slate-400 focus:border-amber-300 focus:ring-amber-300" type="email" name="email" :value="old('email')" required autocomplete="username" placeholder="name@example.com" />
            <x-input-error :messages="$errors->get('email')" class="mt-2 text-rose-300" />
        </div>

        <div>
            <x-input-label for="password" :value="__('Password')" class="text-sm font-medium text-slate-200" />
            <x-text-input id="password" class="mt-2 block w-full rounded-2xl border-white/10 bg-white/5 px-4 py-3 text-white placeholder:text-slate-400 focus:border-amber-300 focus:ring-amber-300" type="password" name="password" required autocomplete="new-password" placeholder="Create a password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2 text-rose-300" />
        </div>

        <div>
            <x-input-label for="password_confirmation" :value="__('Confirm Password')" class="text-sm font-medium text-slate-200" />
            <x-text-input id="password_confirmation" class="mt-2 block w-full rounded-2xl border-white/10 bg-white/5 px-4 py-3 text-white placeholder:text-slate-400 focus:border-amber-300 focus:ring-amber-300" type="password" name="password_confirmation" required autocomplete="new-password" placeholder="Repeat your password" />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2 text-rose-300" />
        </div>

        <button type="submit" class="inline-flex w-full items-center justify-center rounded-2xl bg-amber-300 px-5 py-3 text-sm font-semibold text-slate-950 shadow-xl shadow-amber-950/30 transition hover:-translate-y-0.5 hover:bg-amber-200">
            {{ __('Register') }}
        </button>

        <p class="text-center text-sm text-slate-300">
            {{ __('Already have an account?') }}
            <a href="{{ route('login') }}" class="font-semibold text-amber-200 hover:text-amber-100">{{ __('Log in') }}</a>
        </p>
    </form>
</x-guest-layout>
