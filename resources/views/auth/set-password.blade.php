<x-layouts.auth :subtitle="$isValid ? 'Welcome! Choose a password to activate your account.' : 'This invitation link is invalid or has expired.'">
    @if ($isValid)
        <form method="POST" action="{{ route('invitation.store') }}">
            @include('auth.partials.new-password-fields', ['token' => $token, 'email' => $email])

            <button type="submit">Set password and sign in</button>
        </form>
    @else
        <div class="errors">
            Invitation links are valid for 24 hours. Ask your administrator to resend your invitation.
        </div>

        <p class="links">
            <a href="{{ route('login') }}">Back to sign in</a>
        </p>
    @endif
</x-layouts.auth>
