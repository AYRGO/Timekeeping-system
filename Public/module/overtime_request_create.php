<?php
session_start();
include('../config/db.php');

// Ensure employee is logged in
$employee_id = $_SESSION['employee']['id'] ?? null;
if (!$employee_id) {
    header("Location: ../employee/login.php");
    exit;
}

// Feedback message
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ot_date = $_POST['ot_date'] ?? null;
    $expected_time_out = $_POST['expected_time_out'] ?? null;
    $reason = $_POST['reason'] ?? null;

    if ($ot_date && $expected_time_out && $reason) {
        $stmt = $pdo->prepare("
            INSERT INTO overtime_requests (employee_id, ot_date, expected_time_out, reason, status)
            VALUES (?, ?, ?, ?, 'pending')
        ");
        $stmt->execute([$employee_id, $ot_date, $expected_time_out, $reason]);

        $message = "Overtime request submitted successfully.";
        $messageType = "success";
    } else {
        $message = "All fields are required.";
        $messageType = "error";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Create Overtime Request</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-[#A3F7B5] min-h-screen flex items-center justify-center px-4 py-8 sm:py-12">

  <div class="w-full max-w-xl bg-white shadow-xl rounded-2xl border-t-8 border-[#40C9A2] p-6 sm:p-8 space-y-6">

    <h2 class="text-2xl sm:text-3xl font-bold text-center text-[#2F9C95]">Overtime Request Form</h2>

    <?php if (!empty($message)): ?>
      <p class="text-center font-medium <?= $messageType === 'success' ? 'text-green-600' : 'text-red-600' ?>">
        <?= htmlspecialchars($message) ?>
      </p>
    <?php endif; ?>

    <form method="POST" class="space-y-5 text-base sm:text-lg">

      <!-- OT Date -->
      <div>
        <label class="block text-[#2F9C95] font-semibold mb-1" for="ot_date">OT Date:</label>
        <input 
          type="date" 
          name="ot_date" 
          id="ot_date"
          required 
          class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#40C9A2]"
        />
      </div>

      <!-- Expected Time Out -->
      <div>
        <label class="block text-[#2F9C95] font-semibold mb-1" for="expected_time_out">Expected Time Out:</label>
        <input 
          type="time" 
          name="expected_time_out" 
          id="expected_time_out"
          required 
          class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#40C9A2]"
        />
      </div>

      <!-- Reason -->
      <div>
        <label class="block text-[#2F9C95] font-semibold mb-1" for="reason">Reason:</label>
        <textarea 
          name="reason" 
          id="reason"
          rows="4" 
          required 
          class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#40C9A2]"
        ></textarea>
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
      <a href="time_log_create.php" class="text-[#2F9C95] font-medium underline hover:text-[#40C9A2]">
        ← Back to timekeeping
      </a>
    </p>

  </div>

</body>
</html>
