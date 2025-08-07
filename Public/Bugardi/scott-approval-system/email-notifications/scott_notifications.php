<?php
/**
 * Scott Notification System for OT Requests
 * Sends email notifications when new OT requests are submitted
 */

require_once '../../config/db.php';
require_once '../../../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Load environment variables
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

// Load environment variables
loadEnv(__DIR__ . '/../../.env');

class ScottNotificationSystem {
    private $pdo;
    private $scottEmail = 'cedrickarnigo1723@gmail.com'; // Scott's email - updated for testing
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Send notification to Scott about new OT requests
     */
    public function sendNewOTNotification($requestId, $employeeName, $date, $reason, $duration) {
        $mail = new PHPMailer(true);
        
        try {
            // Server settings from .env file
            $mail->isSMTP();
            $mail->Host       = $_ENV['SMTP_HOST'] ?? 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = $_ENV['SMTP_USER'] ?? 'it.resourcestaff@gmail.com';
            $mail->Password   = $_ENV['SMTP_PASS'] ?? 'fqbr ocgu jcfh jwdy';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = $_ENV['SMTP_PORT'] ?? 587;
            
            // Recipients
            $mail->setFrom($_ENV['SMTP_FROM_EMAIL'] ?? 'it.resourcestaff@gmail.com', $_ENV['SMTP_FROM_NAME'] ?? 'Bugardi Timekeeping System');
            $mail->addAddress($this->scottEmail, 'Scott');
            
            // Generate approval links
            $approveLink = $this->generateQuickApprovalLink($requestId, 'approve');
            $rejectLink = $this->generateQuickApprovalLink($requestId, 'reject');
            $viewLink = $this->generateViewLink();
            
            // Content
            $mail->isHTML(true);
            $mail->Subject = 'New Overtime Request - ' . $employeeName;
            
            $htmlBody = $this->getEmailTemplate($requestId, $employeeName, $date, $reason, $duration, $approveLink, $rejectLink, $viewLink);
            $mail->Body = $htmlBody;
            
            // Plain text version
            $mail->AltBody = "New overtime request from $employeeName for $date ($duration hours). Reason: $reason. View all requests: $viewLink";
            
            $mail->send();
            return true;
            
        } catch (Exception $e) {
            error_log("Scott notification failed: {$mail->ErrorInfo}");
            return false;
        }
    }
    
    /**
     * Generate quick approval links with tokens
     */
    private function generateQuickApprovalLink($requestId, $action) {
        $secret = 'scott-ot-quick-approval-2025';
        $token = hash('sha256', $requestId . $action . $secret . date('Y-m-d'));
        
        // Get base URL - use localhost as fallback for command line testing
        $baseUrl = $this->getBaseUrl();
        return $baseUrl . '/Timekeeping-system/Public/Bugardi/quick_approval.php?id=' . $requestId . '&action=' . $action . '&token=' . $token;
    }
    
    /**
     * Generate Scott's main view link
     */
    private function generateViewLink() {
        $secret = 'scott-ot-approval-bugardi-temporary-2025';
        $token = hash('sha256', 'scott-temporary' . $secret . date('Y-m-d'));
        
        // Get base URL - use localhost as fallback for command line testing
        $baseUrl = $this->getBaseUrl();
        return $baseUrl . '/Timekeeping-system/Public/Bugardi/scott_ot_approval.php?token=' . $token . '&type=temporary';
    }
    
    /**
     * Get the base URL for the application
     */
    private function getBaseUrl() {
        // If running from web server
        if (isset($_SERVER['HTTP_HOST']) && !empty($_SERVER['HTTP_HOST'])) {
            $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
            return $protocol . '://' . $_SERVER['HTTP_HOST'];
        }
        
        // Fallback for command line or when HTTP_HOST is not available
        // You can customize this based on your server setup
        return 'http://localhost';
    }
    
