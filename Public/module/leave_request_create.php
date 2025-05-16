<?php
session_start();
include('../config/db.php');

// Ensure employee is logged in
$employee_id = $_SESSION['employee']['id'] ?? null;
if (!$employee_id) {
    header("Location: ../employee/login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $leave_type = $_POST['leave_type'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $reason = $_POST['reason'];

    $stmt = $pdo->prepare("
        INSERT INTO leave_requests (employee_id, leave_type, start_date, end_date, reason, status)
        VALUES (?, ?, ?, ?, ?, 'Pending')
    ");
    $stmt->execute([$employee_id, $leave_type, $start_date, $end_date, $reason]);

    header("Location: ../views/leave_request_list.php");
    exit;
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
          <option value="VL">Vacation Leave</option>
          <option value="SL">Sick Leave</option>
          <option value="Emergency">Emergency</option>
          <option value="Other">Other</option>
        </select>
      </div>

      <!-- Start Date -->
      <div>
        <label class="block text-[#2F9C95] font-semibold mb-1">Start Date:</label>
        <input 
          type="date" 
          name="start_date" 
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
