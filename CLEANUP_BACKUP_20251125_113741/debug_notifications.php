<?php
include('Public/config/db.php');

echo "<h2>Debugging Notification System</h2>";

// Check leave requests
echo "<h3>Leave Requests (Pending Table)</h3>";
$stmt = $pdo->query("
    SELECT id, employee_id, leave_type, status, notified, created_at 
    FROM leave_requests 
    WHERE status IN ('approved', 'declined', 'rejected')
    ORDER BY created_at DESC 
    LIMIT 10
");
$leave_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($leave_requests) {
    echo "<table border='1'><tr><th>ID</th><th>Employee ID</th><th>Leave Type</th><th>Status</th><th>Notified</th><th>Created At</th></tr>";
    foreach ($leave_requests as $req) {
        echo "<tr>";
        echo "<td>{$req['id']}</td>";
        echo "<td>{$req['employee_id']}</td>";
        echo "<td>{$req['leave_type']}</td>";
        echo "<td>{$req['status']}</td>";
        echo "<td>" . ($req['notified'] ? 'YES' : 'NO') . "</td>";
        echo "<td>{$req['created_at']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No approved/declined leave requests found</p>";
}

// Check schedule change requests
echo "<h3>Schedule Change Requests (Pending Table)</h3>";
$stmt = $pdo->query("
    SELECT id, employee_id, status, notified, created_at 
    FROM schedule_change_requests 
    WHERE status IN ('approved', 'declined')
    ORDER BY created_at DESC 
    LIMIT 10
");
$schedule_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($schedule_requests) {
    echo "<table border='1'><tr><th>ID</th><th>Employee ID</th><th>Status</th><th>Notified</th><th>Created At</th></tr>";
    foreach ($schedule_requests as $req) {
        echo "<tr>";
        echo "<td>{$req['id']}</td>";
        echo "<td>{$req['employee_id']}</td>";
        echo "<td>{$req['status']}</td>";
        echo "<td>" . ($req['notified'] ? 'YES' : 'NO') . "</td>";
        echo "<td>{$req['created_at']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No approved/declined schedule requests found</p>";
}

// Check time adjustment requests
echo "<h3>Time Adjustment Requests (Pending Table)</h3>";
$stmt = $pdo->query("
    SELECT id, employee_id, status, notified, created_at 
    FROM time_adjustment_requests 
    WHERE status IN ('approved', 'declined', 'rejected')
    ORDER BY created_at DESC 
    LIMIT 10
");
$time_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($time_requests) {
    echo "<table border='1'><tr><th>ID</th><th>Employee ID</th><th>Status</th><th>Notified</th><th>Created At</th></tr>";
    foreach ($time_requests as $req) {
        echo "<tr>";
        echo "<td>{$req['id']}</td>";
        echo "<td>{$req['employee_id']}</td>";
        echo "<td>{$req['status']}</td>";
        echo "<td>" . ($req['notified'] ? 'YES' : 'NO') . "</td>";
        echo "<td>{$req['created_at']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No approved/declined time adjustment requests found</p>";
}

// Check overtime requests
echo "<h3>Overtime Requests (Pending Table)</h3>";
$stmt = $pdo->query("
    SELECT id, employee_id, status, created_at 
    FROM overtime_requests 
    WHERE status IN ('approved', 'declined', 'rejected')
    ORDER BY created_at DESC 
    LIMIT 10
");
$overtime_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($overtime_requests) {
    echo "<table border='1'><tr><th>ID</th><th>Employee ID</th><th>Status</th><th>Created At</th></tr>";
    foreach ($overtime_requests as $req) {
        echo "<tr>";
        echo "<td>{$req['id']}</td>";
        echo "<td>{$req['employee_id']}</td>";
        echo "<td>{$req['status']}</td>";
        echo "<td>{$req['created_at']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No approved/declined overtime requests found</p>";
}

// Check employee email addresses
echo "<h3>Employee Email Addresses (Sample)</h3>";
$stmt = $pdo->query("
    SELECT id, fname, lname, personal_email 
    FROM employees 
    WHERE personal_email IS NOT NULL AND personal_email != ''
    LIMIT 5
");
$employees = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($employees) {
    echo "<table border='1'><tr><th>ID</th><th>Name</th><th>Email</th></tr>";
    foreach ($employees as $emp) {
        echo "<tr>";
        echo "<td>{$emp['id']}</td>";
        echo "<td>{$emp['fname']} {$emp['lname']}</td>";
        echo "<td>{$emp['personal_email']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No employees with email addresses found</p>";
}
?>
