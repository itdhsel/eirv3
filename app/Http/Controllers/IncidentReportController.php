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
use setasign\Fpdi\Fpdi; // <-- Added FPDI import for the PDF injection

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
        // Fetch ethnics ordered by their ID so they match the exact grouping from your seeder
        $ethnics = \App\Models\Ethnic::orderBy('id')->pluck('name');
        return view('ir.form', ['incident' => null, 'departments' => self::DEPARTMENTS, 'ethnics' => $ethnics]);
    }

    public function edit(IncidentReport $incidentReport): View
    {
        $this->checkEditable($incidentReport);
        $ethnics = \App\Models\Ethnic::orderBy('id')->pluck('name');
        return view('ir.form', ['incident' => $incidentReport, 'departments' => self::DEPARTMENTS, 'ethnics' => $ethnics]);
    }

    public function store(Request $request): RedirectResponse
    {
        // Allow anyone (even guests) to create
        // Gate::authorize('create', IncidentReport::class);
        
        $data = $this->validateReporter($request);
        
        // Use Auth::id() so it safely falls back to null for public guests
        $data['reporter_id'] = Auth::id(); 
        
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
            'example_incident_type' => ['nullable', 'string', 'max:500'],
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
        // 1. Authorize
        Gate::authorize('manageQaTasks', $incidentReport);

        // 2. Initialize FPDI
        $pdf = new Fpdi();
        $filePath = storage_path('app/IR_FORM_2.pdf');
        $pageCount = $pdf->setSourceFile($filePath);

        // ==========================================
        // PAGE 1: Section A
        // ==========================================
        $templateId = $pdf->importPage(1);
        $pdf->AddPage();
        $pdf->useTemplate($templateId);

        $pdf->SetFont('Arial', '', 9);
        $pdf->SetTextColor(0, 0, 0);

        // Date of Reporting (Top Right) - Split into Day, Month, Year
        if ($incidentReport->reported_at) {
            $pdf->SetXY(176, 21); // X coordinate for Day
            $pdf->Write(0, $incidentReport->reported_at->format('d'));
            
            $pdf->SetXY(183, 21); // X coordinate for Month
            $pdf->Write(0, $incidentReport->reported_at->format('m'));
            
            $pdf->SetXY(190, 21); // X coordinate for Year
            $pdf->Write(0, $incidentReport->reported_at->format('Y'));
        }

        // 1. Facility Name
        $pdf->SetXY(49, 38.5);
        $pdf->Write(0, substr($incidentReport->facility_name, 0, 45));

        // 1. Patient Name
        $pdf->SetXY(136, 38.5);
        $pdf->Write(0, substr($incidentReport->patient_name ?? '', 0, 45));

    // 2. Date of Incident - Split into individual digits for the boxes
        if ($incidentReport->incident_date) {
            $d = $incidentReport->incident_date->format('d');
            $m = $incidentReport->incident_date->format('m');
            $y = $incidentReport->incident_date->format('Y');

            // Day boxes
            $pdf->SetXY(52, 48.7); // Box 1
            $pdf->Write(0, $d[0]);
            $pdf->SetXY(59, 48.7); // Box 2
            $pdf->Write(0, $d[1]);
            
            // Month boxes
            $pdf->SetXY(74, 48.7); // Box 1
            $pdf->Write(0, $m[0]);
            $pdf->SetXY(81.5, 48.7); // Box 2
            $pdf->Write(0, $m[1]);
            
            // Year boxes
            $pdf->SetXY(96, 48.7);  // Box 1
            $pdf->Write(0, $y[0]);
            $pdf->SetXY(103.5, 48.7); // Box 2
            $pdf->Write(0, $y[1]);
            $pdf->SetXY(110.5, 48.7); // Box 3
            $pdf->Write(0, $y[2]);
            $pdf->SetXY(118, 48.7); // Box 4
            $pdf->Write(0, $y[3]);
            
        } elseif ($incidentReport->approximate_incident_date) {
            $pdf->SetXY(150, 49); // X/Y for Approximate Date line (adjust if needed)
            $pdf->Write(0, $incidentReport->approximate_incident_date->format('d / m / Y'));
        }

    // 3. Time of Incident - Split into Hours and Minutes
        if ($incidentReport->incident_time) {
            $timeParts = explode(':', $incidentReport->incident_time);
            
            if (count($timeParts) >= 2) {
                // Pad with zero just in case it's a single digit like "9" -> "09"
                $h = str_pad($timeParts[0], 2, '0', STR_PAD_LEFT);
                $m = str_pad($timeParts[1], 2, '0', STR_PAD_LEFT);

                // Hour boxes
                $pdf->SetXY(52, 57.5); // Box 1
                $pdf->Write(0, $h[0]);
                $pdf->SetXY(58, 57.5); // Box 2
                $pdf->Write(0, $h[1]);
                
                // Minute boxes
                $pdf->SetXY(74, 57.5); // Box 1 (After the colon)
                $pdf->Write(0, $m[0]);
                $pdf->SetXY(80.5, 57.5); // Box 2
                $pdf->Write(0, $m[1]);

                // Determine AM or PM based on 24-hour format
                $isPM = (int)$timeParts[0] >= 12;
                
                if ($isPM) {
                    // It is PM, so we cross out "AM"
                    $pdf->SetXY(89, 55.5); // X coordinate over "AM"
                    $pdf->Write(0, '-____');
                } else {
                    // It is AM, so we cross out "PM"
                    $pdf->SetXY(96, 55.5); // X coordinate over "PM"
                    $pdf->Write(0, '____');
                }
            }
        } elseif ($incidentReport->approximate_incident_time) {
            $pdf->SetXY(150, 58); // X/Y for Approximate Time line
            $pdf->Write(0, substr($incidentReport->approximate_incident_time, 0, 5));
        }

        // 4. Patient Details
        $pdf->SetXY(84, 67);
        $pdf->Write(0, $incidentReport->patient_identifier ?? ''); // RN

        $pdf->SetXY(130.5, 67);
        $pdf->Write(0, $incidentReport->patient_age ?? ''); // Age

        $pdf->SetXY(163, 67);
        $pdf->Write(0, $incidentReport->ethnic ?? ''); // Ethnic

    // Gender, Status, Language (Drawing a box around the selected option)
        $pdf->SetLineWidth(0.4); // Make the box line slightly thicker

        // Box for Gender
        $gender = strtolower($incidentReport->gender);
        if ($gender === 'male') {
            $pdf->Rect(32, 69, 8.5, 3.5); // Rect(X, Y, Width, Height)
        } elseif ($gender === 'female') {
            $pdf->Rect(42, 69, 11.5, 3.5);
        } elseif ($gender === 'unknown') {
            $pdf->Rect(55, 69, 16, 3.5);
        }

        // Box for Status
        $status = strtolower($incidentReport->patient_status);
        if ($status === 'alive') {
            $pdf->Rect(91.5, 69, 8.5, 3.5);
        } elseif ($status === 'deceased') {
            $pdf->Rect(101.5, 69, 16, 3.5);
        }

        // Box for Language Barrier
        $language = strtolower($incidentReport->language_barrier);
        if ($language === 'yes') {
            $pdf->Rect(161, 69, 6, 3.5);
        } elseif ($language === 'no') {
            $pdf->Rect(168.5, 69, 5.5, 3.5);
        }

        // Diagnosis
        $pdf->SetXY(100, 74);
        $pdf->Write(0, substr($incidentReport->diagnosis ?? '', 0, 90));

        // 5. TYPE OF PATIENT
        $pdf->SetFont('Arial', 'B', 12);
        switch ($incidentReport->patient_type) {
            case 'inpatient':
                $pdf->SetXY(19, 86.5); 
                $pdf->Write(0, 'X'); 
                break;
            case 'outpatient':
                $pdf->SetXY(19, 90.5); 
                $pdf->Write(0, 'X'); 
                break;
            case 'a_and_e':
                $pdf->SetXY(19, 94); 
                $pdf->Write(0, 'X'); 
                break;
            case 'day_care':
                $pdf->SetXY(46.5, 86.5); 
                $pdf->Write(0, 'X'); 
                break;
            case 'other':
                $pdf->SetXY(46.5, 90.5); 
                $pdf->Write(0, 'X');
                // Print the 'specify' text on the dotted line below it
                $pdf->SetFont('Arial', '', 9);
                $pdf->SetXY(55, 89);
                $pdf->Write(0, substr($incidentReport->patient_type_other ?? '', 0, 20));
                $pdf->SetFont('Arial', 'B', 12);
                break;
        }

        // 5. DEPARTMENT(S) INVOLVED
        // Map each department to its specific [X, Y] box coordinates
        $deptCoords = [
            'Medical'             => [84.5, 84],
            'Surgical'            => [84.5, 87.5],
            'Orthopaedic'         => [84.5, 93],
            'Paediatric'          => [84.5, 98],
            'Laboratory'          => [84.5, 102],
            
            'O&G'                 => [117.5, 84],
            'Pharmacy'            => [117.5, 87.5],
            'Radiology & Imaging' => [117.5, 93],
            'A&E'                 => [117.5, 98],
            'Psychiatry'          => [117.5, 102],
            
            'Oncology'            => [155, 84],
            'Geriatric'           => [155, 87.5],
            'Rehabilitation'      => [155, 93],
            'ICU/CCU'             => [155, 98],
            
            'Others'              => [84.5, 107]
        ];

        if (is_array($incidentReport->departments)) {
            foreach ($incidentReport->departments as $dept) {
                if (isset($deptCoords[$dept])) {
                    $pdf->SetXY($deptCoords[$dept][0], $deptCoords[$dept][1]);
                    $pdf->Write(0, 'X');
                }
            }
            
            // Print the 'specify' text if 'Others' is selected
            if (in_array('Others', $incidentReport->departments)) {
                $pdf->SetFont('Arial', '', 9);
                $pdf->SetXY(115, 105);
                $pdf->Write(0, substr($incidentReport->departments_other ?? '', 0, 40));
                $pdf->SetFont('Arial', 'B', 12);
            }
        }
        
        $pdf->SetFont('Arial', '', 9); // Reset font for Location/Ward


        // Location / Ward
        $pdf->SetXY(19, 107.5);
        $pdf->Write(0, substr($incidentReport->location_ward_clinic ?? '', 0, 80));

        // 6. Type of Incident Checkbox
        $pdf->SetFont('Arial', 'B', 12);
        if ($incidentReport->incident_type === 'actual') {
            $pdf->SetXY(55, 113.5); // Actual Box
            $pdf->Write(0, 'X');
        } else {
            $pdf->SetXY(90.5, 113.5); // Near Miss Box
            $pdf->Write(0, 'X');
        }
        $pdf->SetFont('Arial', '', 9); // Reset font

        // Examples of incidents that need to be reported
        $pdf->SetFont('Arial', 'B', 12);
        
        // If your database stores the selected example (e.g., 'i', 'ii', 'iii') in example_incident_type
        $exampleCoords = [
            'i'    => 142,
            'ii'   => 147,
            'iii'  => 152,
            'iv'   => 157, // Note: Form has a typo calling this 'vi'
            'v'    => 162,
            'vi'   => 167,
            'vii'  => 172,
            'viii' => 177,
            'ix'   => 182,
            'x'    => 187,
            'xi'   => 192,
            'xii'  => 197,
            'xiii' => 202,
            'xiv'  => 207,
        ];

        if (!empty($incidentReport->example_incident_type) && isset($exampleCoords[$incidentReport->example_incident_type])) {
            $pdf->SetXY(22, $exampleCoords[$incidentReport->example_incident_type]); 
            $pdf->Write(0, 'X');
        }

        // Print the text for "Others type of incident" (xiv)
        if (!empty($incidentReport->other_incident_type)) {
            // Check the xiv box
            $pdf->SetXY(22, 207); 
            $pdf->Write(0, 'X');
            
            // Print the text on the line
            $pdf->SetFont('Arial', '', 9);
            $pdf->SetXY(65, 207); 
            $pdf->Write(0, substr($incidentReport->other_incident_type, 0, 80));
        }

        // 7. Brief Description
        $pdf->SetXY(16, 215);
        $pdf->MultiCell(180, 5, $incidentReport->description ?? '');


        // ==========================================
        // PAGE 2: Section B
        // ==========================================
        if ($pageCount > 1) {
            $templateId2 = $pdf->importPage(2);
            $pdf->AddPage();
            $pdf->useTemplate($templateId2);

            $pdf->SetFont('Arial', '', 9);

            // 9. Immediate Action
            $pdf->SetXY(15, 40);
            $pdf->MultiCell(180, 5, $incidentReport->immediate_action ?? '');

            // 10. Reported By Name
            $pdf->SetXY(25, 72);
            $pdf->Write(0, $incidentReport->reporter_name);

            // 10. Designation
            $pdf->SetXY(25, 82);
            $pdf->Write(0, ucwords(str_replace('_', ' ', $incidentReport->reporter_designation)));

            // 10. Date
            $pdf->SetXY(145, 82);
            $pdf->Write(0, $incidentReport->reported_at->format('d/m/Y'));

            // Section B: 2. Date of Submission
            $pdf->SetXY(55, 213);
            $eirDate = $incidentReport->eir_submission_date ? $incidentReport->eir_submission_date->format('d/m/Y') : '';
            $pdf->Write(0, $eirDate);
            
            // Section B: 3. Risk Manager Name
            $pdf->SetXY(25, 257);
            $pdf->Write(0, $incidentReport->reviewer_name ?? '');

            // Section B: 3. Risk Manager Designation
            $pdf->SetXY(25, 268);
            $pdf->Write(0, $incidentReport->reviewer_designation ?? '');

            // Section B: 3. Risk Manager Date
            $pdf->SetXY(145, 268);
            $pdf->Write(0, $incidentReport->reviewed_at ? $incidentReport->reviewed_at->format('d/m/Y') : '');
        }

        $filename = 'completed-ir-report-' . $incidentReport->id . '.pdf';
        
        return response($pdf->Output('S'), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $filename . '"'
        ]);
    }
}