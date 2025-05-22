<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Pending Overtime Requests</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-[#f0fdfc] min-h-screen px-4 py-8 flex flex-col items-center">

  <div class="w-full max-w-6xl bg-white shadow-xl rounded-2xl border-t-8 border-[#0fe0fc] p-6 sm:p-8 space-y-6">

    <div class="flex justify-between items-center flex-wrap gap-2">
      <h1 class="text-2xl sm:text-3xl font-bold text-[#2F9C95]">Pending Overtime Requests</h1>
      <a href="../views/employee_list.php" class="text-sm sm:text-base text-[#2F9C95] hover:underline">← Back to Employee List</a>
    </div>

    <div class="overflow-x-auto">
      <table class="min-w-full divide-y divide-gray-200 text-sm sm:text-base">
        <thead class="bg-[#0fe0fc] text-white">
          <tr>
            <th class="px-4 py-3 text-left font-semibold">ID</th>
            <th class="px-4 py-3 text-left font-semibold">Employee</th>
            <th class="px-4 py-3 text-left font-semibold">Date</th>
            <th class="px-4 py-3 text-left font-semibold">Expected Time Out</th>
            <th class="px-4 py-3 text-left font-semibold">Reason</th>
            <th class="px-4 py-3 text-left font-semibold">Status</th>
            <th class="px-4 py-3 text-left font-semibold">Action</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          <?php if (!empty($overtime_requests)): ?>
            <?php foreach ($overtime_requests as $ot): ?>
              <tr class="hover:bg-gray-50">
                <td class="px-4 py-3"><?= $ot['id'] ?></td>
                <td class="px-4 py-3"><?= htmlspecialchars($ot['fname'] . ' ' . $ot['lname']) ?></td>
                <td class="px-4 py-3"><?= htmlspecialchars($ot['ot_date']) ?></td>
                <td class="px-4 py-3"><?= htmlspecialchars($ot['expected_time_out']) ?></td>
                <td class="px-4 py-3"><?= htmlspecialchars($ot['reason']) ?></td>
                <td class="px-4 py-3">
                  <span class="inline-block bg-yellow-100 text-yellow-800 text-xs font-medium px-2 py-1 rounded-full">
                    <?= htmlspecialchars(ucfirst($ot['status'])) ?>
                  </span>
                </td>
                <td class="px-4 py-3 space-x-2">
                  <a href="../controller/overtime_request_approve.php?id=<?= $ot['id'] ?>&action=approve"
                     class="inline-block text-white bg-green-500 hover:bg-green-600 font-medium py-1 px-3 rounded-md text-sm">
                    Approve
                  </a>
                  <a href="../controller/overtime_request_approve.php?id=<?= $ot['id'] ?>&action=reject"
                     class="inline-block text-white bg-red-500 hover:bg-red-600 font-medium py-1 px-3 rounded-md text-sm">
                    Reject
                  </a>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr>
              <td colspan="7" class="text-center px-4 py-6 text-gray-500">No pending overtime requests found.</td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

  </div>

</body>
</html>
