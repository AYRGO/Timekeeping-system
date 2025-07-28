<?php

session_start();
include('../config/db.php');
include_once('../config/env.php');
date_default_timezone_set('Asia/Manila');

$employee_id = $_SESSION['employee']['id'] ?? null;
if (!$employee_id) {
    header("Location: ../employee/login.php");
    exit;
}

// Human verification check with reCAPTCHA v2
// Debug: Check current session state
error_log("Session verification status: " . (isset($_SESSION['human_verified_adjustment']) ? ($_SESSION['human_verified_adjustment'] ? 'true' : 'false') : 'not set'));
error_log("Full session data: " . print_r($_SESSION, true));
error_log("POST data received: " . print_r($_POST, true));
error_log("REQUEST_METHOD: " . $_SERVER['REQUEST_METHOD']);

// TEMPORARY BYPASS - Remove this after testing
if (isset($_GET['bypass']) && $_GET['bypass'] === 'test') {
    $_SESSION['human_verified_adjustment'] = true;
    error_log("BYPASS: Setting verification to true");
}

// Check if coming from successful verification
if (isset($_GET['verified']) && $_GET['verified'] === '1') {
    if (!isset($_SESSION['human_verified_adjustment']) || $_SESSION['human_verified_adjustment'] !== true) {
        $_SESSION['human_verified_adjustment'] = true;
        error_log("VERIFIED: Setting verification to true from URL parameter");
    }
}

