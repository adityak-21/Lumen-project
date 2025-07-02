<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Your Pending Tasks</title>
</head>
<body>
    <h2>Hello {{ $user->name }},</h2>

    <p>You have the following pending tasks that are overdue:</p>

    <table border="1" cellpadding="8" cellspacing="0" style="border-collapse: collapse;">
        <thead>
            <tr>
                <th>Title</th>
                <th>Description</th>
                <th>Due Date</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($tasks as $task)
                <tr>
                    <td>{{ $task->title }}</td>
                    <td>{{ $task->description ?? '-' }}</td>
                    <td>{{ \Carbon\Carbon::parse($task->due_date)->format('Y-m-d') }}</td>
                    <td>{{ ucfirst($task->status) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <p>Please take action on these tasks as soon as possible.</p>

    <p>Thank you,<br>
    {{ config('app.name', 'App Name') }}</p>
</body>
</html>