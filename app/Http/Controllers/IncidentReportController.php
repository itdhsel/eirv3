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
        
        $user = Auth::user();
        $role = $user->role->slug ?? 'user';

        $canReview = in_array($role, ['superadmin', 'qa', 'hod', 'lo'], true);

        $reports = IncidentReport::query()
            // 1. LO and HOD Filter
            ->when(in_array($role, ['lo', 'hod']), function ($query) use ($user) {
                $query->where(function ($q) use ($user) {
                    // See their own submitted reports
                    $q->where('reporter_id', $user->id)
                      
                      // OR see reports submitted by ANY user who shares their exact 'jabatan'
                      // (e.g., Abdul Muhaimin sees Nurnuman's reports because both are JTM)
                      ->orWhereHas('reporter', function ($reporterQuery) use ($user) {
                          $reporterQuery->where('jabatan', $user->jabatan);
                      })
                      
                      // OR see reports where their department was explicitly tagged in the form's dropdown
                      ->orWhereJsonContains('departments', $user->jabatan); 
                });
            })
            
            // 2. Standard User Filter
            // (e.g., Test User only sees their own, never Nurnuman's)
            ->when($role === 'user', function ($query) use ($user) {
                $query->where('reporter_id', $user->id);
            })
            
            // 3. Superadmin and QA automatically skip the filters above and see EVERYTHING
            ->with('reporter:id,name,jabatan')
            ->latest('created_at')
            ->paginate(15);

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

        // Explicitly set empty date/time fields to null for the database
        $data['incident_date'] = !empty($data['incident_date']) ? $data['incident_date'] : null;
        $data['approximate_incident_date'] = !empty($data['approximate_incident_date']) ? $data['approximate_incident_date'] : null;
        $data['incident_time'] = !empty($data['incident_time']) ? $data['incident_time'] : null;
        $data['approximate_incident_time'] = !empty($data['approximate_incident_time']) ? $data['approximate_incident_time'] : null;

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
        
        $rca = $incidentReport->rca;
        abort_unless($rca, 404, 'RCA data not found.');

        // Initialize FPDI
        $pdf = new Fpdi();
        $filePath = storage_path('app/RCA_FORM.pdf');
        $pageCount = $pdf->setSourceFile($filePath);

        // ==========================================
        // PAGE 1: TEAM & BASIC INFO
        // ==========================================
        $pdf->AddPage();
        $pdf->useTemplate($pdf->importPage(1));
        $pdf->SetFont('Arial', '', 10);
        $pdf->SetTextColor(0, 0, 0);

        // Hospital Name, RN, Incident Type (Estimated Coordinates)
        $pdf->SetXY(50, 45); 
        $pdf->Write(0, $incidentReport->facility_name);
        
        $pdf->SetXY(80, 55); 
        $pdf->Write(0, $incidentReport->patient_identifier ?? 'N/A');
        
        $pdf->SetXY(45, 65); 
        $pdf->Write(0, $incidentReport->incident_type === 'actual' ? 'Actual Incident' : 'Near Miss');

        // Investigation Team
        $pdf->SetXY(25, 95); // Team Leader
        $pdf->Write(0, $rca->investigation_team['leader'] ?? '');
        
        $pdf->SetXY(25, 115); // Team Members (Using MultiCell in case it's a long list)
        $pdf->MultiCell(70, 5, $rca->investigation_team['members'] ?? '');

        // ==========================================
        // PAGE 2: SUMMARY & SEQUENCE OF EVENTS
        // ==========================================
        if ($pageCount >= 2) {
            $pdf->AddPage();
            $pdf->useTemplate($pdf->importPage(2));
            $pdf->SetFont('Arial', '', 9);

            // Summary
            $pdf->SetXY(15, 30);
            $pdf->MultiCell(180, 5, $rca->summary ?? 'No summary provided.');

            // Sequence of Events (Dynamic Table Loop)
            $startY = 85; // The Y coordinate where the first row of the table starts
            $rowHeight = 10; // How much space to jump down for each new row

            $events = $rca->sequence_of_events ?? [];
            foreach ($events as $index => $event) {
                $currentY = $startY + ($index * $rowHeight);

                $pdf->SetXY(15, $currentY);
                $pdf->Write(0, $event['date'] ?? '-');

                $pdf->SetXY(40, $currentY);
                $pdf->Write(0, $event['time'] ?? '-');

                $pdf->SetXY(60, $currentY);
                $pdf->Write(0, substr($event['location'] ?? '-', 0, 15));

                $pdf->SetXY(90, $currentY);
                $pdf->Write(0, substr($event['description'] ?? '-', 0, 25));

                $pdf->SetXY(140, $currentY);
                $pdf->Write(0, substr($event['person'] ?? '-', 0, 15));

                $pdf->SetXY(170, $currentY);
                $pdf->Write(0, substr($event['comments'] ?? '-', 0, 20));
            }
        }

        // ==========================================
        // PAGE 3: FISHBONE DIAGRAM
        // ==========================================
        if ($pageCount >= 3) {
            $pdf->AddPage();
            $pdf->useTemplate($pdf->importPage(3));
            $pdf->SetFont('Arial', '', 8); // Smaller font to fit inside the diagram boxes

            $factors = $rca->contributing_factors ?? [];
            
            // Top Row Boxes (Estimated Coordinates)
            $pdf->SetXY(20, 35); // Management & Org
            $pdf->MultiCell(50, 4, $factors['management_organizational'] ?? '');
            
            $pdf->SetXY(80, 35); // Team
            $pdf->MultiCell(50, 4, $factors['team'] ?? '');
            
            $pdf->SetXY(140, 35); // Task & Tech
            $pdf->MultiCell(50, 4, $factors['task_technology'] ?? '');

            // Bottom Row Boxes (Estimated Coordinates)
            $pdf->SetXY(15, 130); // External
            $pdf->MultiCell(40, 4, $factors['external'] ?? '');
            
            $pdf->SetXY(60, 130); // Work / Env
            $pdf->MultiCell(40, 4, $factors['work_environmental'] ?? '');
            
            $pdf->SetXY(105, 130); // Individual Staff
            $pdf->MultiCell(40, 4, $factors['individual_staff'] ?? '');
            
            $pdf->SetXY(150, 130); // Patient
            $pdf->MultiCell(40, 4, $factors['patient'] ?? '');
        }

        // ==========================================
        // PAGES 4-5: CONTRIBUTING FACTORS CHECKLIST
        // ==========================================
        if ($pageCount >= 5) {
            // Passing through blank pages since we mapped the custom text directly into the Fishbone on Page 3
            $pdf->AddPage(); 
            $pdf->useTemplate($pdf->importPage(4));
            
            $pdf->AddPage(); 
            $pdf->useTemplate($pdf->importPage(5));
        }

        // ==========================================
        // PAGE 6: 5 RULES OF CAUSATION TABLE
        // ==========================================
        if ($pageCount >= 6) {
            $pdf->AddPage();
            $pdf->useTemplate($pdf->importPage(6));
            $pdf->SetFont('Arial', '', 9);

            $startY = 85; // Starting Y coordinate for the first row of the causation table
            $rowHeight = 15; // Space between rows
            $causations = $rca->causation_analysis ?? [];

            foreach ($causations as $index => $cause) {
                $currentY = $startY + ($index * $rowHeight);

                // Cause / Root Cause
                $pdf->SetXY(15, $currentY);
                $pdf->MultiCell(35, 4, substr($cause['root_cause'] ?? '-', 0, 100));

                // Effect (Skipping "Causing" column as it is static text)
                $pdf->SetXY(85, $currentY);
                $pdf->MultiCell(45, 4, substr($cause['effect'] ?? '-', 0, 100));

                // Event / Incident (Skipping "Leading to" column as it is static text)
                $pdf->SetXY(150, $currentY);
                $pdf->MultiCell(45, 4, substr($cause['event'] ?? '-', 0, 100));
            }
        }

        // ==========================================
        // PAGE 7: ROOT CAUSES SUMMARY
        // ==========================================
        if ($pageCount >= 7) {
            $pdf->AddPage();
            $pdf->useTemplate($pdf->importPage(7));
            $pdf->SetFont('Arial', '', 9);

            // 10. Root Cause(s) summary block
            $pdf->SetXY(15, 140); // Estimated Y-coordinate below the text
            $pdf->MultiCell(180, 5, $rca->root_causes ?? '');
        }

        // ==========================================
        // PAGE 8: ACTION PLAN
        // ==========================================
        if ($pageCount >= 8) {
            // Import and add pages 3 through 7 purely as blank pass-throughs for now
            for ($i = 3; $i <= 7; $i++) {
                $pdf->AddPage();
                $pdf->useTemplate($pdf->importPage($i));
            }

            $pdf->AddPage();
            $pdf->useTemplate($pdf->importPage(8));
            $pdf->SetFont('Arial', '', 9);

            // Action Plan (Dynamic Table Loop)
            $startY = 60; // Estimated starting Y for the Action Plan table
            $rowHeight = 15; 

            $actions = $rca->action_plan ?? [];
            foreach ($actions as $index => $action) {
                $currentY = $startY + ($index * $rowHeight);

                $pdf->SetXY(15, $currentY); // Number
                $pdf->Write(0, $index + 1);

                $pdf->SetXY(25, $currentY); // Factor
                $pdf->Write(0, substr($action['factor'] ?? '-', 0, 20));

                $pdf->SetXY(65, $currentY); // Description
                $pdf->Write(0, substr($action['description'] ?? '-', 0, 30));

                $pdf->SetXY(120, $currentY); // Hierarchy
                $pdf->Write(0, $action['hierarchy'] ?? '-');

                $pdf->SetXY(145, $currentY); // Person Responsible
                $pdf->Write(0, substr($action['person'] ?? '-', 0, 15));

                // Evidence and Date (Shifted further right)
                $pdf->SetXY(170, $currentY);
                $pdf->Write(0, $action['date'] ?? '-');
            }
        }

        $filename = 'rca-report-' . $incidentReport->id . '.pdf';
        
        return response($pdf->Output('S'), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $filename . '"'
        ]);
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

        $pdf->SetFont('Arial', 'B', 9);
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
        } 

        // 2. Approximate Date Line - Split to avoid printing over the form's slashes
        if ($incidentReport->approximate_incident_date) {
            $pdf->SetXY(166, 51); // X for Day
            $pdf->Write(0, $incidentReport->approximate_incident_date->format('d'));
            
            $pdf->SetXY(174, 51); // X for Month
            $pdf->Write(0, $incidentReport->approximate_incident_date->format('m'));
            
            $pdf->SetXY(180, 51); // X for Year
            $pdf->Write(0, $incidentReport->approximate_incident_date->format('Y'));
        } else {
            $pdf->SetXY(167, 51);
            $pdf->Write(0, 'N/A');
        }

        // 3. Time of Incident - Split into Hours and Minutes
        if ($incidentReport->incident_time) {
            $timeParts = explode(':', $incidentReport->incident_time);
            
            if (count($timeParts) >= 2) {
                $h = str_pad($timeParts[0], 2, '0', STR_PAD_LEFT);
                $m = str_pad($timeParts[1], 2, '0', STR_PAD_LEFT);

                // Hour boxes
                $pdf->SetXY(52, 57.5); // Box 1
                $pdf->Write(0, $h[0]);
                $pdf->SetXY(58, 57.5); // Box 2
                $pdf->Write(0, $h[1]);
                
                // Minute boxes
                $pdf->SetXY(74, 57.5); // Box 1 
                $pdf->Write(0, $m[0]);
                $pdf->SetXY(80.5, 57.5); // Box 2
                $pdf->Write(0, $m[1]);

                // Determine AM or PM based on 24-hour format
                $isPM = (int)$timeParts[0] >= 12;
                
                if ($isPM) {
                    $pdf->SetXY(90, 56.5); // Adjusted Y for center strikethrough over "AM"
                    $pdf->Write(0, '----'); // Hyphens create a strikethrough instead of underscores
                } else {
                    $pdf->SetXY(97, 56.5); // Adjusted Y for center strikethrough over "PM"
                    $pdf->Write(0, '----');
                }
            }
        } 

        // 3. Approximate Time Line - Split to avoid printing over the form's colon
        if ($incidentReport->approximate_incident_time) {
            $approxTimeParts = explode(':', $incidentReport->approximate_incident_time);
            if (count($approxTimeParts) >= 2) {
                $approxH = str_pad($approxTimeParts[0], 2, '0', STR_PAD_LEFT);
                $approxM = str_pad($approxTimeParts[1], 2, '0', STR_PAD_LEFT);

                $pdf->SetXY(166, 61); // X for approximate Hour
                $pdf->Write(0, $approxH);
                
                $pdf->SetXY(171, 61); // X for approximate Minute
                $pdf->Write(0, $approxM);

                // Approximate AM/PM cross-out
                $isApproxPM = (int)$approxTimeParts[0] >= 12;
                if ($isApproxPM) {
                    $pdf->SetXY(178, 60.5); // X/Y over approximate "AM"
                    $pdf->Write(0, '----');
                } else {
                    $pdf->SetXY(185, 60.5); // X/Y over approximate "PM"
                    $pdf->Write(0, '----');
                }
            }
        } else {
            $pdf->SetXY(166.5, 61);
            $pdf->Write(0, 'N/A');
        }

        // 4. Patient Details
        $pdf->SetXY(84, 67);
        $pdf->Write(0, $incidentReport->patient_identifier ?? ''); // RN

        $pdf->SetXY(131, 67);
        $pdf->Write(0, $incidentReport->patient_age ?? ''); // Age

        $pdf->SetXY(163, 67);
        $pdf->Write(0, $incidentReport->ethnic ?? ''); // Ethnic

        // 4.Gender, Status, Language (Drawing a box around the selected option)
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
        $pdf->SetXY(100, 74.5);
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
                $pdf->SetFont('Arial', 'B', 9);
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
                $pdf->SetFont('Arial', 'B', 9);
                $pdf->SetXY(115, 105);
                $pdf->Write(0, substr($incidentReport->departments_other ?? '', 0, 40));
                $pdf->SetFont('Arial', 'B', 12);
            }
        }
        
        $pdf->SetFont('Arial', 'B', 9); // Reset font for Location/Ward


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
        $pdf->SetFont('Arial', 'B', 9); // Reset font

        // Examples of incidents that need to be reported
        $pdf->SetFont('Arial', 'B', 12);
                
        // Calibrated Y-coordinates (shifted up by 9 points)
        $exampleCoords = [
            'i'    => 132,
            'ii'   => 137,
            'iii'  => 142,
            'iv'   => 147,
            'v'    => 152,
            'vi'   => 157,
            'vii'  => 162,
            'viii' => 167,
            'ix'   => 172,
            'x'    => 177,
            'xi'   => 182,
            'xii'  => 187,
            'xiii' => 192,
            'xiv'  => 198,
        ];

        // Ensure keys in the array exactly match what your DB saves in example_incident_type
        if (!empty($incidentReport->example_incident_type) && isset($exampleCoords[$incidentReport->example_incident_type])) {
            $pdf->SetXY(13, $exampleCoords[$incidentReport->example_incident_type]);
            $pdf->Write(0, 'X');
        }

        // Print the text for "Others type of incident" (xiv)
        if (!empty($incidentReport->other_incident_type)) {
            // Check the xiv box
            $pdf->SetXY(13, 198); // Match the new X=29 and Y=198
            $pdf->Write(0, 'X');
            
            // Print the text on the line
            $pdf->SetFont('Arial', 'B', 9);
            $pdf->SetXY(65, 197); // Shifted X right from 65 to 78 so it starts on the line
            $pdf->Write(0, substr($incidentReport->other_incident_type, 0, 80));
        }

        // 7. Brief Description
        $pdf->SetFont('Arial', 'B', 6);
        $pdf->SetXY(16, 214);
        $pdf->MultiCell(180, 5, $incidentReport->description ?? '');


        // ==========================================
        // PAGE 2: Section A
        // ==========================================
        if ($pageCount > 1) {
            $templateId2 = $pdf->importPage(2);
            $pdf->AddPage();
            $pdf->useTemplate($templateId2);

            // 8. PATIENT OUTCOME (Map to checkboxes)
            if ($incidentReport->incident_type === 'actual') {
                $pdf->SetFont('Arial', 'B', 12);
                
                // Calibrated based on Y=40 aligning with "SEVERE"
                $outcomeCoords = [
                    'none'         => 27,
                    'mild'         => 32,
                    'moderate'     => 37,
                    'severe'       => 42, 
                    'death'        => 47,
                    'undetermined' => 52,
                ];
                
                if (!empty($incidentReport->patient_outcome) && isset($outcomeCoords[$incidentReport->patient_outcome])) {
                    $pdf->SetXY(42, $outcomeCoords[$incidentReport->patient_outcome]); // X coordinate for the tick box column
                    $pdf->Write(0, 'X');
                }
            }

            $pdf->SetFont('Arial', 'B', 9);

            // 9. Immediate Action (Moved down and shifted right into the empty box)
            $pdf->SetXY(52, 55); 
            $pdf->MultiCell(140, 5, $incidentReport->immediate_action ?? '');

            // 10. Designation (Mapped to checkboxes instead of plain text)
            $pdf->SetFont('Arial', 'B', 12);
            $desigCoords = [
                'nurse'           => [12.4, 104.5],
                'house_officer'   => [12.4, 108.3],
                'medical_officer' => [12.4, 111.7],
                'specialist'      => [53, 104.5],
                'pharmacist'      => [53, 110],
                'other'           => [53, 111.7],
            ];
            
            $designation = $incidentReport->reporter_designation;
            if (!empty($designation) && isset($desigCoords[$designation])) {
                $pdf->SetXY($desigCoords[$designation][0], $desigCoords[$designation][1]);
                $pdf->Write(0, 'X');
                
                // Print specific "other" text on the line next to "OTHERS:"
                if ($designation === 'other' && !empty($incidentReport->reporter_designation_other)) {
                    $pdf->SetFont('Arial', 'B', 9);
                    $pdf->SetXY(70, 111.6); 
                    $pdf->Write(0, substr($incidentReport->reporter_designation_other, 0, 50));
                }
            }

            $pdf->SetFont('Arial', 'B', 9);

            // 10. Reported By Name
            $pdf->SetXY(107, 106.5); // Shifted down to align with "NAME:"
            $pdf->Write(0, $incidentReport->reporter_name);

            // 10. Date
            $pdf->SetXY(107, 110); // Shifted down to align with "DATE:"
            $pdf->Write(0, $incidentReport->reported_at->format('d/m/Y'));

            // ==========================================
            // PAGE 2: Section B
            // ==========================================

            // Section B: 1. Action Taken Checkboxes
            $pdf->SetFont('Arial', 'B', 12);
            
            // Estimated Y-coordinates for the action taken checkboxes
            $actionCoords = [
                'prescription_slip' => 154.8, 
                'monitor_trend'     => 159.5, 
                'rca'               => 164.5, 
                'mirca'             => 169.5, 
            ];

            $actions = is_array($incidentReport->actions_taken) ? $incidentReport->actions_taken : [];
            foreach ($actions as $action) {
                if (isset($actionCoords[$action])) {
                    // Estimated X=69 for the checkbox column. Adjust if the 'X' is too far left/right.
                    $pdf->SetXY(75.5, $actionCoords[$action]); 
                    $pdf->Write(0, 'X');
                }
            }

            // Section B: 1. Additional Comments
            $pdf->SetFont('Arial', 'B', 9);
            // Estimated coordinates below the "Additional comments :" text
            $pdf->SetXY(74, 179); 
            $pdf->MultiCell(125, 5, $incidentReport->review_comments ?? '');
        
            // Section B: 2. Date of Submission - Split to avoid printing over the form's slashes
            if ($incidentReport->eir_submission_date) {
                // Print Day
                $pdf->SetXY(107, 208); // Estimated X for Day blank
                $pdf->Write(0, $incidentReport->eir_submission_date->format('d'));
                
                // Print Month
                $pdf->SetXY(117, 208); // Estimated X for Month blank
                $pdf->Write(0, $incidentReport->eir_submission_date->format('m'));
                
                // Print Year
                $pdf->SetXY(128, 208); // Estimated X for Year blank
                $pdf->Write(0, $incidentReport->eir_submission_date->format('Y'));
            }
            
            // Section B: 3. Risk Manager Name
            $pdf->SetXY(85, 233.7);
            $pdf->Write(0, $incidentReport->reviewer_name ?? '');

            // Section B: 3. Risk Manager Designation
            $pdf->SetXY(97, 245.4);
            $pdf->Write(0, $incidentReport->reviewer_designation ?? '');

            // Section B: 3. Risk Manager Date
            $pdf->SetXY(83, 249.4);
            $pdf->Write(0, $incidentReport->reviewed_at ? $incidentReport->reviewed_at->format('d/m/Y') : '');
        }

        $filename = 'completed-ir-report-' . $incidentReport->id . '.pdf';
        
        return response($pdf->Output('S'), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $filename . '"'
        ]);
    }
}