<?php
// RDOT Filing Page
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include('../config/db.php');
$employee_id = $_SESSION['employee']['id'] ?? null;

// Fetch last 10 time logs for the employee
$time_logs_stmt = $pdo->prepare("SELECT id, log_date, time_in, time_out FROM time_logs WHERE employee_id = ? ORDER BY log_date DESC LIMIT 10");
$time_logs_stmt->execute([$employee_id]);
$time_logs = $time_logs_stmt->fetchAll(PDO::FETCH_ASSOC);

// RDOT filing logic: insert into post_ot_requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selected_log_id = $_POST['selected_log_id'] ?? '';
    $rdot_reason = $_POST['rdot_reason'] ?? '';
    $attachment = $_FILES['attachment'] ?? null;
    $ot_type = 'Restday OT';
    // Get log info from selected time log
    $log_date = '';
    $time_in = '';
    $time_out = '';
    if ($selected_log_id) {
        $log_stmt = $pdo->prepare("SELECT log_date, time_in, time_out FROM time_logs WHERE id = ? AND employee_id = ? LIMIT 1");
        $log_stmt->execute([$selected_log_id, $employee_id]);
        $log_row = $log_stmt->fetch(PDO::FETCH_ASSOC);
        $log_date = $log_row['log_date'] ?? '';
        $time_in = $log_row['time_in'] ?? '';
        $time_out = $log_row['time_out'] ?? '';
    }
    $attachment_filename = '';
    if ($attachment && $attachment['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($attachment['name'], PATHINFO_EXTENSION);
        $attachment_filename = uniqid('rdot_') . '.' . $ext;
        $upload_path = __DIR__ . '/../uploads/overtime_attachments/' . $attachment_filename;
        move_uploaded_file($attachment['tmp_name'], $upload_path);
    }
  if ($log_date && $rdot_reason && $attachment_filename) {
    // Calculate duration in hours (if needed, otherwise set to 0 or blank)
    $ot_duration = 0;
    if ($time_in && $time_out) {
      $start = strtotime($time_in);
      $end = strtotime($time_out);
      if ($end > $start) {
        $ot_duration = round(($end - $start) / 3600, 2);
      }
    }
    $stmt = $pdo->prepare("INSERT INTO post_ot_requests (employee_id, time_log_id, ot_duration, reason, time_in, time_out, ot_type, attachment, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Pending', NOW())");
    $stmt->execute([$employee_id, $selected_log_id, $ot_duration, $rdot_reason, $time_in, $time_out, $ot_type, $attachment_filename]);
        echo '<div class="p-4 bg-green-100 text-green-800 rounded">RDOT request submitted!</div>';
    }
}

