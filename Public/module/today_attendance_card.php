<?php

date_default_timezone_set('Asia/Manila');

$time_in = isset($time_in) ? date('H:i:s', strtotime($time_in)) : null;
$time_out = isset($time_out) ? date('H:i:s', strtotime($time_out)) : null;
// Make sure user is logged in
$employee_id = $_SESSION['employee']['id'] ?? null;
if (!$employee_id) {
    echo "<script>alert('User not logged in.'); location.href='../employee/login.php';</script>";
    exit;
}

    // Check if OT already requested today
$otRequested = false;
$today = date('Y-m-d');
$checkStmt = $pdo->prepare("SELECT COUNT(*) FROM overtime_requests WHERE employee_id = ? AND date = ?");
$checkStmt->execute([$employee_id, $today]);
if ($checkStmt->fetchColumn() > 0) {
    $otRequested = true;
}

$overtimeDetected = false; // default value to avoid warning

// Optional: set $time_in and $time_out if not yet set
$time_in = $time_in ?? null;
$time_out = $time_out ?? null;
$workingDuration = '';

if ($time_in && $time_out) {
    $start = new DateTime($time_in);
    $end = new DateTime($time_out);
    $diff = $start->diff($end);
    $hours = $diff->h + ($diff->i / 60);
    $workingDuration = number_format($hours, 2) . ' hours';
    if ($hours > 8) $overtimeDetected = true;
}


// Handle Overtime Request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_overtime'])) {
    $start_ot = isset($_POST['start_ot']) ? (int)$_POST['start_ot'] : null;
    $end_ot = isset($_POST['end_ot']) ? (int)$_POST['end_ot'] : null;
    $reason = trim($_POST['reason'] ?? '');
    $attachmentPath = null;

    if (!$start_ot || !$end_ot || !$reason) {
        echo "<script>alert('Please fill in all required fields.'); window.history.back();</script>";
        exit;
    }

    if ($end_ot <= $start_ot) {
        echo "<script>alert('End OT must be after Start OT.'); window.history.back();</script>";
        exit;
    }

    $start_dt = DateTime::createFromFormat('U', $start_ot);
    $end_dt = DateTime::createFromFormat('U', $end_ot);
    $duration_hours = round(($end_ot - $start_ot) / 3600, 2);

    // Upload handling
    if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . "/uploads/";
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
        $fileName = time() . "_" . basename($_FILES["attachment"]["name"]);
        $targetPath = $uploadDir . $fileName;

        if (move_uploaded_file($_FILES["attachment"]["tmp_name"], $targetPath)) {
            $attachmentPath = "uploads/" . $fileName;
        } else {
            echo "<script>alert('File upload failed.'); window.history.back();</script>";
            exit;
        }
    }

    // Check duplicate
    $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM overtime_requests WHERE employee_id = ? AND date = ?");
    $checkStmt->execute([$employee_id, $start_dt->format('Y-m-d')]);
    if ($checkStmt->fetchColumn() > 0) {
        echo "<script>alert('You already submitted an OT request today.'); window.history.back();</script>";
        exit;
    }

    // Insert into DB
    $stmt = $pdo->prepare("INSERT INTO overtime_requests (
        employee_id, date, start_time, end_time, duration_hours, reason, status, attachment_ot, created_at, time_in, time_out
    ) VALUES (?, ?, ?, ?, ?, ?, 'Pending', ?, NOW(), ?, ?)");

    $success = $stmt->execute([
        $employee_id,
        $start_dt->format('Y-m-d'),
        $start_dt->format('H:i:s'),
        $end_dt->format('H:i:s'),
        $duration_hours,
        $reason,
        $attachmentPath,
        $time_in,
        $time_out
    ]);

    if ($success) {
        echo "<script>alert('OT request submitted successfully.'); location.href='time_log_create.php';</script>";
        exit;
    } else {
        echo "<script>alert('Failed to submit OT request.'); window.history.back();</script>";
        exit;
    }
}
?>


    <div class="bg-white rounded-2xl shadow-lg p-6 w-full md:w-2/3 mx-auto border border-gray-200">
        <!-- Header -->
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-2xl font-semibold text-gray-800 flex items-center">
                <i class="fas fa-user-clock mr-3 text-green-500 bg-green-100 p-2 rounded-full"></i>
                Today's Time Log
            </h3>
            <span id="dashboardClock" class="text-sm font-mono text-gray-500 tracking-wide">--:--:-- --</span>
        </div>

        <!-- Time Cards -->
        <div class="grid grid-cols-1 md:grid-cols-<?= $overtimeDetected ? '3' : '2' ?> gap-5">
            <div class="flex items-center p-5 rounded-xl border border-green-200 bg-green-50 shadow-inner hover:shadow transition">
                <div class="flex items-center justify-center w-12 h-12 rounded-full bg-gradient-to-tr from-green-300 to-green-500 text-white mr-4">
                    <i class="fas fa-sign-in-alt"></i>
                </div>
                <div>
                    <p class="text-sm text-gray-600">Time In</p>
                    <p class="text-xl font-bold text-gray-800"><?= $time_in ? date("h:i A", strtotime($time_in)) : '—'; ?></p>
                </div>
            </div>

            <div class="flex items-center p-5 rounded-xl border border-yellow-200 bg-yellow-50 shadow-inner hover:shadow transition">
                <div class="flex items-center justify-center w-12 h-12 rounded-full bg-gradient-to-tr from-yellow-300 to-yellow-500 text-white mr-4">
                    <i class="fas fa-sign-out-alt"></i>
                </div>
                <div>
                    <p class="text-sm text-gray-600">Time Out</p>
                    <p class="text-xl font-bold text-gray-800"><?= $time_out ? date("h:i A", strtotime($time_out)) : '—'; ?></p>
                </div>
            </div>

            <?php if ($overtimeDetected): ?>
