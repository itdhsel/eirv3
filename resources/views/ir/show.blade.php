@extends('layouts.eirv3')
@section('title', 'Incident Report')
@section('content')
<section class="ir-intro" style="margin-bottom: 32px;">
    <div>
        <h1 style="margin: 0 0 8px 0; color: #0f172a;">Incident Report #{{ str_pad((string) $incident->id, 6, '0', STR_PAD_LEFT) }}</h1>
        <p style="margin: 0; color: #64748b;">Reported {{ $incident->reported_at->format('d M Y') }} · <span class="ir-status ir-status-{{ $incident->status }}">{{ ucfirst($incident->status) }}</span></p>
    </div>
    
    <div class="ir-actions" style="margin-top: 20px;">
        <a class="ir-btn ir-btn-light" href="{{ route('ir.index') }}">Back to IR List</a>
        @if($incident->status === 'returned' && $incident->reporter_id === auth()->id())
            <a class="ir-btn ir-btn-orange" href="{{ route('ir.edit',$incident) }}">Correct & Resubmit</a>
        @endif
    </div>
</section>

@if(session('success'))
    <div class="ir-alert ir-alert-success">{{ session('success') }}</div>
@endif

<!-- SECTION A -->
<section class="ir-panel">
    <div class="ir-panel-heading">
        <span>A</span>
        <div><h2>Section A — Reporter</h2><p>Incident description, outcome and reporter details</p></div>
    </div>
    
    <div style="padding: 24px; display: flex; flex-direction: column; gap: 24px; background-color: #ffffff; border-bottom-left-radius: 8px; border-bottom-right-radius: 8px;">
        
    <!-- Facility & Date -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
                <div>
                    <label style="font-weight: 700; color: #1e293b; display: block; margin-bottom: 8px; font-size: 0.95em;">Facility / institution</label>
                    <div style="width: 100%; padding: 12px; background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; color: #334155;">{{ $incident->facility_name }}</div>
                </div>
                <div>
                    <label style="font-weight: 700; color: #1e293b; display: block; margin-bottom: 8px; font-size: 0.95em;">Date of incident</label>
                    <div style="width: 100%; padding: 12px; background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; color: #334155;">
                        {{ $incident->incident_date ? $incident->incident_date->format('d M Y') : 'N/A' }} 
                        (Approximate: {{ $incident->approximate_incident_date ? $incident->approximate_incident_date->format('d M Y') : 'N/A' }})
                    </div>
                </div>
                <div>
                    <label style="font-weight: 700; color: #1e293b; display: block; margin-bottom: 8px; font-size: 0.95em;">Time of incident</label>
                    <div style="width: 100%; padding: 12px; background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; color: #334155;">
                        {{ $incident->incident_time ? substr($incident->incident_time, 0, 5) : 'N/A' }} 
                        (Approximate: {{ $incident->approximate_incident_time ? substr($incident->approximate_incident_time, 0, 5) : 'N/A' }})
                    </div>
                </div>
            </div>

            <hr style="border: 0; border-top: 1px solid #e2e8f0; margin: 0;">

    <!-- Patient Details -->
    <h3 style="font-size: 1.05em; color: #0f172a; margin: 0; font-weight: bold;">Patient Details</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
                <div>
                    <label style="font-weight: 700; color: #1e293b; display: block; margin-bottom: 8px; font-size: 0.95em;">Patient's name</label>
                    <div style="width: 100%; padding: 12px; background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; color: #334155;">{{ $incident->patient_name }}</div>
                </div>
                <div>
                    <label style="font-weight: 700; color: #1e293b; display: block; margin-bottom: 8px; font-size: 0.95em;">Patient RN / ID</label>
                    <div style="width: 100%; padding: 12px; background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; color: #334155;">{{ $incident->patient_identifier ?: '—' }}</div>
                </div>
                <div>
                    <label style="font-weight: 700; color: #1e293b; display: block; margin-bottom: 8px; font-size: 0.95em;">Age</label>
                    <div style="width: 100%; padding: 12px; background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; color: #334155;">{{ $incident->patient_age ?? '—' }}</div>
                </div>
                <div>
                    <label style="font-weight: 700; color: #1e293b; display: block; margin-bottom: 8px; font-size: 0.95em;">Ethnic</label>
                    <div style="width: 100%; padding: 12px; background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; color: #334155;">{{ $incident->ethnic ?: '—' }}</div>
                </div>
                <div>
                    <label style="font-weight: 700; color: #1e293b; display: block; margin-bottom: 8px; font-size: 0.95em;">Gender</label>
                    <div style="width: 100%; padding: 12px; background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; color: #334155;">{{ ucfirst($incident->gender) }}</div>
                </div>
                <div>
                    <label style="font-weight: 700; color: #1e293b; display: block; margin-bottom: 8px; font-size: 0.95em;">Status</label>
                    <div style="width: 100%; padding: 12px; background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; color: #334155;">{{ ucfirst($incident->patient_status) }}</div>
                </div>
                <div>
                    <label style="font-weight: 700; color: #1e293b; display: block; margin-bottom: 8px; font-size: 0.95em;">Language barrier</label>
                    <div style="width: 100%; padding: 12px; background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; color: #334155;">{{ ucfirst($incident->language_barrier) }}</div>
                </div>
                <div>
                    <label style="font-weight: 700; color: #1e293b; display: block; margin-bottom: 8px; font-size: 0.95em;">Patient type</label>
                    <div style="width: 100%; padding: 12px; background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; color: #334155;">{{ str_replace('_',' ',ucwords($incident->patient_type,'_')) }} {{ $incident->patient_type_other }}</div>
                </div>
            </div>

        <!-- Incident Details -->
        <h3 style="font-size: 1.05em; color: #0f172a; margin: 0; font-weight: bold;">Incident Details</h3>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
            <div>
                <label style="font-weight: 700; color: #1e293b; display: block; margin-bottom: 8px; font-size: 0.95em;">Location / ward / clinic</label>
                <div style="width: 100%; padding: 12px; background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; color: #334155;">{{ $incident->location_ward_clinic ?: '—' }}</div>
            </div>
            <div>
                <label style="font-weight: 700; color: #1e293b; display: block; margin-bottom: 8px; font-size: 0.95em;">Departments</label>
                <div style="width: 100%; padding: 12px; background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; color: #334155;">{{ implode(', ', $incident->departments ?? []) }} {{ $incident->departments_other }}</div>
            </div>
            <div>
                <label style="font-weight: 700; color: #1e293b; display: block; margin-bottom: 8px; font-size: 0.95em;">Incident type</label>
                <div style="width: 100%; padding: 12px; background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; color: #334155;">{{ $incident->incident_type === 'near_miss' ? 'Near Miss' : 'Actual' }}</div>
            </div>
            <div>
                <label style="font-weight: 700; color: #1e293b; display: block; margin-bottom: 8px; font-size: 0.95em;">Other type of incident</label>
                <div style="width: 100%; padding: 12px; background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; color: #334155;">{{ $incident->other_incident_type ?: '—' }}</div>
            </div>
            <div style="grid-column: 1 / -1;">
                <label style="font-weight: 700; color: #1e293b; display: block; margin-bottom: 8px; font-size: 0.95em;">Example of incident</label>
                <div style="width: 100%; padding: 12px; background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; color: #334155;">{{ $incident->example_incident_type ?: '—' }}</div>
            </div>
        </div>

        <div>
            <label style="font-weight: 700; color: #1e293b; display: block; margin-bottom: 8px; font-size: 0.95em;">Brief description</label>
            <div style="width: 100%; padding: 12px; background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; color: #334155; white-space: pre-wrap;">{{ $incident->description }}</div>
        </div>

        @if ($incident->incident_type === 'actual')
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
            <div>
                <label style="font-weight: 700; color: #1e293b; display: block; margin-bottom: 8px; font-size: 0.95em;">Outcome of incident</label>
                <div style="width: 100%; padding: 12px; background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; color: #334155;">{{ ucfirst($incident->patient_outcome ?? '—') }}</div>
            </div>
        </div>
        <div>
            <label style="font-weight: 700; color: #1e293b; display: block; margin-bottom: 8px; font-size: 0.95em;">Immediate action</label>
            <div style="width: 100%; padding: 12px; background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; color: #334155; white-space: pre-wrap;">{{ $incident->immediate_action }}</div>
        </div>
        @endif

        <hr style="border: 0; border-top: 1px solid #e2e8f0; margin: 0;">

        <!-- Reporter Details -->
        <h3 style="font-size: 1.05em; color: #0f172a; margin: 0; font-weight: bold;">Reporter Details</h3>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
            <div>
                <label style="font-weight: 700; color: #1e293b; display: block; margin-bottom: 8px; font-size: 0.95em;">Reporter</label>
                <div style="width: 100%; padding: 12px; background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; color: #334155;">{{ $incident->reporter_name }}</div>
            </div>
            <div>
                <label style="font-weight: 700; color: #1e293b; display: block; margin-bottom: 8px; font-size: 0.95em;">Designation</label>
                <div style="width: 100%; padding: 12px; background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; color: #334155;">{{ ucwords(str_replace('_',' ',$incident->reporter_designation)) }} {{ $incident->reporter_designation_other }}</div>
            </div>
            <div>
                <label style="font-weight: 700; color: #1e293b; display: block; margin-bottom: 8px; font-size: 0.95em;">Submitted</label>
                <div style="width: 100%; padding: 12px; background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; color: #334155;">{{ $incident->reported_at->format('d M Y') }} · acknowledged via user account</div>
            </div>
        </div>
    </div>
