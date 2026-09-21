@csrf
<input type="hidden" name="token" value="{{ $token }}">

<div class="field">
    <label for="email">Email</label>
    <input id="email" type="email" name="email" value="{{ old('email', $email) }}" required readonly autocomplete="email">
</div>

<div class="field">
    <label for="password">New password</label>
    <input id="password" type="password" name="password" required autofocus autocomplete="new-password">
</div>

<div class="field">
    <label for="password_confirmation">Confirm new password</label>
    <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password">
</div>
