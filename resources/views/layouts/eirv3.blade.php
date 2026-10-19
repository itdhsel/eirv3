<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title>@yield('title', 'Dashboard') | eIRv3</title>
    <link rel="stylesheet" href="{{ asset('css/eirv3.css') }}">
    <link rel="stylesheet" href="{{ asset('css/eirv3-admin.css') }}">
    <link rel="stylesheet" href="{{ asset('css/eirv3-dashboard.css') }}">
</head>
<body>
<div class="app-shell">
    <aside class="sidebar" aria-label="Main navigation">
        <a href="{{ url('/') }}" class="brand">
            <span class="brand-main">e-<strong>IR</strong><span class="brand-version">v3</span></span>
            <span class="brand-subtitle">HOSPITAL SELAYANG</span>
        </a>
        <div class="sidebar-rule"></div>
        <p class="nav-heading">WORKSPACE</p>
        <nav class="nav-links" aria-label="Modules">
            <a class="nav-link {{ request()->is('/') ? 'active' : '' }}" href="{{ url('/') }}">
                <span class="nav-symbol" aria-hidden="true">▦</span> Dashboard
            </a>
            <a class="nav-link {{ request()->routeIs('ir.create') ? 'active' : '' }}" href="{{ route('ir.create') }}">
                    <span class="nav-symbol" aria-hidden="true">＋</span> IR Form</a>


            @auth
                <!-- All authenticated users can submit and view their allowed IRs -->
                <a class="nav-link {{ request()->routeIs('ir.index','ir.show','ir.edit') ? 'active' : '' }}" href="{{ route('ir.index') }}">
                    <span class="nav-symbol" aria-hidden="true">☷</span> List of IR</a>

                <!-- KIV Reports (QA & Superadmin) -->
                @if(in_array(auth()->user()->role->slug ?? '', ['qa', 'superadmin'], true))
                    <div class="nav-link is-coming"><span class="nav-symbol">▥</span> Reports <span class="coming-label">KIV</span></div>
                @endif

                <!-- KIV List of Reports (QA, Superadmin, Director) -->
                @if(in_array(auth()->user()->role->slug ?? '', ['qa', 'superadmin', 'director'], true))
                    <div class="nav-link is-coming"><span class="nav-symbol">≡</span> List of Reports <span class="coming-label">KIV</span></div>
                @endif

                <!-- Administration (Superadmin only) -->
                @if((auth()->user()->role->slug ?? '') === 'superadmin')
                    <a class="nav-link {{ request()->routeIs('admin.*') ? 'active' : '' }}"
                        href="{{ route('admin.users.index') }}">
                            <span class="nav-symbol" aria-hidden="true">⚙</span>
                            Administration
                    </a>
                @endif
            @endauth
        </nav>        <div class="sidebar-bottom"><span class="signal-dot" aria-hidden="true"></span><span>QA / PRSK MODULE</span></div>
    </aside>
    <div class="workspace">
        <header class="topbar">
            <div class="topbar-location">Hospital Selayang <span class="topbar-separator">/</span> eIRv3</div>
            @auth
            <div class="ir-topbar-user"><span>{{ auth()->user()->name }} | {{ auth()->user()->jabatan ?? 'No Jabatan' }}</span>
            <form method="POST" action="{{ route('logout') }}">@csrf <button type="submit" class="ir-btn ir-btn-light">Log out</button></form>
                </div>
            @else
                <a href="{{ route('login') }}" class="ir-btn ir-btn-orange">Sign in</a>
            @endauth
        </header>
        <main class="main-content" id="main-content">@yield('content')</main>
        <footer class="app-footer">eIRv3 · Hospital Selayang · QA/PRSK · SULIT</footer>
    </div>
</div>
</body>
</html>
