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
                <span class="logo-full">eIRv3</span>
                <span class="logo-short" style="display: none; font-size: 28px; color: var(--brand-primary); font-weight: 800;">eIR</span>
                <span class="logo-sub" style="display: block; font-size: 11px; font-weight: 600; color: var(--muted); letter-spacing: 0.1em; text-transform: uppercase; margin-top: 6px;">Incident Report</span>
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
            <button id="sidebar-toggle" onclick="toggleSidebar()" style="background: none; border: none; font-size: 1.4rem; color: var(--brand-dark); cursor: pointer; padding: 0;">
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
        
        <footer class="app-footer" style="text-align: center; color: var(--muted); font-size: 12px; padding: 24px 0; line-height: 1.6;">
            <strong style="color: var(--brand-dark);">eIRv3</strong> &middot; Hospital Selayang &middot; <strong>Team IR Wizard</strong><br>
            <span style="font-size: 11px; opacity: 0.85;">
                Lead: Dr. Ariffah Binti Mokhtar (Unit PRSK) &nbsp;|&nbsp; 
                Analyst: Mr. Nur Nu’man Bin Sidik (ITD) &nbsp;|&nbsp; 
                Developer: Mr. Muhammad Haziq Zikri Bin Zainodin (ITD)
            </span>
        </footer>
    </div>
</div>

<!-- Scroll to Top Button -->
<button id="scrollTopBtn" title="Go to top" style="
    display: none; 
    position: fixed; 
    bottom: 90px; /* Sits just above your locked footer */
    right: 30px; 
    z-index: 999; 
    border: none; 
    outline: none; 
    background-color: var(--brand-primary, #0f766e); 
    color: white; 
    cursor: pointer; 
    padding: 12px 16px; 
    border-radius: 50%; 
    font-size: 16px; 
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    transition: background-color 0.3s;
">
    &#8679;
</button>

<script>
    // Handles the hamburger button click
    function toggleSidebar() {
        const sidebar = document.querySelector('.sidebar');
        sidebar.classList.toggle('collapsed');
        localStorage.setItem('sidebar-collapsed', sidebar.classList.contains('collapsed'));
    }

    document.addEventListener("DOMContentLoaded", function() {
        const sidebar = document.querySelector('.sidebar');
        const navLinks = document.querySelectorAll('.sidebar .nav-link');
        
        // 1. Restore the collapsed state when the page loads
        if (localStorage.getItem('sidebar-collapsed') === 'true') {
            sidebar.classList.add('collapsed');
        }

        // 2. Expand sidebar when a collapsed icon is clicked (and prevent accidental navigation)
        navLinks.forEach(link => {
            link.addEventListener('click', function(e) {
                if (sidebar.classList.contains('collapsed')) {
                    e.preventDefault(); // Stop the link from changing the page
                    sidebar.classList.remove('collapsed'); // Expand the sidebar instead
                    localStorage.setItem('sidebar-collapsed', 'false'); // Save the new open state
                }
            });
        });
    });

    // Get the button
    let mybutton = document.getElementById("scrollTopBtn");

    // When the user scrolls down 100px from the top of the document, show the button
    window.onscroll = function() { scrollFunction() };

    function scrollFunction() {
        if (document.body.scrollTop > 100 || document.documentElement.scrollTop > 100) {
            mybutton.style.display = "block";
        } else {
            mybutton.style.display = "none";
        }
    }

    // When the user clicks on the button, scroll to the top of the document smoothly
    mybutton.onclick = function() {
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
</script
</script>
</body>
</html>