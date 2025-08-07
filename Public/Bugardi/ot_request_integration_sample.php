<?php
/**
 * Sample Integration: How to trigger Scott notifications
 * Add this code to your OT request creation forms
 */

// Example: In your OT request creation script, after inserting the request
function createOvertimeRequest($employeeId, $date, $startTime, $endTime, $reason, $duration) {
    global $pdo;
    
    try {
        // Insert the overtime request
        $stmt = $pdo->prepare("
            INSERT INTO overtime_requests (employee_id, date, start_time, end_time, reason, duration_hours, status, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, 'pending', NOW())
        ");
        
        $success = $stmt->execute([$employeeId, $date, $startTime, $endTime, $reason, $duration]);
        
        if ($success) {
            $requestId = $pdo->lastInsertId();
            
            // Get employee details
            $empStmt = $pdo->prepare("
                SELECT CONCAT(fname, ' ', lname) as employee_name, company 
                FROM employees 
                WHERE id = ?
            ");
            $empStmt->execute([$employeeId]);
            $employee = $empStmt->fetch(PDO::FETCH_ASSOC);
            
            // Only notify Scott for Bugardi employees
            if ($employee && strtolower($employee['company']) === 'bugardi') {
                // Include the notification system
                require_once __DIR__ . '/Bugardi/scott_notifications.php';
                
                // Send notification to Scott
                $notifier = new ScottNotificationSystem($pdo);
                $notifier->sendNewOTNotification(
                    $requestId,
                    $employee['employee_name'],
                    $date,
                    $reason,
                    $duration
                );
                
                // Log the notification attempt
                error_log("Scott notification sent for OT request #$requestId - " . $employee['employee_name']);
            }
            
            return $requestId;
        }
        
        return false;
        
    } catch (Exception $e) {
        error_log("Error creating OT request: " . $e->getMessage());
        return false;
    }
}

/**
 * Alternative: Simple function to call after OT request creation
 */
function notifyScottNewOTRequest($requestId) {
    global $pdo;
    
    // Get request details
    $stmt = $pdo->prepare("
        SELECT 
            ot.id, ot.date, ot.reason, ot.duration_hours,
            CONCAT(e.fname, ' ', e.lname) as employee_name,
            e.company
        FROM overtime_requests ot
        JOIN employees e ON ot.employee_id = e.id
        WHERE ot.id = ?
    ");
    
    $stmt->execute([$requestId]);
    $request = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($request && strtolower($request['company']) === 'bugardi') {
        require_once __DIR__ . '/Bugardi/scott_notifications.php';
        
        $notifier = new ScottNotificationSystem($pdo);
        $success = $notifier->sendNewOTNotification(
            $request['id'],
            $request['employee_name'],
            $request['date'],
            $request['reason'],
            $request['duration_hours']
        );
        
        if ($success) {
            error_log("Scott notification sent successfully for request #$requestId");
        } else {
            error_log("Failed to send Scott notification for request #$requestId");
        }
        
        return $success;
    }
    
    return false;
}

/**
 * Example usage in your existing OT request forms:
 * 
 * // After successfully creating an OT request
 * $requestId = createOvertimeRequest($employeeId, $date, $startTime, $endTime, $reason, $duration);
 * if ($requestId) {
 *     notifyScottNewOTRequest($requestId);
 *     // Redirect or show success message
 * }
 * 
 * // Or if you already have the request ID
 * notifyScottNewOTRequest($existingRequestId);
 */
?>