if (!isset($_SESSION['human_verified_adjustment']) || $_SESSION['human_verified_adjustment'] !== true) {
    // Handle verification form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify_human'])) {
        error_log("Processing verification form submission");
        $recaptcha_response = $_POST['g-recaptcha-response'] ?? '';
        
        if (empty($recaptcha_response)) {
            $verification_error = "Please complete the reCAPTCHA verification.";
            error_log("No reCAPTCHA response found");
        } else {
            error_log("reCAPTCHA response received: " . substr($recaptcha_response, 0, 20) . "...");
            // Verify reCAPTCHA v2 with Google
            $secret_key = EnvLoader::get('RECAPTCHA_SECRET_KEY');
            
            if (!$secret_key) {
                $verification_error = "reCAPTCHA configuration error. Please contact administrator.";
                error_log("reCAPTCHA secret key not configured");
            } else {
                $verify_url = "https://www.google.com/recaptcha/api/siteverify";
                
                $post_data = [
                    'secret' => $secret_key,
                    'response' => $recaptcha_response,
                    'remoteip' => $_SERVER['REMOTE_ADDR']
                ];
                
                $options = [
                    'http' => [
                        'header' => "Content-type: application/x-www-form-urlencoded\r\n",
                        'method' => 'POST',
                        'content' => http_build_query($post_data)
                    ]
                ];
                
                $context = stream_context_create($options);
                $response = file_get_contents($verify_url, false, $context);
                $response_data = json_decode($response);
                
                error_log("Google response: " . json_encode($response_data));
                
                if ($response_data && $response_data->success) {
                    $_SESSION['human_verified_adjustment'] = true;
                    // Force session write to ensure it's saved
                    session_write_close();
                    session_start();
                    error_log("Verification successful, setting session and redirecting");
                    
                    // Clean redirect without parameters to avoid loops
                    header('Location: ' . $_SERVER['PHP_SELF']);
                    exit;
                } else {
                    $error_codes = isset($response_data->{'error-codes'}) ? implode(', ', $response_data->{'error-codes'}) : 'Unknown error';
                    $verification_error = "reCAPTCHA verification failed. Error: " . $error_codes;
                    error_log("Verification failed: " . $verification_error);
                }
            }
        }
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // If it's a POST request but not a verification, redirect to prevent time adjustment form submission
        error_log("Non-verification POST request detected, redirecting to prevent form submission");
        error_log("POST keys: " . implode(', ', array_keys($_POST)));
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
    
    // Show verification page
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Human Verification - Time Adjustment Request</title>
        <script src="https://www.google.com/recaptcha/api.js?onload=onRecaptchaLoad&render=explicit" async defer></script>
        <style>
            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }
            
            body {
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                background: #f8f9fa;
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                color: #333;
            }
            
            .container {
                max-width: 1000px;
                width: 100%;
                background: white;
                box-shadow: 0 4px 20px rgba(0,0,0,0.1);
                border-radius: 12px;
                overflow: hidden;
                display: flex;
                min-height: 500px;
            }
            
            .left-panel {
                flex: 1;
                background: #333;
                color: white;
                padding: 40px;
                display: flex;
                flex-direction: column;
                justify-content: center;
            }
            
            .right-panel {
                flex: 1;
                padding: 40px;
                display: flex;
                flex-direction: column;
                justify-content: center;
            }
            
            .logo {
                width: 60px;
                height: 60px;
                background: white;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                margin-bottom: 30px;
                font-size: 24px;
                color: #333;
            }
            
            .title {
                font-size: 28px;
                font-weight: 700;
                margin-bottom: 15px;
                line-height: 1.2;
            }
            
            .subtitle {
                font-size: 16px;
                opacity: 0.9;
                margin-bottom: 30px;
                line-height: 1.5;
            }
            
            .features {
                list-style: none;
            }
            
            .features li {
                margin-bottom: 12px;
                display: flex;
                align-items: center;
                font-size: 14px;
            }
            
            .features li:before {
                content: "✓";
                margin-right: 12px;
                font-weight: bold;
                color: #4ade80;
            }
            
            .form-title {
                font-size: 24px;
                font-weight: 600;
                margin-bottom: 10px;
                color: #333;
            }
            
            .form-subtitle {
                color: #666;
                margin-bottom: 30px;
                font-size: 14px;
            }
            
            .error-message {
                background: #fee;
                border: 1px solid #fcc;
                color: #c33;
                padding: 12px;
                border-radius: 6px;
                margin-bottom: 20px;
                font-size: 14px;
            }
            
            .recaptcha-container {
                margin: 20px 0;
                display: flex;
                justify-content: center;
                min-height: 78px;
                align-items: center;
            }
            
            .loading-message {
                color: #666;
                font-style: italic;
                text-align: center;
            }
            
            .submit-btn {
                width: 100%;
                padding: 14px;
                background: #333;
                color: white;
                border: none;
                border-radius: 6px;
                font-size: 16px;
                font-weight: 600;
                cursor: pointer;
                transition: all 0.3s ease;
                margin-top: 20px;
            }
            
            .submit-btn:hover:not(:disabled) {
                background: #222;
                transform: translateY(-1px);
            }
            
            .submit-btn:disabled {
                background: #ccc;
                cursor: not-allowed;
                transform: none;
            }
            
            .status-text {
                text-align: center;
                margin-top: 15px;
                font-size: 14px;
                color: #666;
            }
            
            .status-text.success {
                color: #22c55e;
            }
            
            .status-text.error {
                color: #ef4444;
            }
            
            .help-section {
                margin-top: 30px;
                padding: 20px;
                background: #f8f9fa;
                border-radius: 6px;
                border-left: 4px solid #333;
            }
            
            .help-title {
                font-weight: 600;
                margin-bottom: 10px;
                color: #333;
            }
            
            .help-list {
                list-style: none;
                font-size: 13px;
                color: #666;
            }
            
            .help-list li {
                margin-bottom: 6px;
                padding-left: 15px;
                position: relative;
            }
            
            .help-list li:before {
                content: "•";
                position: absolute;
                left: 0;
                color: #333;
            }
            
            @media (max-width: 768px) {
                .container {
                    flex-direction: column;
                    margin: 20px;
                    max-width: none;
                }
                
                .left-panel, .right-panel {
                    padding: 30px;
                }
                
                .title {
                    font-size: 24px;
                }
                
                .form-title {
                    font-size: 20px;
                }
            }
        </style>
    </head>
    <body>
        <div class="container">
            <!-- Left Panel -->
            <div class="left-panel">
                <div class="logo">🛡️</div>
                <h1 class="title">Security Verification Required</h1>
                <p class="subtitle">Please complete the human verification to access the time adjustment request system.</p>
                
                <ul class="features">
                    <li>Secure access protection</li>
                    <li>Bot prevention system</li>
                    <li>Data integrity protection</li>
                    <li>Quick verification process</li>
                </ul>
            </div>
            
            <!-- Right Panel -->
            <div class="right-panel">
                <h2 class="form-title">Human Verification</h2>
                <p class="form-subtitle">Complete the puzzle below to continue</p>
                
                <?php if (isset($verification_error)): ?>
                    <div class="error-message">
                        ⚠️ <?= htmlspecialchars($verification_error) ?>
                    </div>
                <?php endif; ?>
                
                <!-- Debug Information -->
                <div style="margin-top: 20px; padding: 10px; background: #f0f0f0; border-radius: 4px; font-size: 12px; color: #666;">
                    <strong>Debug Info:</strong><br>
                    Session Status: <?= isset($_SESSION['human_verified_adjustment']) ? ($_SESSION['human_verified_adjustment'] ? 'Verified' : 'Not Verified') : 'Not Set' ?><br>
                    URL Parameters: <?= isset($_GET['verified']) ? 'verified=' . $_GET['verified'] : 'none' ?><br>
                    Session ID: <?= session_id() ?><br>
                    Employee ID: <?= $employee_id ?? 'not set' ?><br>
                    Request Method: <?= $_SERVER['REQUEST_METHOD'] ?><br>
                    POST Data: <?= isset($_POST['verify_human']) ? 'verify_human submitted' : 'no verification post' ?><br>
                    <?php if ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>
                        POST Keys: <?= implode(', ', array_keys($_POST)) ?><br>
                    <?php endif; ?>
                </div>
                
                <form method="POST" id="verificationForm">
                    <!-- Hidden field to ensure verify_human is always sent -->
                    <input type="hidden" name="verify_human" value="1">
                    
                    <div class="recaptcha-container" id="recaptchaContainer">
                        <div class="loading-message" id="loadingMessage">
                            🔄 Loading verification puzzle...
                        </div>
                        <div id="recaptcha-widget"></div>
                    </div>
                    
                    <div id="statusMessage" class="status-text">
                        Waiting for puzzle to load...
                    </div>
                    
                    <button type="submit" name="verify_human" id="submitBtn" class="submit-btn" disabled>
                        Complete Puzzle to Continue
                    </button>
                </form>
                
                <div class="help-section">
                    <div class="help-title">How to complete verification:</div>
                    <ul class="help-list">
                        <li>Wait for the puzzle to load completely</li>
                        <li>Click the checkbox "I'm not a robot"</li>
                        <li>Complete the image challenge if prompted</li>
                        <li>Select all squares with the specified object</li>
                        <li>Click "VERIFY" when done selecting</li>
                        <li>The form will unlock automatically</li>
                    </ul>
                </div>
            </div>
        </div>
        
        <script>
            let recaptchaLoaded = false;
            let recaptchaRendered = false;
            let widgetId = null;
            
            // Global callback function - called when reCAPTCHA API loads
            window.onRecaptchaLoad = function() {
                console.log('reCAPTCHA API loaded successfully');
                recaptchaLoaded = true;
                
                try {
                    // Hide loading message
                    document.getElementById('loadingMessage').style.display = 'none';
                    
                    // Render the reCAPTCHA widget
                    widgetId = grecaptcha.render('recaptcha-widget', {
                        'sitekey': '<?= EnvLoader::get('RECAPTCHA_SITE_KEY') ?>',
                        'theme': 'light',
                        'size': 'normal',
                        'hl': 'en',
                        'callback': onRecaptchaSuccess,
                        'expired-callback': onRecaptchaExpired,
                        'error-callback': onRecaptchaError
                    });
                    
                    recaptchaRendered = true;
                    document.getElementById('statusMessage').textContent = 'Complete the puzzle above to proceed';
                    console.log('reCAPTCHA rendered successfully with widget ID:', widgetId);
                    
                } catch (error) {
                    console.error('Error rendering reCAPTCHA:', error);
                    showError('Failed to load puzzle. Please refresh the page.');
                }
            };
            
            function onRecaptchaSuccess(token) {
                console.log('reCAPTCHA completed successfully!', token);

                const submitBtn = document.getElementById('submitBtn');
                const statusMessage = document.getElementById('statusMessage');

                // Enable submit button
                submitBtn.disabled = false;
                submitBtn.textContent = 'Verification Complete - Continue';
                submitBtn.style.background = '#22c55e';

                // Update status
                statusMessage.textContent = 'Puzzle completed successfully! Click the button below to continue.';
                statusMessage.className = 'status-text success';
            }
            function onRecaptchaExpired() {
                console.log('reCAPTCHA expired');
                
                const submitBtn = document.getElementById('submitBtn');
                const statusMessage = document.getElementById('statusMessage');
                
                // Reset button
                submitBtn.disabled = true;
                submitBtn.textContent = 'Complete Puzzle to Continue';
                submitBtn.style.background = '#ccc';
                
                // Update status
                statusMessage.textContent = 'Puzzle expired. Please solve it again.';
                statusMessage.className = 'status-text error';
            }
            
            function onRecaptchaError() {
                console.log('reCAPTCHA error occurred');
                showError('Error loading puzzle. Please refresh the page.');
            }
            
            function showError(message) {
                const statusMessage = document.getElementById('statusMessage');
                statusMessage.textContent = message;
                statusMessage.className = 'status-text error';
                
                // Show refresh suggestion
                setTimeout(() => {
                    statusMessage.innerHTML = message + ' <a href="javascript:window.location.reload()" style="color: #ef4444; text-decoration: underline;">Click here to refresh</a>';
                }, 2000);
            }
            
            // Form validation
            document.getElementById('verificationForm').addEventListener('submit', function(e) {
                console.log('Form submission attempted...');
                console.log('reCAPTCHA loaded:', recaptchaLoaded);
                console.log('reCAPTCHA rendered:', recaptchaRendered);
                console.log('Widget ID:', widgetId);
                
                if (!recaptchaLoaded || !recaptchaRendered || widgetId === null) {
                    e.preventDefault();
                    alert('Please wait for the reCAPTCHA to load completely.');
                    return false;
                }
                
                const response = grecaptcha.getResponse(widgetId);
                console.log('reCAPTCHA response:', response ? 'Present' : 'Missing');
                
                if (!response) {
                    e.preventDefault();
                    alert('Please complete the reCAPTCHA puzzle first.');
                    return false;
                }
                
                // Ensure verify_human is present
                const verifyInput = document.querySelector('input[name="verify_human"]');
                if (!verifyInput) {
                    console.error('verify_human input missing!');
                    const hiddenInput = document.createElement('input');
                    hiddenInput.type = 'hidden';
                    hiddenInput.name = 'verify_human';
                    hiddenInput.value = '1';
                    this.appendChild(hiddenInput);
                }
                
                // Show loading and allow form to submit
                const submitBtn = document.getElementById('submitBtn');
                const statusMessage = document.getElementById('statusMessage');
                
                submitBtn.textContent = 'Verifying & Redirecting...';
                submitBtn.disabled = true;
                statusMessage.textContent = 'Processing verification, please wait...';
                statusMessage.className = 'status-text';
                
                console.log('Form validation passed, submitting with verify_human...');
                // Let the form submit naturally
                return true;
            });
            
            // Fallback check after page loads
            window.addEventListener('load', function() {
                console.log('Page loaded, checking reCAPTCHA status');
                
                setTimeout(function() {
                    if (typeof grecaptcha === 'undefined') {
                        console.log('reCAPTCHA API failed to load');
                        document.getElementById('loadingMessage').textContent = '❌ Failed to load verification system';
                        showError('Network error. Please check your connection and refresh.');
                    } else if (!recaptchaRendered) {
                        console.log('reCAPTCHA API loaded but not rendered, manually triggering');
                        window.onRecaptchaLoad();
                    }
                }, 5000);
            });
        </script>
    </body>
    </html>
    <?php
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
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['verify_human'])) {
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
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f8f9fa;
            min-height: 100vh;
            color: #333;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .verified-badge {
            display: inline-flex;
            align-items: center;
            background: #333;
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 14px;
            margin-bottom: 30px;
            gap: 8px;
        }
        
        .header {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .header h1 {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 10px;
            color: #333;
        }
        
        .header p {
            color: #666;
            font-size: 16px;
        }
        
        .form-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .step-header {
            background: #333;
            color: white;
            padding: 20px 30px;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .step-icon {
            width: 40px;
            height: 40px;
            background: white;
            color: #333;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }
        
        .step-title {
            font-size: 18px;
            font-weight: 600;
        }
        
        .form-content {
            padding: 40px;
        }
        
        .form-group {
            margin-bottom: 30px;
        }
        
        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }
        
        .form-control {
            width: 100%;
            padding: 12px;
            border: 2px solid #e5e5e5;
            border-radius: 6px;
            font-size: 16px;
            transition: border-color 0.3s ease;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #333;
        }
        
        .textarea {
            resize: vertical;
            min-height: 120px;
        }
        
        .file-upload {
            border: 2px dashed #ccc;
            border-radius: 6px;
            padding: 40px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .file-upload:hover {
            border-color: #333;
            background: #f8f9fa;
        }
        
        .file-upload.dragover {
            border-color: #333;
            background: #f0f0f0;
        }
        
        .submit-btn {
            background: #333;
            color: white;
            padding: 14px 30px;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .submit-btn:hover {
            background: #222;
            transform: translateY(-1px);
        }
        
        .alert {
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
        
        .alert-success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        
        .alert-error {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
        
        .back-link {
            color: #333;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-top: 30px;
            font-weight: 500;
        }
        
        .back-link:hover {
            text-decoration: underline;
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 15px;
            }
            
            .form-content {
                padding: 30px 20px;
            }
            
            .header h1 {
                font-size: 24px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="verified-badge">
            🛡️ Verification Complete
        </div>
        
        <div class="header">
            <h1>Time Adjustment Request</h1>
            <p>Submit your time adjustment request with supporting information</p>
        </div>
        
        <!-- Alerts -->
        <?php if (!empty($error)): ?>
            <div class="alert alert-error">
                ⚠️ <?= htmlspecialchars($error) ?>
            </div>
        <?php elseif (!empty($success)): ?>
            <div class="alert alert-success">
                ✅ <?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>
        
        <div class="form-container">
            <div class="step-header">
                <div class="step-icon">📝</div>
                <div class="step-title">Time Adjustment Request Form</div>
            </div>
            
            <div class="form-content">
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    
                    <div class="form-group">
                        <label class="form-label">Select Date to Adjust</label>
                        <select name="log_date" required class="form-control">
                            <option value="">Choose a date to adjust</option>
                            <?php foreach (array_reverse($logs) as $log):
                                $dateObj = new DateTime($log['log_date']);
                                $formattedDate = $dateObj->format('F j, Y (l)');
                                $timeIn = $log['time_in'] ? (new DateTime($log['time_in']))->format('g:i A') : 'No record';
                                $timeOut = $log['time_out'] ? (new DateTime($log['time_out']))->format('g:i A') : 'No record';
                            ?>
                                <option value="<?= $log['log_date'] ?>">
                                    <?= $formattedDate ?> - In: <?= $timeIn ?> | Out: <?= $timeOut ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div class="form-group">
                            <label class="form-label">Requested Time In (Optional)</label>
                            <input type="time" name="requested_time_in" class="form-control">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Requested Time Out (Optional)</label>
                            <input type="time" name="requested_time_out" class="form-control">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Reason for Adjustment *</label>
                        <textarea name="reason" required class="form-control textarea" 
                                  placeholder="Please provide a detailed explanation for this time adjustment request (e.g., medical appointment, emergency, technical issues, etc.)..."></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Supporting Document (Optional)</label>
                        <div class="file-upload" id="fileUpload">
                            <div>📁 Drag and drop your file here or click to browse</div>
                            <div style="margin-top: 10px; font-size: 14px; color: #666;">
                                Supported: PDF, JPG, PNG, DOCX (Max: 10MB)
                            </div>
                            <input type="file" name="attachment" id="fileInput" accept=".pdf,.jpg,.jpeg,.png,.docx" style="display: none;">
                            <div id="fileName" style="margin-top: 15px; font-weight: 600; color: #333; display: none;"></div>
                        </div>
                    </div>
                    
                    <button type="submit" class="submit-btn">
                        📨 Submit Time Adjustment Request
                    </button>
                </form>
            </div>
        </div>
        
        <a href="../module/time_log_create.php" class="back-link">
            ← Back to Dashboard
        </a>
    </div>
    
    <script>
        // File upload handling
        const fileUpload = document.getElementById('fileUpload');
        const fileInput = document.getElementById('fileInput');
        const fileName = document.getElementById('fileName');
        
        fileUpload.addEventListener('click', () => fileInput.click());
        
        fileUpload.addEventListener('dragover', (e) => {
            e.preventDefault();
            fileUpload.classList.add('dragover');
        });
        
        fileUpload.addEventListener('dragleave', () => {
            fileUpload.classList.remove('dragover');
        });
        
        fileUpload.addEventListener('drop', (e) => {
            e.preventDefault();
            fileUpload.classList.remove('dragover');
            
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                fileInput.files = files;
                showFileName(files[0].name);
            }
        });
        
        fileInput.addEventListener('change', (e) => {
            if (e.target.files[0]) {
                showFileName(e.target.files[0].name);
            }
        });
        
        function showFileName(name) {
            fileName.textContent = `Selected: ${name}`;
            fileName.style.display = 'block';
        }
    </script>
</body>
</html>