<?php
require_once 'Public/config/db.php';

// Bugardi employee IDs
$bugardi_employees = [
    58, // Adonis Del Mundo Jabinal - Project Coordinator
    52, // Althea Tansingco Makabenta - Document Controller  
    67, // Apryl Ordonio Pasion - Recruitment Mobilization Officer
    65, // Sabando Nuñeza Dou Lester - HSEQ Assistant Manager
    62  // Shirmiley Canlas Quizon - Recruitment Mobilization Officer
];

// Sample overtime requests data
$overtime_requests = [
    [
        'employee_id' => 58,
        'date' => '2025-08-10',
        'start_time' => '18:00:00',
        'end_time' => '20:30:00',
        'duration_hours' => 2.5,
        'reason' => 'Project deadline completion - urgent documentation required',
        'status' => 'Pending',
        'time_in' => '08:00:00',
        'time_out' => '17:00:00'
    ],
    [
        'employee_id' => 52,
        'date' => '2025-08-09',
        'start_time' => '17:30:00',
        'end_time' => '19:00:00',
        'duration_hours' => 1.5,
        'reason' => 'Document review and compliance updates',
        'status' => 'Pending',
        'time_in' => '08:30:00',
        'time_out' => '17:30:00'
    ],
    [
        'employee_id' => 67,
        'date' => '2025-08-08',
        'start_time' => '18:00:00',
        'end_time' => '21:00:00',
        'duration_hours' => 3.0,
        'reason' => 'Recruitment drive preparation and candidate screening',
        'status' => 'Approved',
        'time_in' => '07:45:00',
        'time_out' => '17:00:00'
    ],
    [
        'employee_id' => 65,
        'date' => '2025-08-07',
        'start_time' => '17:00:00',
        'end_time' => '19:30:00',
        'duration_hours' => 2.5,
        'reason' => 'Safety audit report completion and HSEQ compliance review',
        'status' => 'Pending',
        'time_in' => '08:00:00',
        'time_out' => '17:00:00'
    ],
    [
        'employee_id' => 62,
        'date' => '2025-08-06',
        'start_time' => '18:30:00',
        'end_time' => '20:00:00',
        'duration_hours' => 1.5,
        'reason' => 'Client interview coordination and candidate follow-up',
        'status' => 'Approved',
        'time_in' => '08:15:00',
        'time_out' => '17:30:00'
    ],
    [
        'employee_id' => 58,
        'date' => '2025-08-05',
        'start_time' => '19:00:00',
        'end_time' => '22:00:00',
        'duration_hours' => 3.0,
        'reason' => 'Emergency project meeting with international client',
        'status' => 'Pending',
        'time_in' => '08:00:00',
        'time_out' => '17:00:00'
    ],
    [
        'employee_id' => 52,
        'date' => '2025-08-04',
        'start_time' => '17:00:00',
        'end_time' => '18:30:00',
        'duration_hours' => 1.5,
        'reason' => 'Quality control document verification',
        'status' => 'Rejected',
        'time_in' => '08:30:00',
        'time_out' => '17:00:00',
        'explanation' => 'Regular working hours should be sufficient for this task'
    ],
    [
        'employee_id' => 67,
        'date' => '2025-08-03',
        'start_time' => '18:00:00',
        'end_time' => '20:30:00',
        'duration_hours' => 2.5,
        'reason' => 'Mass hiring event preparation and venue setup',
        'status' => 'Pending',
        'time_in' => '07:30:00',
        'time_out' => '17:00:00'
    ]
];

// Insert overtime requests
$stmt = $pdo->prepare("
    INSERT INTO overtime_requests 
    (employee_id, date, start_time, end_time, duration_hours, reason, status, time_in, time_out, explanation, created_at) 
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
");

$inserted_count = 0;
foreach ($overtime_requests as $request) {
    try {
        $stmt->execute([
            $request['employee_id'],
            $request['date'],
            $request['start_time'],
            $request['end_time'],
            $request['duration_hours'],
            $request['reason'],
            $request['status'],
            $request['time_in'],
            $request['time_out'],
            $request['explanation'] ?? null
        ]);
        $inserted_count++;
        echo "✅ Inserted OT request for employee ID {$request['employee_id']} on {$request['date']}\n";
    } catch (Exception $e) {
        echo "❌ Error inserting request for employee ID {$request['employee_id']}: " . $e->getMessage() . "\n";
    }
}

echo "\n🎉 Successfully inserted $inserted_count overtime requests for Bugardi employees!\n";

// Show summary
$stmt = $pdo->query("
    SELECT 
        o.status,
        COUNT(*) as count,
        SUM(o.duration_hours) as total_hours
    FROM overtime_requests o
    JOIN employees e ON o.employee_id = e.id
    WHERE LOWER(TRIM(e.company)) = 'bugardi'
    GROUP BY o.status
");

echo "\n📊 Summary of Bugardi Overtime Requests:\n";
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "Status: {$row['status']} - Count: {$row['count']} - Total Hours: {$row['total_hours']}\n";
}
?>
