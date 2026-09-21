<x-layouts.auth subtitle="Sign in to continue.">
    <form method="POST" action="{{ route('login.store') }}">
        @csrf

        <div class="field">
            <label for="email">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="email">
        </div>

        <div class="field">
            <label for="password">Password</label>
            <input id="password" type="password" name="password" required autocomplete="current-password">
        </div>

        <label class="remember">
            <input type="checkbox" name="remember">
            Remember me
        </label>

        <button type="submit">Sign in</button>
    </form>

    <p class="links">
        <a href="{{ route('password.request') }}">Forgot password?</a>
    </p>
</x-layouts.auth>
