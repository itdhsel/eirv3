@extends('layouts.eirv3')
@section('title', 'Review RCA')

@section('content')
<section class="ir-intro">
    <div class="dash-workspace-header" style="display: flex; justify-content: space-between; align-items: flex-start;">
        <div class="dash-workspace-title">
            <p class="dash-eyebrow" style="color: var(--orange);">RCA REVIEW</p>
            <h1>Root Cause Analysis Report</h1>
            <p>IR Reference: #{{ str_pad($incidentReport->id, 6, '0', STR_PAD_LEFT) }} | Status: <strong style="text-transform: uppercase;">{{ str_replace('_', ' ', $rca->status) }}</strong></p>
        </div>
        <a href="{{ route('ir.show', $incidentReport) }}" class="ir-btn ir-btn-light">&larr; Back to Incident</a>
    </div>
</section>

<!-- SECTION 4: TEAM & 5: SUMMARY -->
<section class="ir-panel">
    <div class="ir-panel-heading"><span>01</span><div><h2>Investigation Team & Summary</h2></div></div>
    <div style="padding: 24px; display: grid; grid-template-columns: 1fr; gap: 20px;">
        <div style="background: #f8fafc; padding: 16px; border-radius: 6px; border: 1px solid #e2e8f0;">
            <strong style="display:block; margin-bottom: 4px; color: #475569;">Team Leader:</strong>
            {{ $rca->investigation_team['leader'] ?? 'Not specified' }}
            <strong style="display:block; margin-top: 12px; margin-bottom: 4px; color: #475569;">Team Members:</strong>
            {{ $rca->investigation_team['members'] ?? 'None' }}
        </div>
        <div>
            <strong style="display:block; margin-bottom: 8px; color: #1e293b;">Incident Summary:</strong>
            <p style="white-space: pre-wrap; margin: 0; color: #334155;">{{ $rca->summary ?? 'No summary provided.' }}</p>
        </div>
    </div>
</section>

<!-- SECTION 6: SEQUENCE OF EVENTS -->
<section class="ir-panel">
    <div class="ir-panel-heading"><span>02</span><div><h2>Sequence of Events</h2></div></div>
    <div style="padding: 24px; overflow-x: auto;">
        <table class="dash-ui-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Time</th>
                    <th>Location</th>
                    <th>Description</th>
                    <th>Key Person</th>
                    <th>Comments</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rca->sequence_of_events ?? [] as $event)
                <tr>
                    <td>{{ $event['date'] ?? '-' }}</td>
                    <td>{{ $event['time'] ?? '-' }}</td>
                    <td>{{ $event['location'] ?? '-' }}</td>
                    <td>{{ $event['description'] ?? '-' }}</td>
                    <td>{{ $event['person'] ?? '-' }}</td>
                    <td>{{ $event['comments'] ?? '-' }}</td>
                </tr>
                @empty
                <tr><td colspan="6" style="text-align: center; color: #64748b;">No events recorded.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>

<!-- SECTION 7 & 8: CONTRIBUTING FACTORS -->
<section class="ir-panel">
    <div class="ir-panel-heading"><span>03</span><div><h2>London Protocol Fishbone Factors</h2></div></div>
    <div style="padding: 24px; display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
        @foreach($rca->contributing_factors ?? [] as $factor => $description)
            @if(!empty($description))
            <div style="background: #f8fafc; padding: 16px; border-radius: 6px; border: 1px solid #e2e8f0;">
                <strong style="display:block; margin-bottom: 8px; text-transform: capitalize; color: #0f766e;">{{ str_replace('_', ' ', $factor) }} Factor</strong>
                <p style="margin: 0; white-space: pre-wrap; color: #334155; font-size: 0.95em;">{{ $description }}</p>
            </div>
            @endif
        @endforeach
    </div>
</section>

<!-- SECTION 9: 5 RULES OF CAUSATION -->
<section class="ir-panel">
    <div class="ir-panel-heading"><span>04</span><div><h2>Causation Analysis</h2></div></div>
    <div style="padding: 24px; overflow-x: auto;">
        <table class="dash-ui-table">
            <thead>
                <tr>
                    <th>Root Cause(s)</th>
                    <th>Effect</th>
                    <th>Event/Incident</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rca->causation_analysis ?? [] as $cause)
                <tr>
                    <td>{{ $cause['root_cause'] ?? '-' }}</td>
                    <td>{{ $cause['effect'] ?? '-' }}</td>
                    <td>{{ $cause['event'] ?? '-' }}</td>
                </tr>
                @empty
                <tr><td colspan="3" style="text-align: center; color: #64748b;">No causation rules recorded.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>

<!-- SECTION 11: ACTION PLAN -->
<section class="ir-panel">
    <div class="ir-panel-heading"><span>05</span><div><h2>Action Plan</h2></div></div>
    <div style="padding: 24px; overflow-x: auto;">
        <table class="dash-ui-table">
            <thead>
                <tr>
                    <th>Factor</th>
                    <th>Description</th>
                    <th>Hierarchy</th>
                    <th>Person Responsible</th>
                    <th>Evidence</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rca->action_plan ?? [] as $action)
                <tr>
                    <td>{{ $action['factor'] ?? '-' }}</td>
                    <td>{{ $action['description'] ?? '-' }}</td>
                    <td><span style="font-weight: 700; color: {{ $action['hierarchy'] === 'Strong' ? '#0f766e' : '#b45309' }};">{{ $action['hierarchy'] ?? '-' }}</span></td>
                    <td>{{ $action['person'] ?? '-' }}</td>
                    <td>{{ $action['evidence'] ?? '-' }}</td>
                    <td>{{ $action['date'] ?? '-' }}</td>
                </tr>
                @empty
                <tr><td colspan="6" style="text-align: center; color: #64748b;">No action plans recorded.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>

<!-- APPROVAL WORKFLOW ACTIONS -->
@php
    $role = auth()->user()->role->slug ?? 'user';
@endphp

@if(($rca->status === 'pending_hod' && $role === 'hod') || ($rca->status === 'pending_qa' && $role === 'qa'))
<section class="ir-panel" style="border: 1px solid #cbd5e1; background-color: #f8fafc;">
    <div style="padding: 24px;">
        <h3 style="margin-top: 0; color: #0f172a;">Review Decision</h3>
        <p style="color: #64748b; margin-bottom: 20px;">Please review the RCA data above. If revisions are required, you may return the form to the LO/User.</p>
        
        <form method="POST" action="{{ route('ir.rca.review', $incidentReport) }}">
            @csrf
            
            <label style="font-weight: 700; display: block; margin-bottom: 8px;">Rejection Comments (Optional)</label>
            <textarea name="comments" rows="3" class="ir-input" placeholder="If rejecting, explain what needs to be fixed..." style="width: 100%; margin-bottom: 20px;"></textarea>

            <div style="display: flex; gap: 16px;">
                @if($role === 'hod')
                    <button type="submit" name="action" value="hod_reject" class="ir-btn ir-btn-outline" style="color: #ef4444; border-color: #ef4444;">Reject & Return to LO</button>
                    <button type="submit" name="action" value="hod_approve" class="ir-btn ir-btn-orange">Approve & Forward to QA</button>
                @elseif($role === 'qa')
                    <button type="submit" name="action" value="qa_reject" class="ir-btn ir-btn-outline" style="color: #ef4444; border-color: #ef4444;">Reject & Return to LO</button>
                    <button type="submit" name="action" value="qa_approve" class="ir-btn ir-btn-primary" style="background-color: #0f766e; color: white;">Approve & Close Incident</button>
                @endif
            </div>
        </form>
    </div>
</section>
@endif
@endsection