<div class="p-5 rounded-xl border border-red-300 bg-red-100 shadow-inner flex flex-col gap-3 animate-pulse-slow">
    <div class="flex items-center gap-2">
        <i class="fas fa-clock text-red-600 text-lg"></i>
        <h4 class="text-lg font-bold text-red-700">Overtime Alert</h4>
    </div>
    <p class="text-sm text-red-700">
        You've worked <strong><?= htmlspecialchars($workingDuration) ?></strong>, which exceeds the 8-hour limit.
    </p>
    <?php if (!$otRequested): ?>
    <button type="button" onclick="loadOvertimeRequest()" class="mt-3 inline-flex items-center text-sm text-red-600 hover:underline font-medium focus:outline-none">
        ➕ Request Overtime
    </button>
    <?php else: ?>
    <span class="mt-3 inline-flex items-center text-sm text-gray-400 font-medium">
        OT Request already submitted today.
    </span>
    <?php endif; ?>
</div>
<?php endif; ?>
        </div>

        <!-- Action Button -->
        <form method="POST" class="mt-6">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <?php if (!$time_in): ?>
                <button type="submit" name="time_in"
                    class="w-full bg-green-600 hover:bg-green-700 text-white font-semibold py-3 px-4 rounded-xl transition duration-200">
                    Log Time In
                </button>
            <?php elseif ($time_in && !$time_out): ?>
                <button type="submit" name="time_out"
                    class="w-full bg-yellow-500 hover:bg-yellow-600 text-white font-semibold py-3 px-4 rounded-xl transition duration-200">
                    Log Time Out
                </button>
            <?php else: ?>
                <button type="button" disabled
                    class="w-full bg-gray-300 text-white font-semibold py-3 px-4 rounded-xl cursor-not-allowed">
                    Already Logged
                </button>
            <?php endif; ?>
        </form>

        <div class="mt-4 text-center">
            <a href="request_time_adjustment.php"
            onclick="return confirm('Are you requesting a time adjustment because you forgot to time in?')"
            class="text-sm text-blue-600 hover:underline">
                Request Time Adjustment
            </a>
        </div>
    </div>

    <!-- Overtime Request Modal -->
    <div id="endOTModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-40 hidden">
        <div class="bg-white rounded-xl shadow-lg w-full max-w-md p-6 relative">
            <button class="absolute top-3 right-3 text-gray-500 hover:text-red-500"
                    onclick="document.getElementById('endOTModal').classList.add('hidden')">
                <i class="fas fa-times text-lg"></i>
            </button>

            <h3 class="text-xl font-semibold text-gray-800 mb-4">
                <i class="fas fa-plus-circle text-green-500 mr-2"></i>Overtime Request
            </h3>

            <form method="POST" enctype="multipart/form-data" class="space-y-4">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                <input type="hidden" name="submit_overtime" value="1">

                <!-- Start OT -->
                <div>
                    <label class="block text-sm font-medium text-gray-700">Start OT</label>
                    <div class="flex items-center gap-3">
                        <button type="button" onclick="setNow('start_ot')" class="bg-blue-600 hover:bg-blue-700 text-white text-sm px-4 py-2 rounded">
                            ⏱ Start OT
                        </button>
                        <span id="start_ot_display" class="text-sm text-gray-800">Not set</span>
                    </div>
                    <input type="hidden" id="start_ot" name="start_ot">
                </div>

                <!-- End OT -->
                <div>
                    <label class="block text-sm font-medium text-gray-700">End OT</label>
                    <div class="flex items-center gap-3">
                        <button type="button" onclick="setNow('end_ot')" class="bg-yellow-600 hover:bg-yellow-700 text-white text-sm px-4 py-2 rounded">
                            ⏱ End OT
                        </button>
                        <span id="end_ot_display" class="text-sm text-gray-800">Not set</span>
                    </div>
                    <input type="hidden" id="end_ot" name="end_ot">
                </div>

                <!-- Reason -->
                <div>
                    <label for="reason" class="block text-sm font-medium text-gray-700">Reason</label>
                    <textarea id="reason" name="reason" rows="3" required
                            class="mt-1 w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500 text-sm resize-none"
                            placeholder="Explain your reason..."></textarea>
                </div>

                <!-- Attachment -->
                <div>
                    <label for="attachment" class="block text-sm font-medium text-gray-700">Attachment (optional)</label>
                    <input type="file" id="attachment" name="attachment" class="mt-1 w-full text-sm text-gray-700">
                </div>

                <!-- Submit -->
                <div class="pt-2">
                    <button type="submit"
                            class="w-full bg-green-600 hover:bg-green-700 text-white font-semibold py-2.5 rounded-md transition duration-150">
                        Submit Overtime Request
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>

   function setNow(field) {
    const now = Math.floor(Date.now() / 1000); // UNIX timestamp
    document.getElementById(field).value = now;

    const formatted = new Intl.DateTimeFormat('en-US', {
        dateStyle: 'short',
        timeStyle: 'medium'
    }).format(new Date(now * 1000));

    document.getElementById(field + '_display').textContent = formatted;
}

