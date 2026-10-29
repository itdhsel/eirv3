<?php

namespace App\Http\Controllers;

use App\Models\IncidentReport;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();
        
        // Fetch the role slug, or default to null if the user has no role assigned
        $role = $user->role->slug ?? null; 

        // If $role is null, this will cleanly abort with a 403 instead of a 500 crash
        abort_unless(in_array($role, ['superadmin', 'user', 'qa', 'director', 'hod', 'lo'], true), 403, 'Unauthorized: No valid role assigned to this user.');

        // Directors only have access to the reporting module (KIV), not to
        // individual incident reports or their statistics.
        if ($role === 'director') {
            return view('dashboard', [
                'role' => $role,
                'stats' => null,
                'recentReports' => collect(),
            ]);
        }

        // Define who can review/see all reports under the new RBAC system
        $isReviewer = in_array($role, ['superadmin', 'qa', 'hod', 'lo'], true);

        // Restrict data at the SQL query level based on the new RBAC system
        $visibleReports = IncidentReport::query()
        // 1. LO and HOD Filter
        ->when(in_array($role, ['lo', 'hod']), function ($query) use ($user) {
            $query->where(function ($q) use ($user) {
                // See their own submitted reports
                $q->where('reporter_id', $user->id)
                // OR see reports submitted by ANY user who shares their exact 'jabatan'
                ->orWhereHas('reporter', function ($reporterQuery) use ($user) {
                    $reporterQuery->where('jabatan', $user->jabatan);
                })
                // OR see reports where their department was explicitly tagged in the form's dropdown
                ->orWhereJsonContains('departments', $user->jabatan); 
            });
        })
        // 2. Standard User Filter
        ->when($role === 'user', function ($query) use ($user) {
            $query->where('reporter_id', $user->id);
        });

        // 3. Superadmin and QA automatically skip the filters above and see EVERYTHING
        $statusCounts = (clone $visibleReports)
            ->selectRaw('status, COUNT(*) as report_count')
            ->groupBy('status')
            ->pluck('report_count', 'status');

        $pendingQuery = (clone $visibleReports)->where('status', 'submitted');

        // Reviewers cannot review their own submissions.
        // Exclude those records from their actionable pending-review metric.
        if ($isReviewer) {
            $pendingQuery->where('reporter_id', '!=', $user->id);
        }

        $stats = [
            'total' => (int) $statusCounts->sum(),
            'pending' => $pendingQuery->count(),
            'approved' => (int) ($statusCounts->get('approved') ?? 0),
            'returned' => (int) ($statusCounts->get('returned') ?? 0),
        ];

        $recentReports = (clone $visibleReports)
        ->latest('created_at')
        ->limit(8)
        ->get();

            return view('dashboard', compact('role', 'stats', 'recentReports', 'isReviewer'));
    }
}