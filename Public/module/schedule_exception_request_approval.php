<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
include('../config/db.php');

// Fetch all pending requests
$stmt = $pdo->query("
    SELECT ser.id, ser.employee_id, e.fname, e.lname, ser.exception_date, ser.requested_time_in, ser.requested_time_out, ser.reason, ser.status
    FROM schedule_exception_requests ser
    JOIN employees e ON ser.employee_id = e.id
    ORDER BY ser.created_at DESC
");

$requests = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Schedule Exception Request Approval</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-[#f0fdfc] min-h-screen px-4 py-8 flex flex-col items-center">

  <div class="w-full max-w-6xl bg-white shadow-xl rounded-2xl border-t-8 border-[#0fe0fc] p-6 sm:p-8 space-y-6">

    <div class="flex justify-between items-center flex-wrap gap-2">
      <h2 class="text-2xl sm:text-3xl font-bold text-[#2F9C95]">Schedule Exception Request Approvals</h2>
      <a href="../views/employee_list.php" class="text-sm sm:text-base text-[#2F9C95] hover:underline">← Back to Employee List</a>
    </div>

    <div class="overflow-x-auto">
      <table class="min-w-full divide-y divide-gray-200 text-sm sm:text-base">
        <thead class="bg-[#0fe0fc] text-white">
          <tr>
            <th class="px-4 py-3 text-left font-semibold">ID</th>
            <th class="px-4 py-3 text-left font-semibold">Employee</th>
            <th class="px-4 py-3 text-left font-semibold">Exception Date</th>
            <th class="px-4 py-3 text-left font-semibold">Time In</th>
            <th class="px-4 py-3 text-left font-semibold">Time Out</th>
            <th class="px-4 py-3 text-left font-semibold">Reason</th>
            <th class="px-4 py-3 text-left font-semibold">Status</th>
            <th class="px-4 py-3 text-left font-semibold">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          <?php if ($requests): ?>
            <?php foreach ($requests as $req): ?>
              <tr class="hover:bg-gray-50">
                <td class="px-4 py-3"><?= $req['id'] ?></td>
                <td class="px-4 py-3"><?= htmlspecialchars($req['fname'] . ' ' . $req['lname']) ?></td>
                <td class="px-4 py-3"><?= htmlspecialchars($req['exception_date']) ?></td>
                <td class="px-4 py-3"><?= htmlspecialchars($req['requested_time_in']) ?></td>
                <td class="px-4 py-3"><?= htmlspecialchars($req['requested_time_out']) ?></td>
                <td class="px-4 py-3"><?= htmlspecialchars($req['reason']) ?></td>
                <td class="px-4 py-3">
                  <span class="inline-block px-2 py-1 rounded-full text-xs font-medium
                    <?= $req['status'] === 'pending' ? 'bg-yellow-100 text-yellow-800' :
                       ($req['status'] === 'approved' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800') ?>">
                    <?= ucfirst($req['status']) ?>
                  </span>
                </td>
                <td class="px-4 py-3 space-x-2">
                  <?php if ($req['status'] === 'pending'): ?>
                    <a href="../controller/schedule_exception_request_approve.php?id=<?= $req['id'] ?>&action=approve"
                       class="inline-block text-white bg-green-500 hover:bg-green-600 font-medium py-1 px-3 rounded-md text-sm">
                      Approve
                    </a>
                    <a href="../controller/schedule_exception_request_approve.php?id=<?= $req['id'] ?>&action=reject"
                       class="inline-block text-white bg-red-500 hover:bg-red-600 font-medium py-1 px-3 rounded-md text-sm">
                      Reject
                    </a>
                  <?php else: ?>
                    <span class="text-gray-500 italic">No action</span>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr>
              <td colspan="8" class="text-center px-4 py-6 text-gray-500">No schedule exception requests found.</td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

  </div>

</body>
</html>

