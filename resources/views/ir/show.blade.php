@extends('layouts.eirv3')
@section('title', 'Incident Report')
@section('content')
<section class="ir-intro"><div>
    <p class="ir-eyebrow">SULIT · MOH IR 2.0/2017</p>
    <h1>Incident Report #{{ str_pad((string) $incident->id, 6, '0', STR_PAD_LEFT) }}</h1>
    <p>Reported {{ $incident->reported_at->format('d M Y') }} · <span class="ir-status ir-status-{{ $incident->status }}">{{ ucfirst($incident->status) }}</span></p>
</div><div class="ir-actions"><a class="ir-btn ir-btn-light" href="{{ route('ir.index') }}">Back to IR List</a>
@if($incident->status === 'returned' && $incident->reporter_id === auth()->id())
    <a class="ir-btn ir-btn-orange" href="{{ route('ir.edit',$incident) }}">Correct & Resubmit</a>
@endif</div></section>
@if(session('success'))<div class="ir-alert ir-alert-success">{{ session('success') }}</div>@endif
<section class="ir-panel"><div class="ir-panel-heading"><span>A</span><div><h2>Section A — Reporter</h2><p>Incident description, outcome and reporter details</p></div></div>
    <dl class="ir-details">
        <dt>Facility / institution</dt><dd>{{ $incident->facility_name }}</dd>
        <dt>Patient's name</dt><dd>{{ $incident->patient_name }}</dd>
        <dt>Date of incident</dt><dd>{{ ($incident->incident_date ?? $incident->approximate_incident_date)?->format('d M Y') ?? '—' }} {{ $incident->approximate_incident_date ? '(approximate)' : '' }}</dd>
        <dt>Time of incident</dt><dd>{{ $incident->incident_time ?? $incident->approximate_incident_time ?? '—' }} {{ $incident->approximate_incident_time ? '(approximate)' : '' }}</dd>
        <dt>Patient RN / ID</dt><dd>{{ $incident->patient_identifier ?: '—' }}</dd>
        <dt>Age / Ethnic</dt><dd>{{ $incident->patient_age ?? '—' }} / {{ $incident->ethnic ?: '—' }}</dd>
        <dt>Gender / Status</dt><dd>{{ ucfirst($incident->gender) }} / {{ ucfirst($incident->patient_status) }}</dd>
        <dt>Language barrier</dt><dd>{{ ucfirst($incident->language_barrier) }}</dd>
        <dt>Diagnosis</dt><dd class="ir-prewrap">{{ $incident->diagnosis ?: '—' }}</dd>
        <dt>Patient type</dt><dd>{{ str_replace('_',' ',ucwords($incident->patient_type,'_')) }} {{ $incident->patient_type_other }}</dd>
        <dt>Departments</dt><dd>{{ implode(', ', $incident->departments ?? []) }} {{ $incident->departments_other }}</dd>
        <dt>Location / ward / clinic</dt><dd>{{ $incident->location_ward_clinic ?: '—' }}</dd>
        <dt>Incident type</dt><dd>{{ $incident->incident_type === 'near_miss' ? 'Near Miss' : 'Actual' }}</dd>
        <dt>Other type of incident</dt><dd>{{ $incident->other_incident_type ?: '—' }}</dd>
        <dt>Brief description</dt><dd class="ir-prewrap">{{ $incident->description }}</dd>
        @if ($incident->incident_type === 'actual')
        <dt>Outcome of incident</dt><dd>{{ ucfirst($incident->patient_outcome ?? '—') }}</dd>
        <dt>Immediate action</dt><dd class="ir-prewrap">{{ $incident->immediate_action }}</dd>
        @endif
        <dt>Reporter</dt><dd>{{ $incident->reporter_name }}</dd>
        <dt>Designation</dt><dd>{{ ucwords(str_replace('_',' ',$incident->reporter_designation)) }} {{ $incident->reporter_designation_other }}</dd>
        <dt>Submitted</dt><dd>{{ $incident->reported_at->format('d M Y') }} · acknowledged via user account</dd>
    </dl>
