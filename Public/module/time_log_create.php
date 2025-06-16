  <?php
  session_start();
  include('../config/db.php');
  date_default_timezone_set('Asia/Manila');

  if (!isset($_SESSION['regenerated'])) {
      session_regenerate_id(true);
      $_SESSION['regenerated'] = true;
  }

  $employee_id = $_SESSION['employee']['id'] ?? null;
  if (!$employee_id) {
      header("Location: ../employee/login.php");
      exit;
  }

  if (empty($_SESSION['csrf_token'])) {
      $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
  }

  try {
      $stmt = $pdo->prepare("SELECT fname, lname, email, contact, position, company, profile_picture FROM employees WHERE id = ?");
      $stmt->execute([$employee_id]);
      $user = $stmt->fetch();

      $fname = $user['fname'] ?? '';
      $lname = $user['lname'] ?? '';
      $email = $user['email'] ?? '';
      $contact = $user['contact'] ?? '';
      $position = $user['position'] ?? '';
      $company = $user['company'] ?? '';
      $profile_picture = $user['profile_picture'] ?? null;

      $current_date = date("Y-m-d");
      $stmt = $pdo->prepare("SELECT time_in, time_out FROM time_logs WHERE employee_id = ? AND log_date = ?");
      $stmt->execute([$employee_id, $current_date]);
      $time_log = $stmt->fetch();
      $time_in = $time_log['time_in'] ?? null;
      $time_out = $time_log['time_out'] ?? null;

      $can_time_in = true;

      if ($_SERVER['REQUEST_METHOD'] === 'POST') {
          if (isset($_POST['time_in']) && hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
              $stmt = $pdo->prepare("INSERT INTO time_logs (employee_id, log_date, time_in) VALUES (?, ?, ?)");
              $stmt->execute([$employee_id, $current_date, date("H:i:s")]);
              header("Location: time_log_create.php");
              exit;
          }

          if (isset($_POST['time_out']) && hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
              $stmt = $pdo->prepare("UPDATE time_logs SET time_out = ? WHERE employee_id = ? AND log_date = ?");
              $stmt->execute([date("H:i:s"), $employee_id, $current_date]);
              header("Location: time_log_create.php");
              exit;
          }
      }
  } catch (Exception $e) {
      die("Something went wrong.");
  }
  ?>
  <!DOCTYPE html>
  <html lang="en">
  <head>
    <meta charset="UTF-8">
    <title>RSS Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <style>
      body { font-family: 'Poppins', sans-serif; background: #F4F6FA; overflow-x: hidden; }
      .sidebar { background-color:rgb(24, 136, 84); }
      .sidebar a { color: #ffffffb3; }
      .sidebar a.active, .sidebar a:hover { color: #ffffff; }
      .profile-img:hover .overlay { opacity: 1; }
      .overlay { transition: opacity 0.3s; opacity: 0; }
      .fixed-box { max-height: 400px; overflow-y: auto; }
    </style>
  </head>
  <body class="flex min-h-screen overflow-x-hidden">

  <!-- Sidebar -->
  <aside class="sidebar w-64 p-6 flex flex-col">
    <div class="mb-10 text-center">
      <img src="../asset/RSS-logo-colour.png" alt="RSS Logo" class="w-24 mx-auto mb-2">
    </div>
    <nav class="flex-1 space-y-2 text-sm">
      <a href="#" onclick="showSection('dashboardView')" class="flex items-center space-x-2 active">
        <span>📋</span><span>Dashboard (Time In/Out)</span>
      </a>
      <a href="#" onclick="showSection('requestView')" class="flex items-center space-x-2">
        <span>📝</span><span>Request Leave</span>
      </a>
    </nav>
    <div class="border-t border-white mt-6 pt-4">
      <a href="#" class="flex items-center space-x-2">
        <span>⚙️</span><span>Settings</span>
      </a>
      <a href="../employee/logout.php" class="flex items-center space-x-2 mt-2 text-red-400 hover:text-red-200">
        <span>🚪</span><span>Logout</span>
      </a>
    </div>
  </aside>

  <!-- Main Content -->
  <main class="flex-1 p-10 overflow-auto">
    <div id="dashboardView">
      <div class="flex justify-between items-start mb-4 flex-col md:flex-row">
        <div class="mb-2">
          <h1 class="text-3xl font-bold">Welcome back, <?= htmlspecialchars($fname) ?> 👋</h1>
          <p class="text-gray-500 text-sm mb-1">Here’s your time log and profile summary.</p>
          <div class="flex items-center space-x-3 mt-4">
            <span class="text-4xl md:text-5xl font-mono font-bold text-green-700" id="clock">--:--:--</span>
          </div>
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Profile -->
        <div class="bg-white p-6 rounded-lg shadow space-y-4 fixed-box">
          <div class="relative w-24 h-24 mx-auto rounded-full overflow-hidden border-4 border-green-600 profile-img">
            <?php if ($profile_picture): ?>
              <img src="../uploads/profile_images/<?= htmlspecialchars($profile_picture) ?>" class="w-full h-full object-cover">
            <?php else: ?>
              <div class="w-full h-full bg-gray-300 flex items-center justify-center text-4xl text-white">👤</div>
            <?php endif; ?>
            <div class="absolute inset-0 bg-black bg-opacity-50 text-white flex items-center justify-center overlay cursor-pointer" onclick="document.getElementById('fileInput').click()">Change</div>
            <form action="upload_profile.php" method="POST" enctype="multipart/form-data">
              <input type="file" id="fileInput" name="profile_picture" class="hidden" onchange="this.form.submit()">
            </form>
          </div>
          <div class="text-center">
            <h2 class="text-xl font-semibold"><?= htmlspecialchars($fname . ' ' . $lname) ?></h2>
            <p class="text-gray-500"><?= htmlspecialchars($position) ?> @ <?= htmlspecialchars($company) ?></p>
          </div>
          <div class="text-sm text-gray-600 space-y-1">
            <p><strong>Email:</strong> <?= htmlspecialchars($email) ?></p>
            <p><strong>Contact:</strong> <?= htmlspecialchars($contact) ?></p>
          </div>
        </div>

        <!-- Time Log -->
        <div class="bg-white p-6 rounded-lg shadow space-y-4 col-span-2 fixed-box">
          <h2 class="text-lg font-semibold">Today’s Time Log</h2>
          <div class="grid grid-cols-2 gap-4 text-center">
            <div class="bg-green-100 p-4 rounded">
              <p class="text-gray-500">Time In</p>
              <p class="text-2xl font-bold"><?= $time_in ? date("h:i A", strtotime($time_in)) : '—'; ?></p>
            </div>
            <div class="bg-yellow-100 p-4 rounded">
              <p class="text-gray-500">Time Out</p>
              <p class="text-2xl font-bold"><?= $time_out ? date("h:i A", strtotime($time_out)) : '—'; ?></p>
            </div>
          </div>
          <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <?php if (!$time_in && $can_time_in): ?>
              <button type="submit" name="time_in" class="w-full mt-4 bg-green-600 text-white py-2 rounded hover:bg-green-700">Log Time In</button>
            <?php elseif ($time_in && !$time_out): ?>
              <button type="submit" name="time_out" class="w-full mt-4 bg-yellow-600 text-white py-2 rounded hover:bg-yellow-700">Log Time Out</button>
            <?php else: ?>
              <button type="button" disabled class="w-full mt-4 bg-gray-400 text-white py-2 rounded">Already Logged</button>
            <?php endif; ?>
          </form>
        </div>
      </div>
    </div>


    <!-- Request Leave View -->
    <div id="requestView" class="max-w-lg mx-auto mt-10 bg-white p-6 rounded shadow hidden">
      <h2 class="text-xl font-semibold mb-4">Request Leave</h2>
      <label class="block text-sm font-medium mb-1">Leave Type</label>
      <select id="leaveTypeDropdown" class="w-full border p-2 rounded mb-4" required onchange="openLeaveModal()">
        <option value="" disabled selected>Select type</option>
        <option value="sick">Sick Leave</option>
        <option value="vacation">Vacation Leave</option>
        <option value="maternity">Maternity Leave</option>
        <option value="paternity">Paternity Leave</option>
        <option value="solo_parent">Solo Parent Leave</option>
        <option value="halfday">Halfday Leave</option>
        <option value="halfday_sick">Halfday Sick Leave</option>
      </select>
    </div>
  </main>

  <!-- Leave Modal -->
  <div id="leaveModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex justify-center items-center z-50">
    <form method="POST" action="submit_leave.php" class="bg-white text-gray-900 p-6 rounded-lg shadow-lg w-full max-w-md space-y-4 relative">
      <h2 class="text-xl font-bold">Request Leave</h2>
      <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
      <input type="hidden" name="leave_type" id="hiddenLeaveType">
      <label class="block text-sm font-medium">Start Date</label>
      <input type="date" name="start_date" class="w-full p-2 border border-gray-300 rounded" required>
      <label class="block text-sm font-medium">End Date</label>
      <input type="date" name="end_date" class="w-full p-2 border border-gray-300 rounded" required>
      <label class="block text-sm font-medium">Reason</label>
      <textarea name="reason" placeholder="Optional reason..." class="w-full p-2 border border-gray-300 rounded-md"></textarea>

      <div class="flex justify-end gap-2 pt-4">
        <button type="button" onclick="closeLeaveModal()" class="px-4 py-2 bg-gray-300 rounded hover:bg-gray-400">Cancel</button>
        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Submit</button>
      </div>
    </form>
  </div>

  <!-- JS -->
  <script>
  function updateClock() {
    const now = new Date();
    let h = now.getHours(), m = now.getMinutes(), s = now.getSeconds();
    const ampm = h >= 12 ? 'PM' : 'AM';
    h = h % 12 || 12;
    document.getElementById('clock').textContent =
      String(h).padStart(2, '0') + ':' + String(m).padStart(2, '0') + ':' + String(s).padStart(2, '0') + ' ' + ampm;
  }
  setInterval(updateClock, 1000);
  window.onload = updateClock;

  function showSection(id) {
    document.getElementById('dashboardView').classList.add('hidden');
    document.getElementById('requestView').classList.add('hidden');
    document.getElementById(id).classList.remove('hidden');
  }

  function openLeaveModal() {
    const selectedType = document.getElementById('leaveTypeDropdown').value;
    if (!selectedType) return;
    document.getElementById('leaveModal').classList.remove('hidden');
    document.getElementById('hiddenLeaveType').value = selectedType;
  }

  function closeLeaveModal() {
    document.getElementById('leaveModal').classList.add('hidden');
    document.getElementById('leaveTypeDropdown').value = '';
  }
  </script>
  </body>
  </html>
