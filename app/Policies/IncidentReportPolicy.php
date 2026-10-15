<?php

namespace App\Policies;

use App\Models\IncidentReport;
use App\Models\User;

class IncidentReportPolicy
{
    // Let the superadmin bypass all checks
    public function before(User $user, string $ability): bool|null
    {
        if ($user->role->slug === 'superadmin') {
            return true;
        }
        return null;
    }

    // Allow any authenticated user to view the list (the controller filters the query)
    public function viewAny(User $user): bool
    {
        return true; 
    }

    // Allow users to view a specific report if they created it, or if they are staff
    public function view(User $user, IncidentReport $report): bool
    {
        if (in_array($user->role->slug, ['qa', 'lo', 'hod'])) {
            return true;
        }
        return $user->id === $report->reporter_id;
    }

    // Allow users to create reports
    public function create(User $user): bool
    {
        return $user->role->slug === 'user';
    }

    // "QA PROCESS THE FORM" / "QA DOWNLOAD" / "REPORT CLOSED BY QA"
    public function manageQaTasks(User $user): bool
    {
        return $user->role->slug === 'qa';
    }

    // "FEEDBACK FILL BY LO" / "RCA FILL BY LO"
    public function processAsLo(User $user): bool
    {
        return $user->role->slug === 'lo';
    }

    // "FEEDBACK FILL BY USER" / "RCA FILL BY USER"
    public function processAsUser(User $user): bool
    {
        return $user->role->slug === 'user';
    }

    // "FEEDBACK APPROVE BY HOD" / "RCA APPROVE BY HOD"
    public function approveAsHod(User $user): bool
    {
        return $user->role->slug === 'hod';
    }
}