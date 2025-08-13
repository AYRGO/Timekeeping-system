<?php
/**
 * Quick Database Check for Pending OT Requests
 */

require_once __DIR__ . '/../../config/db.php';

echo "🔍 Checking for Pending OT Requests in Database...\n\n";

try {
    // Check post_ot_requests table
    $sql = "
        SELECT 
            ot.id,
            CONCAT(emp.fname, ' ', emp.lname) as employee_name,
            emp.company,
            ot.status,
            DATE(ot.created_at) as request_date
        FROM post_ot_requests ot
        JOIN employees emp ON ot.employee_id = emp.id
        WHERE LOWER(emp.company) = 'bugardi'
        ORDER BY ot.created_at DESC
        LIMIT 10
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($requests)) {
        echo "❌ No OT requests found for Bugardi employees.\n";
        echo "This explains why no approve/reject buttons are showing.\n\n";
        
        // Check if there are any employees with company = 'bugardi'
        $empSql = "SELECT id, fname, lname, company FROM employees WHERE LOWER(company) = 'bugardi' LIMIT 5";
        $empStmt = $pdo->prepare($empSql);
        $empStmt->execute();
        $employees = $empStmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($employees)) {
            echo "❌ No Bugardi employees found in database.\n";
        } else {
            echo "✅ Found Bugardi employees:\n";
            foreach ($employees as $emp) {
                echo "   - {$emp['fname']} {$emp['lname']} (ID: {$emp['id']}, Company: {$emp['company']})\n";
            }
        }
        
    } else {
        echo "✅ Found " . count($requests) . " OT requests for Bugardi employees:\n\n";
        
        $pendingCount = 0;
        foreach ($requests as $request) {
            if (strtolower($request['status']) === 'pending') {
                $statusIcon = '⏳';
            } elseif (strtolower($request['status']) === 'approved') {
                $statusIcon = '✅';
            } else {
                $statusIcon = '❌';
            }
            
            if (strtolower($request['status']) === 'pending') $pendingCount++;
            
            echo "$statusIcon Request #{$request['id']} - {$request['employee_name']} - {$request['status']} ({$request['request_date']})\n";
        }
        
        echo "\n📊 Summary:\n";
        echo "   - Total Requests: " . count($requests) . "\n";
        echo "   - Pending Requests: $pendingCount\n";
        
        if ($pendingCount > 0) {
            echo "\n✅ There are $pendingCount pending requests that should show approve/reject buttons!\n";
        } else {
            echo "\n❌ No pending requests found. Buttons only show for pending requests.\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Database Error: " . $e->getMessage() . "\n";
}

echo "\n🔧 To test the approval system:\n";
echo "1. Have a Bugardi employee submit an OT request\n";
echo "2. Check that the request status is 'Pending'\n";
echo "3. Access the approval page with a valid token\n";
echo "4. Approve/reject buttons should appear for pending requests\n";
?>
