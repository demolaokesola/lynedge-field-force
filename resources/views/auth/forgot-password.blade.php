<x-layouts.auth subtitle="Enter your email and we will send you a link to reset your password.">
    <form method="POST" action="{{ route('password.email') }}">
        @csrf

        <div class="field">
            <label for="email">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="email">
        </div>

        <button type="submit">Send reset link</button>
    </form>

    <p class="links">
        <a href="{{ route('login') }}">Back to sign in</a>
    </p>
</x-layouts.auth>
