<!DOCTYPE html>
<html>
<head>
    <title>Root Cause Analysis (RCA) - IR #{{ $incident->id }}</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 14px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #000; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .header { text-align: center; margin-bottom: 30px; }
    </style>
</head>
<body>
    <div class="header">
        <h2>Root Cause Analysis (RCA) Form</h2>
        <p>Incident Report ID: #{{ $incident->id }}</p>
    </div>
    <table>
        <tr>
            <th>Incident Date</th>
            <td>{{ $incident->incident_date ? $incident->incident_date->format('d-m-Y') : 'N/A' }}</td>
        </tr>
        <tr>
            <th>Incident Type</th>
            <td>{{ ucfirst(str_replace('_', ' ', $incident->incident_type)) }}</td>
        </tr>
        <tr>
            <th>Actions Taken (RCA)</th>
            <td>
                @if(is_array($incident->actions_taken))
                    <ul>
                        @foreach($incident->actions_taken as $action)
                            <li>{{ $action }}</li>
                        @endforeach
                    </ul>
                @else
                    N/A
                @endif
            </td>
        </tr>
    </table>
</body>
</html>