<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title>@yield('title', 'Dashboard') | eIRv3</title>
    <link rel="stylesheet" href="{{ asset('css/eirv3.css') }}?v=3">
    <link rel="stylesheet" href="{{ asset('css/eirv3-admin.css') }}">
    <link rel="stylesheet" href="{{ asset('css/eirv3-dashboard.css') }}?v=2">
</head>
<body>
<div class="app-shell">
    <aside class="sidebar" id="app-sidebar" aria-label="Main navigation">
        <a href="{{ url('/') }}" class="brand">
            <span class="brand-main">e-<strong>IR</strong><span class="brand-version">v3</span></span>
            <span class="brand-subtitle">HOSPITAL SELAYANG</span>
        </a>
        <div class="sidebar-rule"></div>
        <p class="nav-heading">WORKSPACE</p>
        <nav class="nav-links" aria-label="Modules">
            <a class="nav-link {{ request()->is('/') ? 'active' : '' }}" href="{{ url('/') }}">
                <span class="nav-symbol" aria-hidden="true">▦</span> <span class="nav-text">Dashboard</span>
            </a>
            <a class="nav-link {{ request()->routeIs('ir.create') ? 'active' : '' }}" href="{{ route('ir.create') }}">
                <span class="nav-symbol" aria-hidden="true">＋</span> <span class="nav-text">IR Form</span>
            </a>

            @auth
                <!-- All authenticated users can submit and view their allowed IRs -->
                <a class="nav-link {{ request()->routeIs('ir.index','ir.show','ir.edit') ? 'active' : '' }}" href="{{ route('ir.index') }}">
                    <span class="nav-symbol" aria-hidden="true">☷</span> <span class="nav-text">List of IR</span>
                </a>

                <!-- KIV Reports (QA & Superadmin) -->
                @if(in_array(auth()->user()->role->slug ?? '', ['qa', 'superadmin'], true))
                    <div class="nav-link is-coming">
                        <span class="nav-symbol">▥</span> <span class="nav-text">Reports</span> <span class="coming-label">KIV</span>
                    </div>
                @endif

                <!-- KIV List of Reports (QA, Superadmin, Director) -->
                @if(in_array(auth()->user()->role->slug ?? '', ['qa', 'superadmin', 'director'], true))
                    <div class="nav-link is-coming">
                        <span class="nav-symbol">≡</span> <span class="nav-text">List of Reports</span> <span class="coming-label">KIV</span>
                    </div>
                @endif

                <!-- Administration (Superadmin only) -->
                @if((auth()->user()->role->slug ?? '') === 'superadmin')
                    <a class="nav-link {{ request()->routeIs('admin.*') ? 'active' : '' }}" href="{{ route('admin.users.index') }}">
                        <span class="nav-symbol" aria-hidden="true">⚙</span> <span class="nav-text">Administration</span>
                    </a>
                @endif
            @endauth
        </nav>
        <div class="sidebar-bottom">
            <span class="signal-dot" aria-hidden="true"></span>
            <span>QA / PRSK MODULE</span>
        </div>
    </aside>
    
    <div class="workspace">
        <header class="topbar">
            <div style="display: flex; align-items: center; gap: 16px;">
                <button id="sidebar-toggle" style="background: none; border: none; font-size: 1.4rem; color: var(--navy); cursor: pointer; padding: 0;">☰</button>
                <div class="topbar-location">Hospital Selayang <span class="topbar-separator">/</span> eIRv3</div>
            </div>
            
            @auth
                <div class="ir-topbar-user">
                    <span>{{ auth()->user()->name }} | {{ auth()->user()->jabatan ?? 'No Jabatan' }}</span>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf 
                        <button type="submit" class="ir-btn ir-btn-light">Log out</button>
                    </form>
                </div>
            @else
                <a href="{{ route('login') }}" class="ir-btn ir-btn-orange">Sign in</a>
            @endauth
        </header>
        
        <main class="main-content" id="main-content">
            @yield('content')
        </main>
        
        <footer class="app-footer">eIRv3 · Hospital Selayang · QA/PRSK · SULIT</footer>
    </div>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        const sidebar = document.getElementById('app-sidebar');
        const toggleBtn = document.getElementById('sidebar-toggle');
        
        // Restore sidebar state from local storage on page load
        if (localStorage.getItem('sidebar-collapsed') === 'true') {
            sidebar.classList.add('collapsed');
        }
        
        // Toggle the sidebar when the button is clicked
        toggleBtn.addEventListener('click', function() {
            sidebar.classList.toggle('collapsed');
            localStorage.setItem('sidebar-collapsed', sidebar.classList.contains('collapsed'));
        });
    });
</script>
</body>
</html>