// Show RDOT request history for the employee
$stmt = $pdo->prepare("SELECT * FROM post_ot_requests WHERE employee_id = ? AND ot_type = 'Restday OT' ORDER BY created_at DESC LIMIT 10");
$stmt->execute([$employee_id]);
$rdot_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>RDOT Filing</title>
   <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://kit.fontawesome.com/2c7fc25c36.js" crossorigin="anonymous"></script>
    <style>
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @keyframes slideInRight {
            from { opacity: 0; transform: translateX(20px); }
            to { opacity: 1; transform: translateX(0); }
        }
        @keyframes pulse-glow {
            0%, 100% { transform: scale(1); box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3); }
            50% { transform: scale(1.02); box-shadow: 0 8px 25px rgba(16, 185, 129, 0.4); }
        }
        .animate-fade-in-up { animation: fadeInUp 0.6s ease-out; }
        .animate-slide-in-right { animation: slideInRight 0.4s ease-out; }
        .hover-scale { transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
        .hover-scale:hover { transform: scale(1.02) translateY(-2px); box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15); }
        .tab-active { background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; border-color: #10b981; box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3); transform: translateY(-1px); }
        .tab-inactive { background: white; color: #6b7280; border-color: #e5e7eb; transition: all 0.3s ease; }
        .tab-inactive:hover { background: #f9fafb; color: #374151; transform: translateY(-1px); box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1); }
        .status-approved { background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; border-color: #10b981; }
        .status-declined { background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); color: white; border-color: #ef4444; }
        .status-pending { background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color: white; border-color: #f59e0b; }
        .group:hover .group-hover\:scale-105 { transform: scale(1.05); }
        @supports (backdrop-filter: blur(10px)) { .backdrop-blur-sm { backdrop-filter: blur(10px); } }
        @media (max-width: 768px) { .table-responsive { font-size: 0.875rem; } .table-responsive th, .table-responsive td { padding: 0.75rem 0.5rem; } }
        .overflow-x-auto::-webkit-scrollbar { height: 8px; }
        .overflow-x-auto::-webkit-scrollbar-track { background: #f1f5f9; border-radius: 4px; }
        .overflow-x-auto::-webkit-scrollbar-thumb { background: linear-gradient(90deg, #10b981, #059669); border-radius: 4px; }
        .overflow-x-auto::-webkit-scrollbar-thumb:hover { background: linear-gradient(90deg, #059669, #047857); }
    </style>
</head>
<body class="bg-gray-50">
    <div class="max-w-xl mx-auto mt-12 bg-white rounded-2xl shadow-xl overflow-hidden animate-fade-in-up">
      <div class="bg-gradient-to-br from-emerald-600 via-green-600 to-teal-600 px-8 py-6 flex items-center">
        <div class="p-3 bg-white/20 backdrop-blur-sm rounded-xl mr-4">
          <i class="fas fa-calendar-day text-3xl text-white"></i>
        </div>
        <div>
          <h2 class="text-3xl font-bold text-white mb-1">File Rest Day Overtime (RDOT)</h2>
          <p class="text-emerald-100 text-base">Submit and track your RDOT requests</p>
        </div>
      </div>
      <div class="px-8 py-8">
        <form method="POST" enctype="multipart/form-data" class="space-y-6">
          <div>
            <label for="selected_log_id" class="block font-semibold mb-2 text-gray-700">Select Time Log</label>
            <select name="selected_log_id" id="selected_log_id" required class="border border-emerald-300 rounded-lg px-4 py-3 w-full focus:ring-2 focus:ring-emerald-500 bg-white text-emerald-700 text-base">
              <option value="">Choose a time log...</option>
              <?php foreach ($time_logs as $log): ?>
                <option value="<?= $log['id'] ?>">
                  <?= htmlspecialchars($log['log_date']) ?> | In: <?= htmlspecialchars($log['time_in']) ?> | Out: <?= htmlspecialchars($log['time_out']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div>
            <label for="rdot_reason" class="block font-semibold mb-2 text-gray-700">Reason</label>
            <textarea name="rdot_reason" id="rdot_reason" required rows="3" class="border border-emerald-300 rounded-lg px-4 py-3 w-full focus:ring-2 focus:ring-emerald-500 bg-white text-emerald-700 text-base resize-none" placeholder="Please provide a detailed explanation..."></textarea>
          </div>
          <div>
            <label for="attachment" class="block font-semibold mb-2 text-gray-700">Supporting Document <span class="text-red-500">*</span></label>
            <input type="file" name="attachment" id="attachment" accept=".pdf,.jpg,.jpeg,.png" required class="w-full px-4 py-3 border border-emerald-300 rounded-lg focus:ring-2 focus:ring-emerald-500 transition-all file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-medium file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100">
            <p class="text-xs text-gray-500 mt-1">Upload PDF, JPG, or PNG (Max 5MB)</p>
          </div>
          <button type="submit" class="w-full bg-gradient-to-r from-emerald-600 to-green-600 hover:from-emerald-700 hover:to-green-700 text-white font-bold rounded-xl px-6 py-3 transition-all shadow-lg animate-fade-in-up">Submit RDOT Request</button>
        </form>
      </div>
    </div>

    <?php if ($rdot_requests): ?>
    <div class="max-w-xl mx-auto mt-8 bg-white rounded-2xl shadow-xl p-8 animate-fade-in-up">
      <h3 class="text-2xl font-bold mb-6 text-emerald-700 flex items-center">
        <span class="p-2 bg-emerald-100 rounded-full mr-2"><i class="fas fa-history text-emerald-600"></i></span>
        Your RDOT Requests
      </h3>
      <div class="overflow-x-auto">
        <table class="min-w-full border rounded-xl overflow-hidden">
          <thead class="bg-emerald-50">
            <tr>
              <th class="px-6 py-3 border text-left text-emerald-700 text-base">Date</th>
              <th class="px-6 py-3 border text-left text-emerald-700 text-base">Reason</th>
              <th class="px-6 py-3 border text-left text-emerald-700 text-base">Status</th>
              <th class="px-6 py-3 border text-left text-emerald-700 text-base">Attachment</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($rdot_requests as $req): ?>
            <tr class="hover:bg-emerald-50">
              <td class="px-6 py-3 border text-emerald-900 font-semibold"><?= htmlspecialchars(date('Y-m-d', strtotime($req['created_at']))) ?></td>
              <td class="px-6 py-3 border text-emerald-900 font-semibold"><?= htmlspecialchars($req['reason']) ?></td>
              <td class="px-6 py-3 border">
                <?php if ($req['status'] === 'Approved'): ?>
                  <span class="inline-block px-3 py-1 rounded bg-emerald-100 text-emerald-700 font-semibold">Approved</span>
                <?php elseif ($req['status'] === 'Declined' || $req['status'] === 'Rejected'): ?>
                  <span class="inline-block px-3 py-1 rounded bg-red-100 text-red-700 font-semibold">Declined</span>
                <?php else: ?>
                  <span class="inline-block px-3 py-1 rounded bg-yellow-100 text-yellow-700 font-semibold">Pending</span>
                <?php endif; ?>
              </td>
              <td class="px-6 py-3 border">
                <?php if (!empty($req['attachment'])): ?>
                  <a href="../uploads/overtime_attachments/<?= htmlspecialchars($req['attachment']) ?>" target="_blank" class="inline-flex items-center px-3 py-1 bg-emerald-50 text-emerald-700 rounded hover:bg-emerald-100 transition-all">
                    <i class="fas fa-paperclip mr-2"></i>View
                  </a>
                <?php else: ?>
                  <span class="text-gray-400">—</span>
                <?php endif; ?>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
    <?php endif; ?>
</body>
</html>
