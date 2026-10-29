<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IncidentRca extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'investigation_team'   => 'array',
            'sequence_of_events'   => 'array',
            'contributing_factors' => 'array',
            'causation_analysis'   => 'array',
            'action_plan'          => 'array',
            'submitted_at'         => 'datetime',
            'verified_at'          => 'datetime',
            'approved_at'          => 'datetime',
        ];
    }

    public function incidentReport(): BelongsTo
    {
        return $this->belongsTo(IncidentReport::class);
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function hodVerifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by_hod');
    }

    public function qaApprover(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_qa');
    }
}