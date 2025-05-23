<?php
session_start();

// Prevent browser from caching this page
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

if (!isset($_SESSION['admin'])) {
    header("Location: ../admin/login.php");
    exit;
}

include('../config/db.php');
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Check if form is submitted via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the ID and action from the form data
    $request_id = $_POST['id'] ?? null;
    $action = $_POST['action'] ?? null;

    // Ensure both 'id' and 'action' are provided
    if ($request_id && in_array($action, ['approve', 'reject'])) {
        // Prepare the status based on the action
        $status = ($action === 'approve') ? 'approved' : 'rejected';

        // Update the leave request status in the database
        $stmt = $pdo->prepare("UPDATE leave_requests SET status = ? WHERE id = ?");
        $stmt->execute([$status, $request_id]);

        // Redirect back to the employee list page after the update
        header('Location: ../views/employee_list.php');
        exit;  // Make sure script stops after redirect
    } else {
        echo "Error: Missing 'id' or 'action' in form submission.";
        exit;
    }
}

// Fetch pending leave requests with employee details (fname, lname)
$stmt = $pdo->query("
    SELECT lr.id, lr.leave_type, lr.start_date, lr.end_date, lr.reason, lr.status,
           e.fname, e.lname
    FROM leave_requests lr
    JOIN employees e ON lr.employee_id = e.id
    WHERE lr.status = 'pending'
");

$leave_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

// // Debug: Check if leave requests are fetched correctly
// if (empty($leave_requests)) {
//     echo "No pending leave requests found.";
//     // exit;
// }
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Pending Leave Requests</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-[#f0fdfc] min-h-screen px-4 py-8 flex flex-col items-center">

  <div class="w-full max-w-6xl bg-white shadow-xl rounded-2xl border-t-8 border-[#0fe0fc] p-6 sm:p-8 space-y-6">

    <div class="flex justify-between items-center flex-wrap gap-2">
      <h1 class="text-2xl sm:text-3xl font-bold text-[#2F9C95]">Pending Leave Requests</h1>
      <a href="../views/employee_list.php" class="text-sm sm:text-base text-[#2F9C95] hover:underline">← Back to Employee List</a>
    </div>

    <div class="overflow-x-auto">
      <table class="min-w-full divide-y divide-gray-200 text-sm sm:text-base">
        <thead class="bg-[#0fe0fc] text-white">
          <tr>
            <th class="px-4 py-3 text-left font-semibold">ID</th>
            <th class="px-4 py-3 text-left font-semibold">Employee</th>
            <th class="px-4 py-3 text-left font-semibold">Leave Type</th>
            <th class="px-4 py-3 text-left font-semibold">Start Date</th>
            <th class="px-4 py-3 text-left font-semibold">End Date</th>
            <th class="px-4 py-3 text-left font-semibold">Reason</th>
            <th class="px-4 py-3 text-left font-semibold">Status</th>
            <th class="px-4 py-3 text-left font-semibold">Action</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          <?php if (!empty($leave_requests)): ?>
            <?php foreach ($leave_requests as $lr): ?>
              <tr class="hover:bg-gray-50">
                <td class="px-4 py-3"><?= $lr['id'] ?></td>
                <td class="px-4 py-3"><?= htmlspecialchars($lr['fname'] . ' ' . $lr['lname']) ?></td>
                <td class="px-4 py-3"><?= htmlspecialchars($lr['leave_type']) ?></td>
                <td class="px-4 py-3"><?= htmlspecialchars($lr['start_date']) ?></td>
                <td class="px-4 py-3"><?= htmlspecialchars($lr['end_date']) ?></td>
                <td class="px-4 py-3"><?= htmlspecialchars($lr['reason']) ?></td>
                <td class="px-4 py-3">
                  <span class="inline-block bg-yellow-100 text-yellow-800 text-xs font-medium px-2 py-1 rounded-full">
                    <?= htmlspecialchars(ucfirst($lr['status'])) ?>
                  </span>
                </td>
                <td class="px-4 py-3 space-x-2">
                  <form action="../controller/leave_request_approve.php" method="POST" class="inline">
                    <input type="hidden" name="id" value="<?= $lr['id'] ?>">
                    <input type="hidden" name="action" value="approve">
                    <button type="submit" class="bg-green-500 hover:bg-green-600 text-white text-sm px-3 py-1 rounded-md font-medium">
                      Approve
                    </button>
                  </form>

                  <form action="../controller/leave_request_approve.php" method="POST" class="inline">
                    <input type="hidden" name="id" value="<?= $lr['id'] ?>">
                    <input type="hidden" name="action" value="reject">
                    <button type="submit" class="bg-red-500 hover:bg-red-600 text-white text-sm px-3 py-1 rounded-md font-medium">
                      Reject
                    </button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr>
              <td colspan="8" class="text-center px-4 py-6 text-gray-500">No pending leave requests found.</td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

  </div>

</body>
</html>
