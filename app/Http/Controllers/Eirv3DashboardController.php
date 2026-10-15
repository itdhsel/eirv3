<?php

namespace App\Http\Controllers;

use App\Models\IncidentReport;
use App\Support\Eirv3Access;
use Illuminate\Http\Request;
use Illuminate\View\View;

class Eirv3DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();
        $role = Eirv3Access::role($user);

        abort_unless(in_array($role, ['super_admin', 'user', 'qatu', 'director'], true), 403);

        // Directors only have access to the reporting module (KIV), not to
        // individual incident reports or their statistics.
        if ($role === 'director') {
            return view('dashboard', [
                'role' => $role,
                'stats' => null,
                'recentReports' => collect(),
            ]);
        }

        $isReviewer = Eirv3Access::canReview($user);

        // Restrict data at the SQL query level. The User role can never query
        // or count reports owned by another user.
        $visibleReports = IncidentReport::query()
            ->when(! $isReviewer, fn ($query) => $query->where('reporter_id', $user->id));

        $statusCounts = (clone $visibleReports)
            ->selectRaw('status, COUNT(*) as report_count')
            ->groupBy('status')
            ->pluck('report_count', 'status');

        $pendingQuery = (clone $visibleReports)->where('status', 'submitted');

        // QATU / Super Admin cannot review their own submissions in Phase 2.
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
            ->select(['id', 'reporter_id', 'reported_at', 'incident_type', 'status', 'created_at'])
            ->latest('created_at')
            ->limit(8)
            ->get();

        return view('dashboard', compact('role', 'stats', 'recentReports'));
    }
}
