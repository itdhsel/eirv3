<!DOCTYPE html>
<html>
<head>
    <title>Feedback Form - IR #{{ $incident->id }}</title>
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
        <h2>Feedback Form</h2>
        <p>Incident Report ID: #{{ $incident->id }}</p>
    </div>
    <table>
        <tr>
            <th>Facility Name</th>
            <td>{{ $incident->facility_name }}</td>
        </tr>
        <tr>
            <th>Patient Name</th>
            <td>{{ decrypt($incident->patient_name) }}</td>
        </tr>
        <tr>
            <th>Review Comments (Feedback)</th>
            <td>{{ $incident->review_comments ? decrypt($incident->review_comments) : 'N/A' }}</td>
        </tr>
    </table>
</body>
</html>