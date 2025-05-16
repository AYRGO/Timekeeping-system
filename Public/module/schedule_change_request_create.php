<?php
session_start();
include('../config/db.php');

// Ensure employee is logged in
$employee_id = $_SESSION['employee']['id'] ?? null;
if (!$employee_id) {
    header("Location: ../employee/login.php");
    exit;
}

// Fetch available work schedules
$schedules = $pdo->query("SELECT id, time_in, time_out FROM work_schedules")->fetchAll(PDO::FETCH_ASSOC);

// Fetch employee name
$employee = $pdo->prepare("SELECT fname, lname FROM employees WHERE id = ?");
$employee->execute([$employee_id]);
$emp = $employee->fetch();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Schedule Change Request</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-[#A3F7B5] min-h-screen flex items-center justify-center px-4 py-8 sm:py-12">

  <div class="w-full max-w-xl bg-white shadow-xl rounded-2xl border-t-8 border-[#40C9A2] p-6 sm:p-8 space-y-6">

    <h1 class="text-2xl sm:text-3xl font-bold text-center text-[#2F9C95] mb-4">Schedule Change Request</h1>

    <form action="../controller/schedule_change_request_store.php" method="POST" class="space-y-5 text-base sm:text-lg">

      <!-- Employee Name (Disabled) -->
      <div>
        <label class="block text-[#2F9C95] font-semibold mb-1">Employee:</label>
        <input 
          type="text" 
          value="<?= htmlspecialchars($emp['fname'] . ' ' . $emp['lname']) ?>" 
          disabled 
          class="w-full px-4 py-3 border border-gray-300 rounded-lg bg-gray-100 text-gray-700"
        />
        <input type="hidden" name="employee_id" value="<?= $employee_id ?>">
      </div>

      <!-- Schedule Dropdown -->
<div>
  <label class="block text-[#2F9C95] font-semibold mb-1">New Schedule:</label>
  <select 
    name="requested_schedule_id" 
    required 
    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#40C9A2]"
  >
    <option value="">-- Select Schedule --</option>
    <?php foreach ($schedules as $sched): ?>
      <?php
        $timeIn = date('g:i A', strtotime($sched['time_in']));
        $timeOut = date('g:i A', strtotime($sched['time_out']));
      ?>
      <option value="<?= $sched['id'] ?>">
        <?= htmlspecialchars("$timeIn - $timeOut") ?>
      </option>
    <?php endforeach; ?>
  </select>
</div>

      <!-- Effective Date -->
      <div>
        <label class="block text-[#2F9C95] font-semibold mb-1">Effective Date:</label>
        <input 
          type="date" 
          name="requested_effective_date" 
          required 
          class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#40C9A2]"
        />
      </div>

      <!-- Reason -->
      <div>
        <label class="block text-[#2F9C95] font-semibold mb-1">Reason:</label>
        <textarea 
          name="reason" 
          rows="4" 
          required 
          class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#40C9A2]"
        ></textarea>
      </div>

      <!-- Submit Button -->
      <button 
        type="submit" 
        class="w-full bg-[#40C9A2] hover:bg-[#2F9C95] text-white font-semibold py-3 rounded-xl transition duration-200 text-lg"
      >
        Submit Request
      </button>

    </form>
  </div>

</body>
</html>
