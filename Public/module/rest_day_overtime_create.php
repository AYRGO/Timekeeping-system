<?php
session_start();
include('../config/db.php');

// Ensure employee is logged in
$employee_id = $_SESSION['employee']['id'] ?? null;
if (!$employee_id) {
    header("Location: ../employee/login.php");
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rest_day_date = $_POST['rest_day_date'] ?? null;
    $expected_time_in = $_POST['expected_time_in'] ?? null;
    $expected_time_out = $_POST['expected_time_out'] ?? null;
    $reason = $_POST['reason'] ?? null;

    if ($rest_day_date && $expected_time_in && $expected_time_out && $reason) {
        $stmt = $pdo->prepare("
            INSERT INTO rest_day_overtime_requests 
            (employee_id, rest_day_date, expected_time_in, expected_time_out, reason, status, created_at) 
            VALUES (?, ?, ?, ?, ?, 'pending', NOW())
        ");
        $stmt->execute([$employee_id, $rest_day_date, $expected_time_in, $expected_time_out, $reason]);

        echo "<p style='color: green;'>Request submitted successfully!</p>";
    } else {
        echo "<p style='color: red;'>All fields are required.</p>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Submit Rest Day Overtime Request</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-[#A3F7B5] min-h-screen flex items-center justify-center px-4 py-8 sm:py-12">

  <div class="w-full max-w-xl bg-white shadow-xl rounded-2xl border-t-8 border-[#40C9A2] p-6 sm:p-8 space-y-6">

    <h1 class="text-2xl sm:text-3xl font-bold text-center text-[#2F9C95]">Rest Day Overtime Request</h1>

    <form method="POST" class="space-y-5 text-base sm:text-lg">

      <!-- Hidden Employee ID -->
      <input type="hidden" name="employee_id" value="<?= htmlspecialchars($employee_id) ?>">

      <!-- Rest Day Date -->
      <div>
        <label class="block text-[#2F9C95] font-semibold mb-1">Rest Day Date:</label>
        <input 
          type="date" 
          name="rest_day_date" 
          required 
          class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#40C9A2]"
        />
      </div>

      <!-- Expected Time In -->
      <div>
        <label class="block text-[#2F9C95] font-semibold mb-1">Expected Time In:</label>
        <input 
          type="time" 
          name="expected_time_in" 
          required 
          class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#40C9A2]"
        />
      </div>

      <!-- Expected Time Out -->
      <div>
        <label class="block text-[#2F9C95] font-semibold mb-1">Expected Time Out:</label>
        <input 
          type="time" 
          name="expected_time_out" 
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

    <!-- back -->
    <p class="text-center mt-4">
      <a href="time_log_create.php" class="text-[#2F9C95] font-medium underline hover:text-[#40C9A2]">
        ← Back to timekeeping
      </a>
    </p>

  </div>

</body>
</html>
