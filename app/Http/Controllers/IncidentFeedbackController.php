<?php

namespace App\Http\Controllers;

use App\Models\IncidentReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class IncidentFeedbackController extends Controller
{
    public function store(Request $request, IncidentReport $incidentReport)
    {
        // 1. Authorize: User or LO
        abort_unless(
            $request->user()->id === $incidentReport->reporter_id || $request->user()->role->slug === 'lo',
            403, 
            'Only the reporter or a Liaison Officer can submit feedback.'
        );

        // 2. Ensure the report is actually waiting for feedback
        abort_unless($incidentReport->status === 'feedback_required', 409, 'This report is not pending feedback.');

        // 3. Validate input
        $data = $request->validate([
            'feedback_comments' => ['required', 'string', 'max:20000'],
        ]);

        // 4. Update status and state machine to send to HOD
        // Note: You will need to add 'feedback_comments' to your database migration/model if it doesn't exist yet.
        $incidentReport->update([
            'feedback_comments' => $data['feedback_comments'],
            'status' => 'pending_hod_approval',
            'state' => \App\States\IncidentState\PendingHODApproval::class,
        ]);

        return redirect()->route('ir.show', $incidentReport)
            ->with('success', 'Feedback submitted successfully. It is now pending HOD approval.');
    }
}