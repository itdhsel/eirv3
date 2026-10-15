<?php

namespace App\Policies;

use App\Models\IncidentReport;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class IncidentReportPolicy
{
    // Optionally, let the superadmin bypass all checks
    public function before(User $user, string $ability): bool|null
    {
        if ($user->role->slug === 'superadmin') {
            return true;
        }
        return null;
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