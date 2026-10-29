@extends('layouts.eirv3')
@section('title', 'Root Cause Analysis Form')

@section('content')
<section class="ir-intro">
    <div class="dash-workspace-header">
        <div class="dash-workspace-title">
            <p class="dash-eyebrow" style="color: var(--orange);">CONFIDENTIAL RCA2</p>
            <h1>Root Cause Analysis & Action Report</h1>
            <p>IR Reference: #{{ str_pad($incidentReport->id, 6, '0', STR_PAD_LEFT) }} | Patient: {{ $incidentReport->patient_name ?? 'Unknown' }}</p>
        </div>
    </div>
</section>

<form method="POST" action="{{ route('ir.rca.store', $incidentReport) }}" class="ir-form">
    @csrf

    <!-- SECTION 4: TEAM & 5: SUMMARY -->
    <section class="ir-panel">
        <div class="ir-panel-heading"><span>01</span><div><h2>Investigation Team & Summary</h2></div></div>
        <div class="ir-fields">
            <label>Team Leader Name & Designation
                <input type="text" name="investigation_team[leader]" value="{{ $rca->investigation_team['leader'] ?? '' }}" class="ir-input">
            </label>
            <label>Team Members (Comma separated)
                <input type="text" name="investigation_team[members]" value="{{ $rca->investigation_team['members'] ?? '' }}" class="ir-input">
            </label>
            <label class="ir-full">5. Summary of the Incident
                <textarea name="summary" rows="4" class="ir-input">{{ $rca->summary ?? '' }}</textarea>
            </label>
        </div>
    </section>

    <!-- SECTION 6: SEQUENCE OF EVENTS -->
    <section class="ir-panel">
        <div class="ir-panel-heading"><span>02</span><div><h2>Sequence of Events</h2><p>Important events leading to the incident</p></div></div>
        <div style="padding: 24px; overflow-x: auto;">
            <table class="dash-ui-table" id="sequenceTable">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Time (24h)</th>
                        <th>Location</th>
                        <th>Event Description</th>
                        <th>Key Person & Designation</th>
                        <th>Comments (What went wrong)</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- JS will populate rows here -->
                </tbody>
            </table>
            <button type="button" class="ir-btn ir-btn-light" onclick="addRow('sequenceTable')" style="margin-top: 12px;">+ Add Event Row</button>
        </div>
    </section>

    <!-- SECTION 7 & 8: CONTRIBUTING FACTORS -->
    <section class="ir-panel">
        <div class="ir-panel-heading"><span>03</span><div><h2>London Protocol Fishbone Factors</h2></div></div>
        <div class="ir-fields ir-two-col">
            @php
                $factors = ['Team', 'Individual Staff', 'Patient', 'Task & Technology', 'Management & Organizational', 'Work & Environmental', 'External'];
            @endphp
            @foreach($factors as $factor)
                <div style="border: 1px solid #e2e8f0; padding: 16px; border-radius: 6px;">
                    <label style="font-weight: 700; margin-bottom: 8px; display: block;">{{ $factor }} Factor</label>
                    <textarea name="contributing_factors[{{ strtolower(str_replace([' & ', ' '], '_', $factor)) }}]" rows="3" class="ir-input" placeholder="Describe issues relating to {{ $factor }}..."></textarea>
                </div>
            @endforeach
        </div>
    </section>

    <!-- SECTION 9: 5 RULES OF CAUSATION -->
    <section class="ir-panel">
        <div class="ir-panel-heading"><span>04</span><div><h2>Causation Analysis</h2><p>Using 5 Rules of Causation</p></div></div>
        <div style="padding: 24px; overflow-x: auto;">
            <table class="dash-ui-table" id="causationTable">
                <thead>
                    <tr>
                        <th>Cause(s) / Root Cause(s)</th>
                        <th>Causing</th>
                        <th>Effect</th>
                        <th>Leading to</th>
                        <th>Event/Incident</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- JS will populate rows here -->
                </tbody>
            </table>
            <button type="button" class="ir-btn ir-btn-light" onclick="addRow('causationTable')" style="margin-top: 12px;">+ Add Causation Row</button>
        </div>
    </section>

    <!-- SECTION 11: ACTION PLAN -->
    <section class="ir-panel">
        <div class="ir-panel-heading"><span>05</span><div><h2>Action Plan</h2><p>Identified actions based on root causes</p></div></div>
        <div style="padding: 24px; overflow-x: auto;">
            <table class="dash-ui-table" id="actionPlanTable">
                <thead>
                    <tr>
                        <th>Root Causes</th>
                        <th>Description of Action Plan</th>
                        <th>Hierarchy (Strong/Med/Weak)</th>
                        <th>Person Responsible</th>
                        <th>Evidence of Progress</th>
                        <th>Expected Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- JS will populate rows here -->
                </tbody>
            </table>
            <button type="button" class="ir-btn ir-btn-light" onclick="addRow('actionPlanTable')" style="margin-top: 12px;">+ Add Action Plan</button>
        </div>
    </section>

    <div class="ir-actions" style="padding: 24px; background: #fff; display: flex; justify-content: flex-end; gap: 16px; border-top: 1px solid #e2e8f0;">
        <button type="submit" name="action" value="draft" class="ir-btn ir-btn-light">Save Draft</button>
        <button type="submit" name="action" value="submit" class="ir-btn ir-btn-orange">Submit RCA to HOD</button>
    </div>
