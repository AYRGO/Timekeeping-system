<?php
session_start();  // Ensure session is started

include('../config/db.php');

// Get employee_id from session
$employee_id = $_SESSION['employee']['id'];

// Check if the employee_id is valid
if (!$employee_id) {
    // Redirect to login page if the employee_id is not found in the session
    header("Location: ../employee/login.php");
    exit;
}

// Get current server time and date
$current_time = date("H:i");
$current_date = date("Y-m-d");  // Format: YYYY-MM-DD

// Check if there's already a time log for today
$stmt = $pdo->prepare("SELECT * FROM time_logs WHERE employee_id = ? AND log_date = CURDATE() LIMIT 1");
$stmt->execute([$employee_id]);
$time_log = $stmt->fetch();

// If a time log exists, use the stored time_in and time_out for the page
$time_in = $time_log ? $time_log['time_in'] : null;
$time_out = $time_log ? $time_log['time_out'] : null;

// Get the employee's work schedule
$work_schedule_stmt = $pdo->prepare("SELECT * FROM employee_work_schedule WHERE employee_id = ? LIMIT 1");
$work_schedule_stmt->execute([$employee_id]);
$work_schedule = $work_schedule_stmt->fetch();

// Retrieve the scheduled start and end times
$work_start_time = isset($work_schedule['start_time']) ? $work_schedule['start_time'] : null;
$work_end_time = isset($work_schedule['end_time']) ? $work_schedule['end_time'] : null;

// Logic to determine whether the user can time in or out based on their schedule
$can_time_in = false;
$can_time_out = false;

// Check if the current time is within the scheduled work hours for time in
if ($work_start_time && $work_end_time) {
    $can_time_in = ($current_time >= $work_start_time && $current_time <= $work_end_time);
}

// Check if there's a valid time log (for Time Out) and the current time is within schedule for Time Out
if ($time_in && !$time_out) {
    $can_time_out = true;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // If the "Time Out" button is clicked
    if (isset($_POST['time_out'])) {
        if (!$can_time_out) {
            echo "<script>alert('Outside Scheduled Hours');</script>";
        } else {
            $log_date = $_POST['log_date'];  // Automatically use the current date
            $time_out = $current_time;

            // Update the time log with the time out
            $update = $pdo->prepare("UPDATE time_logs SET time_out = ? WHERE employee_id = ? AND log_date = ?");
            $update->execute([$time_out, $employee_id, $log_date]);

            // Redirect back to the time log creation page for the same employee
            header("Location: time_log_create.php");
            exit;
        }
    }
    // If the "Time In" button is clicked, log the current time as Time In
    elseif (isset($_POST['time_in'])) {
        if (!$can_time_in) {
            echo "<script>alert('Outside Scheduled Hours');</script>";
        } else {
            $log_date = $current_date;  // Use the current date automatically
            $time_in = $current_time;  // Use current server time as Time In
            $time_out = null;  // Time Out remains empty for now

            // Insert a new time log with current Time In and empty Time Out
            $insert = $pdo->prepare("INSERT INTO time_logs (employee_id, log_date, time_in, time_out) VALUES (?, ?, ?, ?)");
            $insert->execute([$employee_id, $log_date, $time_in, $time_out]);

            // Redirect back to the time log creation page for the same employee
            header("Location: time_log_create.php");
            exit;
        }
    }
}
?>

<html>
<head><title>Log Time</title></head>
<body>
<h1>Manual Time Log</h1>

<form method="POST">
    <!-- Always show the buttons -->

    <!-- Time In button -->
    <?php if (!$time_in): ?>
        <!-- If within scheduled hours, enable Time In button -->
        <?php if ($can_time_in): ?>
            <button type="submit" name="time_in">Log Time In</button><br><br>
        <?php else: ?>
            <button type="button" disabled>Cannot Log In - Outside Scheduled Hours</button><br><br>
        <?php endif; ?>
    <?php else: ?>
        <button type="button" disabled>Already Logged In</button><br><br>
    <?php endif; ?>

    <!-- Time Out button -->
    <?php if ($time_in && !$time_out): ?>
        <!-- If logged in, show Time Out button -->
        <button type="submit" name="time_out">Log Time Out</button><br><br>
    <?php elseif ($time_out): ?>
        <button type="button" disabled>Already Logged Out</button><br><br>
    <?php endif; ?>

</form>

</body>
</html>
