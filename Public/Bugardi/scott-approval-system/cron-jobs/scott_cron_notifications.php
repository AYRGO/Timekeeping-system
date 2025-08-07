<?php
/**
 * Cron Job for Scott Notifications
 * Run this daily to send automatic reminders
 * 
 * Setup Instructions:
 * 1. Add to your server's crontab:
 *    0 9 * * 1-5 /usr/bin/php /path/to/your/project/Public/Bugardi/scott_cron_notifications.php
 *    (This runs Monday-Friday at 9 AM)
 * 
 * 2. For Windows Task Scheduler:
 *    Program: C:\xampp\php\php.exe
 *    Arguments: C:\xampp\htdocs\Timekeeping-system\Public\Bugardi\scott_cron_notifications.php
 *    Schedule: Daily at 9:00 AM, Monday-Friday
 */

require_once '../config/db.php';
require_once 'scott_notifications.php';

// Load environment variables if not already loaded
if (!isset($_ENV['SMTP_HOST'])) {
    function loadEnv($path) {
        if (!file_exists($path)) {
            return;
        }
        
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos(trim($line), '#') === 0) {
                continue;
            }
            
            list($name, $value) = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value);
            
            if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
                putenv(sprintf('%s=%s', $name, $value));
                $_ENV[$name] = $value;
                $_SERVER[$name] = $value;
            }
        }
    }
    
    loadEnv(__DIR__ . '/../../.env');
}

try {
    // Check if there are pending requests
    $stmt = $pdo->query("
        SELECT COUNT(*) as pending_count
        FROM overtime_requests ot
        JOIN employees e ON ot.employee_id = e.id
        WHERE ot.status = 'Pending' AND LOWER(e.company) = 'bugardi'
    ");
    
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $pendingCount = $result['pending_count'];
    
    // Log the cron execution
    error_log("Scott notification cron executed at " . date('Y-m-d H:i:s') . " - Found $pendingCount pending requests");
    
    // Only send notification if there are pending requests
    if ($pendingCount > 0) {
        $notifier = new ScottNotificationSystem($pdo);
        
        echo "Found $pendingCount pending OT requests for Bugardi\n";
        echo "Sending reminder email to Scott...\n";
        
        $success = $notifier->sendWeeklyReminder();
        
        if ($success) {
            echo "✅ SUCCESS: Reminder email sent to Scott about $pendingCount pending requests\n";
            error_log("SUCCESS: Scott reminder sent - $pendingCount pending requests");
        } else {
            echo "❌ ERROR: Failed to send reminder email\n";
            error_log("ERROR: Failed to send Scott reminder");
        }
    } else {
        echo "ℹ️  INFO: No pending requests to notify about\n";
        error_log("INFO: No pending requests for Scott reminder");
    }
    
} catch (Exception $e) {
    $errorMsg = "ERROR in Scott notification cron: " . $e->getMessage();
    echo $errorMsg . "\n";
    error_log($errorMsg);
}
?>
