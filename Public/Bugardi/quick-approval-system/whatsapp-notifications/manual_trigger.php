<?php
/**
 * Manual WhatsApp Trigger for Existing OT Requests
 * Use this to send WhatsApp notifications for existing OT requests
 */

require_once '../../../config/db.php';
require_once 'CallMeBotWhatsApp.php';

echo "<h1>📤 Manual WhatsApp Trigger</h1>";

// Get recent Bugardi OT requests
$stmt = $pdo->prepare("
    SELECT 
        ot.id,
        CONCAT(emp.fname, ' ', emp.lname) as employee_name,
        emp.company,
        emp.position,
        ot.ot_duration,
        ot.reason,
        ot.status,
        ot.created_at,
        DATE(ot.time_in) as date,
        TIME(ot.time_in) as start_time,
        TIME(ot.time_out) as end_time
    FROM post_ot_requests ot
    JOIN employees emp ON ot.employee_id = emp.id
    WHERE LOWER(emp.company) = 'bugardi'
    ORDER BY ot.created_at DESC
    LIMIT 5
");
$stmt->execute();
$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (isset($_POST['send_whatsapp'])) {
    $requestId = (int)$_POST['request_id'];
    
    // Get the specific request
    $stmt = $pdo->prepare("
        SELECT 
            ot.id,
            CONCAT(emp.fname, ' ', emp.lname) as employee_name,
            emp.position,
            ot.ot_duration,
            ot.reason,
            ot.status,
            DATE(ot.time_in) as date,
            TIME(ot.time_in) as start_time,
            TIME(ot.time_out) as end_time
        FROM post_ot_requests ot
        JOIN employees emp ON ot.employee_id = emp.id
        WHERE ot.id = ? AND LOWER(emp.company) = 'bugardi'
    ");
    $stmt->execute([$requestId]);
    $request = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($request) {
        try {
            $whatsapp = new CallMeBotWhatsApp();
            
            $otData = [
                'employee_name' => $request['employee_name'],
                'position' => $request['position'],
                'date' => $request['date'],
                'start_time' => $request['start_time'],
                'end_time' => $request['end_time'],
                'duration_hours' => $request['ot_duration'],
                'reason' => $request['reason']
            ];
            
            $result = $whatsapp->sendOTNotification($requestId, $otData);
            
            if ($result) {
                echo "<div style='background: #d4edda; color: #155724; padding: 15px; border-radius: 8px; margin: 15px 0;'>";
                echo "<h3>✅ WhatsApp Sent Successfully!</h3>";
                echo "<p>Notification sent for OT Request #$requestId</p>";
                echo "<p>Employee: " . htmlspecialchars($request['employee_name']) . "</p>";
                echo "<p>Check your WhatsApp at +639762477146</p>";
                echo "</div>";
            } else {
                echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 8px; margin: 15px 0;'>";
                echo "<h3>❌ WhatsApp Failed</h3>";
                echo "<p>Could not send notification for OT Request #$requestId</p>";
                echo "</div>";
            }
            
        } catch (Exception $e) {
            echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 8px; margin: 15px 0;'>";
            echo "<h3>❌ Error</h3>";
            echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
            echo "</div>";
        }
    } else {
        echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 8px; margin: 15px 0;'>";
        echo "<h3>❌ Request Not Found</h3>";
        echo "<p>Could not find Bugardi OT request with ID #$requestId</p>";
        echo "</div>";
    }
}

echo "<h2>📋 Recent Bugardi OT Requests</h2>";

if (!empty($requests)) {
    echo "<div style='background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 4px rgba(0,0,0,0.1);'>";
    echo "<table style='width: 100%; border-collapse: collapse;'>";
    echo "<thead style='background: #f8f9fa;'>";
    echo "<tr>";
    echo "<th style='padding: 12px; text-align: left; border-bottom: 1px solid #dee2e6;'>ID</th>";
    echo "<th style='padding: 12px; text-align: left; border-bottom: 1px solid #dee2e6;'>Employee</th>";
    echo "<th style='padding: 12px; text-align: left; border-bottom: 1px solid #dee2e6;'>Date</th>";
    echo "<th style='padding: 12px; text-align: left; border-bottom: 1px solid #dee2e6;'>Hours</th>";
    echo "<th style='padding: 12px; text-align: left; border-bottom: 1px solid #dee2e6;'>Status</th>";
    echo "<th style='padding: 12px; text-align: left; border-bottom: 1px solid #dee2e6;'>Action</th>";
    echo "</tr>";
    echo "</thead>";
    echo "<tbody>";
    
    foreach ($requests as $request) {
        $statusColor = [
            'Pending' => '#ffc107',
            'Approved' => '#28a745', 
            'Rejected' => '#dc3545'
        ][$request['status']] ?? '#6c757d';
        
        echo "<tr style='border-bottom: 1px solid #f8f9fa;'>";
        echo "<td style='padding: 12px;'>#" . $request['id'] . "</td>";
        echo "<td style='padding: 12px;'>" . htmlspecialchars($request['employee_name']) . "</td>";
        echo "<td style='padding: 12px;'>" . date('M d, Y', strtotime($request['date'])) . "</td>";
        echo "<td style='padding: 12px;'>" . $request['ot_duration'] . " hrs</td>";
        echo "<td style='padding: 12px;'>";
        echo "<span style='background: $statusColor; color: white; padding: 4px 8px; border-radius: 12px; font-size: 12px;'>";
        echo htmlspecialchars($request['status']);
        echo "</span>";
        echo "</td>";
        echo "<td style='padding: 12px;'>";
        echo "<form method='POST' style='display: inline;'>";
        echo "<input type='hidden' name='request_id' value='" . $request['id'] . "'>";
        echo "<button type='submit' name='send_whatsapp' ";
        echo "style='background: #25D366; color: white; border: none; padding: 8px 12px; border-radius: 4px; cursor: pointer; font-size: 12px;' ";
        echo "onclick='return confirm(\"Send WhatsApp notification for this OT request?\");'>";
        echo "📱 Send WhatsApp";
        echo "</button>";
        echo "</form>";
        echo "</td>";
        echo "</tr>";
    }
    
    echo "</tbody>";
    echo "</table>";
    echo "</div>";
} else {
    echo "<div style='background: #f8f9fa; padding: 20px; border-radius: 8px; text-align: center;'>";
    echo "<h3>No Bugardi OT Requests Found</h3>";
    echo "<p>No overtime requests found for Bugardi employees.</p>";
    echo "</div>";
}

echo "<hr style='margin: 30px 0;'>";

echo "<h2>🧪 Quick Tests</h2>";
echo "<div style='display: flex; gap: 10px; flex-wrap: wrap;'>";

echo "<a href='test_ot_whatsapp.php' style='background: #007bff; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px; font-size: 14px;'>";
echo "📱 Test WhatsApp System";
echo "</a>";

echo "<a href='../test_approval_link.php' style='background: #28a745; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px; font-size: 14px;'>";
echo "🔗 Test Approval Link";
echo "</a>";

echo "<a href='test_config.php' style='background: #6f42c1; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px; font-size: 14px;'>";
echo "⚙️ Check Configuration";
echo "</a>";

echo "</div>";

echo "<div style='background: #e8f5e8; padding: 15px; border-radius: 8px; border-left: 4px solid #4CAF50; margin: 20px 0;'>";
echo "<h3>💡 How to Test Complete Workflow:</h3>";
echo "<ol style='margin: 0;'>";
echo "<li><strong>Submit OT Request:</strong> Have a Bugardi employee submit an overtime request</li>";
echo "<li><strong>Auto WhatsApp:</strong> System automatically sends WhatsApp to +639762477146</li>";
echo "<li><strong>Manual Trigger:</strong> Or use this page to manually send WhatsApp for existing requests</li>";
echo "<li><strong>Check Phone:</strong> Look for message from +34684734044</li>";
echo "<li><strong>Click Link:</strong> Tap the approval link in the WhatsApp message</li>";
echo "</ol>";
echo "</div>";
?>
