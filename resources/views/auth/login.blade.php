<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name') }}</title>
    <style>
        :root {
            color-scheme: light;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #0f172a;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            padding: 1.5rem;
        }

        .card {
            width: 100%;
            max-width: 24rem;
            background: #ffffff;
            border-radius: 0.75rem;
            padding: 2.5rem 2rem;
            box-shadow: 0 20px 40px -12px rgb(0 0 0 / 0.35);
        }

        .card h1 {
            margin: 0 0 0.25rem;
            font-size: 1.25rem;
            font-weight: 600;
            color: #0f172a;
        }

        .card p.subtitle {
            margin: 0 0 1.75rem;
            font-size: 0.875rem;
            color: #64748b;
        }

        label {
            display: block;
            font-size: 0.8125rem;
            font-weight: 500;
            color: #334155;
            margin-bottom: 0.375rem;
        }

        .field {
            margin-bottom: 1.125rem;
        }

        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 0.625rem 0.75rem;
            border: 1px solid #cbd5e1;
            border-radius: 0.5rem;
            font-size: 0.9375rem;
            color: #0f172a;
        }

        input[type="email"]:focus,
        input[type="password"]:focus {
            outline: 2px solid #6366f1;
            outline-offset: 1px;
            border-color: #6366f1;
        }

        .remember {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
            font-size: 0.8125rem;
            color: #475569;
        }

        .remember input {
            margin: 0;
        }

        button {
            width: 100%;
            padding: 0.625rem 0.75rem;
            background: #4f46e5;
            color: #ffffff;
            border: none;
            border-radius: 0.5rem;
            font-size: 0.9375rem;
            font-weight: 600;
            cursor: pointer;
        }

        button:hover {
            background: #4338ca;
        }

        .errors {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #b91c1c;
            border-radius: 0.5rem;
            padding: 0.75rem 1rem;
            font-size: 0.8125rem;
            margin-bottom: 1.25rem;
        }

        .errors ul {
            margin: 0;
            padding-left: 1.1rem;
        }
    </style>
</head>
<body>
    <div class="card">
        <h1>{{ config('app.name') }}</h1>
        <p class="subtitle">Sign in to continue.</p>

        @if ($errors->any())
            <div class="errors">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

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
    </div>
</body>
</html>
