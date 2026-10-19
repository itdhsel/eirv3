@extends('layouts.eirv3')
@section('title', 'Dashboard')
@section('content')
@php
    $roleNames = [
        'superadmin' => 'Super Admin',
        'qa'         => 'Quality Assurance',
        'hod'        => 'Head of Department',
        'lo'         => 'Liaison Officer',
        'user'       => 'Staff / User',
        'director'   => 'Hospital Director'
    ];
@endphp

<section class="dash-hero" aria-labelledby="dash-welcome-title">
    <div class="dash-hero-content">
        <span class="dash-eyebrow">HOSPITAL SELAYANG · QA/PRSK MODULE</span>
        <h1 id="dash-welcome-title">e-IR <span>System</span></h1>
        <p>Welcome, {{ auth()->user()->name }} ({{ auth()->user()->jabatan ?? 'No Jabatan' }}).</p>
        <p class="dash-hero-subtitle">Your {{ $roleNames[$role] ?? ucfirst($role ?? 'Unknown') }} workspace for incident reporting and review.</p>
    </div>
    <div class="dash-hero-mark" aria-hidden="true">✚</div>
</section>

@if ($role === 'director')
    <section class="dash-section" aria-labelledby="director-heading">
        <div class="dash-section-heading">
            <div><span class="dash-section-label">DIRECTOR WORKSPACE</span>
                <h2 id="director-heading">Reports for Director Review</h2>
            </div>
        </div>
        <div class="dash-empty-panel">
            <div class="dash-empty-icon" aria-hidden="true">▥</div>
            <h3>Reporting module is coming next</h3>
            <p>The weekly/monthly report module and Director's review list are currently KIV. Individual incident reports and incident statistics are not available to the Director role.</p>
        </div>
    </section>
@else
    <section class="dash-section" aria-labelledby="stats-heading">
        <div class="dash-section-heading">
            <div><span class="dash-section-label">INCIDENT REPORTING OVERVIEW</span>
                <h2 id="stats-heading">{{ $role === 'user' ? 'My incident reports' : 'Incident report overview' }}</h2>
            </div>
            <span class="dash-role-tag">{{ $roleNames[$role] ?? ucfirst($role ?? '') }}</span>
        </div>

        <div class="dash-stat-grid">
            <article class="dash-stat-card">
                <div class="dash-stat-symbol" aria-hidden="true">▤</div>
                <span class="dash-stat-title">{{ $role === 'user' ? 'My Total IR' : 'Total IR' }}</span>
                <strong class="dash-stat-number">{{ number_format($stats['total']) }}</strong>
                <span class="dash-stat-footer">Visible to your role</span>
            </article>
            <article class="dash-stat-card dash-stat-card-orange">
                <div class="dash-stat-symbol" aria-hidden="true">◷</div>
                <span class="dash-stat-title">{{ $isReviewer ? 'Pending Your Review' : 'Awaiting QATU' }}</span>
                <strong class="dash-stat-number">{{ number_format($stats['pending']) }}</strong>
                <span class="dash-stat-footer">{{ $isReviewer ? 'Excludes your own submissions' : 'Your submitted reports' }}</span>
            </article>
            <article class="dash-stat-card">
                <div class="dash-stat-symbol" aria-hidden="true">✓</div>
                <span class="dash-stat-title">Approved IR</span>
                <strong class="dash-stat-number">{{ number_format($stats['approved']) }}</strong>
                <span class="dash-stat-footer">Approved by QATU</span>
            </article>
            <article class="dash-stat-card">
                <div class="dash-stat-symbol" aria-hidden="true">↶</div>
                <span class="dash-stat-title">Returned IR</span>
                <strong class="dash-stat-number">{{ number_format($stats['returned']) }}</strong>
                <span class="dash-stat-footer">Requires reporter correction</span>
            </article>
        </div>
    </section>

    <section class="dash-section" aria-labelledby="quicklinks-heading">
        <div class="dash-section-heading">
            <div><span class="dash-section-label">QUICK ACTIONS</span><h2 id="quicklinks-heading">Workspace shortcuts</h2></div>
        </div>
        <div class="dash-actions">
            <a href="{{ route('ir.create') }}" class="dash-action dash-action-primary"><span aria-hidden="true">＋</span><span>New Incident Report</span></a>
            <a href="{{ route('ir.index') }}" class="dash-action"><span aria-hidden="true">☷</span><span>List of IR</span></a>
            @if ($role === 'superadmin')
                <a href="{{ route('admin.users.index') }}" class="dash-action"><span aria-hidden="true">⚙</span><span>User Management</span></a>
            @endif
            @if ($role === 'qa')
                <span class="dash-action dash-action-disabled" aria-label="Weekly and monthly reports: coming soon"><span aria-hidden="true">▥</span><span>Reports (KIV)</span></span>
            @endif
        </div>
    </section>

    <section class="dash-section" aria-labelledby="recent-heading">
        <div class="dash-section-heading">
            <div><span class="dash-section-label">RECENT ACTIVITY</span>
                <h2 id="recent-heading">{{ $role === 'user' ? 'My recent submissions' : 'Recent incident reports' }}</h2>
            </div>
            <a class="dash-text-link" href="{{ route('ir.index') }}">View all IR →</a>
        </div>
        <div class="dash-recent-panel">
            @if ($recentReports->isEmpty())
                <div class="dash-empty-panel dash-empty-compact">
                    <h3>No incident reports yet</h3>
                    <p>Submitted incident reports will appear here when available to your role.</p>
                </div>
            @else
                <div class="dash-table-scroll">
                    <table class="dash-table">
                        <thead><tr><th scope="col">IR Reference</th><th scope="col">Submitted</th><th scope="col">Type</th><th scope="col">Status</th><th scope="col">Action</th></tr></thead>
                        <tbody>
                        @foreach ($recentReports as $report)
                            <tr>
                                <td><strong>#{{ str_pad((string) $report->id, 6, '0', STR_PAD_LEFT) }}</strong></td>
                                <td>{{ $report->reported_at?->format('d M Y') ?? '—' }}</td>
                                <td>{{ $report->incident_type === 'near_miss' ? 'Near Miss' : 'Actual' }}</td>
                                <td><span class="dash-status dash-status-{{ $report->status }}">{{ ucfirst($report->status) }}</span></td>
                                <td><a href="{{ route('ir.show', $report) }}">{{ $isReviewer && $report->status === 'submitted' && $report->reporter_id !== auth()->id() ? 'Review' : 'View' }}</a></td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </section>
@endif
@endsection