<?php

namespace App\Http\Controllers;

use App\Models\IncidentReport;
use App\Support\Eirv3Access;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
        abort_unless(Eirv3Access::canCreate(Auth::user()), 403);

        $reports = IncidentReport::query()
            ->when(! Eirv3Access::canReview(Auth::user()), fn ($query) =>
                $query->where('reporter_id', Auth::id())
            )
            ->with('reporter:id,name')
            ->latest('created_at')
            ->paginate(15);

        return view('ir.index', compact('reports'));
    }

    public function create(): View
    {
        abort_unless(Eirv3Access::canCreate(Auth::user()), 403);
        return view('ir.form', ['incident' => null, 'departments' => self::DEPARTMENTS]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(Eirv3Access::canCreate($request->user()), 403);
        $data = $this->validateReporter($request);
        $data['reporter_id'] = $request->user()->id;
        $data['reported_at'] = now()->toDateString();
        $data['status'] = 'submitted';
        $data['reporter_attested'] = true;
        $incident = IncidentReport::create($data);

        return redirect()->route('ir.show', $incident)
            ->with('success', 'Incident report submitted to QATU.');
    }

    public function show(IncidentReport $incidentReport): View
    {
        abort_unless(Eirv3Access::canView(Auth::user(), $incidentReport->reporter_id), 403);
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
        // A returned report needs a new QATU decision.
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
            && Eirv3Access::canCreate(Auth::user())
            && $incident->reporter_id === Auth::id()
            && $incident->status === 'returned',
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
}