</section>

<!-- SECTION B -->
<section class="ir-panel">
    <div class="ir-panel-heading ir-heading-peach">
        <span>B</span>
        <div><h2>Section B — QATU Review</h2><p>Risk Manager / Quality Manager</p></div>
    </div>

    @if($incident->status !== 'submitted')
    <!-- Read-only form view for already processed reports -->
    <div style="padding: 24px; display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; background-color: #ffffff; border-bottom-left-radius: 8px; border-bottom-right-radius: 8px;">
        <div>
            <label style="font-weight: 700; color: #1e293b; display: block; margin-bottom: 8px; font-size: 0.95em;">Decision</label>
            <div style="width: 100%; padding: 12px; background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; color: #334155;">
                <span class="ir-status ir-status-{{ $incident->status }}">{{ ucwords(str_replace('_', ' ', $incident->status)) }}</span>
            </div>
        </div>
        <div>
            <label style="font-weight: 700; color: #1e293b; display: block; margin-bottom: 8px; font-size: 0.95em;">Action taken</label>
            <div style="width: 100%; padding: 12px; background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; color: #334155;">
                {{ $incident->actions_taken ? implode(', ', array_map(fn ($item) => ucwords(str_replace('_', ' ', $item)), $incident->actions_taken)) : '-' }}
            </div>
        </div>
        <div>
            <label style="font-weight: 700; color: #1e293b; display: block; margin-bottom: 8px; font-size: 0.95em;">e-IR submitted?</label>
            <div style="width: 100%; padding: 12px; background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; color: #334155;">
                {{ is_null($incident->eir_submitted) ? 'Not recorded' : ($incident->eir_submitted ? 'Yes' : 'No') }}
            </div>
        </div>
        <div>
            <label style="font-weight: 700; color: #1e293b; display: block; margin-bottom: 8px; font-size: 0.95em;">e-IR submission date</label>
            <div style="width: 100%; padding: 12px; background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; color: #334155;">
                {{ $incident->eir_submission_date?->format('d M Y') ?? '-' }}
            </div>
        </div>
        <div style="grid-column: 1 / -1;">
            <label style="font-weight: 700; color: #1e293b; display: block; margin-bottom: 8px; font-size: 0.95em;">Comments</label>
            <div style="width: 100%; padding: 12px; background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; color: #334155; white-space: pre-wrap;">{{ $incident->review_comments ?: '-' }}</div>
        </div>
        
        <div style="grid-column: 1 / -1;"><hr style="border: 0; border-top: 1px solid #e2e8f0; margin: 0;"></div>
        
        <div>
            <label style="font-weight: 700; color: #1e293b; display: block; margin-bottom: 8px; font-size: 0.95em;">Reviewer</label>
            <div style="width: 100%; padding: 12px; background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; color: #334155;">
                {{ $incident->reviewer_name ?? '-' }} ({{ $incident->reviewer_designation }})
            </div>
        </div>
        <div>
            <label style="font-weight: 700; color: #1e293b; display: block; margin-bottom: 8px; font-size: 0.95em;">Review date</label>
            <div style="width: 100%; padding: 12px; background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; color: #334155;">
                {{ $incident->reviewed_at?->format('d M Y H:i') ?? '-' }}
            </div>
        </div>
    </div>
    
    <!-- NEW: Download Button replaces the form when closed -->
    @if(strtolower($incident->status) === 'closed')
        <div style="padding: 20px 24px; background: #f8fafc; border-top: 1px solid #e2e8f0; display: flex; justify-content: flex-end; border-bottom-left-radius: 8px; border-bottom-right-radius: 8px;">
            <a href="{{ route('ir.download.completed', $incident) }}" style="background-color: #0f766e; color: white; padding: 10px 16px; border-radius: 6px; text-decoration: none; font-weight: bold; display: inline-block;">Download Completed IR Form</a>        
        </div>
    @endif
        
    @elseif(in_array(auth()->user()->role->slug ?? '', ['qa', 'superadmin']) && $incident->reporter_id !== auth()->id())
        <!-- Cohesive Form matching the screenshot UI -->
        <form method="POST" action="{{ route('ir.review', $incident) }}">
            @csrf
            <div style="padding: 24px; display: flex; flex-direction: column; gap: 32px; background-color: #f8fafc; border-bottom-left-radius: 8px; border-bottom-right-radius: 8px;">
                
                <!-- 1. Action Taken -->
                <div>
                    <h3 style="font-size: 1.1em; color: #0f172a; margin-bottom: 16px; font-weight: bold;">1. Action Taken</h3>
                    
                    <div style="display: grid; grid-template-columns: minmax(250px, 1fr) 2fr; gap: 24px; align-items: flex-start;">
                        <!-- Left Info Box -->
                        <div style="background-color: #e0f2fe; border: 1px solid #bae6fd; border-radius: 6px; padding: 16px; color: #0369a1; font-size: 0.9em;">
                            <strong style="display: block; margin-bottom: 8px;">Mandatory Root Cause Analysis:</strong>
                            <ol style="margin: 0; padding-left: 16px; line-height: 1.6;">
                                <li>Incident with Severe or Death outcome</li>
                                <li>Other incident/near miss based on the Risk Manager/ Quality Manager assessment</li>
                                <li>Directive from State Health Department / Ministry.</li>
                            </ol>
                        </div>
                        
                        <!-- Right Checkboxes & Comments -->
                        <div>
                            <div style="display: flex; flex-direction: column; gap: 12px; margin-bottom: 24px;">
                                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; color: #334155;">
                                    <input type="checkbox" name="actions_taken[]" value="prescription_slip" style="width: 16px; height: 16px;"> "PRESCRIPTION SLIP"
                                </label>
                                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; color: #334155;">
                                    <input type="checkbox" name="actions_taken[]" value="monitor_trend" style="width: 16px; height: 16px;"> MONITOR THE TREND FIRST
                                </label>
                                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; color: #334155;">
                                    <input type="checkbox" name="actions_taken[]" value="rca" style="width: 16px; height: 16px;"> RCA
                                </label>
                                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; color: #334155;">
                                    <input type="checkbox" name="actions_taken[]" value="mirca" style="width: 16px; height: 16px;"> MIRCA (Multi-incident Root Cause Analysis)
                                </label>
                            </div>
                            
                            <!-- Styled Free Text Area -->
                            <label style="font-weight: 700; color: #1e293b; display: block; margin-bottom: 8px; font-size: 0.95em;">Additional comments</label>
                            <textarea name="review_comments" rows="3" style="width: 100%; padding: 12px; border: 1px solid #cbd5e1; border-radius: 6px; background-color: #ffffff; color: #334155; font-family: inherit; font-size: 1em; box-sizing: border-box; outline: none; transition: border-color 0.2s ease-in-out;" onfocus="this.style.borderColor='#3b82f6'" onblur="this.style.borderColor='#cbd5e1'"></textarea>
                        </div>
                    </div>
                </div>

                <hr style="border: 0; border-top: 1px solid #e2e8f0; margin: 0;">

                <!-- 2. e-IR Submitted -->
                <div>
                    <h3 style="font-size: 1.1em; color: #0f172a; margin-bottom: 4px; font-weight: bold;">2. e-IR Submitted?</h3>
                    <p style="font-size: 0.9em; color: #64748b; margin-top: 0; margin-bottom: 16px;">Please submit to e-IR <strong>within 5 days</strong> from the date of the incident.</p>
                    
                    <div style="max-width: 300px;">
                        <!-- Styled Date Input -->
                        <label style="font-weight: 700; color: #1e293b; display: block; margin-bottom: 8px; font-size: 0.95em;">Date of Submission</label>
                        <input type="date" name="eir_submission_date" style="width: 100%; padding: 12px; border: 1px solid #cbd5e1; border-radius: 6px; background-color: #ffffff; color: #334155; font-family: inherit; font-size: 1em; box-sizing: border-box; outline: none; transition: border-color 0.2s ease-in-out;" onfocus="this.style.borderColor='#3b82f6'" onblur="this.style.borderColor='#cbd5e1'">
                    </div>
                </div>

                <hr style="border: 0; border-top: 1px solid #e2e8f0; margin: 0;">

                <!-- 3. Risk / Quality Manager -->
                <div>
                    <h3 style="font-size: 1.1em; color: #0f172a; margin-bottom: 4px; font-weight: bold;">3. Risk / Quality Manager</h3>
                    <p style="font-size: 0.9em; color: #64748b; margin-top: 0; margin-bottom: 20px;">System verified credentials</p>
                    
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 24px;">
                        <div>
                            <label style="font-weight: 700; color: #1e293b; display: block; margin-bottom: 8px; font-size: 0.95em;">Name</label>
                            <input type="text" name="reviewer_name" value="{{ auth()->user()->name }}" readonly style="width: 100%; padding: 12px; background-color: #f1f5f9; border: 1px solid #e2e8f0; border-radius: 6px; color: #64748b; font-family: inherit; font-size: 1em; box-sizing: border-box; cursor: not-allowed;">
                        </div>
                        <div>
                            <label style="font-weight: 700; color: #1e293b; display: block; margin-bottom: 8px; font-size: 0.95em;">Signature</label>
                            <input type="text" value="Digitally Signed ✓" readonly style="width: 100%; padding: 12px; background-color: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 6px; color: #166534; font-weight: bold; font-family: inherit; font-size: 1em; box-sizing: border-box; cursor: not-allowed;">
                        </div>
                        <div>
                            <label style="font-weight: 700; color: #1e293b; display: block; margin-bottom: 8px; font-size: 0.95em;">Designation <span style="color: #ef4444;">*</span></label>
                            <select name="reviewer_designation" required style="width: 100%; padding: 12px; border: 1px solid #cbd5e1; border-radius: 6px; background-color: #ffffff; color: #334155; font-family: inherit; font-size: 1em; box-sizing: border-box; outline: none; transition: border-color 0.2s ease-in-out;" onfocus="this.style.borderColor='#3b82f6'" onblur="this.style.borderColor='#cbd5e1'">
                                <option value="">Select</option>
                                <option value="Risk Manager">Risk Manager</option>
                                <option value="Quality Manager">Quality Manager</option>
                            </select>
                        </div>
                        <div>
                            <label style="font-weight: 700; color: #1e293b; display: block; margin-bottom: 8px; font-size: 0.95em;">Date</label>
                            <input type="date" name="reviewed_at" value="{{ now()->toDateString() }}" style="width: 100%; padding: 12px; border: 1px solid #cbd5e1; border-radius: 6px; background-color: #ffffff; color: #334155; font-family: inherit; font-size: 1em; box-sizing: border-box; outline: none; transition: border-color 0.2s ease-in-out;" onfocus="this.style.borderColor='#3b82f6'" onblur="this.style.borderColor='#cbd5e1'">
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Actions Footer -->
            <div class="ir-actions" style="padding: 20px 24px; background: #ffffff; border-top: 1px solid #e2e8f0; display: flex; justify-content: flex-end; gap: 12px; border-bottom-left-radius: 8px; border-bottom-right-radius: 8px;">
                
                <!-- New Close Button with Popup Confirmation -->
                <button type="submit" name="action" value="close" class="ir-btn" style="background-color: #ef4444; color: white; border: none; padding: 10px 16px; border-radius: 6px; cursor: pointer; font-weight: bold;" onclick="return confirm('Are you sure you want to close this Incident Report? No further amendments can be made.');">Close Report</button>
                
                <button type="submit" name="action" value="needs_feedback" class="ir-btn ir-btn-light" style="padding: 10px 16px; border-radius: 6px; cursor: pointer; border: 1px solid #cbd5e1; background: white; font-weight: bold; color: #1e293b;">Request Feedback</button>
                <button type="submit" name="action" value="needs_rca" class="ir-btn ir-btn-orange" style="background-color: #0f766e; color: white; border: none; padding: 10px 16px; border-radius: 6px; cursor: pointer; font-weight: bold;">Require RCA</button>
            </div>
        </form>
    @else
        <p style="padding: 24px; color: #64748b; font-style: italic; text-align: center;">This report is pending review by QATU.</p>
    @endif
