@extends('layouts.eirv3')
@section('title', 'Dashboard')
@section('content')
<section class="welcome-banner" aria-labelledby="welcome-title">
    <div class="welcome-copy"><span class="eyebrow">ELECTRONIC INCIDENT REPORTING SYSTEM</span>
        <h1 id="welcome-title">Welcome to <span>eIRv3</span></h1>
        <p>QA/PRSK Module — Hospital Selayang</p>
        <p class="welcome-description">A secure workspace for patient safety reporting and QATU review.</p>
        @auth
            @if(\App\Support\Eirv3Access::canCreate(auth()->user()))
                <a class="ir-btn ir-btn-orange" href="{{ route('ir.create') }}">+ New Incident Report</a>
            @endif
        @else
            <a class="ir-btn ir-btn-orange" href="{{ route('login') }}">Sign in</a>
        @endauth
    </div><div class="welcome-graphic" aria-hidden="true"><div class="graphic-ring"><span>✚</span></div><div class="graphic-wave">⌁</div></div>
</section>
<section class="section-block">
    <div class="section-intro"><div><span class="section-kicker">MODULES</span><h2>eIRv3 workspace</h2></div></div>
    <div class="module-grid">
        <article class="module-card"><div class="module-icon">＋</div><h3>Incident Report</h3><p>Submit the Ministry of Health IR 2.0/2017 form.</p>
            <span class="module-status">Reporter module</span></article>
        <article class="module-card"><div class="module-icon">☷</div><h3>List of IR</h3><p>View your own IR, or review all IR as QATU.</p>
            <span class="module-status">Role-based access</span></article>
        <article class="module-card"><div class="module-icon">▥</div><h3>Reports</h3><p>Weekly and monthly summaries for management.</p>
            <span class="module-status">KIV</span></article>
        <article class="module-card"><div class="module-icon">⚙</div><h3>Administration</h3><p>User registration and role assignment.</p>
            <span class="module-status">Next phase</span></article>
    </div>
</section>
@endsection
