@extends('layouts.eirv3')
@section('title', 'List of IR')
@section('content')
@php
    // Determine if the logged-in user is a reviewer so we can show "Review" instead of "View"
    $isReviewer = in_array(auth()->user()->role->slug ?? '', ['superadmin', 'qa', 'hod', 'lo']);
@endphp

<div class="dash-workspace-header">
    <div class="dash-workspace-title">
        <h1>Incident Register</h1>
        <p>Comprehensive list of all reported incidents, near misses, and review statuses.</p>
    </div>
    <div style="display: flex; gap: 12px;">
        <a href="{{ route('ir.create') }}" class="ir-btn ir-btn-orange" style="display: flex; align-items: center; gap: 8px;">
            <span aria-hidden="true">＋</span> New Incident
        </a>
    </div>
</div>

@if(session('success'))
    <div class="ir-alert ir-alert-success" style="margin-bottom: 24px;">{{ session('success') }}</div>
@endif

<div class="dash-table-section">
    <!-- Optional: If you have an existing filter form, you can place it right here -->

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
                @forelse ($reports as $report)
                    <tr>
                        <td>
                            <strong style="color: var(--navy);">#{{ str_pad((string) $report->id, 6, '0', STR_PAD_LEFT) }}</strong>
                            <div style="font-size: 12px; color: var(--muted); margin-top: 4px; max-width: 250px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                {{ $report->location_ward_clinic ?? $report->facility_name ?? 'Facility Area' }}
                            </div>
                        </td>
                        <td>
                            <span style="font-weight: 700;">{{ $report->incident_type === 'near_miss' ? 'Near Miss' : 'Actual Incident' }}</span>
                            <div style="font-size: 12px; color: var(--muted); margin-top: 4px;">{{ $report->patient_outcome ? ucfirst($report->patient_outcome) : 'No Outcome' }}</div>
                        </td>
                        <td>
                            <span style="font-weight: 700;">{{ $report->reporter->name ?? $report->reporter_name ?? 'Unknown' }}</span>
                            <div style="font-size: 12px; color: var(--muted); margin-top: 4px;">{{ $report->reported_at?->format('Y-m-d') ?? '—' }}</div>
                        </td>
                        <td>
                            <span style="font-weight: 700;">{{ ($report->incident_date ?? $report->approximate_incident_date)?->format('Y-m-d') ?? '—' }}</span>
                            <div style="font-size: 12px; color: var(--muted); margin-top: 4px;">{{ $report->incident_time ?? $report->approximate_incident_time ?? '—' }}</div>
                        </td>
                        <td>
                            <!-- Dynamic Badge Coloring -->
                            @php
                                $badgeClass = 'dash-badge-gray';
                                if (strtolower($report->status) === 'submitted' || strtolower($report->status) === 'feedback_required') $badgeClass = 'dash-badge-submitted';
                                if (strtolower($report->status) === 'approved') $badgeClass = 'dash-badge-approved';
                                if (strtolower($report->status) === 'returned' || strtolower($report->status) === 'rca_required') $badgeClass = 'dash-badge-returned';
                            @endphp
                            <span class="dash-badge {{ $badgeClass }}">{{ ucwords(str_replace('_', ' ', $report->status)) }}</span>
                        </td>
                        <td style="text-align: right;">
                            <div style="display: flex; gap: 8px; justify-content: flex-end;">
                                
                                <!-- View / Review Button -->
                                <a href="{{ route('ir.show', $report) }}" class="dash-action-btn-outline">
                                    <span aria-hidden="true">👁</span>
                                    {{ $isReviewer && strtolower($report->status) === 'submitted' && $report->reporter_id != auth()->id() ? 'Review' : 'View' }}
                                </a>

                                <!-- Edit Button (For Original Reporter) -->
                                @if(auth()->id() == $report->reporter_id && in_array(strtolower($report->status), ['submitted', 'returned']))
                                    <a href="{{ route('ir.edit', $report) }}" class="dash-action-btn-outline" style="color: #ea580c; border-color: #ea580c;">
                                        <span aria-hidden="true">✎</span> Edit
                                    </a>
                                @endif

                                <!-- Send Feedback Button (For Reporter or LO) -->
                                @if(strtolower($report->status) === 'feedback_required' && (auth()->id() == $report->reporter_id || (auth()->user()->role->slug ?? '') === 'lo'))
                                    <a href="{{ route('ir.show', $report) }}" class="dash-action-btn-outline" style="color: #eab308; border-color: #eab308;">
                                        <span aria-hidden="true">💬</span> Feedback
                                    </a>
                                @endif

                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 40px; color: var(--muted);">
                            No incident reports found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        <!-- Add this directly below your table -->
        <div style="margin-top: 20px; padding: 10px;">
            {{ $reports->links() }}
        </div>
    </div>

    <!-- Pagination -->
    @if($reports->hasPages())
        <div style="margin-top: 24px; padding-top: 16px; border-top: 1px solid var(--border);">
            {{ $reports->links() }}
        </div>
    @endif
</div>
@endsection