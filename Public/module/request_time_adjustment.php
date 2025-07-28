<?php

session_start();
include('../config/db.php');
include_once('../config/env.php');
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

// CSRF token generation
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Initialize form data in session if not exists
if (!isset($_SESSION['adjustment_form'])) {
    $_SESSION['adjustment_form'] = [
        'log_date' => '',
        'requested_time_in' => '',
        'requested_time_out' => '',
        'reason' => '',
        'attachment' => ''
    ];
}

// Get current step
$current_step = isset($_GET['step']) ? (int)$_GET['step'] : 1;
if ($current_step < 1) $current_step = 1;
if ($current_step > 5) $current_step = 5;

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

    // Save current step data
    if (isset($_POST['log_date'])) {
        $_SESSION['adjustment_form']['log_date'] = $_POST['log_date'];
    }
    if (isset($_POST['requested_time_in'])) {
        $_SESSION['adjustment_form']['requested_time_in'] = $_POST['requested_time_in'];
    }
    if (isset($_POST['requested_time_out'])) {
        $_SESSION['adjustment_form']['requested_time_out'] = $_POST['requested_time_out'];
    }
    if (isset($_POST['reason'])) {
        $_SESSION['adjustment_form']['reason'] = $_POST['reason'];
    }

    // Handle file upload - NOW REQUIRED
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
            $upload_dir = __DIR__ . "/../uploads/time_adjustments/";
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            $destination = $upload_dir . $new_filename;
            if (move_uploaded_file($file_tmp, $destination)) {
                $_SESSION['adjustment_form']['attachment'] = $new_filename;
            } else {
                $error = "Failed to upload file.";
            }
        } else {
            $error = "Unsupported file type. Please upload PDF, DOC, DOCX, JPG, JPEG, or PNG files only.";
        }
    } elseif (isset($_POST['next_step']) && $_POST['next_step'] == '5') {
        // Check if attachment is required when moving to step 5 (review)
        if (empty($_SESSION['adjustment_form']['attachment'])) {
            $error = "Supporting document is required. Please upload a file before proceeding.";
        }
    }

    // Handle navigation
    if (isset($_POST['next_step']) && empty($error)) {
        $next_step = (int)$_POST['next_step'];
        header("Location: ?step=" . $next_step);
        exit;
    }

    // Handle final submission
    if (isset($_POST['submit_request'])) {
        $form_data = $_SESSION['adjustment_form'];
        
        if (empty($form_data['log_date']) || empty($form_data['reason']) || empty($form_data['attachment'])) {
            $error = "Log date, reason, and supporting document are all required.";
        } else {
            $log_stmt = $pdo->prepare("SELECT time_in, time_out FROM time_logs WHERE employee_id = :id AND log_date = :date");
            $log_stmt->execute(['id' => $employee_id, 'date' => $form_data['log_date']]);
            $existing_log = $log_stmt->fetch();

            if (!$existing_log) {
                $createLog = $pdo->prepare("INSERT INTO time_logs (employee_id, log_date) VALUES (:id, :log_date)");
                $createLog->execute(['id' => $employee_id, 'log_date' => $form_data['log_date']]);
                $existing_log = ['time_in' => null, 'time_out' => null];
            }

            $insert = $pdo->prepare("
                INSERT INTO time_adjustment_requests 
                (employee_id, log_date, current_time_in, current_time_out, requested_time_in, requested_time_out, reason, attachment) 
                VALUES 
                (:employee_id, :log_date, :current_time_in, :current_time_out, :requested_time_in, :requested_time_out, :reason, :attachment)
            ");
            $insert->execute([
                'employee_id' => $employee_id,
                'log_date' => $form_data['log_date'],
                'current_time_in' => $existing_log['time_in'],
                'current_time_out' => $existing_log['time_out'],
                'requested_time_in' => $form_data['requested_time_in'] ?: null,
                'requested_time_out' => $form_data['requested_time_out'] ?: null,
                'reason' => $form_data['reason'],
                'attachment' => $form_data['attachment']
            ]);

            // Clear form data
            unset($_SESSION['adjustment_form']);
            $success = "Request submitted successfully!";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Time Adjustment Request - Step <?= $current_step ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #333; 
            min-height: 100vh;
            padding: 20px;
        }
        .container { 
            max-width: 800px; 
            margin: auto; 
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .header { 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-align: center; 
            padding: 40px 20px;
            position: relative;
        }
        
        .verified-badge {
            display: inline-flex;
            align-items: center;
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            border: 2px solid rgba(255, 255, 255, 0.3);
            color: white;
            padding: 12px 24px;
            border-radius: 50px;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 20px;
            gap: 10px;
            position: relative;
            z-index: 1;
        }
        
        .header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="20" cy="20" r="2" fill="white" opacity="0.1"/><circle cx="80" cy="80" r="2" fill="white" opacity="0.1"/></svg>');
        }
        
        .header h1 { 
            font-size: 32px; 
            font-weight: 800;
            margin-bottom: 10px;
            position: relative;
            z-index: 1;
        }
        
        .header p {
            font-size: 18px;
            opacity: 0.9;
            position: relative;
            z-index: 1;
        }
        
        .step-indicator { 
            display: flex; 
            justify-content: center;
            padding: 30px 20px;
            background: #f8f9fa;
            border-bottom: 1px solid #e9ecef;
        }
        
        .step-item {
            display: flex;
            align-items: center;
            margin: 0 10px;
        }
        
        .step { 
            width: 50px; 
            height: 50px; 
            background: #e9ecef; 
            color: #6c757d; 
            border-radius: 50%; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            font-weight: 700;
            font-size: 18px;
            transition: all 0.3s ease;
            position: relative;
        }
        
        .step.active { 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            transform: scale(1.1);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        .step.completed {
            background: #28a745;
            color: white;
        }
        
        .step.completed::after {
            content: '✓';
            position: absolute;
            font-size: 16px;
        }
        
        .step-connector {
            width: 40px;
            height: 3px;
            background: #e9ecef;
            margin: 0 5px;
        }
        
        .step-connector.completed {
            background: #28a745;
        }
        
        .form-content {
            padding: 50px;
            min-height: 400px;
        }
        
        .step-title {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 10px;
            color: #333;
            text-align: center;
        }
        
        .step-description {
            text-align: center;
            color: #666;
            font-size: 16px;
            margin-bottom: 40px;
        }
        
        .alert { 
            padding: 20px; 
            border-radius: 10px; 
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 600;
        }
        .alert-success { 
            background: #d4edda; 
            color: #155724;
            border: 2px solid #c3e6cb;
        }
        .alert-error { 
            background: #f8d7da; 
            color: #721c24;
            border: 2px solid #f5c6cb;
        }
        
        .form-group { 
            margin-bottom: 30px; 
        }
        
        .form-label { 
            display: block; 
            margin-bottom: 12px; 
            font-weight: 700;
            color: #333;
            font-size: 16px;
        }
        
        .form-label.required::after {
            content: ' *';
            color: #dc3545;
            font-weight: 700;
        }
        
        .form-control { 
            width: 100%; 
            padding: 16px 20px; 
            font-size: 16px; 
            border: 2px solid #e9ecef; 
            border-radius: 10px;
            transition: all 0.3s ease;
            background: #fff;
        }
        
        .form-control:focus { 
            border-color: #667eea; 
            outline: none;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .textarea { 
            min-height: 150px; 
            resize: vertical;
            font-family: inherit;
        }
        
        .file-upload { 
            border: 3px dashed #dc3545; 
            padding: 50px; 
            text-align: center; 
            border-radius: 15px; 
            cursor: pointer;
            transition: all 0.3s ease;
            background: #fff5f5;
            position: relative;
        }
        
        .file-upload.has-file {
            border-color: #28a745;
            background: #f8fff9;
        }
        
        .file-upload:hover { 
            background: #fee;
            border-color: #c82333;
        }
        
        .file-upload.has-file:hover {
            background: #e6ffed;
            border-color: #1e7e34;
        }
        
        .file-upload-icon {
            font-size: 48px;
            margin-bottom: 15px;
            color: #dc3545;
        }
        
        .file-upload.has-file .file-upload-icon {
            color: #28a745;
        }
        
        .file-upload-text {
            font-size: 18px;
            font-weight: 600;
            color: #721c24;
            margin-bottom: 8px;
        }
        
        .file-upload.has-file .file-upload-text {
            color: #155724;
        }
        
        .file-upload-subtext {
            font-size: 14px;
            color: #856404;
            margin-bottom: 10px;
        }
        
        .required-badge {
            display: inline-block;
            background: #dc3545;
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            margin-top: 10px;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.7; }
            100% { opacity: 1; }
        }
        
        .navigation {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 30px 50px;
            background: #f8f9fa;
            border-top: 1px solid #e9ecef;
        }
        
        .btn {
            padding: 15px 30px;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #5a6268;
            transform: translateY(-2px);
        }
        
        .btn-success {
            background: #28a745;
            color: white;
        }
        
        .btn-success:hover {
            background: #218838;
            transform: translateY(-2px);
        }
        
        .summary-item {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 15px;
            border-left: 4px solid #667eea;
        }
        
        .summary-label {
            font-weight: 700;
            color: #333;
            margin-bottom: 5px;
        }
        
        .summary-value {
            color: #666;
            font-size: 16px;
        }
        
        @media (max-width: 768px) {
            .form-content { 
                padding: 30px 20px; 
            }
            .navigation {
                padding: 20px;
                flex-direction: column;
                gap: 15px;
            }
            .step-indicator {
                padding: 20px 10px;
            }
            .step {
                width: 40px;
                height: 40px;
                font-size: 16px;
            }
            .step-connector {
                width: 20px;
            }
        }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <div class="verified-badge">
            🧩 Human Verified - Access Granted
        </div>
        <h1>Time Adjustment Request</h1>
        <p>Step <?= $current_step ?> of 5: Complete your request step by step</p>
    </div>

    <div class="step-indicator">
        <?php for ($i = 1; $i <= 5; $i++): ?>
            <?php if ($i > 1): ?>
                <div class="step-connector <?= $i <= $current_step ? 'completed' : '' ?>"></div>
            <?php endif; ?>
            <div class="step-item">
                <div class="step <?= $i == $current_step ? 'active' : ($i < $current_step ? 'completed' : '') ?>">
                    <?= $i < $current_step ? '' : $i ?>
                </div>
            </div>
        <?php endfor; ?>
    </div>

    <div class="form-content">
        <?php if (!empty($error)): ?>
            <div class="alert alert-error">⚠️ <?= htmlspecialchars($error) ?></div>
        <?php elseif (!empty($success)): ?>
            <div class="alert alert-success">✅ <?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

            <?php if ($current_step == 1): ?>
                <div class="step-title">📅 Select Date to Adjust</div>
                <div class="step-description">Choose the date for which you want to request a time adjustment</div>
                
                <div class="form-group">
                    <label class="form-label">Filter by Month</label>
                    <select id="monthFilter" class="form-control" style="margin-bottom: 20px;">
                        <option value="">-- All Months --</option>
                        <?php
                        $months = [];
                        foreach ($logs as $log) {
                            $month = (new DateTime($log['log_date']))->format('Y-m');
                            $months[$month] = (new DateTime($log['log_date']))->format('F Y');
                        }
                        foreach (array_unique($months) as $monthVal => $monthLabel): ?>
                            <option value="<?= $monthVal ?>"><?= $monthLabel ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label required">Select Date</label>
                    <select name="log_date" required class="form-control" id="logDateSelect">
                        <option value="">-- Choose a date --</option>
                        <?php foreach (array_reverse($logs) as $log): ?>
                            <?php
                            $d = new DateTime($log['log_date']);
                            $in = $log['time_in'] ? (new DateTime($log['time_in']))->format('g:i A') : 'No IN';
                            $out = $log['time_out'] ? (new DateTime($log['time_out']))->format('g:i A') : 'No OUT';
                            $selected = ($_SESSION['adjustment_form']['log_date'] == $log['log_date']) ? 'selected' : '';
                            $month = $d->format('Y-m');
                            ?>
                            <option value="<?= $log['log_date'] ?>" data-month="<?= $month ?>" <?= $selected ?>>
                                <?= $d->format('F j, Y (l)') ?> - In: <?= $in ?> | Out: <?= $out ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <script>
                    document.getElementById('monthFilter').addEventListener('change', function() {
                        var selectedMonth = this.value;
                        var options = document.querySelectorAll('#logDateSelect option[data-month]');
                        options.forEach(function(opt) {
                            if (!selectedMonth || opt.getAttribute('data-month') === selectedMonth) {
                                opt.style.display = '';
                            } else {
                                opt.style.display = 'none';
                            }
                        });
                    });
                </script>

            <?php elseif ($current_step == 2): ?>
                <div class="step-title">🕐 Set Requested Times</div>
                <div class="step-description">Enter your requested time in and time out (optional)</div>
                
                <div class="form-group">
                    <label class="form-label">Requested Time In</label>
                    <input type="time" name="requested_time_in" class="form-control" value="<?= $_SESSION['adjustment_form']['requested_time_in'] ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Requested Time Out</label>
                    <input type="time" name="requested_time_out" class="form-control" value="<?= $_SESSION['adjustment_form']['requested_time_out'] ?>">
                </div>

            <?php elseif ($current_step == 3): ?>
                <div class="step-title">📝 Provide Reason</div>
                <div class="step-description">Explain why you need this time adjustment</div>
                
                <div class="form-group">
                    <label class="form-label required">Reason for Adjustment</label>
                    <textarea name="reason" class="form-control textarea" required 
                              placeholder="Please provide a detailed explanation for this time adjustment request (e.g., medical appointment, emergency, technical issues, traffic delay, etc.)..."><?= $_SESSION['adjustment_form']['reason'] ?></textarea>
                </div>

            <?php elseif ($current_step == 4): ?>
                <div class="step-title">📎 Upload Document</div>
                <div class="step-description">Attach supporting documentation (REQUIRED)</div>
                
                <div class="form-group">
                    <label class="form-label required">Supporting Document</label>
                    <div class="file-upload <?= !empty($_SESSION['adjustment_form']['attachment']) ? 'has-file' : '' ?>" id="fileUpload">
                        <div class="file-upload-icon">📁</div>
                        <div class="file-upload-text">Click or drag file here</div>
                        <div class="file-upload-subtext">Supported: PDF, DOC, DOCX, JPG, JPEG, PNG (Max: 10MB)</div>
                        <div class="required-badge">REQUIRED FIELD</div>
                        <input type="file" name="attachment" id="fileInput" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" style="display: none;" required>
                        <div id="fileName" style="margin-top: 15px; font-weight: 600; color: #667eea;"></div>
                    </div>
                    <?php if (!empty($_SESSION['adjustment_form']['attachment'])): ?>
                        <div style="margin-top: 15px; color: #28a745; font-weight: 600;">
                            ✅ File uploaded: <?= $_SESSION['adjustment_form']['attachment'] ?>
                        </div>
                    <?php endif; ?>
                </div>

            <?php elseif ($current_step == 5): ?>
                <div class="step-title">📋 Review & Submit</div>
                <div class="step-description">Review your request details before submitting</div>
                
                <div class="summary-item">
                    <div class="summary-label">Selected Date:</div>
                    <div class="summary-value">
                        <?php if (!empty($_SESSION['adjustment_form']['log_date'])): ?>
                            <?= (new DateTime($_SESSION['adjustment_form']['log_date']))->format('F j, Y (l)') ?>
                        <?php else: ?>
                            <span style="color: #dc3545;">Not selected ❌</span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="summary-item">
                    <div class="summary-label">Requested Time In:</div>
                    <div class="summary-value">
                        <?= !empty($_SESSION['adjustment_form']['requested_time_in']) ? 
                            (new DateTime($_SESSION['adjustment_form']['requested_time_in']))->format('g:i A') : 
                            'Not specified' ?>
                    </div>
                </div>
                
                <div class="summary-item">
                    <div class="summary-label">Requested Time Out:</div>
                    <div class="summary-value">
                        <?= !empty($_SESSION['adjustment_form']['requested_time_out']) ? 
                            (new DateTime($_SESSION['adjustment_form']['requested_time_out']))->format('g:i A') : 
                            'Not specified' ?>
                    </div>
                </div>
                
                <div class="summary-item">
                    <div class="summary-label">Reason:</div>
                    <div class="summary-value">
                        <?php if (!empty($_SESSION['adjustment_form']['reason'])): ?>
                            <?= htmlspecialchars($_SESSION['adjustment_form']['reason']) ?>
                        <?php else: ?>
                            <span style="color: #dc3545;">Not provided ❌</span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="summary-item">
                    <div class="summary-label">Supporting Document:</div>
                    <div class="summary-value">
                        <?php if (!empty($_SESSION['adjustment_form']['attachment'])): ?>
                            <span style="color: #28a745;">✅ <?= $_SESSION['adjustment_form']['attachment'] ?></span>
                        <?php else: ?>
                            <span style="color: #dc3545;">❌ No document uploaded - REQUIRED</span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <?php if (empty($_SESSION['adjustment_form']['log_date']) || empty($_SESSION['adjustment_form']['reason']) || empty($_SESSION['adjustment_form']['attachment'])): ?>
                    <div class="alert alert-error">
                        ⚠️ Please complete all required fields before submitting your request.
                    </div>
                <?php endif; ?>
                
            <?php endif; ?>
        </form>
    </div>

    <div class="navigation">
        <div>
            <?php if ($current_step > 1): ?>
                <a href="?step=<?= $current_step - 1 ?>" class="btn btn-secondary">
                    ← Previous
                </a>
            <?php else: ?>
                <a href="../module/time_log_create.php" class="btn btn-secondary">
                    ← Back to Dashboard
                </a>
            <?php endif; ?>
        </div>
        
        <div>
            <?php if ($current_step < 5): ?>
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <input type="hidden" name="next_step" value="<?= $current_step + 1 ?>">
                    
                    <?php if ($current_step == 1): ?>
                        <input type="hidden" name="log_date" id="hiddenLogDate">
                    <?php elseif ($current_step == 2): ?>
                        <input type="hidden" name="requested_time_in" id="hiddenTimeIn">
                        <input type="hidden" name="requested_time_out" id="hiddenTimeOut">
                    <?php elseif ($current_step == 3): ?>
                        <input type="hidden" name="reason" id="hiddenReason">
                    <?php endif; ?>
                    
                    <button type="submit" class="btn btn-primary" id="nextBtn">
                        Next →
                    </button>
                </form>
            <?php else: ?>
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <button type="submit" name="submit_request" class="btn btn-success" 
                            <?= (empty($_SESSION['adjustment_form']['log_date']) || empty($_SESSION['adjustment_form']['reason']) || empty($_SESSION['adjustment_form']['attachment'])) ? 'disabled' : '' ?>>
                        📤 Submit Request
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    // File upload handling
    const fileUpload = document.getElementById('fileUpload');
    const fileInput = document.getElementById('fileInput');
    const fileName = document.getElementById('fileName');

    if (fileUpload && fileInput) {
        fileUpload.addEventListener('click', () => fileInput.click());
        fileUpload.addEventListener('dragover', (e) => {
            e.preventDefault();
            if (fileUpload.classList.contains('has-file')) {
                fileUpload.style.borderColor = '#1e7e34';
                fileUpload.style.background = '#e6ffed';
            } else {
                fileUpload.style.borderColor = '#c82333';
                fileUpload.style.background = '#fee';
            }
        });
        fileUpload.addEventListener('dragleave', () => {
            if (fileUpload.classList.contains('has-file')) {
                fileUpload.style.borderColor = '#28a745';
                fileUpload.style.background = '#f8fff9';
            } else {
                fileUpload.style.borderColor = '#dc3545';
                fileUpload.style.background = '#fff5f5';
            }
        });
        fileUpload.addEventListener('drop', (e) => {
            e.preventDefault();
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                fileInput.files = files;
                fileName.textContent = `Selected: ${files[0].name}`;
                fileName.style.display = 'block';
                fileUpload.classList.add('has-file');
                fileUpload.style.borderColor = '#28a745';
                fileUpload.style.background = '#f8fff9';
            }
        });
        fileInput.addEventListener('change', (e) => {
            if (e.target.files.length > 0) {
                fileName.textContent = `Selected: ${e.target.files[0].name}`;
                fileName.style.display = 'block';
                fileUpload.classList.add('has-file');
            }
        });
    }

    // Form validation and data passing
    const nextBtn = document.getElementById('nextBtn');
    if (nextBtn) {
        nextBtn.addEventListener('click', function(e) {
            <?php if ($current_step == 1): ?>
                const logDate = document.querySelector('select[name="log_date"]').value;
                if (!logDate) {
                    e.preventDefault();
                    alert('Please select a date to continue.');
                    return;
                }
                document.getElementById('hiddenLogDate').value = logDate;
            <?php elseif ($current_step == 2): ?>
                const timeIn = document.querySelector('input[name="requested_time_in"]').value;
                const timeOut = document.querySelector('input[name="requested_time_out"]').value;
                document.getElementById('hiddenTimeIn').value = timeIn;
                document.getElementById('hiddenTimeOut').value = timeOut;
            <?php elseif ($current_step == 3): ?>
                const reason = document.querySelector('textarea[name="reason"]').value;
                if (!reason.trim()) {
                    e.preventDefault();
                    alert('Please provide a reason for the adjustment.');
                    return;
                }
                document.getElementById('hiddenReason').value = reason;
            <?php elseif ($current_step == 4): ?>
                const fileInput = document.querySelector('input[name="attachment"]');
                const hasExistingFile = <?= !empty($_SESSION['adjustment_form']['attachment']) ? 'true' : 'false' ?>;
                
                if (!hasExistingFile && (!fileInput.files || fileInput.files.length === 0)) {
                    e.preventDefault();
                    alert('Please upload a supporting document before proceeding. This field is required.');
                    return;
                }
            <?php endif; ?>
        });
    }
</script>
</body>
</html>