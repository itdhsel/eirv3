<!DOCTYPE html>
<html>
<head>
    <title>Completed Incident Report - IR #{{ $incident->id }}</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 14px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; margin-bottom: 20px; }
        th, td { border: 1px solid #000; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; width: 30%; }
        .header { text-align: center; margin-bottom: 30px; }
    </style>
</head>
<body>
    <div class="header">
        <h2>Completed Incident Report</h2>
        <p>Report ID: #{{ $incident->id }} | Status: {{ $incident->state }}</p>
    </div>
    
    <h3>1. General Information</h3>
    <table>
        <tr><th>Facility Name</th><td>{{ $incident->facility_name }}</td></tr>
        <tr><th>Reported At</th><td>{{ $incident->reported_at->format('d-m-Y') }}</td></tr>
        <tr><th>Reporter Name</th><td>{{ $incident->reporter_name }}</td></tr>
    </table>

    <h3>2. Incident Details</h3>
    <table>
        <tr><th>Description</th><td>{{ decrypt($incident->description) }}</td></tr>
        <tr><th>Immediate Action</th><td>{{ $incident->immediate_action ? decrypt($incident->immediate_action) : 'N/A' }}</td></tr>
    </table>

    <h3>3. Review & Sign Off</h3>
    <table>
        <tr><th>Reviewer Name</th><td>{{ $incident->reviewer_name ?? 'N/A' }}</td></tr>
        <tr><th>Reviewed At</th><td>{{ $incident->reviewed_at ? $incident->reviewed_at->format('d-m-Y H:i') : 'N/A' }}</td></tr>
    </table>
</body>
</html>