<?php
/**
 * Debug script for OT Approval Page
 * Check if requests are being fetched correctly
 */

require_once '../../../config/db.php';

echo "<h1>🔍 OT Approval Debug</h1>";

// Test token validation
$token = $_GET['token'] ?? '';
$tokenType = $_GET['type'] ?? 'temporary';

echo "<h2>🔐 Token Validation</h2>";
echo "<ul>";
echo "<li><strong>Token:</strong> " . htmlspecialchars($token) . "</li>";
echo "<li><strong>Type:</strong> " . htmlspecialchars($tokenType) . "</li>";
echo "</ul>";

// Token verification functions (same as in main file)
function verifyTemporaryToken($token, $date = null) {
    $secret = 'quick-ot-approval-bugardi-2025';
    $date = $date ?: date('Y-m-d');
    $expectedToken = hash('sha256', 'quick' . $date . $secret);
    $yesterdayToken = hash('sha256', 'quick' . date('Y-m-d', strtotime('-1 day')) . $secret);
    
    return hash_equals($expectedToken, $token) || hash_equals($yesterdayToken, $token);
}

function verifyPermanentToken($token) {
    $secret = 'quick-ot-approval-bugardi-permanent-2025';
    $expectedToken = hash('sha256', 'quick-permanent' . $secret);
    
    return hash_equals($expectedToken, $token);
}

$isValidToken = false;
if ($tokenType === 'permanent') {
    $isValidToken = verifyPermanentToken($token);
} else {
    $isValidToken = verifyTemporaryToken($token);
}

echo "<p><strong>Token Valid:</strong> " . ($isValidToken ? '✅ Yes' : '❌ No') . "</p>";

if (!$isValidToken) {
    echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 8px; margin: 15px 0;'>";
    echo "<h3>❌ Invalid Token</h3>";
    echo "<p>The token is not valid. This will prevent access to the approval page.</p>";
    echo "</div>";
    exit;
}

echo "<h2>📊 Database Query Test</h2>";

// Test the database query
$whereConditions = ["LOWER(emp.company) = 'bugardi'"];
$params = [];

$whereClause = implode(' AND ', $whereConditions);

// Fetch overtime requests for Bugardi employees
$sql = "
    SELECT 
        ot.id,
        CONCAT(emp.fname, ' ', emp.lname) as employee_name,
        emp.position,
        DATE(ot.time_in) as date,
        TIME(ot.time_in) as start_time,
        TIME(ot.time_out) as end_time,
        ot.ot_duration as duration_hours,
        ot.reason,
        ot.status,
        ot.created_at,
        ot.time_in,
        ot.time_out
    FROM post_ot_requests ot
    JOIN employees emp ON ot.employee_id = emp.id
    WHERE $whereClause
    ORDER BY 
        CASE WHEN ot.status = 'Pending' THEN 1 ELSE 2 END,
        ot.created_at DESC
";

