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
$stmt = $pdo->prepare("SELECT * FROM time_logs WHERE employee_id = :id");
$stmt->execute(['id' => $employee_id]);
$log_rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
$logs_by_date = [];
foreach ($log_rows as $log) {
    $logs_by_date[$log['log_date']] = $log;
}

// Date range: July 1 to today
$startDate = new DateTime('2025-07-01');
$today = new DateTime();
$interval = new DateInterval('P1D');
$period = new DatePeriod($startDate, $interval, $today);

$logs = [];
foreach ($period as $date) {
    $d = $date->format('Y-m-d');
    $logs[] = [
        'log_date' => $d,
        'time_in' => $logs_by_date[$d]['time_in'] ?? null,
        'time_out' => $logs_by_date[$d]['time_out'] ?? null,
    ];
}

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

    // File upload
    if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = [
            'application/pdf', 'image/jpeg', 'image/png',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
        ];

        $file_tmp = $_FILES['attachment']['tmp_name'];
        $file_name = basename($_FILES['attachment']['name']);
        $file_type = mime_content_type($file_tmp);
        $ext = pathinfo($file_name, PATHINFO_EXTENSION);

        if (in_array($file_type, $allowed_types)) {
            $new_filename = uniqid("attach_", true) . "." . $ext;
            $upload_dir = __DIR__ . "/../Public/uploads/time_adjustments/";
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }

            $destination = $upload_dir . $new_filename;

            if (move_uploaded_file($file_tmp, $destination)) {
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
        // Check and create missing log
        $log_stmt = $pdo->prepare("SELECT time_in, time_out FROM time_logs WHERE employee_id = :id AND log_date = :date");
        $log_stmt->execute(['id' => $employee_id, 'date' => $log_date]);
        $existing_log = $log_stmt->fetch();

        if (!$existing_log) {
            $createLog = $pdo->prepare("INSERT INTO time_logs (employee_id, log_date, time_in, time_out) VALUES (:id, :log_date, NULL, NULL)");
            $createLog->execute([
                'id' => $employee_id,
                'log_date' => $log_date
            ]);
            $existing_log = ['time_in' => null, 'time_out' => null];
        }

        // Insert time adjustment request
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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request Time Adjustment</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .step-indicator {
            transition: all 0.3s ease;
        }
        .step-indicator.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            transform: scale(1.1);
        }
        .step-indicator.completed {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
        }
        .step-content {
            animation: fadeInUp 0.5s ease-out;
        }
        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .glass-effect {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            transition: all 0.3s ease;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }
        .btn-success {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            transition: all 0.3s ease;
        }
        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(17, 153, 142, 0.3);
        }
        .input-field {
            transition: all 0.3s ease;
            border: 2px solid #e2e8f0;
        }
        .input-field:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
            transform: translateY(-1px);
        }
        .file-upload-area {
            border: 2px dashed #d1d5db;
            transition: all 0.3s ease;
        }
        .file-upload-area:hover {
            border-color: #667eea;
            background-color: #f8fafc;
        }
        .alert-success {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
        }
        .alert-error {
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%);
        }
    </style>
