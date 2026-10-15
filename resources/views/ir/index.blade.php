@extends('layouts.eirv3')
@section('title', 'List of IR')
@section('content')
<section class="ir-intro"><div><p class="ir-eyebrow">CONFIDENTIAL · INCIDENT MANAGEMENT</p>
    <h1>List of Incident Reports</h1>
    <p>{{ \App\Support\Eirv3Access::canReview(auth()->user()) ? 'All reports submitted for QATU review.' : 'Your submitted incident reports only.' }}</p>
</div>
@if (\App\Support\Eirv3Access::canCreate(auth()->user()))
<a href="{{ route('ir.create') }}" class="ir-btn ir-btn-orange">+ New Incident Report</a>
@endif
</section>
@if(session('success'))<div class="ir-alert ir-alert-success">{{ session('success') }}</div>@endif
<section class="ir-panel"><div class="ir-table-wrap"><table class="ir-table">
    <thead><tr><th>IR Reference</th><th>Incident Date</th><th>Incident Type</th><th>Facility</th><th>Status</th><th>Action</th></tr></thead>
    <tbody>@forelse($reports as $report)
    <tr><td>#{{ str_pad((string) $report->id, 6, '0', STR_PAD_LEFT) }}</td>
        <td>{{ ($report->incident_date ?? $report->approximate_incident_date)?->format('d M Y') ?? '—' }}</td>
        <td>{{ $report->incident_type === 'near_miss' ? 'Near Miss' : 'Actual' }}</td>
        <td>{{ $report->facility_name }}</td>
        <td><span class="ir-status ir-status-{{ $report->status }}">{{ ucfirst($report->status) }}</span></td>
        <td><a href="{{ route('ir.show', $report) }}">View</a></td>
    </tr>
    @empty <tr><td colspan="6">No incident reports found.</td></tr> @endforelse</tbody>
</table></div><div class="ir-pagination">{{ $reports->links() }}</div></section>
@endsection
