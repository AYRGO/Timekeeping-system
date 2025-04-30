<?php
session_start();
include('../config/db.php');

// Get employee_id from session
$employee_id = $_SESSION['employee']['id'];

if (!$employee_id) {
    header("Location: ../employee/login.php");
    exit;
}

// Get current time and date
$current_time = date("H:i");
$current_date = date("Y-m-d");

// Fetch today's time log
$stmt = $pdo->prepare("SELECT * FROM time_logs WHERE employee_id = ? AND log_date = CURDATE() LIMIT 1");
$stmt->execute([$employee_id]);
$time_log = $stmt->fetch();

$time_in = $time_log['time_in'] ?? null;
$time_out = $time_log['time_out'] ?? null;

// Fetch employee's work schedule
$schedule_stmt = $pdo->prepare("SELECT * FROM employee_work_schedule WHERE employee_id = ? LIMIT 1");
$schedule_stmt->execute([$employee_id]);
$work_schedule = $schedule_stmt->fetch();

$work_start_time = $work_schedule['start_time'] ?? null;
$work_end_time   = $work_schedule['end_time'] ?? null;

// Logic to allow only one time in/out per day
$can_time_in = !$time_in;
$can_time_out = $time_in && !$time_out;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['time_in']) && $can_time_in) {
        $is_late_in = ($work_start_time && $current_time > $work_start_time) ? 1 : 0;
        $insert = $pdo->prepare("INSERT INTO time_logs (employee_id, log_date, time_in, time_out, is_late_in) VALUES (?, ?, ?, ?, ?)");
        $insert->execute([$employee_id, $current_date, $current_time, null, $is_late_in]);

        header("Location: time_log_create.php");
        exit;
    }

    if (isset($_POST['time_out']) && $can_time_out) {
        $is_early_out = ($work_end_time && $current_time < $work_end_time) ? 1 : 0;
        $update = $pdo->prepare("UPDATE time_logs SET time_out = ?, is_early_out = ? WHERE employee_id = ? AND log_date = ?");
        $update->execute([$current_time, $is_early_out, $employee_id, $current_date]);

        header("Location: time_log_create.php");
        exit;
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