function loadOvertimeRequest() {
    document.getElementById('endOTModal').classList.remove('hidden');
}

function hideOTModal() {
    document.getElementById('endOTModal').classList.add('hidden');
}

    </script>

<script>
function setNow(field) {
    const now = Math.floor(Date.now() / 1000); // UNIX timestamp
    document.getElementById(field).value = now;

    const formatted = new Intl.DateTimeFormat('en-US', {
        dateStyle: 'short',
        timeStyle: 'medium'
    }).format(new Date(now * 1000));

    document.getElementById(field + '_display').textContent = formatted;

    // Save the value and date in localStorage
    const today = new Date().toISOString().split('T')[0];
    localStorage.setItem(`${field}_set_date`, today);
    localStorage.setItem(`${field}_value`, now);

    // Disable the button after setting
    const button = document.querySelector(`[onclick="setNow('${field}')"]`);
    if (button) {
        button.disabled = true;
        button.classList.remove('bg-blue-600', 'bg-yellow-600', 'hover:bg-blue-700', 'hover:bg-yellow-700');
        button.classList.add('bg-gray-400', 'cursor-not-allowed');
    }
}

function restoreOTFields() {
    const fields = ['start_ot', 'end_ot'];
    const today = new Date().toISOString().split('T')[0];

    fields.forEach(field => {
        const setDate = localStorage.getItem(`${field}_set_date`);
        const value = localStorage.getItem(`${field}_value`);
        if (setDate === today && value) {
            // Restore hidden input value
            const input = document.getElementById(field);
            if (input) input.value = value;

            // Restore display
            const formatted = new Intl.DateTimeFormat('en-US', {
                dateStyle: 'short',
                timeStyle: 'medium'
            }).format(new Date(value * 1000));
            const display = document.getElementById(field + '_display');
            if (display) display.textContent = formatted;
        }
    });
}

function applyOTButtonState() {
    const fields = ['start_ot', 'end_ot'];
    const today = new Date().toISOString().split('T')[0];

    fields.forEach(field => {
        const setDate = localStorage.getItem(`${field}_set_date`);
        const button = document.querySelector(`[onclick="setNow('${field}')"]`);

        if (button) {
            if (setDate === today) {
                // Already clicked today
                button.disabled = true;
                button.classList.remove('bg-blue-600', 'bg-yellow-600', 'hover:bg-blue-700', 'hover:bg-yellow-700');
                button.classList.add('bg-gray-400', 'cursor-not-allowed');
            } else {
                // Enable button
                button.disabled = false;
                button.classList.remove('bg-gray-400', 'cursor-not-allowed');
                if (field === 'start_ot') {
                    button.classList.add('bg-blue-600', 'hover:bg-blue-700');
                } else {
                    button.classList.add('bg-yellow-600', 'hover:bg-yellow-700');
                }
            }
        }
    });
}

function resetLocalStorageDaily() {
    const lastReset = localStorage.getItem('last_reset');
    const today = new Date().toISOString().split('T')[0];

    if (lastReset !== today) {
        // New day = reset OT buttons and values
        ['start_ot', 'end_ot'].forEach(field => {
            localStorage.removeItem(`${field}_set_date`);
            localStorage.removeItem(`${field}_value`);
        });
        localStorage.setItem('last_reset', today);
    }
}

function loadOvertimeRequest() {
    document.getElementById('endOTModal').classList.remove('hidden');
}

function hideOTModal() {
    document.getElementById('endOTModal').classList.add('hidden');
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', () => {
    resetLocalStorageDaily();
    restoreOTFields();
    applyOTButtonState();
});
</script>