echo "<p><strong>SQL Query:</strong></p>";
echo "<pre style='background: #f8f9fa; padding: 15px; border-radius: 8px; overflow-x: auto; font-size: 12px;'>";
echo htmlspecialchars($sql);
echo "</pre>";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $overtime_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p><strong>Query Result:</strong> " . count($overtime_requests) . " records found</p>";
    
    if (!empty($overtime_requests)) {
        echo "<h3>📋 Found Requests:</h3>";
        echo "<table style='width: 100%; border-collapse: collapse; margin: 15px 0;'>";
        echo "<thead>";
        echo "<tr style='background: #f8f9fa;'>";
        echo "<th style='border: 1px solid #ddd; padding: 8px;'>ID</th>";
        echo "<th style='border: 1px solid #ddd; padding: 8px;'>Employee</th>";
        echo "<th style='border: 1px solid #ddd; padding: 8px;'>Date</th>";
        echo "<th style='border: 1px solid #ddd; padding: 8px;'>Hours</th>";
        echo "<th style='border: 1px solid #ddd; padding: 8px;'>Status</th>";
        echo "<th style='border: 1px solid #ddd; padding: 8px;'>Reason</th>";
        echo "</tr>";
        echo "</thead>";
        echo "<tbody>";
        
        foreach ($overtime_requests as $request) {
            $statusColor = ($request['status'] === 'Pending') ? '#ffc107' : (($request['status'] === 'Approved') ? '#28a745' : '#dc3545');
            echo "<tr>";
            echo "<td style='border: 1px solid #ddd; padding: 8px;'>#" . htmlspecialchars($request['id']) . "</td>";
            echo "<td style='border: 1px solid #ddd; padding: 8px;'>" . htmlspecialchars($request['employee_name']) . "</td>";
            echo "<td style='border: 1px solid #ddd; padding: 8px;'>" . htmlspecialchars($request['date']) . "</td>";
            echo "<td style='border: 1px solid #ddd; padding: 8px;'>" . htmlspecialchars($request['duration_hours']) . " hrs</td>";
            echo "<td style='border: 1px solid #ddd; padding: 8px; background: $statusColor; color: white; font-weight: bold;'>" . htmlspecialchars($request['status']) . "</td>";
            echo "<td style='border: 1px solid #ddd; padding: 8px; max-width: 200px; word-wrap: break-word;'>" . htmlspecialchars(substr($request['reason'], 0, 50)) . "...</td>";
            echo "</tr>";
        }
        
        echo "</tbody>";
        echo "</table>";
        
        // Count statistics
        $pendingCount = count(array_filter($overtime_requests, fn($req) => $req['status'] === 'Pending'));
        $approvedCount = count(array_filter($overtime_requests, fn($req) => $req['status'] === 'Approved'));
        $rejectedCount = count(array_filter($overtime_requests, fn($req) => $req['status'] === 'Rejected'));
        
        echo "<h3>📈 Statistics:</h3>";
        echo "<ul>";
        echo "<li><strong>Total Requests:</strong> " . count($overtime_requests) . "</li>";
        echo "<li><strong>Pending:</strong> $pendingCount (these should show approve/reject buttons)</li>";
        echo "<li><strong>Approved:</strong> $approvedCount</li>";
        echo "<li><strong>Rejected:</strong> $rejectedCount</li>";
        echo "</ul>";
        
        if ($pendingCount > 0) {
            echo "<div style='background: #d4edda; color: #155724; padding: 15px; border-radius: 8px; margin: 15px 0;'>";
            echo "<h3>✅ Pending Requests Found!</h3>";
            echo "<p>There are $pendingCount pending requests that should show approve/reject buttons on the main approval page.</p>";
            echo "</div>";
        } else {
            echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 8px; margin: 15px 0;'>";
            echo "<h3>❌ No Pending Requests</h3>";
            echo "<p>No pending requests found. The approve/reject buttons only appear for pending requests.</p>";
            echo "</div>";
        }
        
    } else {
        echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 8px; margin: 15px 0;'>";
        echo "<h3>❌ No Requests Found</h3>";
        echo "<p>No overtime requests found for Bugardi employees in the <code>post_ot_requests</code> table.</p>";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 8px; margin: 15px 0;'>";
    echo "<h3>❌ Database Error</h3>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}

echo "<h2>🔧 Troubleshooting</h2>";
echo "<ul>";
echo "<li><strong>Check if Bugardi employees have submitted OT requests</strong></li>";
echo "<li><strong>Verify requests are in 'post_ot_requests' table with status 'Pending'</strong></li>";
echo "<li><strong>Ensure employee company field is set to 'bugardi' (case insensitive)</strong></li>";
echo "</ul>";

echo "<h2>🚀 Quick Tests</h2>";
echo "<p><a href='../manual_trigger.php' style='background: #007bff; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px;'>📤 Manual WhatsApp Trigger</a></p>";
echo "<p><a href='quick_ot_approval.php?token=$token&type=$tokenType' style='background: #28a745; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px;'>🔗 Back to Approval Page</a></p>";
?>
