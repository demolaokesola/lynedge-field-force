<x-layouts.auth subtitle="Choose a new password for your account.">
    <form method="POST" action="{{ route('password.update') }}">
        @include('auth.partials.new-password-fields', ['token' => $token, 'email' => $email])

        <button type="submit">Reset password</button>
    </form>

    <p class="links">
        <a href="{{ route('login') }}">Back to sign in</a>
    </p>
</x-layouts.auth>
