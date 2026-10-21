<?php

namespace App\Http\Controllers;

use App\Models\IncidentReport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class IncidentReviewController extends Controller
{
    public function store(Request $request, IncidentReport $incidentReport): RedirectResponse
    {
        // 1. Authorize using the new Gate policy for QA tasks
        Gate::authorize('manageQaTasks', $incidentReport);

        abort_if(
            $incidentReport->reporter_id === $request->user()->id,
            403,
            'A reviewer cannot process their own incident report.'
        );

        // 2. Validate data from the new QA Form - Added 'close'
        $data = $request->validate([
            'action' => ['required', Rule::in(['needs_feedback', 'needs_rca', 'close'])], // Added 'close'
            'actions_taken' => ['nullable', 'array'],
            'actions_taken.*' => ['string', Rule::in([
                'prescription_slip', 'monitor_trend', 'rca', 'mirca',
            ])],
            'review_comments' => ['nullable', 'string', 'max:10000'],
            'eir_submission_date' => ['nullable', 'date'],
            'reviewer_designation' => ['required', Rule::in(['Risk Manager', 'Quality Manager'])],
            'reviewed_at' => ['required', 'date'],
        ]);

        DB::transaction(function () use ($incidentReport, $data, $request): void {
            // A lock prevents two reviewers from simultaneously processing the same IR
            $locked = IncidentReport::query()->lockForUpdate()->findOrFail($incidentReport->id);
            abort_unless($locked->status === 'submitted', 409, 'This report has already been reviewed.');

            // Mandatory RCA validation
            if (in_array($locked->patient_outcome, ['severe', 'death'], true)
                && ! in_array('rca', $data['actions_taken'] ?? [], true)) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'actions_taken' => 'RCA must be selected for a severe or death outcome, as required by the source form.',
                ]);
            }

            // 3. Determine Status and State Machine class based on the QA's action
            if ($data['action'] === 'needs_feedback') {
                $newStatus = 'feedback_required';
                $newState = \App\States\IncidentState\NeedsFeedback::class;
            } elseif ($data['action'] === 'needs_rca') {
                $newStatus = 'rca_required';
                $newState = \App\States\IncidentState\NeedsRCA::class;
            } else {
                // New Close logic
                $newStatus = 'closed';
                $newState = \App\States\IncidentState\Closed::class;
            }

            // 4. Update the report
            $locked->update([
                'status' => $newStatus,
                'state' => $newState,
                'actions_taken' => $data['actions_taken'] ?? null,
                'review_comments' => $data['review_comments'] ?? null,
                'eir_submitted' => !empty($data['eir_submission_date']),
                'eir_submission_date' => $data['eir_submission_date'] ?? null,
                'reviewer_id' => $request->user()->id,
                'reviewer_name' => $request->user()->name,
                'reviewer_designation' => $data['reviewer_designation'],
                'reviewed_at' => $data['reviewed_at'],
            ]);
        });

        // Determine status name for the success message
        if ($request->action === 'needs_feedback') {
            $statusName = 'Feedback Required';
        } elseif ($request->action === 'needs_rca') {
            $statusName = 'RCA Required';
        } else {
            $statusName = 'Closed';
        }

        return redirect()->route('ir.show', $incidentReport)
            ->with('success', "Review submitted successfully. The report is now marked as {$statusName}.");
    }
}