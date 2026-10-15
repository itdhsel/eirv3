<?php

namespace App\Http\Controllers;

use App\Models\IncidentReport;
use Illuminate\Http\Request;
use Illuminate\View\View;

class Eirv3DashboardController extends Controller
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

        // Restrict data at the SQL query level. The User role can never query
        // or count reports owned by another user.
        $visibleReports = IncidentReport::query()
            ->when(! $isReviewer, fn ($query) => $query->where('reporter_id', $user->id));

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
            // Added 'state' to the select array so you can display the workflow state on the dashboard
            ->select(['id', 'reporter_id', 'reported_at', 'incident_type', 'status', 'state', 'created_at'])
            ->latest('created_at')
            ->limit(8)
            ->get();

            return view('dashboard', compact('role', 'stats', 'recentReports', 'isReviewer'));
    }
}