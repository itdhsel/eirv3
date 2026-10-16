<?php

namespace App\Http\Controllers;

use App\Models\IncidentReport;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class IncidentReportController extends Controller
{
    private const DEPARTMENTS = [
        'Medical', 'Surgical', 'Orthopaedic', 'Paediatric', 'Laboratory',
        'O&G', 'Pharmacy', 'Radiology & Imaging', 'A&E', 'Psychiatry',
        'Oncology', 'Geriatric', 'Rehabilitation', 'ICU/CCU', 'Others',
    ];

    public function index(): View
    {
        Gate::authorize('viewAny', IncidentReport::class);

        // Determine if the user has review privileges based on the new roles
        $canReview = in_array(Auth::user()->role->slug ?? 'user', ['superadmin', 'qa', 'hod', 'lo'], true);

        $reports = IncidentReport::query()
            // Filter reports: if they are not a reviewer, only show their own
            ->when(! $canReview, fn ($query) =>
                $query->where('reporter_id', Auth::id())
            )
            ->with('reporter:id,name')
            ->latest('created_at')
            ->paginate(15);

        // Add $canReview to the compact() array so the view can use it
        return view('ir.index', compact('reports', 'canReview'));
    }

    public function create(): View
    {
        Gate::authorize('create', IncidentReport::class);
        return view('ir.form', ['incident' => null, 'departments' => self::DEPARTMENTS]);
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('create', IncidentReport::class);
        
        $data = $this->validateReporter($request);
        $data['reporter_id'] = $request->user()->id;
        $data['reported_at'] = now()->toDateString();
        $data['state'] = \App\States\IncidentState\Reported::class;
        $data['status'] = 'submitted';
        $data['reporter_attested'] = true;
        
        $incident = IncidentReport::create($data);

        return redirect()->route('ir.show', $incident)
            ->with('success', 'Incident report submitted to QATU.');
    }

    public function show(IncidentReport $incidentReport): View
    {
        Gate::authorize('view', $incidentReport);
        
        $incidentReport->load('reporter:id,name', 'reviewer:id,name');

        return view('ir.show', ['incident' => $incidentReport]);
    }

    public function edit(IncidentReport $incidentReport): View
    {
        $this->checkEditable($incidentReport);
        return view('ir.form', ['incident' => $incidentReport, 'departments' => self::DEPARTMENTS]);
    }

    public function update(Request $request, IncidentReport $incidentReport): RedirectResponse
    {
        $this->checkEditable($incidentReport);
        $data = $this->validateReporter($request);
        $data['status'] = 'submitted';
        $data['reported_at'] = now()->toDateString();
        $data['reporter_attested'] = true;
        $data['reviewer_id'] = null;
        $data['reviewer_name'] = null;
        $data['reviewer_designation'] = null;
        $data['reviewed_at'] = null;
        $data['actions_taken'] = null;
        $data['review_comments'] = null;
        $data['eir_submitted'] = null;
        $data['eir_submission_date'] = null;
        $incidentReport->update($data);

        return redirect()->route('ir.show', $incidentReport)
            ->with('success', 'Incident report resubmitted to QATU.');
    }

    private function checkEditable(IncidentReport $incident): void
    {
        abort_unless(
            Auth::check()
            && Gate::allows('create', IncidentReport::class)
            && $incident->reporter_id === Auth::id()
            && in_array($incident->status, ['submitted', 'returned'], true),
            403
        );
    }

    private function validateReporter(Request $request): array
    {
        $data = $request->validate([
            'facility_name' => ['required', 'string', 'max:255'],
            'patient_name' => ['required', 'string', 'max:255'],
            'incident_date' => ['nullable', 'date', 'required_without:approximate_incident_date'],
            'approximate_incident_date' => ['nullable', 'date'],
            'incident_time' => ['nullable', 'date_format:H:i', 'required_without:approximate_incident_time'],
            'approximate_incident_time' => ['nullable', 'date_format:H:i'],
            'patient_identifier' => ['nullable', 'string', 'max:255'],
            'patient_age' => ['nullable', 'integer', 'between:0,125'],
            'ethnic' => ['nullable', 'string', 'max:120'],
            'gender' => ['required', Rule::in(['male', 'female', 'unknown'])],
            'patient_status' => ['required', Rule::in(['alive', 'deceased'])],
            'language_barrier' => ['required', Rule::in(['yes', 'no'])],
            'diagnosis' => ['nullable', 'string', 'max:5000'],
            'patient_type' => ['required', Rule::in(['inpatient','outpatient','a_and_e','day_care','other'])],
            'patient_type_other' => ['nullable', 'required_if:patient_type,other', 'string', 'max:255'],
            'departments' => ['required', 'array', 'min:1'],
            'departments.*' => ['string', Rule::in(self::DEPARTMENTS)],
            'departments_other' => ['nullable', 'string', 'max:255'],
            'location_ward_clinic' => ['nullable', 'string', 'max:255'],
            'incident_type' => ['required', Rule::in(['actual', 'near_miss'])],
            'other_incident_type' => ['nullable', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:20000'],
            'patient_outcome' => ['nullable', 'required_if:incident_type,actual', Rule::in(['none','mild','moderate','severe','death','undetermined'])],
            'immediate_action' => ['nullable', 'required_if:incident_type,actual', 'string', 'max:20000'],
            'reporter_designation' => ['required', Rule::in(['nurse','house_officer','medical_officer','specialist','pharmacist','other'])],
            'reporter_designation_other' => ['nullable', 'required_if:reporter_designation,other', 'string', 'max:255'],
            'reporter_name' => ['required', 'string', 'max:255'],
            'reporter_attested' => ['accepted'],
        ]);

        if (in_array('Others', $data['departments'], true)
            && blank($data['departments_other'] ?? null)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'departments_other' => 'Specify the other department.',
            ]);
        }

        if ($data['incident_type'] === 'near_miss') {
            $data['patient_outcome'] = null;
            $data['immediate_action'] = null;
        }
        if ($data['patient_type'] !== 'other') $data['patient_type_other'] = null;
        if ($data['reporter_designation'] !== 'other') $data['reporter_designation_other'] = null;
        if (! in_array('Others', $data['departments'], true)) $data['departments_other'] = null;

        return $data;
    }

    public function downloadFeedback(IncidentReport $incidentReport)
    {
        Gate::authorize('manageQaTasks', $incidentReport);

        $pdf = Pdf::loadView('ir.pdf-feedback', ['incident' => $incidentReport]);
        return $pdf->download('feedback-form-' . $incidentReport->id . '.pdf');
    }

    public function downloadRca(IncidentReport $incidentReport)
    {
        Gate::authorize('manageQaTasks', $incidentReport);

        $pdf = Pdf::loadView('ir.pdf-rca', ['incident' => $incidentReport]);
        return $pdf->download('rca-form-' . $incidentReport->id . '.pdf');
    }

    public function downloadCompleted(IncidentReport $incidentReport)
    {
        Gate::authorize('manageQaTasks', $incidentReport);

        $pdf = Pdf::loadView('ir.pdf-completed', ['incident' => $incidentReport]);
        return $pdf->download('completed-ir-report-' . $incidentReport->id . '.pdf');
    }
}