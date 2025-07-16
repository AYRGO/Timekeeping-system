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

// Fetch user's logs
$stmt = $pdo->prepare("SELECT * FROM time_logs WHERE employee_id = :id ORDER BY log_date DESC");
$stmt->execute(['id' => $employee_id]);
$logs = $stmt->fetchAll();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die("Invalid CSRF token.");
    }

    $log_date = $_POST['log_date'] ?? '';
    $requested_time_in = $_POST['requested_time_in'] ?? null;
    $requested_time_out = $_POST['requested_time_out'] ?? null;
    $reason = trim($_POST['reason'] ?? '');

    $attachment_path = null;

// Handle file upload
if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
    $allowed_types = [
        'application/pdf', 'image/jpeg', 'image/png',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
    ];

    $file_tmp  = $_FILES['attachment']['tmp_name'];
    $file_name = basename($_FILES['attachment']['name']);
    $file_type = mime_content_type($file_tmp);
    $ext       = pathinfo($file_name, PATHINFO_EXTENSION);

    if (in_array($file_type, $allowed_types)) {
        $new_filename = uniqid("attach_", true) . "." . $ext;

        // Full path to C:/xampp/htdocs/Timekeeping-system/Public/uploads/time_adjustments
        $upload_dir = __DIR__ . "/../Public/uploads/time_adjustments/";

        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        $destination = $upload_dir . $new_filename;

        if (move_uploaded_file($file_tmp, $destination)) {
            // Save only filename to DB
            $attachment_path = $new_filename;
        } else {
            $error = "Failed to upload file.";
        }
    } else {
        $error = "Unsupported file type.";
    }
}

    if (!$log_date || !$reason) {
        $error = "Log date and reason are required.";
    } else {
        $log_stmt = $pdo->prepare("SELECT time_in, time_out FROM time_logs WHERE employee_id = :id AND log_date = :date");
        $log_stmt->execute(['id' => $employee_id, 'date' => $log_date]);
        $existing_log = $log_stmt->fetch();

        if (!$existing_log) {
            $error = "No time log found for selected date.";
        } else {
            $insert = $pdo->prepare("
                INSERT INTO time_adjustment_requests 
                    (employee_id, log_date, current_time_in, current_time_out, requested_time_in, requested_time_out, reason, attachment) 
                VALUES 
                    (:employee_id, :log_date, :current_time_in, :current_time_out, :requested_time_in, :requested_time_out, :reason, :attachment)
            ");
            $insert->execute([
                'employee_id' => $employee_id,
                'log_date' => $log_date,
                'current_time_in' => $existing_log['time_in'],
                'current_time_out' => $existing_log['time_out'],
                'requested_time_in' => $requested_time_in ?: null,
                'requested_time_out' => $requested_time_out ?: null,
                'reason' => $reason,
                'attachment' => $attachment_path
            ]);

            $success = "Request submitted successfully!";
        }
    }
}
?>

    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>Request Time Adjustment</title>
        <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    </head>
    <body class="bg-gray-100 min-h-screen flex items-center justify-center px-4">
        <div class="bg-white rounded-2xl shadow-xl p-8 w-full max-w-2xl border border-gray-200">
            <h2 class="text-2xl font-semibold text-gray-800 flex items-center mb-6">
                <i class="fas fa-clock text-blue-500 bg-blue-100 p-2 rounded-full mr-3"></i>
                Request Time Adjustment
            </h2>

            <?php if (!empty($error)): ?>
                <div class="bg-red-100 text-red-700 px-4 py-3 rounded mb-4"><?= htmlspecialchars($error) ?></div>
            <?php elseif (!empty($success)): ?>
                <div class="bg-green-100 text-green-700 px-4 py-3 rounded mb-4"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data" id="stepForm" class="space-y-4">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

                <!-- Step 1 -->
                <div class="step" id="step-1">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Step 1: Select Log Date</label>
                    <select name="log_date" required class="w-full border-gray-300 rounded-lg shadow-sm focus:ring focus:ring-blue-200">
                        <option value="">Select a date</option>
                        <?php foreach ($logs as $log): ?>
                            <option value="<?= $log['log_date'] ?>">
                                <?= $log['log_date'] ?> (In: <?= $log['time_in'] ?: '—' ?> / Out: <?= $log['time_out'] ?: '—' ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="flex justify-end mt-4">
                        <button type="button" onclick="nextStep()" class="bg-blue-600 text-white px-4 py-2 rounded">Next</button>
                    </div>
                </div>

                <!-- Step 2 -->
                <div class="step hidden" id="step-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Step 2: Requested Time In (optional)</label>
                    <input type="time" name="requested_time_in" class="w-full border-gray-300 rounded-lg shadow-sm focus:ring focus:ring-blue-200">
                    <div class="flex justify-between mt-4">
                        <button type="button" onclick="prevStep()" class="bg-gray-300 text-gray-800 px-4 py-2 rounded">Back</button>
                        <button type="button" onclick="nextStep()" class="bg-blue-600 text-white px-4 py-2 rounded">Next</button>
                    </div>
                </div>

                <!-- Step 3 -->
                <div class="step hidden" id="step-3">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Step 3: Requested Time Out (optional)</label>
                    <input type="time" name="requested_time_out" class="w-full border-gray-300 rounded-lg shadow-sm focus:ring focus:ring-blue-200">
                    <div class="flex justify-between mt-4">
                        <button type="button" onclick="prevStep()" class="bg-gray-300 text-gray-800 px-4 py-2 rounded">Back</button>
                        <button type="button" onclick="nextStep()" class="bg-blue-600 text-white px-4 py-2 rounded">Next</button>
                    </div>
                </div>

                <!-- Step 4 -->
                <div class="step hidden" id="step-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Step 4: Reason for Adjustment</label>
                    <textarea name="reason" rows="4" required class="w-full border-gray-300 rounded-lg shadow-sm focus:ring focus:ring-blue-200" placeholder="Explain your reason..."></textarea>
                    <div class="flex justify-between mt-4">
                        <button type="button" onclick="prevStep()" class="bg-gray-300 text-gray-800 px-4 py-2 rounded">Back</button>
                        <button type="button" onclick="nextStep()" class="bg-blue-600 text-white px-4 py-2 rounded">Next</button>
                    </div>
                </div>

<!-- Step 5 -->
<div class="step hidden" id="step-5">
    <label class="block text-sm font-medium text-gray-700 mb-1">Step 5: Attach File <span class="text-red-500">*</span></label>
    <input 
        type="file" 
        name="attachment" 
        required
        accept=".pdf,.jpg,.jpeg,.png,.docx"
        class="block w-full text-sm text-gray-600 bg-gray-50 border border-gray-300 rounded-lg"
    >
    <p class="text-xs text-gray-500 mt-1">Allowed: PDF, JPG, PNG, DOCX</p>

    <div class="flex justify-between mt-4">
        <button type="button" onclick="prevStep()" class="bg-gray-300 text-gray-800 px-4 py-2 rounded">Back</button>
        <button type="submit" class="bg-green-600 text-white px-6 py-3 rounded font-semibold">
            <i class="fas fa-paper-plane mr-1"></i> Submit Request
        </button>
    </div>
</div>

           <!-- Back to Dashboard Button -->
    <div class="w-full max-w-2xl mt-6 flex justify-start">
        <a href="../module/time_log_create.php" class="inline-flex items-center text-blue-600 hover:underline">
            <i class="fas fa-arrow-left mr-2"></i> Back to Dashboard
        </a>
    </div>
</body>
</html>

    <script>
        let currentStep = 1;
        const totalSteps = 5;

        function showStep(step) {
            for (let i = 1; i <= totalSteps; i++) {
                document.getElementById(`step-${i}`).classList.add('hidden');
            }
            document.getElementById(`step-${step}`).classList.remove('hidden');
        }

        function nextStep() {
            if (currentStep < totalSteps) {
                currentStep++;  
                showStep(currentStep);
            }
        }

        function prevStep() {
            if (currentStep > 1) {
                currentStep--;
                showStep(currentStep);
            }
        }
    </script>
</body>
</html>
