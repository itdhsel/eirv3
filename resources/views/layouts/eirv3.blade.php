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
        <div class="ir-sidebar-brand">
            <a href="{{ route('dashboard') }}" class="ir-logo-text" style="text-decoration: none;">
                eIRv3
                <span style="display: block; font-size: 11px; font-weight: 600; color: var(--muted); letter-spacing: 0.1em; text-transform: uppercase; margin-top: 6px;">Incident Report</span>
            </a>
        </div>
        <div class="sidebar-rule"></div>
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
    </aside>
    
    <div class="workspace">
        <header class="topbar">
            <div style="display: flex; align-items: center; gap: 16px;">
            <button id="sidebar-toggle" style="background: none; border: none; font-size: 1.4rem; color: var(--brand-dark); cursor: pointer; padding: 0;">
    ☰
</button>
                <div class="topbar-location">Hospital Selayang <span class="topbar-separator">/</span> eIRv3</div>
            </div>
            
            @auth
            <div style="display: flex; align-items: center; gap: 16px; font-size: 12px; font-weight: 700; color: var(--brand-dark);">
                <span>{{ auth()->user()->name }} | {{ auth()->user()->jabatan ?? 'No Department' }}</span>
                
                <form method="POST" action="{{ route('logout') }}" style="margin: 0;">
                    @csrf
                    <button type="submit" class="ir-btn ir-btn-light" style="padding: 6px 16px; min-height: 32px; font-size: 12px;">Log out</button>
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
        // Use querySelector to target the class instead of an ID
        const sidebar = document.querySelector('.sidebar');
        const toggleBtn = document.getElementById('sidebar-toggle');
        
        // Only run if both elements exist on the page
        if (sidebar && toggleBtn) {
            // Restore sidebar state from local storage on page load
            if (localStorage.getItem('sidebar-collapsed') === 'true') {
                sidebar.classList.add('collapsed');
            }
            
            // Toggle the sidebar when the button is clicked
            toggleBtn.addEventListener('click', function() {
                sidebar.classList.toggle('collapsed');
                localStorage.setItem('sidebar-collapsed', sidebar.classList.contains('collapsed'));
            });
        }
    });
</script>
</body>
</html>