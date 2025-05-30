<?php
include('../config/db.php');

// Fetch all work schedules
$schedule_stmt = $pdo->query("SELECT * FROM work_schedules");
$schedules = $schedule_stmt->fetchAll(PDO::FETCH_ASSOC);

// ✅ Fetch all employees (moved outside POST block)
$employee_stmt = $pdo->query("SELECT id, fname, lname FROM employees");
$employees = $employee_stmt->fetchAll(PDO::FETCH_ASSOC);

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
    header("Location: ../views/employee_list.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Assign Work Schedule</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-[#f0fdfc] min-h-screen flex items-center justify-center px-4 py-10">

  <div class="w-full max-w-xl bg-white shadow-xl rounded-2xl border-t-8 border-[#0fe0fc] p-6 sm:p-8 space-y-6">
    
    <h1 class="text-2xl sm:text-3xl font-bold text-[#2F9C95] text-center">Assign Work Schedule</h1>

    <form method="POST" action="" class="space-y-5 text-base sm:text-lg">

      <?php if (!$employee_id): ?>
        <div>
          <label class="block text-[#2F9C95] font-semibold mb-1">Select Employee</label>
          <select name="employee_id" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#0fe0fc] focus:outline-none">
            <option value="">-- Select Employee --</option>
            <?php foreach ($employees as $emp): ?>
              <option value="<?= $emp['id'] ?>">
                <?= $emp['id'] ?> - <?= htmlspecialchars($emp['fname'] . ' ' . $emp['lname']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
      <?php else: ?>
        <input type="hidden" name="employee_id" value="<?= htmlspecialchars($employee_id) ?>">
        <p class="text-gray-600 text-sm italic">Assigning to Employee ID: <span class="font-semibold"><?= htmlspecialchars($employee_id) ?></span></p>
      <?php endif; ?>

      <div>
        <label class="block text-[#2F9C95] font-semibold mb-1">Work Schedule</label>
        <select name="work_schedule_id" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#0fe0fc] focus:outline-none">
          <option value="">-- Select Work Schedule --</option>
          <?php foreach ($schedules as $sched): ?>
            <option value="<?= $sched['id'] ?>">
              <?= htmlspecialchars($sched['name']) ?> (<?= $sched['time_in'] ?> - <?= $sched['time_out'] ?>)
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div>
        <label class="block text-[#2F9C95] font-semibold mb-1">Effective Date</label>
        <input type="date" name="effective_date" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#0fe0fc] focus:outline-none" />
      </div>

      <button type="submit" class="w-full bg-[#2F9C95] hover:bg-[#269387] text-white font-semibold py-3 rounded-xl transition duration-200 text-lg">
        Assign Schedule
      </button>

    </form>

    <div class="pt-4 text-center">
      <a href="../views/employee_list.php" class="text-sm text-[#2F9C95] hover:underline">← Back to Employee List</a>
    </div>
    
  </div>

</body>
</html>
