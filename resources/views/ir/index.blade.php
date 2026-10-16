@extends('layouts.eirv3')
@section('title', 'List of IR')
@section('content')
<section class="ir-intro">
  <div>
    <p class="ir-eyebrow">CONFIDENTIAL · INCIDENT MANAGEMENT</p>
    <h1>List of Incident Reports</h1>
    <p>{{ $canReview ? 'Submitted incident reports for QATU and authorized administration.' : 'Your submitted incident reports only.' }}</p>
  </div>
  <a class="ir-btn ir-btn-orange" href="{{ route('ir.create') }}">+ New Incident Report</a>
</section>

@if(session('success'))
  <div class="ir-alert ir-alert-success" role="status">{{ session('success') }}</div>
@endif
@if($errors->any())
  <div class="ir-alert ir-alert-error" role="alert">
    <strong>Check your search filters.</strong>
    <ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
  </div>
@endif

<section class="ir-panel">
  <form action="{{ route('ir.index') }}" method="GET" class="ir-list-filters" aria-label="Incident report filters">
    <label>IR Reference #
      <input type="number" name="reference" min="1" placeholder="e.g. 12" value="{{ $filters['reference'] ?? '' }}">
    </label>
    <label>Status
      <select name="status">
        <option value="">All statuses</option>
        @foreach(['submitted'=>'Submitted','approved'=>'Approved','returned'=>'Returned'] as $value=>$label)
          <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
        @endforeach
      </select>
    </label>
    <label>Incident Type
      <select name="incident_type">
        <option value="">All types</option>
        <option value="actual" @selected(($filters['incident_type'] ?? '') === 'actual')>Actual</option>
        <option value="near_miss" @selected(($filters['incident_type'] ?? '') === 'near_miss')>Near Miss</option>
      </select>
    </label>
    <label>Submitted From
      <input type="date" name="from" value="{{ $filters['from'] ?? '' }}">
    </label>
    <label>Submitted To
      <input type="date" name="to" value="{{ $filters['to'] ?? '' }}">
    </label>
    <div class="ir-filter-actions">
      <button type="submit" class="ir-btn ir-btn-orange">Search</button>
      <a href="{{ route('ir.index') }}" class="ir-btn ir-btn-light">Clear</a>
    </div>
  </form>
</section>

<section class="ir-panel">
  <div class="ir-list-summary">{{ $reports->total() }} incident report(s) found</div>
  <div class="ir-table-wrap">
    <table class="ir-table">
      <thead>
      <tr>
        <th scope="col">Reference</th>
        <th scope="col">Incident Date</th>
        <th scope="col">Submitted</th>
        <th scope="col">Type</th>
        @if($canReview)<th scope="col">Reporter</th>@endif
        <th scope="col">Facility</th>
        <th scope="col">Status</th>
        <th scope="col">Action</th>
      </tr>
      </thead>
      <tbody>
      @forelse($reports as $report)
        <tr>
          <td><strong>#{{ str_pad((string) $report->id, 6, '0', STR_PAD_LEFT) }}</strong></td>
          <td>{{ ($report->incident_date ?? $report->approximate_incident_date)?->format('d M Y') ?? '—' }}</td>
          <td>{{ $report->reported_at?->format('d M Y') ?? '—' }}</td>
          <td>{{ $report->incident_type === 'near_miss' ? 'Near Miss' : 'Actual' }}</td>
          @if($canReview)<td>{{ $report->reporter?->name ?? '—' }}</td>@endif
          <td>{{ $report->facility_name }}</td>
          <td><span class="ir-status ir-status-{{ $report->status }}">{{ ucfirst($report->status) }}</span></td>
          <td>
            <a href="{{ route('ir.show', $report) }}">
              {{ $canReview && $report->status === 'submitted' && $report->reporter_id !== auth()->id() ? 'Review' : 'View' }}
            </a>
            
            @if(auth()->id() === $report->reporter_id && in_array($report->status, ['submitted', 'returned']))
              <span style="margin: 0 8px; color: #ccc;">|</span>
              <a href="{{ route('ir.edit', $report) }}">Edit</a>
            @endif
          </td>
        </tr>
      @empty
        <tr><td colspan="{{ $canReview ? 8 : 7 }}">No incident reports match these filters.</td></tr>
      @endforelse
      </tbody>
    </table>
  </div>
  <nav class="ir-list-pages" aria-label="Incident report pages">
    <span>Page {{ $reports->currentPage() }} of {{ $reports->lastPage() }}</span>
    @unless($reports->onFirstPage())<a href="{{ $reports->previousPageUrl() }}">Previous</a>@endunless
    @if($reports->hasMorePages())<a href="{{ $reports->nextPageUrl() }}">Next</a>@endif
  </nav>
</section>
@endsection
