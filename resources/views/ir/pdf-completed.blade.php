<!DOCTYPE html>
<html>
<head>
    <title>IR Form #{{ $incident->id }}</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; line-height: 1.3; }
        .header { text-align: center; margin-bottom: 20px; font-weight: bold; }
        .sulit { float: right; font-weight: bold; font-size: 14px; }
        .section-title { background-color: #d1d5db; padding: 5px; font-weight: bold; margin-top: 15px; border: 1px solid #000; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        th, td { border: 1px solid #000; padding: 6px; vertical-align: top; }
        .label { font-weight: bold; font-size: 11px; }
        .box { border: 1px solid #000; display: inline-block; width: 12px; height: 12px; margin-right: 5px; text-align: center; line-height: 12px; font-size: 10px; }
        .checked { background-color: #000; color: #fff; }
    </style>
</head>
<body>
    <div class="sulit">SULIT</div>
    <div class="header">
        MINISTRY OF HEALTH MALAYSIA<br>
        IR 2.0/2017<br>
        PATIENT SAFETY INCIDENT REPORTING FORM<br>
        <span style="font-weight: normal;">DATE OF REPORTING: {{ $incident->reported_at->format('d/m/Y') }}</span>
    </div>

    <div class="section-title">SECTION A: TO BE COMPLETED BY THE REPORTER OF THE INCIDENT</div>
    <table>
        <tr>
            <td width="50%">
                <div class="label">1. NAME OF FACILITY / INSTITUTION</div>
                {{ $incident->facility_name }}
            </td>
            <td width="50%">
                <div class="label">PATIENT'S NAME</div>
                {{ $incident->patient_name ?? '' }}
            </td>
        </tr>
        <tr>
            <td>
                <div class="label">2. DATE OF INCIDENT</div>
                {{ $incident->incident_date ? $incident->incident_date->format('d/m/Y') : ($incident->approximate_incident_date ? $incident->approximate_incident_date->format('d/m/Y') . ' (Approximate)' : '-') }}
            </td>
            <td>
                <div class="label">3. TIME OF INCIDENT</div>
                {{ $incident->incident_time ?? ($incident->approximate_incident_time ? $incident->approximate_incident_time . ' (Approx)' : '-') }}
            </td>
        </tr>
        <tr>
            <td colspan="2">
                <div class="label">4. PATIENT DETAILS</div>
                <strong>RN/ID:</strong> {{ $incident->patient_identifier ?? '-' }} | 
                <strong>AGE:</strong> {{ $incident->patient_age ?? '-' }} | 
                <strong>ETHNIC:</strong> {{ $incident->ethnic ?? '-' }}<br>
                <strong>GENDER:</strong> {{ strtoupper($incident->gender) }} | 
                <strong>STATUS:</strong> {{ strtoupper($incident->patient_status) }} | 
                <strong>LANGUAGE BARRIER:</strong> {{ strtoupper($incident->language_barrier) }}<br>
                <strong>DIAGNOSIS:</strong> {{ $incident->diagnosis ?? '-' }}
            </td>
        </tr>
        <tr>
            <td>
                <div class="label">5. TYPE OF PATIENT</div>
                {{ ucwords(str_replace('_', ' ', $incident->patient_type)) }}
                {{ $incident->patient_type_other ? ' - ' . $incident->patient_type_other : '' }}
            </td>
            <td>
                <div class="label">DEPARTMENT(S) INVOLVED & LOCATION</div>
                <strong>Dept:</strong> {{ is_array($incident->departments) ? implode(', ', $incident->departments) : '-' }}<br>
                <strong>Location:</strong> {{ $incident->location_ward_clinic ?? '-' }}
            </td>
        </tr>
        <tr>
            <td colspan="2">
                <div class="label">6. TYPE OF INCIDENT</div>
                {{ $incident->incident_type === 'near_miss' ? 'Near Miss' : 'Actual' }} 
                {{ $incident->other_incident_type ? ' - ' . $incident->other_incident_type : '' }}
            </td>
        </tr>
        <tr>
            <td colspan="2">
                <div class="label">7. BRIEF DESCRIPTION OF WHAT HAPPENED</div>
                {{ $incident->description ?? '-' }}
            </td>
        </tr>
        @if($incident->incident_type === 'actual')
        <tr>
            <td colspan="2">
                <div class="label">8. PATIENT OUTCOME (Actual Incident)</div>
                {{ strtoupper($incident->patient_outcome ?? '-') }}
            </td>
        </tr>
        <tr>
            <td colspan="2">
                <div class="label">9. IMMEDIATE ACTION FOLLOWING INCIDENT</div>
                {{ $incident->immediate_action ?? '-' }}
            </td>
        </tr>
        @endif
        <tr>
            <td colspan="2">
                <div class="label">10. REPORTED BY</div>
                <strong>Name:</strong> {{ $incident->reporter_name }} | 
                <strong>Designation:</strong> {{ ucwords(str_replace('_', ' ', $incident->reporter_designation)) }} | 
                <strong>Date:</strong> {{ $incident->reported_at->format('d/m/Y') }}
            </td>
        </tr>
    </table>

    <div class="section-title">SECTION B: TO BE COMPLETED BY THE RISK MANAGER / QUALITY MANAGER</div>
    <table>
        <tr>
            <td colspan="2">
                <div class="label">1. ACTION TAKEN</div>
                @php $actions = is_array($incident->actions_taken) ? $incident->actions_taken : []; @endphp
                <div class="box {{ in_array('prescription_slip', $actions) ? 'checked' : '' }}"></div> Prescription Slip<br>
                <div class="box {{ in_array('monitor_trend', $actions) ? 'checked' : '' }}"></div> Monitor Trend<br>
                <div class="box {{ in_array('rca', $actions) ? 'checked' : '' }}"></div> RCA<br>
                <div class="box {{ in_array('mirca', $actions) ? 'checked' : '' }}"></div> MIRCA<br><br>
                <strong>Additional Comments:</strong><br>
                {{ $incident->review_comments ?? '-' }}
            </td>
        </tr>
        <tr>
            <td colspan="2">
                <div class="label">2. e-IR SUBMITTED?</div>
                <strong>Date of Submission:</strong> {{ $incident->eir_submission_date ? $incident->eir_submission_date->format('d/m/Y') : 'Not Recorded' }}
            </td>
        </tr>
        <tr>
            <td colspan="2">
                <div class="label">3. RISK MANAGER / QUALITY MANAGER</div>
                <strong>Name:</strong> {{ $incident->reviewer_name ?? '-' }}<br>
                <strong>Designation:</strong> {{ $incident->reviewer_designation ?? '-' }}<br>
                <strong>Date:</strong> {{ $incident->reviewed_at ? $incident->reviewed_at->format('d/m/Y') : '-' }}<br>
                <strong>Signature:</strong> Digitally Signed (System Verified)
            </td>
        </tr>
    </table>
</body>
</html>