</form>

<script>
    // Templates for dynamic rows
    const templates = {
        sequenceTable: `
            <td><input type="date" name="sequence_of_events[{idx}][date]" class="ir-input"></td>
            <td><input type="time" name="sequence_of_events[{idx}][time]" class="ir-input"></td>
            <td><input type="text" name="sequence_of_events[{idx}][location]" class="ir-input"></td>
            <td><textarea name="sequence_of_events[{idx}][description]" class="ir-input" rows="2"></textarea></td>
            <td><input type="text" name="sequence_of_events[{idx}][person]" class="ir-input"></td>
            <td><textarea name="sequence_of_events[{idx}][comments]" class="ir-input" rows="2"></textarea></td>
            <td><button type="button" class="ir-btn" style="background:#ef4444; color:#fff;" onclick="this.closest('tr').remove()">X</button></td>`,
        
        causationTable: `
            <td><textarea name="causation_analysis[{idx}][root_cause]" class="ir-input" rows="2"></textarea></td>
            <td style="vertical-align: middle; text-align: center; color: #64748b; font-weight: bold;">Causing</td>
            <td><textarea name="causation_analysis[{idx}][effect]" class="ir-input" rows="2"></textarea></td>
            <td style="vertical-align: middle; text-align: center; color: #64748b; font-weight: bold;">Leading to</td>
            <td><textarea name="causation_analysis[{idx}][event]" class="ir-input" rows="2"></textarea></td>
            <td><button type="button" class="ir-btn" style="background:#ef4444; color:#fff;" onclick="this.closest('tr').remove()">X</button></td>`,
        
        actionPlanTable: `
            <td><input type="text" name="action_plan[{idx}][factor]" class="ir-input"></td>
            <td><textarea name="action_plan[{idx}][description]" class="ir-input" rows="2"></textarea></td>
            <td>
                <select name="action_plan[{idx}][hierarchy]" class="ir-input">
                    <option value="Strong">Strong</option><option value="Intermediate">Intermediate</option><option value="Weak">Weak</option>
                </select>
            </td>
            <td><input type="text" name="action_plan[{idx}][person]" class="ir-input"></td>
            <td><input type="text" name="action_plan[{idx}][evidence]" class="ir-input"></td>
            <td><input type="date" name="action_plan[{idx}][date]" class="ir-input"></td>
            <td><button type="button" class="ir-btn" style="background:#ef4444; color:#fff;" onclick="this.closest('tr').remove()">X</button></td>`
    };

    let counters = { sequenceTable: 0, causationTable: 0, actionPlanTable: 0 };

    function addRow(tableId) {
        const tbody = document.getElementById(tableId).querySelector('tbody');
        const tr = document.createElement('tr');
        tr.innerHTML = templates[tableId].replace(/{idx}/g, counters[tableId]++);
        tbody.appendChild(tr);
    }

    // Initialize with one empty row for each table
    document.addEventListener('DOMContentLoaded', () => {
        addRow('sequenceTable');
        addRow('causationTable');
        addRow('actionPlanTable');
    });
</script>
@endsection