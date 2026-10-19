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

<div class="dash-workspace-header">
    <div class="dash-workspace-title">
        <h1>Incidents Hub</h1>
        <p>Manage incident reporting, investigation readiness, and executive visibility from one operational module. You are logged in as <strong>{{ $roleNames[$role] ?? ucfirst($role ?? 'Unknown') }}</strong>.</p>
    </div>
    <div style="display: flex; gap: 12px;">
        @if ($role === 'superadmin')
            <a href="{{ route('admin.users.index') }}" class="ir-btn ir-btn-light" style="display: flex; align-items: center; gap: 8px;">
                <span aria-hidden="true">⚙</span> User Management
            </a>
        @endif
        <a href="{{ route('ir.create') }}" class="ir-btn ir-btn-orange" style="display: flex; align-items: center; gap: 8px;">
            <span aria-hidden="true">＋</span> New Incident
        </a>
    </div>
</div>

@if ($role === 'director')
    <div class="dash-table-section" style="text-align: center; padding: 60px 20px;">
        <h2 style="font-size: 20px; color: var(--navy); margin-bottom: 8px;">Reporting module is coming next</h2>
        <p style="color: var(--muted); max-width: 500px; margin: 0 auto;">The weekly/monthly report module and Director's review list are currently KIV. Individual incident reports and incident statistics are not available to the Director role.</p>
    </div>
@else
    <!-- Metric Cards Row -->
    <div class="dash-metric-grid">
        <div class="dash-metric-card blue-top">
            <span class="dash-metric-title">{{ $role === 'user' ? 'My Total IR' : 'Total IR' }}</span>
            <span class="dash-metric-value">{{ number_format($stats['total']) }}</span>
            <span class="dash-metric-desc">Total records visible to your role across the system.</span>
        </div>
        
        <div class="dash-metric-card orange-top">
            <span class="dash-metric-title">{{ $isReviewer ? 'Pending Your Review' : 'Awaiting QATU' }}</span>
            <span class="dash-metric-value">{{ number_format($stats['pending']) }}</span>
            <span class="dash-metric-desc">{{ $isReviewer ? 'Excludes your own submissions.' : 'Your submitted reports awaiting review.' }}</span>
        </div>
        
        <div class="dash-metric-card green-top">
            <span class="dash-metric-title">Approved IR</span>
            <span class="dash-metric-value">{{ number_format($stats['approved']) }}</span>
            <span class="dash-metric-desc">Records successfully moved through the closure workflow.</span>
        </div>
        
        <div class="dash-metric-card gray-top">
            <span class="dash-metric-title">Returned IR</span>
            <span class="dash-metric-value">{{ number_format($stats['returned']) }}</span>
            <span class="dash-metric-desc">Records requiring correction or active follow-up.</span>
        </div>
    </div>

    <!-- Data Table Row -->
    <div class="dash-table-section">
        <div class="dash-table-header">
            <div>
                <h2>Incident Register</h2>
                <p>Operational view of incident records, type, reporting details, and status.</p>
            </div>
            <a href="{{ route('ir.index') }}" style="font-size: 13px; color: var(--navy); font-weight: 700; text-decoration: none;">View All Incidents →</a>
        </div>
        
        @if ($recentReports->isEmpty())
            <div style="text-align: center; padding: 40px 20px; color: var(--muted);">
                <p>No incident reports available to display.</p>
            </div>
        @else
            <div style="overflow-x: auto;">
                <table class="dash-ui-table">
                    <thead>
                        <tr>
                            <th>Incident</th>
                            <th>Type</th>
                            <th>Reported By</th>
                            <th>Occurred</th>
                            <th>Status</th>
                            <th style="text-align: right;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($recentReports as $report)
                            <tr>
                                <td>
                                    <strong style="color: var(--navy);">#{{ str_pad((string) $report->id, 6, '0', STR_PAD_LEFT) }}</strong>
                                    <div style="font-size: 12px; color: var(--muted); margin-top: 4px; max-width: 250px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                        {{ $report->location_ward_clinic ?? 'Facility Area' }}
                                    </div>
                                </td>
                                <td>
                                    <span style="font-weight: 700;">{{ $report->incident_type === 'near_miss' ? 'Near Miss' : 'Actual Incident' }}</span>
                                    <div style="font-size: 12px; color: var(--muted); margin-top: 4px;">{{ $report->patient_outcome ? ucfirst($report->patient_outcome) : 'No Outcome' }}</div>
                                </td>
                                <td>
                                    <span style="font-weight: 700;">{{ $report->reporter_name ?? 'Unknown' }}</span>
                                    <div style="font-size: 12px; color: var(--muted); margin-top: 4px;">{{ $report->reported_at?->format('Y-m-d') ?? '—' }}</div>
                                </td>
                                <td>
                                    <span style="font-weight: 700;">{{ ($report->incident_date ?? $report->approximate_incident_date)?->format('Y-m-d') ?? '—' }}</span>
                                    <div style="font-size: 12px; color: var(--muted); margin-top: 4px;">{{ $report->incident_time ?? $report->approximate_incident_time ?? '—' }}</div>
                                </td>
                                <td>
                                    @php
                                        $badgeClass = 'dash-badge-gray';
                                        if (strtolower($report->status) === 'submitted' || strtolower($report->status) === 'feedback_required') $badgeClass = 'dash-badge-submitted';
                                        if (strtolower($report->status) === 'approved') $badgeClass = 'dash-badge-approved';
                                        if (strtolower($report->status) === 'returned' || strtolower($report->status) === 'rca_required') $badgeClass = 'dash-badge-returned';
                                    @endphp
                                    <span class="dash-badge {{ $badgeClass }}">{{ ucwords(str_replace('_', ' ', $report->status)) }}</span>
                                </td>
                                <td style="text-align: right;">
                                    <a href="{{ route('ir.show', $report) }}" class="dash-action-btn-outline">
                                        <span aria-hidden="true">👁</span>
                                        {{ $isReviewer && $report->status === 'submitted' && $report->reporter_id !== auth()->id() ? 'Review' : 'Open' }}
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
@endif
@endsection