<?php
/**
 * Simple Email Test for Scott Notification System
 * Tests email delivery without database logging
 */

// Include necessary files
require_once '../../vendor/autoload.php';
require_once '../config/db.php';
require_once 'scott_notifications.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

echo "🔧 Testing Scott Email Notification System\n";
echo str_repeat("=", 50) . "\n\n";

// Test database connection
echo "📊 Testing database connection...\n";
try {
    $testQuery = $pdo->query("SELECT 1");
    echo "✅ Database connection successful\n\n";
} catch (Exception $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "\n";
    exit(1);
}

// Check environment variables
echo "🔐 Checking SMTP configuration...\n";
$requiredVars = ['SMTP_HOST', 'SMTP_USER', 'SMTP_PASS', 'SMTP_PORT', 'SMTP_FROM_EMAIL', 'SMTP_FROM_NAME'];
$missingVars = [];

foreach ($requiredVars as $var) {
    if (empty($_ENV[$var])) {
        $missingVars[] = $var;
    } else {
        $masked = (strpos($var, 'PASS') !== false) ? str_repeat('*', 8) : $_ENV[$var];
        echo "✅ {$var}: {$masked}\n";
    }
}

if (!empty($missingVars)) {
    echo "❌ Missing environment variables: " . implode(', ', $missingVars) . "\n";
    exit(1);
}

echo "\n📧 Sending test email to cedrickarnigo1723@gmail.com...\n";

// Create test notification
$notificationSystem = new ScottNotificationSystem($pdo);

// Get a real OT request from the database
echo "📋 Getting real OT request from database...\n";
$stmt = $pdo->prepare("
    SELECT 
        ot.id, ot.date, ot.reason, ot.duration_hours,
        CONCAT(e.fname, ' ', e.lname) as employee_name
    FROM overtime_requests ot
    JOIN employees e ON ot.employee_id = e.id
    WHERE ot.status = 'Pending' AND LOWER(e.company) = 'bugardi'
    ORDER BY ot.id DESC
    LIMIT 1
");
$stmt->execute();
$request = $stmt->fetch(PDO::FETCH_ASSOC);

if ($request) {
    echo "✅ Found real request: ID {$request['id']} for {$request['employee_name']}\n";
    
    // Send test email with real OT request data
    $testResult = $notificationSystem->sendNewOTNotification(
        $request['id'],
        $request['employee_name'],
        $request['date'],
        $request['reason'],
        $request['duration_hours']
    );
    
    if ($testResult) {
        echo "✅ Test email sent successfully!\n";
        echo "📬 Check cedrickarnigo1723@gmail.com for the notification email\n\n";
        
        echo "📋 The email contains REAL data:\n";
        echo "- Request ID: #{$request['id']}\n";
        echo "- Employee: {$request['employee_name']}\n";
        echo "- Date: {$request['date']}\n";
        echo "- Duration: {$request['duration_hours']} hours\n";
        echo "- Reason: {$request['reason']}\n";
        echo "- Working approve/reject buttons\n";
        echo "- Link to full Scott approval page\n";
    } else {
        echo "❌ Email sending failed\n";
        echo "💡 Check SMTP configuration and logs\n";
    }
} else {
    echo "❌ No pending Bugardi OT requests found in database\n";
    echo "💡 Creating a test request...\n";
    
    // Create a test request with real employee
    $testEmployeeId = 52; // Althea Tansingco Makabenta from Bugardi
    $insertStmt = $pdo->prepare("
        INSERT INTO overtime_requests (employee_id, date, reason, duration_hours, status, created_at) 
        VALUES (?, ?, ?, ?, 'Pending', NOW())
    ");
    $insertStmt->execute([
        $testEmployeeId,
        '2025-08-06',
        'Testing Scott notification system with real data',
        4.0
    ]);
    
    $newRequestId = $pdo->lastInsertId();
    echo "✅ Created test request ID: $newRequestId\n";
    
    // Now get the new request and send notification
    $stmt = $pdo->prepare("
        SELECT 
            ot.id, ot.date, ot.reason, ot.duration_hours,
            CONCAT(e.fname, ' ', e.lname) as employee_name
        FROM overtime_requests ot
        JOIN employees e ON ot.employee_id = e.id
        WHERE ot.id = ?
    ");
    $stmt->execute([$newRequestId]);
    $request = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $testResult = $notificationSystem->sendNewOTNotification(
        $request['id'],
        $request['employee_name'],
        $request['date'],
        $request['reason'],
        $request['duration_hours']
    );
    
    if ($testResult) {
        echo "✅ Test email sent with new request!\n";
        echo "📬 Check cedrickarnigo1723@gmail.com\n";
    }
}

echo "\n" . str_repeat("=", 50) . "\n";
echo "🏁 Test completed!\n";
echo "📧 Target: cedrickarnigo1723@gmail.com\n";
echo "🔗 Scott's page: http://localhost/Timekeeping-system/Public/Bugardi/scott_ot_approval.php\n";
?>
