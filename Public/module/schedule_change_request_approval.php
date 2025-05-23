<?php
session_start();

// Prevent browser from caching this page
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Admin session check
if (!isset($_SESSION['admin'])) {
    header("Location: ../admin/login.php");
    exit;
}

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include('../config/db.php');

// Fetch pending schedule change requests
$stmt = $pdo->query("
    SELECT scr.id, scr.employee_id, scr.requested_effective_date, scr.requested_schedule_id, scr.reason, scr.status, 
           e.fname, e.lname, ws.name AS schedule_name
    FROM schedule_change_requests scr
    JOIN employees e ON scr.employee_id = e.id
    JOIN work_schedules ws ON scr.requested_schedule_id = ws.id
    WHERE scr.status = 'pending'
");

$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Schedule Change Requests</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-[#f0fdfc] min-h-screen px-4 py-8 flex flex-col items-center">

  <div class="w-full max-w-6xl bg-white shadow-xl rounded-2xl border-t-8 border-[#0fe0fc] p-6 sm:p-8 space-y-6">

    <div class="flex justify-between items-center flex-wrap gap-2">
      <h1 class="text-2xl sm:text-3xl font-bold text-[#2F9C95]">Pending Schedule Change Requests</h1>
      <a href="../views/employee_list.php" class="text-sm sm:text-base text-[#2F9C95] hover:underline">← Back to Employee List</a>
    </div>

    <div class="overflow-x-auto">
      <table class="min-w-full divide-y divide-gray-200 text-sm sm:text-base">
        <thead class="bg-[#0fe0fc] text-white">
          <tr>
            <th class="px-4 py-3 text-left font-semibold">ID</th>
            <th class="px-4 py-3 text-left font-semibold">Employee</th>
            <th class="px-4 py-3 text-left font-semibold">Requested Schedule</th>
            <th class="px-4 py-3 text-left font-semibold">Effective Date</th>
            <th class="px-4 py-3 text-left font-semibold">Reason</th>
            <th class="px-4 py-3 text-left font-semibold">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          <?php if (count($requests) > 0): ?>
            <?php foreach ($requests as $req): ?>
              <tr class="hover:bg-gray-50">
                <td class="px-4 py-3"><?= $req['id'] ?></td>
                <td class="px-4 py-3"><?= htmlspecialchars($req['fname'] . ' ' . $req['lname']) ?></td>
                <td class="px-4 py-3"><?= htmlspecialchars($req['schedule_name']) ?></td>
                <td class="px-4 py-3"><?= htmlspecialchars($req['requested_effective_date']) ?></td>
                <td class="px-4 py-3"><?= htmlspecialchars($req['reason']) ?></td>
                <td class="px-4 py-3 space-x-2">
                  <a href="../controller/schedule_change_request_approve.php?id=<?= $req['id'] ?>&action=approve"
                     class="inline-block text-white bg-green-500 hover:bg-green-600 font-medium py-1 px-3 rounded-md text-sm">
                    Approve
                  </a>
                  <a href="../controller/schedule_change_request_approve.php?id=<?= $req['id'] ?>&action=reject"
                     class="inline-block text-white bg-red-500 hover:bg-red-600 font-medium py-1 px-3 rounded-md text-sm">
                    Reject
                  </a>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr>
              <td colspan="6" class="text-center px-4 py-6 text-gray-500">No pending requests.</td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

  </div>

</body>
</html>
