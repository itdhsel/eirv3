@extends('layouts.eirv3')
@section('title', $incident ? 'Resubmit IR' : 'New Incident Report')
@section('content')
@php
    $edit = $incident !== null;
    $selectedDepartments = old('departments', $incident?->departments ?? []);
@endphp
<section class="ir-intro">
    <div>
        <p class="ir-eyebrow">CONFIDENTIAL · MOH IR 2.0/2017</p>
        <h1>{{ $edit ? 'Resubmit Incident Report' : 'New Incident Report' }}</h1>
        <p>Patient Safety Incident Reporting Form · Section A: Reporter</p>
    </div>
    <a class="ir-btn ir-btn-light" href="{{ route('ir.index') }}">Back to IR List</a>
</section>

@if ($errors->any())
    <div class="ir-alert ir-alert-error" role="alert">
        <strong>Please correct the following:</strong>
        <ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
    </div>
@endif

<form method="POST" action="{{ $edit ? route('ir.update', $incident) : route('ir.store') }}" class="ir-form">
    @csrf
    @if ($edit) @method('PUT') @endif

    <section class="ir-panel">
        <div class="ir-panel-heading"><span>01</span><div><h2>Incident description</h2><p>Items 1–4 of Section A</p></div></div>
        <div class="ir-fields ir-two-col">
            <label>1. Name of facility / institution <span class="ir-required">*</span>
                <input type="text" name="facility_name" maxlength="255" required value="{{ old('facility_name', $incident?->facility_name ?? 'Hospital Selayang') }}">
            </label>
            <label>Patient's name <span class="ir-required">*</span>
                <input type="text" name="patient_name" maxlength="255" required value="{{ old('patient_name', $incident?->patient_name) }}" autocomplete="off">
            </label>
            <label>2. Date of incident (known)
                <input type="date" name="incident_date" value="{{ old('incident_date', $incident?->incident_date?->format('Y-m-d')) }}">
            </label>
            <label>Approximate date (if uncertain)
                <input type="date" name="approximate_incident_date" value="{{ old('approximate_incident_date', $incident?->approximate_incident_date?->format('Y-m-d')) }}">
            </label>
            <label>3. Time of incident (known)
                <input type="time" name="incident_time" value="{{ old('incident_time', $incident?->incident_time ? substr($incident->incident_time, 0, 5) : '') }}">
            </label>
            <label>Approximate time (if uncertain)
                <input type="time" name="approximate_incident_time" value="{{ old('approximate_incident_time', $incident?->approximate_incident_time ? substr($incident->approximate_incident_time, 0, 5) : '') }}">
            </label>
            <label>4. Patient's RN / other identification number
                <input type="text" name="patient_identifier" maxlength="255" autocomplete="off" value="{{ old('patient_identifier', $incident?->patient_identifier) }}">
            </label>
            <label>Age
                <input type="number" name="patient_age" min="0" max="125" value="{{ old('patient_age', $incident?->patient_age) }}">
            </label>
            <label>Ethnic
                <input type="text" name="ethnic" maxlength="120" value="{{ old('ethnic', $incident?->ethnic) }}">
            </label>
            <label>Gender <span class="ir-required">*</span>
                <select name="gender" required><option value="">Select</option>
                    @foreach (['male'=>'Male','female'=>'Female','unknown'=>'Unknown'] as $k=>$v)
                        <option value="{{ $k }}" @selected(old('gender',$incident?->gender)===$k)>{{ $v }}</option>
                    @endforeach
                </select>
            </label>
            <label>Patient status <span class="ir-required">*</span>
                <select name="patient_status" required><option value="">Select</option>
                    @foreach (['alive'=>'Alive','deceased'=>'Deceased'] as $k=>$v)
                        <option value="{{ $k }}" @selected(old('patient_status',$incident?->patient_status)===$k)>{{ $v }}</option>
                    @endforeach
                </select>
            </label>
            <label>Language barrier <span class="ir-required">*</span>
                <select name="language_barrier" required><option value="">Select</option>
                    @foreach (['yes'=>'Yes','no'=>'No'] as $k=>$v)
                        <option value="{{ $k }}" @selected(old('language_barrier',$incident?->language_barrier)===$k)>{{ $v }}</option>
                    @endforeach
                </select>
            </label>
            <label class="ir-full">Diagnosis
                <textarea name="diagnosis" rows="2" maxlength="5000">{{ old('diagnosis', $incident?->diagnosis) }}</textarea>
            </label>
        </div>
    </section>

    <section class="ir-panel">
        <div class="ir-panel-heading"><span>02</span><div><h2>Location & incident type</h2><p>Items 5–7 of Section A</p></div></div>
        <div class="ir-fields">
            <fieldset><legend>5. Type of patient <span class="ir-required">*</span></legend><div class="ir-choices">
                @foreach (['inpatient'=>'Inpatient','outpatient'=>'Outpatient','a_and_e'=>'A&E','day_care'=>'Day Care','other'=>'Others'] as $k=>$v)
                    <label class="ir-option"><input type="radio" name="patient_type" value="{{ $k }}" @checked(old('patient_type',$incident?->patient_type)===$k) required> {{ $v }}</label>
                @endforeach
            </div></fieldset>
            <label>Other patient type (if selected)
                <input type="text" name="patient_type_other" maxlength="255" value="{{ old('patient_type_other', $incident?->patient_type_other) }}">
            </label>
            <fieldset><legend>Department(s) involved <span class="ir-required">*</span> <small>(select all that apply)</small></legend>
                <div class="ir-choices ir-departments">
                    @foreach ($departments as $department)
                        <label class="ir-option"><input type="checkbox" name="departments[]" value="{{ $department }}" @checked(in_array($department, $selectedDepartments, true))> {{ $department }}</label>
                    @endforeach
                </div>
            </fieldset>
            <label>Other department (if selected)
                <input type="text" name="departments_other" maxlength="255" value="{{ old('departments_other', $incident?->departments_other) }}">
            </label>
            <label>Location / ward / clinic
                <input type="text" name="location_ward_clinic" maxlength="255" value="{{ old('location_ward_clinic', $incident?->location_ward_clinic) }}">
            </label>
            <fieldset><legend>6. Type of incident <span class="ir-required">*</span></legend><div class="ir-choices">
                @foreach (['actual'=>'Actual','near_miss'=>'Near Miss'] as $k=>$v)
                    <label class="ir-option"><input type="radio" name="incident_type" value="{{ $k }}" @checked(old('incident_type',$incident?->incident_type)===$k) required> {{ $v }}</label>
                @endforeach
            </div></fieldset>
            <details class="ir-reference"><summary>Examples of reportable incidents from the MOH form</summary>
                <p>Wrong surgery/procedure; retained foreign body; transfusion error; medication error (complete MERS form as well); patient fall; obstetric incident; adverse outcome of clinical procedure; ambulance-related incident; radiotherapy incident; suicide/attempt; wrong next-of-kin discharge; assault/battery; unanticipated fire. This list is not exhaustive.</p>
            </details>
            <label>Other type of incident (if applicable)
                <input type="text" name="other_incident_type" maxlength="255" value="{{ old('other_incident_type', $incident?->other_incident_type) }}">
            </label>
            <label>7. Brief description of what happened <span class="ir-required">*</span>
                <small>Explain what happened before and during the incident, how it occurred, and relevant contributing information.</small>
                <textarea name="description" rows="7" required maxlength="20000">{{ old('description', $incident?->description) }}</textarea>
            </label>
        </div>
    </section>

    <section class="ir-panel" id="actual-only">
        <div class="ir-panel-heading"><span>03</span><div><h2>Patient outcome & immediate action</h2><p>Items 8–9 · Complete only for an actual incident</p></div></div>
        <div class="ir-fields">
            <label>8. Outcome of incident
                <select name="patient_outcome"><option value="">Select for actual incident</option>
                    @foreach (['none'=>'None','mild'=>'Mild','moderate'=>'Moderate','severe'=>'Severe','death'=>'Death','undetermined'=>'Currently cannot be determined'] as $k=>$v)
                        <option value="{{ $k }}" @selected(old('patient_outcome',$incident?->patient_outcome)===$k)>{{ $v }}</option>
                    @endforeach
                </select>
            </label>
            <label>9. Immediate action following incident
                <textarea name="immediate_action" rows="4" maxlength="20000">{{ old('immediate_action', $incident?->immediate_action) }}</textarea>
            </label>
            <div class="ir-alert ir-alert-note">Severe or death outcomes require mandatory Root Cause Analysis under the source form's instructions. QATU will assess this during review.</div>
        </div>
    </section>

    <section class="ir-panel">
        <div class="ir-panel-heading"><span>04</span><div><h2>Reported by</h2><p>Item 10 · Reporter declaration</p></div></div>
        <div class="ir-fields ir-two-col">
            <label>Designation <span class="ir-required">*</span>
                <select name="reporter_designation" required><option value="">Select</option>
                    @foreach (['nurse'=>'Nurse','house_officer'=>'House Officer','medical_officer'=>'Medical Officer','specialist'=>'Specialist','pharmacist'=>'Pharmacist','other'=>'Others'] as $k=>$v)
                        <option value="{{ $k }}" @selected(old('reporter_designation',$incident?->reporter_designation)===$k)>{{ $v }}</option>
                    @endforeach
                </select>
            </label>
            <label>Other designation (if selected)
                <input type="text" name="reporter_designation_other" maxlength="255" value="{{ old('reporter_designation_other', $incident?->reporter_designation_other) }}">
            </label>
            <label class="ir-full">Reporter full name <span class="ir-required">*</span>
                <input type="text" name="reporter_name" maxlength="255" required value="{{ old('reporter_name', $incident?->reporter_name ?? auth()->user()->name) }}">
            </label>
            <div class="ir-full ir-alert ir-alert-note"><strong>Head of Department notification:</strong> Inform the relevant Head of Department immediately, as directed by the MOH form. Submitting here does not send a separate notification to the Head of Department.</div>
            <label class="ir-full ir-consent"><input type="checkbox" name="reporter_attested" value="1" required>
                I confirm that I am the reporter identified above and that this report is accurate to the best of my knowledge. Submission records my user identity and date; it is not a handwritten signature.</label>
        </div>
        <div class="ir-actions">
            <a href="{{ route('ir.index') }}" class="ir-btn ir-btn-light">Cancel</a>
            <button type="submit" class="ir-btn ir-btn-orange">{{ $edit ? 'Resubmit to QATU' : 'Submit IR to QATU' }}</button>
        </div>
    </section>
</form>
@endsection
