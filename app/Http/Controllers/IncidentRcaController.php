<?php

namespace App\Http\Controllers;

use App\Models\IncidentReport;
use App\Models\IncidentRca;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class IncidentRcaController extends Controller
{
    public function create(IncidentReport $incidentReport)
    {
        // Only allow if the report requires an RCA and the user is authorized (LO/HOD/Reporter)
        abort_unless($incidentReport->status === 'rca_required', 403, 'RCA is not currently required for this report.');
        
        $rca = $incidentReport->rca ?? new IncidentRca();
        return view('rca.form', compact('incidentReport', 'rca'));
    }

    public function store(Request $request, IncidentReport $incidentReport)
    {
        abort_unless($incidentReport->status === 'rca_required' || $incidentReport->status === 'returned', 403);

        $data = $request->validate([
            'investigation_team'     => ['nullable', 'array'],
            'summary'                => ['nullable', 'string', 'max:5000'],
            'sequence_of_events'     => ['nullable', 'array'],
            'contributing_factors'   => ['nullable', 'array'],
            'causation_analysis'     => ['nullable', 'array'],
            'root_causes'            => ['nullable', 'string', 'max:5000'],
            'action_plan'            => ['nullable', 'array'],
            'action'                 => ['required', 'in:draft,submit'],
        ]);

        // Determine workflow status based on the button clicked
        $status = $request->action === 'submit' ? 'pending_hod' : 'draft';

        $incidentRca = IncidentRca::updateOrCreate(
            ['incident_report_id' => $incidentReport->id],
            [
                'investigation_team'   => $data['investigation_team'] ?? null,
                'summary'              => $data['summary'] ?? null,
                'sequence_of_events'   => array_values($data['sequence_of_events'] ?? []),
                'contributing_factors' => $data['contributing_factors'] ?? null,
                'causation_analysis'   => array_values($data['causation_analysis'] ?? []),
                'root_causes'          => $data['root_causes'] ?? null,
                'action_plan'          => array_values($data['action_plan'] ?? []),
                'status'               => $status,
                'submitted_by'         => $request->action === 'submit' ? Auth::id() : null,
                'submitted_at'         => $request->action === 'submit' ? now() : null,
            ]
        );

        if ($request->action === 'submit') {
            // Update the main IR status to reflect it's waiting for HOD RCA approval
            $incidentReport->update(['status' => 'rca_pending_hod']);
            return redirect()->route('ir.show', $incidentReport)->with('success', 'RCA submitted to HOD for verification.');
        }

        return back()->with('success', 'RCA Draft saved successfully.');
    }

    public function show(IncidentReport $incidentReport)
    {
        $rca = $incidentReport->rca;
        abort_unless($rca, 404, 'RCA not found for this report.');

        return view('rca.show', compact('incidentReport', 'rca'));
    }

    public function review(Request $request, IncidentReport $incidentReport)
    {
        $rca = $incidentReport->rca;
        abort_unless($rca, 404);

        $role = Auth::user()->role->slug ?? 'user';
        
        $request->validate([
            'action' => ['required', 'in:hod_approve,hod_reject,qa_approve,qa_reject'],
            'comments' => ['nullable', 'string', 'max:1000'] // Optional rejection notes
        ]);

        if (in_array($request->action, ['hod_approve', 'hod_reject'])) {
            abort_unless($role === 'hod', 403, 'Only HOD can perform this action.');
            
            if ($request->action === 'hod_approve') {
                $rca->update([
                    'status' => 'pending_qa',
                    'verified_by_hod' => Auth::id(),
                    'verified_at' => now(),
                ]);
                $incidentReport->update(['status' => 'rca_pending_qa']);
                $msg = 'RCA verified and forwarded to QA.';
            } else {
                $rca->update(['status' => 'returned']);
                $incidentReport->update(['status' => 'returned']); // Send back to LO/User
                $msg = 'RCA rejected and returned for corrections.';
            }
        }

        if (in_array($request->action, ['qa_approve', 'qa_reject'])) {
            abort_unless($role === 'qa', 403, 'Only QA can perform this action.');
            
            if ($request->action === 'qa_approve') {
                $rca->update([
                    'status' => 'approved',
                    'approved_by_qa' => Auth::id(),
                    'approved_at' => now(),
                ]);
                // Close the loop!
                $incidentReport->update(['status' => 'closed']);
                $msg = 'RCA approved. Incident Report is now Closed.';
            } else {
                $rca->update(['status' => 'returned']);
                $incidentReport->update(['status' => 'returned']); // Send back to LO/User
                $msg = 'RCA rejected and returned for corrections.';
            }
        }

        return redirect()->route('ir.show', $incidentReport)->with('success', $msg);
    }
}