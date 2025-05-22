<?php
include('../config/db.php');

// Fetch all pending rest day overtime requests with employee names
$stmt = $pdo->query("
    SELECT r.id, r.rest_day_date, r.expected_time_in, r.expected_time_out, r.reason, r.status,
           e.fname, e.lname
    FROM rest_day_overtime_requests r
    JOIN employees e ON r.employee_id = e.id
    WHERE r.status = 'pending'
");

$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Pending Rest Day OT Requests</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-[#f0fdfc] min-h-screen px-4 py-8 flex flex-col items-center">

  <div class="w-full max-w-6xl bg-white shadow-xl rounded-2xl border-t-8 border-[#0fe0fc] p-6 sm:p-8 space-y-6">

    <div class="flex justify-between items-center flex-wrap gap-2">
      <h1 class="text-2xl sm:text-3xl font-bold text-[#2F9C95]">Pending Rest Day Overtime Requests</h1>
      <a href="../views/employee_list.php" class="text-sm sm:text-base text-[#2F9C95] hover:underline">← Back to Employee List</a>
    </div>

    <div class="overflow-x-auto">
      <table class="min-w-full divide-y divide-gray-200 text-sm sm:text-base">
        <thead class="bg-[#0fe0fc] text-white">
          <tr>
            <th class="px-4 py-3 text-left font-semibold">ID</th>
            <th class="px-4 py-3 text-left font-semibold">Employee</th>
            <th class="px-4 py-3 text-left font-semibold">Rest Day</th>
            <th class="px-4 py-3 text-left font-semibold">Expected Time In</th>
            <th class="px-4 py-3 text-left font-semibold">Expected Time Out</th>
            <th class="px-4 py-3 text-left font-semibold">Reason</th>
            <th class="px-4 py-3 text-left font-semibold">Status</th>
            <th class="px-4 py-3 text-left font-semibold">Action</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          <?php if (!empty($requests)): ?>
            <?php foreach ($requests as $req): ?>
              <tr class="hover:bg-gray-50">
                <td class="px-4 py-3"><?= $req['id'] ?></td>
                <td class="px-4 py-3"><?= htmlspecialchars($req['fname'] . ' ' . $req['lname']) ?></td>
                <td class="px-4 py-3"><?= htmlspecialchars($req['rest_day_date']) ?></td>
                <td class="px-4 py-3"><?= htmlspecialchars($req['expected_time_in']) ?></td>
                <td class="px-4 py-3"><?= htmlspecialchars($req['expected_time_out']) ?></td>
                <td class="px-4 py-3"><?= htmlspecialchars($req['reason']) ?></td>
                <td class="px-4 py-3">
                  <span class="inline-block bg-yellow-100 text-yellow-800 text-xs font-medium px-2 py-1 rounded-full">
                    <?= htmlspecialchars(ucfirst($req['status'])) ?>
                  </span>
                </td>
                <td class="px-4 py-3 space-x-2">
                  <a href="../controller/rest_day_overtime_approve.php?id=<?= $req['id'] ?>&action=approve"
                     class="inline-block text-white bg-green-500 hover:bg-green-600 font-medium py-1 px-3 rounded-md text-sm">
                    Approve
                  </a>
                  <a href="../controller/rest_day_overtime_approve.php?id=<?= $req['id'] ?>&action=reject"
                     class="inline-block text-white bg-red-500 hover:bg-red-600 font-medium py-1 px-3 rounded-md text-sm">
                    Reject
                  </a>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr>
              <td colspan="8" class="text-center px-4 py-6 text-gray-500">No pending requests.</td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

  </div>

</body>
</html>

