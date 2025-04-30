<?php
include('../config/db.php');

// Fetch all employees
$employees = $pdo->query("SELECT id, fname, lname FROM employees")->fetchAll(PDO::FETCH_ASSOC);

// Fetch available work schedules
$schedules = $pdo->query("SELECT id, time_in, time_out FROM work_schedules")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Create Schedule Change Request</title>
</head>
<body>

<h2>Create Schedule Change Request</h2>

<form action="../controller/schedule_change_request_store.php" method="POST">
    <label>Employee:</label>
    <select name="employee_id" required>
        <option value="">-- Select Employee --</option>
        <?php foreach ($employees as $emp): ?>
            <option value="<?= $emp['id'] ?>">
                <?= htmlspecialchars($emp['fname'] . ' ' . $emp['lname']) ?>
            </option>
        <?php endforeach; ?>
    </select><br><br>

    <label>New Schedule:</label>
    <select name="requested_schedule_id" required>
        <option value="">-- Select Schedule --</option>
        <?php foreach ($schedules as $sched): ?>
            <option value="<?= $sched['id'] ?>">
                <?= htmlspecialchars($sched['time_in'] . ' - ' . $sched['time_out']) ?>
            </option>
        <?php endforeach; ?>
    </select><br><br>

    <label>Effective Date:</label>
    <input type="date" name="requested_effective_date" required><br><br>

    <label>Reason:</label><br>
    <textarea name="reason" rows="4" cols="40" required></textarea><br><br>

    <button type="submit">Submit Request</button>
</form>

</body>
</html>