</section>

<!-- SECTION C: FEEDBACK SUBMISSION (Visible to User/LO) -->
@if(strtolower($incident->status) === 'feedback_required' && (auth()->id() == $incident->reporter_id || (auth()->user()->role->slug ?? '') === 'lo'))
<section class="ir-panel">
    <div class="ir-panel-heading">
        <span style="background-color: #eab308;">C</span>
        <div><h2>Section C — Provide Feedback</h2><p>Requested by QATU</p></div>
    </div>    
    <form method="POST" action="{{ route('ir.feedback.store', $incident) }}">
        @csrf
        <div style="padding: 24px; background-color: #f8fafc; border-bottom-left-radius: 8px; border-bottom-right-radius: 8px;">
            <div style="margin-bottom: 20px;">
                <label style="font-weight: 700; color: #1e293b; display: block; margin-bottom: 8px;">Your Feedback / Investigation Findings <span style="color: #ef4444;">*</span></label>
                <textarea name="feedback_comments" rows="5" required style="width: 100%; padding: 12px; border: 1px solid #cbd5e1; border-radius: 6px; outline: none; font-family: inherit;" placeholder="Detail your findings, actions taken, or requested information here..."></textarea>
            </div>
            
            <div style="display: flex; justify-content: flex-end;">
                <button type="submit" class="ir-btn ir-btn-orange">Submit to HOD for Approval</button>
            </div>
        </div>
    </form>
</section>
@endif

<!-- SECTION D: RCA SUBMISSION (Visible to User/LO) -->
@if(in_array(strtolower($incident->status), ['rca_required', 'returned']) && (auth()->id() === $incident->reporter_id || in_array(auth()->user()->role->slug ?? '', ['lo', 'hod'])))
<section class="ir-panel" style="border: 1px solid #fca5a5;">
    <div class="ir-panel-heading" style="background-color: #fef2f2;">
        <span style="background-color: #ef4444; color: white;">RCA</span>
        <div>
            <h2 style="color: #991b1b;">Root Cause Analysis Required</h2>
            <p style="color: #b91c1c;">Please complete the RCA2 London Protocol form</p>
        </div>
    </div>
    <div style="padding: 24px; background-color: #fff; display: flex; justify-content: space-between; align-items: center; border-bottom-left-radius: 8px; border-bottom-right-radius: 8px;">
        <p style="margin: 0; color: #64748b; font-size: 0.95em;">This incident requires a thorough investigation and Root Cause Analysis.</p>
        <a href="{{ route('ir.rca.create', $incident) }}" class="ir-btn ir-btn-orange">
            Proceed to Fill RCA Form &rarr;
        </a>
    </div>
</section>
@endif
@endsection