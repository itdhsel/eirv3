<!doctype html>
<html lang="en"><head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title>Sign In | eIRv3 — Hospital Selayang</title>
    <link rel="stylesheet" href="{{ asset('css/eirv3.css') }}">
</head>
<body class="ir-login-body"><main class="ir-login-card">
    <div class="ir-login-brand"><strong>e-IR</strong> <span>System</span><small>Hospital Selayang · QA/PRSK Module</small></div>
    <h1>Sign in to eIRv3</h1>
    <p>Authorized hospital personnel only. Patient safety information is confidential.</p>
    @if($errors->any())<div class="ir-alert ir-alert-error" role="alert">{{ $errors->first() }}</div>@endif
    <form method="POST" action="{{ route('login.store') }}" class="ir-fields">
        @csrf
        <label>Email address <input type="email" name="email" autocomplete="username" required autofocus value="{{ old('email') }}"></label>
        <label>Password <input type="password" name="password" autocomplete="current-password" required></label>
        <button class="ir-btn ir-btn-orange" type="submit">Sign In</button>
    </form>
    <p class="ir-login-foot">SULIT · Ministry of Health Malaysia</p>
</main></body></html>
