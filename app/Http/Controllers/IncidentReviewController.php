<?php

namespace App\Http\Controllers;

use App\Models\IncidentReport;
use App\Support\Eirv3Access;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class IncidentReviewController extends Controller
{
    public function store(Request $request, IncidentReport $incidentReport): RedirectResponse
    {
        abort_unless(Eirv3Access::canReview($request->user()), 403);
        abort_if(
            $incidentReport->reporter_id === $request->user()->id,
            403,
            'A reviewer cannot approve or return their own incident report.'
        );

        $data = $request->validate([
            'decision' => ['required', Rule::in(['approved', 'returned'])],
            'actions_taken' => ['nullable', 'required_if:decision,approved', 'array', 'min:1'],
            'actions_taken.*' => ['string', Rule::in([
                'prescription_slip', 'monitor_trend', 'rca', 'mirca',
            ])],
            'review_comments' => ['nullable', 'required_if:decision,returned', 'string', 'max:10000'],
            'eir_submitted' => ['nullable', Rule::in(['yes', 'no'])],
            'eir_submission_date' => ['nullable', 'date', 'required_if:eir_submitted,yes'],
            'reviewer_designation' => ['required', Rule::in(['Risk Manager', 'Quality Manager'])],
            'reviewer_attested' => ['accepted'],
        ]);

        DB::transaction(function () use ($incidentReport, $data, $request): void {
            // A lock prevents two reviewers from simultaneously approving the same IR.
            $locked = IncidentReport::query()->lockForUpdate()->findOrFail($incidentReport->id);
            abort_unless($locked->status === 'submitted', 409, 'This report has already been reviewed.');

            if ($data['decision'] === 'approved'
                && in_array($locked->patient_outcome, ['severe', 'death'], true)
                && ! in_array('rca', $data['actions_taken'] ?? [], true)) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'actions_taken' => 'RCA must be selected for a severe or death outcome, as required by the source form.',
                ]);
            }

            $locked->update([
                'status' => $data['decision'],
                'actions_taken' => $data['actions_taken'] ?? null,
                'review_comments' => $data['review_comments'] ?? null,
                'eir_submitted' => isset($data['eir_submitted']) ? $data['eir_submitted'] === 'yes' : null,
                'eir_submission_date' => ($data['eir_submitted'] ?? null) === 'yes' ? ($data['eir_submission_date'] ?? null) : null,
                'reviewer_id' => $request->user()->id,
                'reviewer_name' => $request->user()->name,
                'reviewer_designation' => $data['reviewer_designation'],
                'reviewed_at' => now(),
            ]);
        });

        return redirect()->route('ir.show', $incidentReport)
            ->with('success', 'QATU decision recorded.');
    }
}
