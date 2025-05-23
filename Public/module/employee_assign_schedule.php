<?php
include('../config/db.php');

// Fetch all work schedules
$schedule_stmt = $pdo->query("SELECT * FROM work_schedules");
$schedules = $schedule_stmt->fetchAll(PDO::FETCH_ASSOC);

// If coming from link with employee id
$employee_id = isset($_GET['id']) ? $_GET['id'] : null;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $employee_id = $_POST['employee_id'];
    $work_schedule_id = $_POST['work_schedule_id'];
    $effective_date = $_POST['effective_date'];

    // Insert into employee_work_schedule
    $insert_stmt = $pdo->prepare("INSERT INTO employee_work_schedule (employee_id, work_schedule_id, effective_date) VALUES (?, ?, ?)");
    $insert_stmt->execute([$employee_id, $work_schedule_id, $effective_date]);

    // Redirect back to employee list
    header("Location: ../views/employee_list.php"); // Corrected path
    exit;
}
?>

<html>
<head><title>Assign Work Schedule</title></head>
<body>
<h1>Assign Work Schedule to Employee</h1>

<form method="POST" action="">
    <?php if (!$employee_id): ?>
        <label>Employee ID:</label><br>
        <input type="text" name="employee_id" required><br><br>
    <?php else: ?>
        <input type="hidden" name="employee_id" value="<?= htmlspecialchars($employee_id) ?>">
        <p>Assigning to Employee ID: <?= htmlspecialchars($employee_id) ?></p>
    <?php endif; ?>

    <label>Work Schedule:</label><br>
    <select name="work_schedule_id" required>
        <option value="">-- Select Work Schedule --</option>
        <?php foreach($schedules as $sched): ?>
            <option value="<?= $sched['id'] ?>">
                <?= htmlspecialchars($sched['name']) ?> (<?= $sched['time_in'] ?> - <?= $sched['time_out'] ?>)
            </option>
        <?php endforeach; ?>
    </select><br><br>

    <label>Effective Date:</label><br>
    <input type="date" name="effective_date" required><br><br>

    <button type="submit">Assign Schedule</button>
</form>

<p><a href="../views/employee_list.php">← Back to Employee List</a></p>

</body>
</html>
