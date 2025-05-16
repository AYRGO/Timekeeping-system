<?php
session_start();
include('../config/db.php');

$employee_id = $_SESSION['employee']['id'] ?? null;
if (!$employee_id) {
    header("Location: ../employee/login.php");
    exit;
}

$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $leave_type = $_POST['leave_type'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $reason = $_POST['reason'];

    // Calculate requested days (inclusive)
    $start = new DateTime($start_date);
    $end = new DateTime($end_date);
    $diff = $start->diff($end);
    $requested_days = $diff->days + 1;

    // Leave types that use credits
    $valid_leave_types = ['VL', 'SL', 'SPL', 'Half_SL', 'Half_VL'];

    if (in_array($leave_type, $valid_leave_types)) {
        $stmt = $pdo->prepare("SELECT balance FROM leave_credits WHERE employee_id = ? AND leave_type = ? AND year = ?");
        $stmt->execute([$employee_id, $leave_type, date('Y')]);
        $credit = $stmt->fetchColumn();

        if ($credit === false || $credit < $requested_days) {
            $error_message = "You do not have enough leave credits for this request.";
        }
    }

    if (empty($error_message)) {
    $stmt = $pdo->prepare("
        INSERT INTO leave_requests (employee_id, leave_type, start_date, end_date, reason, status)
        VALUES (?, ?, ?, ?, ?, 'Pending')
    ");
    $stmt->execute([$employee_id, $leave_type, $start_date, $end_date, $reason]);

    // Deduct leave credits
    if (in_array($leave_type, $valid_leave_types)) {
        $deduction = $requested_days;

        // Special case for half-day types
        if ($leave_type === 'Half_SL' || $leave_type === 'Half_VL') {
            $deduction = 0.5 * $requested_days;
        } elseif ($leave_type === 'SPL') {
            $deduction = $requested_days; // SPL fixed limit, but still uses credits
        }

        $deduct = $pdo->prepare("
            UPDATE leave_credits 
            SET balance = balance - ? 
            WHERE employee_id = ? AND leave_type = ? AND year = ?
        ");
        $deduct->execute([$deduction, $employee_id, $leave_type, date('Y')]);
    }

    header("Location: ../views/leave_request_list.php");
    exit;
}
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Create Leave Request</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-[#A3F7B5] min-h-screen flex items-center justify-center px-4 py-8 sm:py-12">

  <div class="w-full max-w-xl bg-white shadow-xl rounded-2xl border-t-8 border-[#40C9A2] p-6 sm:p-8 space-y-6">

    <h1 class="text-2xl sm:text-3xl font-bold text-center text-[#2F9C95]">Leave Request Form</h1>

    <?php if (!empty($error_message)): ?>
      <div class="mb-4 p-4 text-red-700 bg-red-100 rounded">
        <?= htmlspecialchars($error_message) ?>
      </div>
    <?php endif; ?>

    <form method="POST" class="space-y-5 text-base sm:text-lg">

      <!-- Leave Type -->
      <div>
        <label class="block text-[#2F9C95] font-semibold mb-1">Leave Type:</label>
        <select 
          name="leave_type" 
          required 
          class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#40C9A2]"
        >
          <option value="">-- Select Type --</option>
          <option value="VL" <?= (isset($leave_type) && $leave_type == 'VL') ? 'selected' : '' ?>>Vacation Leave</option>
          <option value="SL" <?= (isset($leave_type) && $leave_type == 'SL') ? 'selected' : '' ?>>Sick Leave</option>
          <option value="SPL" <?= (isset($leave_type) && $leave_type == 'SPL') ? 'selected' : '' ?>>Solo Parent Leave</option>
          <option value="Half_SL" <?= (isset($leave_type) && $leave_type == 'Half_SL') ? 'selected' : '' ?>>Half Day Sick Leave</option>
          <option value="Half_VL" <?= (isset($leave_type) && $leave_type == 'Half_VL') ? 'selected' : '' ?>>Half Day Vacation Leave</option>
          <option value="Maternity" <?= (isset($leave_type) && $leave_type == 'Maternity') ? 'selected' : '' ?>>Maternity Leave</option>
          <option value="Paternity" <?= (isset($leave_type) && $leave_type == 'Paternity') ? 'selected' : '' ?>>Paternity Leave</option>
          <option value="LWOP" <?= (isset($leave_type) && $leave_type == 'LWOP') ? 'selected' : '' ?>>Leave Without Pay</option>
        </select>
      </div>

      <!-- Start Date -->
      <div>
        <label class="block text-[#2F9C95] font-semibold mb-1">Start Date:</label>
        <input 
          type="date" 
          name="start_date" 
          value="<?= htmlspecialchars($start_date ?? '') ?>" 
          required 
          class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#40C9A2]"
        />
      </div>

      <!-- End Date -->
      <div>
        <label class="block text-[#2F9C95] font-semibold mb-1">End Date:</label>
        <input 
          type="date" 
          name="end_date" 
          value="<?= htmlspecialchars($end_date ?? '') ?>" 
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
        ><?= htmlspecialchars($reason ?? '') ?></textarea>
      </div>

      <!-- Submit -->
      <button 
        type="submit" 
        class="w-full bg-[#40C9A2] hover:bg-[#2F9C95] text-white font-semibold py-3 rounded-xl transition duration-200 text-lg"
      >
        Submit Request
      </button>

    </form>

    <p class="text-center mt-4">
      <a href="../views/leave_request_list.php" class="text-[#2F9C95] font-medium underline hover:text-[#40C9A2]">
        ← Back to Leave List
      </a>
    </p>

  </div>

</body>
</html>