</section>
<section class="ir-panel"><div class="ir-panel-heading ir-heading-peach"><span>B</span><div><h2>Section B — QATU Review</h2><p>Risk Manager / Quality Manager</p></div></div>
@if($incident->status !== 'submitted')
    <dl class="ir-details">
        <dt>Decision</dt><dd><span class="ir-status ir-status-{{ $incident->status }}">{{ ucfirst($incident->status) }}</span></dd>
        <dt>Action taken</dt><dd>{{ $incident->actions_taken ? implode(', ', array_map(fn ($item) => ucwords(str_replace('_', ' ', $item)), $incident->actions_taken)) : '—' }}</dd>
        <dt>Comments</dt><dd class="ir-prewrap">{{ $incident->review_comments ?: '—' }}</dd>
        <dt>e-IR submitted?</dt><dd>{{ is_null($incident->eir_submitted) ? 'Not recorded' : ($incident->eir_submitted ? 'Yes' : 'No') }}</dd>
        <dt>e-IR submission date</dt><dd>{{ $incident->eir_submission_date?->format('d M Y') ?? '—' }}</dd>
        <dt>Reviewer</dt><dd>{{ $incident->reviewer_name ?? '—' }} · {{ $incident->reviewer_designation }}</dd>
        <dt>Review date</dt><dd>{{ $incident->reviewed_at?->format('d M Y H:i') ?? '—' }}</dd>
    </dl>
@elseif(\App\Support\Eirv3Access::canReview(auth()->user()) && $incident->reporter_id !== auth()->id())
    <form method="POST" action="{{ route('ir.review', $incident) }}" class="ir-review-form">
        @csrf
        @if($errors->any())<div class="ir-alert ir-alert-error"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
        <div class="ir-fields">
            <fieldset><legend>1. Action taken (tick applicable actions)</legend><div class="ir-choices">
                    @foreach (['prescription_slip'=>'Prescription Slip','monitor_trend'=>'Monitor the Trend First','rca'=>'RCA','mirca'=>'MIRCA (Multi-incident Root Cause Analysis)'] as $k=>$v)
                        <label class="ir-option"><input type="checkbox" name="actions_taken[]" value="{{ $k }}" @checked(in_array($k,old('actions_taken',[]),true))> {{ $v }}</label>
                    @endforeach
                </div></fieldset>
            @if(in_array($incident->patient_outcome,['severe','death'],true))
                <div class="ir-alert ir-alert-error">Mandatory RCA applies for severe or death outcome. Confirm the required clinical governance action before approval.</div>
            @endif
            <label>Additional comments / reason for return
                <textarea name="review_comments" rows="4" maxlength="10000">{{ old('review_comments') }}</textarea>
            </label>
            <label>2. e-IR submitted?
                <select name="eir_submitted"><option value="">Not recorded</option>
                    <option value="yes" @selected(old('eir_submitted')==='yes')>Yes</option>
                    <option value="no" @selected(old('eir_submitted')==='no')>No</option>
                </select>
            </label>
            <label>Date of e-IR submission (if yes)
                <input type="date" name="eir_submission_date" value="{{ old('eir_submission_date') }}">
            </label>
            <div class="ir-alert ir-alert-note">The source form states that e-IR should be submitted within five days of the incident. Confirm the appropriate reporting procedure.</div>
            <label>3. Reviewer designation
                <select name="reviewer_designation" required><option value="">Select</option>
                    <option value="Risk Manager" @selected(old('reviewer_designation')==='Risk Manager')>Risk Manager</option>
                    <option value="Quality Manager" @selected(old('reviewer_designation')==='Quality Manager')>Quality Manager</option>
                </select>
            </label>
            <p>Reviewer name: <strong>{{ auth()->user()->name }}</strong> · Date: <strong>{{ now()->format('d M Y') }}</strong></p>
            <label class="ir-consent"><input type="checkbox" name="reviewer_attested" value="1" required> I confirm that I am recording this review under my own authenticated user account.</label>
            <div class="ir-actions">
                <button name="decision" value="returned" class="ir-btn ir-btn-light" type="submit">Return for Correction</button>
                <button name="decision" value="approved" class="ir-btn ir-btn-orange" type="submit">Approve IR</button>
            </div>
        </div>
    </form>
@else
    <p class="ir-pending">Awaiting QATU assessment.</p>
@endif
</section>
@endsection
