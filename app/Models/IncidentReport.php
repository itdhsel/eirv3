<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\ModelStates\HasStates;
use App\States\IncidentState\IncidentState;

class IncidentReport extends Model
{
    use HasStates;

    protected $fillable = [
        'reporter_id', 'status', 'state', 'reported_at', 'facility_name', 'patient_name',
        'incident_date', 'approximate_incident_date', 'incident_time',
        'approximate_incident_time', 'patient_identifier', 'patient_age',
        'ethnic', 'gender', 'patient_status', 'language_barrier', 'diagnosis',
        'patient_type', 'patient_type_other', 'departments', 'departments_other',
        'location_ward_clinic', 'incident_type', 'other_incident_type',
        'description', 'patient_outcome', 'immediate_action',
        'reporter_designation', 'reporter_designation_other', 'reporter_name',
        'reporter_attested', 'actions_taken', 'review_comments', 'eir_submitted',
        'eir_submission_date', 'reviewer_id', 'reviewer_name',
        'reviewer_designation', 'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'state' => IncidentState::class,
            'reported_at' => 'date',
            'incident_date' => 'date',
            'approximate_incident_date' => 'date',
            'eir_submission_date' => 'date',
            'reviewed_at' => 'datetime',
            'departments' => 'array',
            'actions_taken' => 'array',
            'reporter_attested' => 'boolean',
            'eir_submitted' => 'boolean',
            'patient_name' => 'encrypted',
            'patient_identifier' => 'encrypted',
            'diagnosis' => 'encrypted',
            'description' => 'encrypted',
            'immediate_action' => 'encrypted',
            'review_comments' => 'encrypted',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'state', 
                'status', 
                'actions_taken', 
                'review_comments', 
                'reviewer_id'
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }
}