    /**
     * Get email template
     */
    private function getEmailTemplate($requestId, $employeeName, $date, $reason, $duration, $approveLink, $rejectLink, $viewLink) {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>New Overtime Request</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #ea580c, #dc2626); color: white; padding: 20px; border-radius: 8px 8px 0 0; text-align: center; }
                .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 8px 8px; }
                .request-card { background: white; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #ea580c; }
                .action-buttons { text-align: center; margin: 30px 0; }
                .btn { display: inline-block; padding: 12px 24px; margin: 0 10px; text-decoration: none; border-radius: 6px; font-weight: bold; }
                .btn-approve { background: #059669; color: white; }
                .btn-reject { background: #dc2626; color: white; }
                .btn-view { background: #2563eb; color: white; }
                .footer { text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd; color: #666; font-size: 12px; }
                .highlight { background: #fef3c7; padding: 2px 6px; border-radius: 4px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>🔔 New Overtime Request</h1>
                    <p>Resource Staff Solutions</p>
                </div>
                
                <div class='content'>
                    <p>Hi Scott,</p>
                    
                    <p>A new overtime request has been submitted and requires your approval:</p>
                    
                    <div class='request-card'>
                        <h3>📋 Request Details</h3>
                        <p><strong>Request ID:</strong> <span class='highlight'>#$requestId</span></p>
                        <p><strong>Employee:</strong> $employeeName</p>
                        <p><strong>Date:</strong> " . date('l, F d, Y', strtotime($date)) . "</p>
                        <p><strong>Duration:</strong> $duration hours</p>
                        <p><strong>Reason:</strong> $reason</p>
                    </div>
                    
                    <div class='action-buttons'>
                        <h3>🚀 Quick Actions</h3>
                        <a href='$approveLink' class='btn btn-approve'>✅ APPROVE</a>
                        <a href='$rejectLink' class='btn btn-reject'>❌ REJECT</a>
                    </div>
                    
                    <div style='text-align: center; margin-top: 20px;'>
                        <p><strong>Or view all pending requests:</strong></p>
                        <a href='$viewLink' class='btn btn-view'>📊 VIEW ALL REQUESTS</a>
                    </div>
                    
                    <div style='background: #e0f2fe; padding: 15px; border-radius: 6px; margin-top: 20px;'>
                        <p><strong>💡 Quick Tip:</strong> You can bookmark the 'View All Requests' link for easy access to your approval dashboard anytime!</p>
                    </div>
                </div>
                
                <div class='footer'>
                    <p>This notification was sent automatically by the Bugardi Timekeeping System.</p>
                    <p>© " . date('Y') . " ResourceStaffing Solutions. All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>";
    }
    
    /**
     * Send weekly reminder for pending requests
     */
    public function sendWeeklyReminder() {
        // Get pending requests with details
        $stmt = $this->pdo->query("
            SELECT 
                ot.id, ot.date, ot.reason, ot.duration_hours,
                CONCAT(e.fname, ' ', e.lname) as employee_name
            FROM overtime_requests ot
            JOIN employees e ON ot.employee_id = e.id
            WHERE ot.status = 'Pending' AND LOWER(e.company) = 'bugardi'
            ORDER BY ot.created_at DESC
        ");
        
        $pendingRequests = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $pendingCount = count($pendingRequests);
        
        if ($pendingCount > 0) {
            return $this->sendReminderEmail($pendingCount, $pendingRequests);
        }
        
        return true; // No emails to send, consider it successful
    }
    
    private function sendReminderEmail($pendingCount, $pendingRequests) {
        $mail = new PHPMailer(true);
        
        try {
            // Server settings from .env file
            $mail->isSMTP();
            $mail->Host       = $_ENV['SMTP_HOST'] ?? 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = $_ENV['SMTP_USER'] ?? 'it.resourcestaff@gmail.com';
            $mail->Password   = $_ENV['SMTP_PASS'] ?? 'fqbr ocgu jcfh jwdy';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = $_ENV['SMTP_PORT'] ?? 587;
            
            $mail->setFrom($_ENV['SMTP_FROM_EMAIL'] ?? 'it.resourcestaff@gmail.com', $_ENV['SMTP_FROM_NAME'] ?? 'Bugardi Timekeeping System');
            $mail->addAddress($this->scottEmail, 'Scott');
            
            $viewLink = $this->generateViewLink();
            
            $mail->isHTML(true);
            $mail->Subject = '⏰ Weekly Reminder: ' . $pendingCount . ' Pending OT Requests';
            $mail->Body = $this->getReminderTemplate($pendingCount, $pendingRequests, $viewLink);
            
            $mail->send();
            return true;
            
        } catch (Exception $e) {
            error_log("Weekly reminder failed: {$mail->ErrorInfo}");
            return false;
        }
    }
    
    private function getReminderTemplate($pendingCount, $pendingRequests, $viewLink) {
        // Generate request cards with individual approve/reject buttons
        $requestCards = '';
        foreach ($pendingRequests as $request) {
            $approveLink = $this->generateQuickApprovalLink($request['id'], 'approve');
            $rejectLink = $this->generateQuickApprovalLink($request['id'], 'reject');
            $formattedDate = date('M d, Y', strtotime($request['date']));
            
            $requestCards .= "
            <div style='background: white; margin: 15px 0; padding: 20px; border-radius: 8px; border-left: 4px solid #ea580c; box-shadow: 0 2px 4px rgba(0,0,0,0.1);'>
                <h4 style='margin: 0 0 10px 0; color: #1f2937; font-size: 16px;'>
                    <span style='background: #fef3c7; padding: 2px 6px; border-radius: 4px; font-weight: bold;'>#" . $request['id'] . "</span>
                    " . htmlspecialchars($request['employee_name']) . "
                </h4>
                <div style='margin-bottom: 15px; font-size: 14px; color: #4b5563;'>
                    <p style='margin: 5px 0;'><strong>📅 Date:</strong> $formattedDate</p>
                    <p style='margin: 5px 0;'><strong>⏱️ Duration:</strong> " . $request['duration_hours'] . " hours</p>
                    <p style='margin: 5px 0;'><strong>📝 Reason:</strong> " . htmlspecialchars($request['reason']) . "</p>
                </div>
                <div style='text-align: center;'>
                    <a href='$approveLink' style='display: inline-block; background: #059669; color: white; padding: 8px 16px; margin: 0 5px; text-decoration: none; border-radius: 5px; font-weight: bold; font-size: 14px;'>✅ APPROVE</a>
                    <a href='$rejectLink' style='display: inline-block; background: #dc2626; color: white; padding: 8px 16px; margin: 0 5px; text-decoration: none; border-radius: 5px; font-weight: bold; font-size: 14px;'>❌ REJECT</a>
                </div>
            </div>";
        }
        
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Weekly OT Reminder</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; }
                .container { max-width: 700px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #f59e0b, #ea580c); color: white; padding: 25px; border-radius: 12px 12px 0 0; text-align: center; }
                .content { background: #f9fafb; padding: 25px; border-radius: 0 0 12px 12px; }
                .summary { background: white; padding: 20px; border-radius: 10px; text-align: center; margin-bottom: 25px; border: 2px solid #fbbf24; }
                .count { font-size: 42px; font-weight: bold; color: #ea580c; margin: 10px 0; }
                .btn-main { display: inline-block; padding: 15px 30px; background: #2563eb; color: white; text-decoration: none; border-radius: 8px; font-weight: bold; margin: 20px 10px; font-size: 16px; }
                .requests-section { margin-top: 30px; }
                .section-title { color: #1f2937; font-size: 20px; font-weight: bold; margin-bottom: 15px; border-bottom: 2px solid #e5e7eb; padding-bottom: 10px; }
                .footer-note { margin-top: 30px; padding: 20px; background: #e0f2fe; border-radius: 8px; text-align: center; font-size: 14px; color: #0f766e; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>Weekly Reminder</h1>
                    <p style='margin: 0; font-size: 18px; opacity: 0.9;'>Bugardi OT Approvals</p>
                </div>
                
                <div class='content'>
                    <div class='summary'>
                        <p style='margin: 0 0 10px 0; font-size: 18px; color: #374151;'><strong>Hi Scott,</strong></p>
                        <p style='margin: 0; color: #6b7280;'>You have</p>
                        <div class='count'>$pendingCount</div>
                        <p style='margin: 0; color: #6b7280; font-size: 16px;'>pending overtime request" . ($pendingCount > 1 ? 's' : '') . " waiting for your approval</p>
                        
                        <a href='$viewLink' class='btn-main'>🌐 VIEW ALL ON WEBSITE</a>
                    </div>
                    
                    <div class='requests-section'>
                        <div class='section-title'>📋 Individual Requests - Quick Actions</div>
                        $requestCards
                    </div>
                    
                    <div class='footer-note'>
                        <p style='margin: 0; font-weight: bold;'>💡 Quick Actions:</p>
                        <p style='margin: 5px 0 0 0;'>Click <strong>APPROVE</strong> or <strong>REJECT</strong> for individual requests, or use <strong>VIEW ALL ON WEBSITE</strong> for the full dashboard experience.</p>
                        <p style='margin: 15px 0 0 0; font-size: 12px; opacity: 0.8;'>This is your weekly reminder. Contact your administrator to disable these notifications.</p>
                    </div>
                </div>
                
                <div style='text-align: center; margin-top: 20px; color: #9ca3af; font-size: 12px;'>
                    <p style='margin: 0;'>© " . date('Y') . " ResourceStaffing Solutions. All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>";
    }
}

/**
 * Function to trigger notification when new OT request is created
 * Call this from your OT request creation form
 */
function notifyScottNewOTRequest($requestId) {
    global $pdo;
    
    // Get request details
    $stmt = $pdo->prepare("
        SELECT 
            ot.id, ot.date, ot.reason, ot.duration_hours,
            CONCAT(e.fname, ' ', e.lname) as employee_name
        FROM overtime_requests ot
        JOIN employees e ON ot.employee_id = e.id
        WHERE ot.id = ? AND LOWER(e.company) = 'bugardi'
    ");
    
    $stmt->execute([$requestId]);
    $request = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($request) {
        $notifier = new ScottNotificationSystem($pdo);
        $notifier->sendNewOTNotification(
            $request['id'],
            $request['employee_name'],
            $request['date'],
            $request['reason'],
            $request['duration_hours']
        );
    }
}
?>