</head>
<body class="min-h-screen gradient-bg">
    <div class="min-h-screen flex items-center justify-center px-4 py-8">
        <div class="glass-effect rounded-3xl shadow-2xl p-8 w-full max-w-4xl overflow-y-auto max-h-[90vh]">
            
            <!-- Header -->
            <div class="text-center mb-8">
                <div class="inline-flex items-center justify-center w-16 h-16 bg-gradient-to-r from-blue-500 to-purple-600 rounded-full mb-4 shadow-lg">
                    <i class="fas fa-clock text-white text-2xl"></i>
                </div>
                <h1 class="text-3xl font-bold text-gray-800 mb-2">Time Adjustment Request</h1>
                <p class="text-gray-600">Follow the steps below to submit your time adjustment request</p>
            </div>

            <!-- Progress Indicator -->
            <div class="flex items-center justify-center mb-8">
                <div class="flex items-center space-x-4">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <div class="flex items-center">
                            <div id="step-indicator-<?= $i ?>" class="step-indicator w-10 h-10 rounded-full flex items-center justify-center text-white font-semibold <?= $i === 1 ? 'active' : 'bg-gray-300' ?> shadow-lg">
                                <?= $i ?>
                            </div>
                            <?php if ($i < 5): ?>
                                <div class="w-16 h-1 bg-gray-300 mx-2"></div>
                            <?php endif; ?>
                        </div>
                    <?php endfor; ?>
                </div>
            </div>

            <!-- Alerts -->
            <?php if (!empty($error)): ?>
                <div class="alert-error text-white px-6 py-4 rounded-2xl mb-6 shadow-lg">
                    <div class="flex items-center">
                        <i class="fas fa-exclamation-circle mr-3"></i>
                        <?= htmlspecialchars($error) ?>
                    </div>
                </div>
            <?php elseif (!empty($success)): ?>
                <div class="alert-success text-white px-6 py-4 rounded-2xl mb-6 shadow-lg">
                    <div class="flex items-center">
                        <i class="fas fa-check-circle mr-3"></i>
                        <?= htmlspecialchars($success) ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Form -->
            <form method="POST" enctype="multipart/form-data" id="stepForm" class="space-y-6">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

                <!-- Step 1 -->
                <div class="step step-content" id="step-1">
                    <div class="bg-white rounded-2xl p-6 shadow-lg border border-gray-100">
                        <div class="flex items-center mb-4">
                            <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center mr-3">
                                <i class="fas fa-calendar text-blue-600"></i>
                            </div>
                            <h3 class="text-xl font-semibold text-gray-800">Select Log Date</h3>
                        </div>
                        <select name="log_date" required class="input-field w-full p-4 rounded-xl bg-gray-50 border-0 text-gray-700 font-medium">
                            <option value="">Choose a date to adjust</option>
                            <?php foreach (array_reverse($logs) as $log):
                                $dateObj = new DateTime($log['log_date']);
                                $formattedDate = $dateObj->format('F j, Y');
                                $timeIn = $log['time_in'] ? (new DateTime($log['time_in']))->format('g:i A') : '—';
                                $timeOut = $log['time_out'] ? (new DateTime($log['time_out']))->format('g:i A') : '—';
                            ?>
                                <option value="<?= $log['log_date'] ?>">
                                    <?= $formattedDate ?> (In: <?= $timeIn ?> / Out: <?= $timeOut ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="flex justify-end mt-6">
                            <button type="button" onclick="nextStep()" class="btn-primary text-white px-8 py-3 rounded-xl font-semibold shadow-lg">
                                Next <i class="fas fa-arrow-right ml-2"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Step 2 -->
                <div class="step step-content hidden" id="step-2">
                    <div class="bg-white rounded-2xl p-6 shadow-lg border border-gray-100">
                        <div class="flex items-center mb-4">
                            <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center mr-3">
                                <i class="fas fa-sign-in-alt text-green-600"></i>
                            </div>
                            <h3 class="text-xl font-semibold text-gray-800">Requested Time In</h3>
                        </div>
                        <p class="text-gray-600 mb-4">Enter your preferred check-in time</p>
                        <input type="time" name="requested_time_in" class="input-field w-full p-4 rounded-xl bg-gray-50 border-0 text-gray-700 font-medium text-lg">
                        <div class="flex justify-between mt-6">
                            <button type="button" onclick="prevStep()" class="bg-gray-500 text-white px-8 py-3 rounded-xl font-semibold shadow-lg hover:bg-gray-600 transition-all">
                                <i class="fas fa-arrow-left mr-2"></i> Back
                            </button>
                            <button type="button" onclick="nextStep()" class="btn-primary text-white px-8 py-3 rounded-xl font-semibold shadow-lg">
                                Next <i class="fas fa-arrow-right ml-2"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Step 3 -->
                <div class="step step-content hidden" id="step-3">
                    <div class="bg-white rounded-2xl p-6 shadow-lg border border-gray-100">
                        <div class="flex items-center mb-4">
                            <div class="w-8 h-8 bg-red-100 rounded-full flex items-center justify-center mr-3">
                                <i class="fas fa-sign-out-alt text-red-600"></i>
                            </div>
                            <h3 class="text-xl font-semibold text-gray-800">Requested Time Out</h3>
                        </div>
                        <p class="text-gray-600 mb-4">Enter your preferred check-out time</p>
                        <input type="time" name="requested_time_out" class="input-field w-full p-4 rounded-xl bg-gray-50 border-0 text-gray-700 font-medium text-lg">
                        <div class="flex justify-between mt-6">
                            <button type="button" onclick="prevStep()" class="bg-gray-500 text-white px-8 py-3 rounded-xl font-semibold shadow-lg hover:bg-gray-600 transition-all">
                                <i class="fas fa-arrow-left mr-2"></i> Back
                            </button>
                            <button type="button" onclick="nextStep()" class="btn-primary text-white px-8 py-3 rounded-xl font-semibold shadow-lg">
                                Next <i class="fas fa-arrow-right ml-2"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Step 4 -->
                <div class="step step-content hidden" id="step-4">
                    <div class="bg-white rounded-2xl p-6 shadow-lg border border-gray-100">
                        <div class="flex items-center mb-4">
                            <div class="w-8 h-8 bg-yellow-100 rounded-full flex items-center justify-center mr-3">
                                <i class="fas fa-edit text-yellow-600"></i>
                            </div>
                            <h3 class="text-xl font-semibold text-gray-800">Reason for Adjustment</h3>
                        </div>
                        <p class="text-gray-600 mb-4">Please provide a detailed explanation for this time adjustment</p>
                        <textarea name="reason" rows="5" required 
                                  class="input-field w-full p-4 rounded-xl bg-gray-50 border-0 text-gray-700 resize-none" 
                                  placeholder="Explain why you need this time adjustment..."></textarea>
                        <div class="flex justify-between mt-6">
                            <button type="button" onclick="prevStep()" class="bg-gray-500 text-white px-8 py-3 rounded-xl font-semibold shadow-lg hover:bg-gray-600 transition-all">
                                <i class="fas fa-arrow-left mr-2"></i> Back
                            </button>
                            <button type="button" onclick="nextStep()" class="btn-primary text-white px-8 py-3 rounded-xl font-semibold shadow-lg">
                                Next <i class="fas fa-arrow-right ml-2"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Step 5 -->
                <div class="step step-content hidden" id="step-5">
                    <div class="bg-white rounded-2xl p-6 shadow-lg border border-gray-100">
                        <div class="flex items-center mb-4">
                            <div class="w-8 h-8 bg-purple-100 rounded-full flex items-center justify-center mr-3">
                                <i class="fas fa-paperclip text-purple-600"></i>
                            </div>
                            <h3 class="text-xl font-semibold text-gray-800">Attach Supporting Document</h3>
                        </div>
                        <p class="text-gray-600 mb-4">Upload a document to support your request</p>
                        
                        <div class="file-upload-area rounded-xl p-8 text-center">
                            <i class="fas fa-cloud-upload-alt text-4xl text-gray-400 mb-4"></i>
                            <p class="text-gray-600 mb-2">Drag and drop your file here or</p>
                            <input type="file" name="attachment" id="fileInput" required accept=".pdf,.jpg,.jpeg,.png,.docx"
                                   class="hidden">
                            <label for="fileInput" class="bg-blue-600 text-white px-6 py-2 rounded-lg cursor-pointer hover:bg-blue-700 transition-all inline-block">
                                Choose File
                            </label>
                            <p class="text-xs text-gray-500 mt-3">Allowed formats: PDF, JPG, PNG, DOCX (Max: 10MB)</p>
                            <div id="fileName" class="mt-3 text-sm text-green-600 hidden"></div>
                        </div>
                        
                        <div class="flex justify-between mt-6">
                            <button type="button" onclick="prevStep()" class="bg-gray-500 text-white px-8 py-3 rounded-xl font-semibold shadow-lg hover:bg-gray-600 transition-all">
                                <i class="fas fa-arrow-left mr-2"></i> Back
                            </button>
                            <button type="submit" class="btn-success text-white px-8 py-3 rounded-xl font-semibold shadow-lg">
                                <i class="fas fa-paper-plane mr-2"></i> Submit Request
                            </button>
                        </div>
                    </div>
                </div>
            </form>

            <!-- Back to Dashboard -->
            <div class="text-center mt-8">
                <a href="../module/time_log_create.php" class="text-gray-600 hover:text-gray-800 inline-flex items-center transition-all hover:transform hover:translate-x-1">
                    <i class="fas fa-arrow-left mr-2"></i> Back to Dashboard
                </a>
            </div>
        </div>
    </div>

    <script>
        let currentStep = 1;
        const totalSteps = 5;

        function updateProgressIndicator() {
            for (let i = 1; i <= totalSteps; i++) {
                const indicator = document.getElementById(`step-indicator-${i}`);
                indicator.classList.remove('active', 'completed');
                
                if (i < currentStep) {
                    indicator.classList.add('completed');
                    indicator.innerHTML = '<i class="fas fa-check"></i>';
                } else if (i === currentStep) {
                    indicator.classList.add('active');
                    indicator.innerHTML = i;
                } else {
                    indicator.classList.add('bg-gray-300');
                    indicator.innerHTML = i;
                }
            }
        }

        function showStep(step) {
            // Hide all steps
            for (let i = 1; i <= totalSteps; i++) {
                document.getElementById(`step-${i}`).classList.add('hidden');
            }
            
            // Show current step with animation
            const currentStepElement = document.getElementById(`step-${step}`);
            currentStepElement.classList.remove('hidden');
            
            // Update progress indicator
            updateProgressIndicator();
        }

        function nextStep() {
            if (validateCurrentStep() && currentStep < totalSteps) {
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

        function validateCurrentStep() {
            const currentStepElement = document.getElementById(`step-${currentStep}`);
            const requiredFields = currentStepElement.querySelectorAll('[required]');
            
            for (let field of requiredFields) {
                if (!field.value.trim()) {
                    field.focus();
                    field.classList.add('border-red-500');
                    setTimeout(() => field.classList.remove('border-red-500'), 3000);
                    return false;
                }
            }
            return true;
        }

        // File upload handling
        document.getElementById('fileInput').addEventListener('change', function(e) {
            const fileName = e.target.files[0]?.name;
            const fileNameDisplay = document.getElementById('fileName');
            
            if (fileName) {
                fileNameDisplay.textContent = `Selected: ${fileName}`;
                fileNameDisplay.classList.remove('hidden');
            } else {
                fileNameDisplay.classList.add('hidden');
            }
        });

        // Drag and drop functionality
        const fileUploadArea = document.querySelector('.file-upload-area');
        const fileInput = document.getElementById('fileInput');

        fileUploadArea.addEventListener('dragover', (e) => {
            e.preventDefault();
            fileUploadArea.classList.add('border-blue-500', 'bg-blue-50');
        });

        fileUploadArea.addEventListener('dragleave', (e) => {
            e.preventDefault();
            fileUploadArea.classList.remove('border-blue-500', 'bg-blue-50');
        });

        fileUploadArea.addEventListener('drop', (e) => {
            e.preventDefault();
            fileUploadArea.classList.remove('border-blue-500', 'bg-blue-50');
            
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                fileInput.files = files;
                fileInput.dispatchEvent(new Event('change'));
            }
        });

        // Initialize
        showStep(1);
    </script>
</body>
</html>