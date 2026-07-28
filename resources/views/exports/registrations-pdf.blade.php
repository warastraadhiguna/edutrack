<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Registrations</title>
    <style>
        @page {
            margin: 24px;
        }

        body {
            color: #111827;
            font-family: DejaVu Sans, sans-serif;
            font-size: 9px;
        }

        h1 {
            margin: 0 0 4px;
            font-size: 18px;
        }

        .meta {
            margin-bottom: 14px;
            color: #4b5563;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            padding: 6px;
            border: 1px solid #d1d5db;
            vertical-align: top;
        }

        th {
            background: #e5e7eb;
            text-align: left;
        }

        .center {
            text-align: center;
        }

        .right {
            text-align: right;
        }
    </style>
</head>
<body>
    <h1>Registrations</h1>
    <div class="meta">
        Generated: {{ $generatedAt->format('d-m-Y H:i:s') }} WIB · Total: {{ $rows->count() }} records
    </div>

    <table>
        <thead>
            <tr>
                <th class="center">No.</th>
                <th>Email</th>
                <th>Student</th>
                <th>Subject</th>
                <th>Note</th>
                <th class="center">Submitted Task</th>
                <th class="right">Total Score</th>
                <th class="center">Grade</th>
                <th>User</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $index => $row)
                <tr>
                    <td class="center">{{ $index + 1 }}</td>
                    <td>{{ $row['email'] }}</td>
                    <td>{{ $row['student_name'] }}</td>
                    <td>{{ $row['subject'] }}</td>
                    <td>{{ $row['note'] }}</td>
                    <td class="center">{{ $row['submitted_tasks'] }}</td>
                    <td class="right">{{ $row['total_score'] }}</td>
                    <td class="center">{{ $row['grade'] }}</td>
                    <td>{{ $row['created_by'] }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="center">No registration data found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
