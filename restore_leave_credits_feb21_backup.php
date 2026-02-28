<?php
/**
 * RESTORE LEAVE CREDITS — Based on Feb 21, 2026 Database Backup
 * 
 * This script restores leave_credits to the EXACT values from the Feb 21 backup,
 * then checks for any approved leaves between Feb 22-27 to deduct.
 * 
 * Also fixes the ROOT CAUSE: system_settings duplicate rows + missing UNIQUE index.
 * 
 * USAGE: Upload to production, run with ?confirm=yes
 * DRY RUN: Just visit the URL without parameters
 */

$host = 'localhost';
$dbname = 'u816220874_calendartype';
$username = 'u816220874_calendartype';
$password = 'Gr33n$$wRf';

date_default_timezone_set('Asia/Manila');

echo "<html><head><title>Restore Leave Credits - Feb 21 Backup</title>";
echo "<style>
body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
h2 { color: #333; border-bottom: 2px solid #dc3545; padding-bottom: 10px; }
h3 { color: #555; }
table { border-collapse: collapse; width: 100%; margin-bottom: 20px; background: white; }
th { background: #333; color: white; padding: 8px 12px; text-align: left; font-size: 13px; }
td { padding: 6px 12px; border: 1px solid #ddd; font-size: 13px; }
tr:nth-child(even) { background: #f9f9f9; }
.alert { padding: 15px; border-radius: 5px; margin: 10px 0; }
.alert-danger { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; }
.alert-warning { background: #fff3cd; border: 1px solid #ffc107; color: #856404; }
.alert-success { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; }
.alert-info { background: #d1ecf1; border: 1px solid #bee5eb; color: #0c5460; }
.changed { background: #d4edda !important; }
.nochange { opacity: 0.6; }
</style></head><body>";

echo "<h2>Restore Leave Credits — Feb 21, 2026 Database Backup</h2>";
echo "<p><strong>Script Run:</strong> " . date('Y-m-d H:i:s') . "</p>";
echo "<p><strong>Baseline:</strong> u816220874_calendartype.20260221080927.sql backup</p>";

$isDryRun = !isset($_GET['confirm']) || $_GET['confirm'] !== 'yes';

if ($isDryRun) {
    echo "<div class='alert alert-warning'><strong>DRY RUN MODE</strong> — No changes will be made. Review the plan below, then add <code>?confirm=yes</code> to execute.</div>";
} else {
    echo "<div class='alert alert-danger'><strong>LIVE MODE</strong> — Changes WILL be applied to the database!</div>";
}

// ==============================================
// Feb 21 Backup Data — ALL 2026 sick/vacation records
// Format: employee_id => ['sick' => [balance, carry_over], 'vacation' => [balance, carry_over]]
// carry_over = null means the field was NULL in the backup
// ==============================================
$feb21Data = [
    1    => ['sick' => [4.00, null],  'vacation' => [3.25, 5]],
    1009 => ['sick' => [5.00, null],  'vacation' => [1.25, 5]],
    14   => ['sick' => [4.00, null],  'vacation' => [6.25, 5]],
    16   => ['sick' => [4.00, null],  'vacation' => [5.25, 5]],
    4    => ['sick' => [5.00, null],  'vacation' => [5.75, 5]],
    74   => ['sick' => [4.00, null],  'vacation' => [4.75, 5]],
    68   => ['sick' => [5.00, null],  'vacation' => [1.75, 5]],
    1006 => ['sick' => [4.00, null],  'vacation' => [4.00, 0]],
    1042 => ['sick' => [0.00, null],  'vacation' => [1.25, 5]],
    28   => ['sick' => [5.00, null],  'vacation' => [3.25, 5]],
    1038 => ['sick' => [0.00, null],  'vacation' => [0.00, null]],
    84   => ['sick' => [0.00, null],  'vacation' => [7.50, null]],
    83   => ['sick' => [0.00, null],  'vacation' => [7.50, null]],
    85   => ['sick' => [0.00, null],  'vacation' => [0.00, null]],
    35   => ['sick' => [4.00, null],  'vacation' => [-0.50, 5]],
    27   => ['sick' => [1.00, null],  'vacation' => [1.75, 5]],
    75   => ['sick' => [5.00, null],  'vacation' => [5.75, 5]],
    1026 => ['sick' => [0.00, null],  'vacation' => [0.00, null]],
    17   => ['sick' => [6.25, null],  'vacation' => [2.25, 5]],
    50   => ['sick' => [5.00, null],  'vacation' => [6.25, 5]],
    70   => ['sick' => [5.00, null],  'vacation' => [3.25, 5]],
    34   => ['sick' => [5.00, null],  'vacation' => [3.75, 5]],
    1039 => ['sick' => [0.00, null],  'vacation' => [0.00, null]],
    71   => ['sick' => [0.00, null],  'vacation' => [0.00, null]],
    56   => ['sick' => [4.00, null],  'vacation' => [1.50, 5]],
    82   => ['sick' => [0.00, null],  'vacation' => [1.25, 5]],
    76   => ['sick' => [5.00, null],  'vacation' => [5.75, 5]],
    5    => ['sick' => [5.00, null],  'vacation' => [6.25, 5]],
    37   => ['sick' => [5.00, null],  'vacation' => [0.75, 0]],
    36   => ['sick' => [5.00, null],  'vacation' => [1.25, 5]],
    43   => ['sick' => [4.00, null],  'vacation' => [6.25, 5]],
    1041 => ['sick' => [0.00, null],  'vacation' => [0.00, null]],
    6    => ['sick' => [5.00, null],  'vacation' => [2.25, 5]],
    24   => ['sick' => [5.00, null],  'vacation' => [2.75, 5]],
    1002 => ['sick' => [0.00, null],  'vacation' => [1.25, 5]],
    1030 => ['sick' => [0.00, null],  'vacation' => [0.00, null]],
    1025 => ['sick' => [0.00, null],  'vacation' => [0.00, null]],
    73   => ['sick' => [4.00, null],  'vacation' => [5.25, 5]],
    1027 => ['sick' => [5.00, null],  'vacation' => [7.50, 0]],
    86   => ['sick' => [5.00, null],  'vacation' => [4.50, 5]],
    1005 => ['sick' => [5.00, null],  'vacation' => [5.25, 0]],
    2    => ['sick' => [5.00, null],  'vacation' => [2.00, 5]],
    78   => ['sick' => [4.00, null],  'vacation' => [1.50, 5]],
    21   => ['sick' => [4.00, null],  'vacation' => [2.00, 5]],
    1004 => ['sick' => [5.00, null],  'vacation' => [6.25, 5]],
    1033 => ['sick' => [0.00, null],  'vacation' => [0.00, null]],
    11   => ['sick' => [5.00, null],  'vacation' => [2.50, 5]],
    63   => ['sick' => [5.00, null],  'vacation' => [4.75, 5]],
    57   => ['sick' => [5.00, null],  'vacation' => [7.50, 5]],
    7    => ['sick' => [3.00, null],  'vacation' => [6.25, 5]],
    80   => ['sick' => [5.00, null],  'vacation' => [1.50, 0]],
    25   => ['sick' => [4.00, null],  'vacation' => [0.25, 5]],
    1037 => ['sick' => [0.00, null],  'vacation' => [0.00, null]],
    1031 => ['sick' => [0.00, null],  'vacation' => [0.00, null]],
    1029 => ['sick' => [0.00, null],  'vacation' => [0.00, null]],
    12   => ['sick' => [4.00, null],  'vacation' => [2.00, 5]],
    29   => ['sick' => [5.00, null],  'vacation' => [2.25, 5]],
    1036 => ['sick' => [0.00, null],  'vacation' => [0.00, null]],
    77   => ['sick' => [5.00, null],  'vacation' => [6.25, 5]],
    58   => ['sick' => [5.00, null],  'vacation' => [3.50, 5]],
    1007 => ['sick' => [0.00, null],  'vacation' => [0.00, 0]],
    1034 => ['sick' => [0.00, null],  'vacation' => [0.00, null]],
    3    => ['sick' => [4.00, null],  'vacation' => [1.75, 5]],
    33   => ['sick' => [3.00, null],  'vacation' => [3.75, 5]],
    55   => ['sick' => [5.00, null],  'vacation' => [5.75, 5]],
    49   => ['sick' => [5.00, null],  'vacation' => [4.75, 5]],
    52   => ['sick' => [4.00, null],  'vacation' => [6.25, 5]],
    13   => ['sick' => [4.00, null],  'vacation' => [2.00, 5]],
    53   => ['sick' => [4.00, null],  'vacation' => [8.75, 7.5]],
    18   => ['sick' => [1.50, null],  'vacation' => [4.25, 5]],
    69   => ['sick' => [5.00, null],  'vacation' => [1.75, 0]],
    22   => ['sick' => [5.00, null],  'vacation' => [5.00, 5]],
    59   => ['sick' => [5.00, null],  'vacation' => [7.50, 5]],
    15   => ['sick' => [4.00, null],  'vacation' => [1.25, 5]],
    46   => ['sick' => [4.00, null],  'vacation' => [8.75, 5]],
    65   => ['sick' => [5.00, null],  'vacation' => [5.75, 5]],
    20   => ['sick' => [5.00, null],  'vacation' => [2.75, 5]],
    66   => ['sick' => [4.00, null],  'vacation' => [1.75, 5]],
    1040 => ['sick' => [0.00, null],  'vacation' => [0.00, null]],
    9    => ['sick' => [5.00, null],  'vacation' => [3.75, 5]],
    1003 => ['sick' => [5.00, null],  'vacation' => [4.75, 5]],
    81   => ['sick' => [5.00, null],  'vacation' => [6.50, 5]],
    67   => ['sick' => [3.00, null],  'vacation' => [1.75, 5]],
    38   => ['sick' => [3.00, null],  'vacation' => [-2.75, 5]],
    30   => ['sick' => [3.00, null],  'vacation' => [2.50, 5]],
    41   => ['sick' => [5.00, null],  'vacation' => [6.25, 5]],
    1032 => ['sick' => [0.00, null],  'vacation' => [0.00, null]],
    62   => ['sick' => [5.00, null],  'vacation' => [5.75, 5]],
    10   => ['sick' => [5.00, null],  'vacation' => [4.75, 5]],
    31   => ['sick' => [2.00, null],  'vacation' => [5.75, 5]],
    61   => ['sick' => [4.00, null],  'vacation' => [3.25, 5]],
    60   => ['sick' => [4.00, null],  'vacation' => [2.50, 5]],
    1024 => ['sick' => [4.00, null],  'vacation' => [0.25, 5]],
    1035 => ['sick' => [0.00, null],  'vacation' => [1.25, 5]],
    1028 => ['sick' => [0.00, null],  'vacation' => [0.00, null]],
    47   => ['sick' => [4.00, null],  'vacation' => [1.75, 5]],
    1043 => ['sick' => [0.00, null],  'vacation' => [0.00, null]],
    1044 => ['sick' => [0.00, null],  'vacation' => [1.25, 5]],
    8    => ['sick' => [0.00, null],  'vacation' => [0.00, null]],
    1045 => ['sick' => [0.00, null],  'vacation' => [1.25, 5]],
    1046 => ['sick' => [0.00, null],  'vacation' => [0.00, null]],
    // Vacation-only records (no separate sick record with updated_at)
    26   => ['vacation' => [1.25, 5]],
    72   => ['vacation' => [1.25, 5]],
    79   => ['vacation' => [1.25, 5]],
    87   => ['vacation' => [1.25, 5]],
    88   => ['vacation' => [1.25, 5]],
    89   => ['vacation' => [1.25, 5]],
    // Zero-value employees from backup (all NULL dates)
    1051 => ['sick' => [0.00, null],  'vacation' => [0.00, null]],
    1049 => ['sick' => [0.00, null],  'vacation' => [0.00, null]],
    1048 => ['sick' => [0.00, null],  'vacation' => [0.00, null]],
    1054 => ['sick' => [0.00, null],  'vacation' => [0.00, null]],
    1052 => ['sick' => [0.00, null],  'vacation' => [0.00, null]],
    1050 => ['sick' => [0.00, null],  'vacation' => [0.00, null]],
    1055 => ['sick' => [0.00, null],  'vacation' => [0.00, null]],
    1056 => ['sick' => [0.00, null],  'vacation' => [0.00, null]],
    1053 => ['sick' => [0.00, null],  'vacation' => [0.00, null]],
    1060 => ['sick' => [0.00, null],  'vacation' => [0.00, null]],
    1059 => ['sick' => [0.00, null],  'vacation' => [0.00, null]],
];

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<div class='alert alert-success'>Connected to production database</div>";
    
    // ============================================
    // STEP 1: Check for approved leaves between Feb 22-27
    // ============================================
    echo "<h2>Step 1: Approved Leaves (Feb 22-27, 2026)</h2>";
    echo "<p>These will be deducted from the Feb 21 baseline to get Feb 27 values.</p>";
    
    $febLeaves = []; // employee_id => ['vacation' => days_used, 'sick' => days_used]
    
    try {
        // Check leave_requests
        $stmt = $pdo->query("
            SELECT lr.employee_id, lr.leave_type, lr.start_date, lr.end_date, lr.leave_dates,
                   CONCAT(e.fname, ' ', e.lname) as full_name,
                   DATEDIFF(lr.end_date, lr.start_date) + 1 as day_count
            FROM leave_requests lr
            JOIN employees e ON lr.employee_id = e.id
            WHERE lr.status = 'approved'
            AND lr.deleted = 0
            AND lr.leave_type IN ('vacation', 'sick', 'halfday', 'halfday_sick')
            AND lr.start_date >= '2026-02-22' AND lr.start_date <= '2026-02-27'
            ORDER BY e.lname, lr.leave_type
        ");
        $leaveRows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Also check post_leave_requests
        $stmt2 = $pdo->query("
            SELECT plr.employee_id, plr.leave_type, plr.start_date, plr.end_date,
                   '' as leave_dates,
                   CONCAT(e.fname, ' ', e.lname) as full_name,
                   DATEDIFF(plr.end_date, plr.start_date) + 1 as day_count
            FROM post_leave_requests plr
            JOIN employees e ON plr.employee_id = e.id
            WHERE plr.status = 'approved'
            AND plr.leave_type IN ('vacation', 'sick', 'halfday', 'halfday_sick')
            AND plr.start_date >= '2026-02-22' AND plr.start_date <= '2026-02-27'
            ORDER BY e.lname, plr.leave_type
        ");
        $leaveRows = array_merge($leaveRows, $stmt2->fetchAll(PDO::FETCH_ASSOC));
        
        if (count($leaveRows) > 0) {
            echo "<table><tr><th>Employee</th><th>Leave Type</th><th>Start</th><th>End</th><th>Days</th></tr>";
            foreach ($leaveRows as $lr) {
                $empId = $lr['employee_id'];
                $type = $lr['leave_type'];
                
                $days = 0;
                if (!empty($lr['leave_dates'])) {
                    $dates = json_decode($lr['leave_dates'], true);
                    $days = is_array($dates) ? count($dates) : $lr['day_count'];
                } else {
                    $days = $lr['day_count'];
                }
                
                $creditType = $type;
                if ($type === 'halfday') { $creditType = 'vacation'; $days = $days * 0.5; }
                if ($type === 'halfday_sick') { $creditType = 'sick'; $days = $days * 0.5; }
                
                if (!isset($febLeaves[$empId])) $febLeaves[$empId] = ['vacation' => 0, 'sick' => 0];
                $febLeaves[$empId][$creditType] += $days;
                
                echo "<tr><td>{$lr['full_name']}</td><td>$type</td><td>{$lr['start_date']}</td><td>{$lr['end_date']}</td><td>$days</td></tr>";
            }
            echo "</table>";
        } else {
            echo "<div class='alert alert-info'>No approved VL/SL leaves found between Feb 22-27.</div>";
        }
    } catch (Exception $e) {
        echo "<div class='alert alert-warning'>Could not check leave requests: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
    
    // ============================================
    // STEP 2: Get current production values and compare
    // ============================================
    echo "<h2>Step 2: Restore Plan (Feb 21 Backup → Current)</h2>";
    
    $stmt = $pdo->query("
        SELECT lc.id, lc.employee_id, lc.leave_type, lc.balance, lc.carry_over, lc.updated_at,
               CONCAT(e.fname, ' ', e.lname) as full_name, e.Emp_Type
        FROM leave_credits lc 
        JOIN employees e ON lc.employee_id = e.id 
        WHERE lc.year = 2026 
        AND lc.leave_type IN ('sick', 'vacation')
        ORDER BY e.lname, e.fname, lc.leave_type
    ");
    $currentCredits = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $restoreActions = [];
    $skipped = [];
    
    echo "<table>";
    echo "<tr><th>Employee</th><th>Type</th><th>Emp Type</th><th>Current Bal</th><th>Current CO</th><th>→ Restore Bal</th><th>→ Restore CO</th><th>Feb21 Backup</th><th>Feb22-27 Used</th><th>Status</th></tr>";
    
    foreach ($currentCredits as $credit) {
        $empId = $credit['employee_id'];
        $leaveType = $credit['leave_type'];
        $currentBalance = floatval($credit['balance']);
        $currentCarryOver = $credit['carry_over'] !== null ? floatval($credit['carry_over']) : null;
        
        // Find in Feb 21 backup
        if (!isset($feb21Data[$empId]) || !isset($feb21Data[$empId][$leaveType])) {
            // Employee not in Feb 21 backup — skip/flag
            $skipped[] = $credit;
            continue;
        }
        
        $backupBalance = $feb21Data[$empId][$leaveType][0];
        $backupCarryOver = $feb21Data[$empId][$leaveType][1];
        
        // Calculate target (backup - any Feb 22-27 leaves)
        $daysUsed = $febLeaves[$empId][$leaveType] ?? 0;
        $targetBalance = $backupBalance - $daysUsed;
        $targetCarryOver = $backupCarryOver;
        
        // Check if change needed
        $balanceChanged = (abs($currentBalance - $targetBalance) > 0.001);
        $carryChanged = ($currentCarryOver !== $targetCarryOver && 
                        !(is_null($currentCarryOver) && is_null($targetCarryOver)));
        if (!$balanceChanged && !$carryChanged) {
            // If carry_over is numeric comparison
            if ($currentCarryOver !== null && $targetCarryOver !== null) {
                $carryChanged = (abs($currentCarryOver - $targetCarryOver) > 0.001);
            }
        }
        
        $needsChange = $balanceChanged || $carryChanged;
        
        $rowClass = $needsChange ? "class='changed'" : "class='nochange'";
        $status = $needsChange ? '<strong>RESTORE</strong>' : 'OK';
        
        echo "<tr {$rowClass}>";
        echo "<td>{$credit['full_name']}</td>";
        echo "<td>{$leaveType}</td>";
        echo "<td>{$credit['Emp_Type']}</td>";
        echo "<td><strong>{$currentBalance}</strong></td>";
        echo "<td>" . ($currentCarryOver !== null ? $currentCarryOver : 'NULL') . "</td>";
        echo "<td><strong>{$targetBalance}</strong></td>";
        echo "<td>" . ($targetCarryOver !== null ? $targetCarryOver : 'NULL') . "</td>";
        echo "<td>{$backupBalance}</td>";
        echo "<td>" . ($daysUsed > 0 ? $daysUsed : '-') . "</td>";
        echo "<td>{$status}</td>";
        echo "</tr>";
        
        if ($needsChange) {
            $restoreActions[] = [
                'id' => $credit['id'],
                'employee_id' => $empId,
                'name' => $credit['full_name'],
                'type' => $leaveType,
                'current_balance' => $currentBalance,
                'current_carry' => $currentCarryOver,
                'target_balance' => $targetBalance,
                'target_carry' => $targetCarryOver,
            ];
        }
    }
    echo "</table>";
    
    echo "<div class='alert alert-info'>";
    echo "Total records to RESTORE: <strong>" . count($restoreActions) . "</strong><br>";
    echo "Total records already OK: <strong>" . (count($currentCredits) - count($restoreActions) - count($skipped)) . "</strong><br>";
    echo "Total records NOT in backup: <strong>" . count($skipped) . "</strong>";
    echo "</div>";
    
    // Show skipped employees
    if (!empty($skipped)) {
        echo "<h3>Employees NOT in Feb 21 Backup (will not be touched)</h3>";
        echo "<table><tr><th>Employee</th><th>Type</th><th>Emp Type</th><th>Balance</th><th>Carry Over</th></tr>";
        foreach ($skipped as $s) {
            echo "<tr>";
            echo "<td>{$s['full_name']}</td>";
            echo "<td>{$s['leave_type']}</td>";
            echo "<td>{$s['Emp_Type']}</td>";
            echo "<td>{$s['balance']}</td>";
            echo "<td>" . ($s['carry_over'] !== null ? $s['carry_over'] : 'NULL') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // ============================================
    // STEP 3: Execute restores
    // ============================================
    if (!$isDryRun) {
        echo "<h2>Step 3: Executing Restores...</h2>";
        
        $pdo->beginTransaction();
        
        try {
            $restoredCount = 0;
            
            foreach ($restoreActions as $action) {
                $stmt = $pdo->prepare("UPDATE leave_credits SET balance = ?, carry_over = ?, updated_at = NOW() WHERE id = ?");
                $stmt->execute([$action['target_balance'], $action['target_carry'], $action['id']]);
                $restoredCount++;
            }
            
            echo "<div class='alert alert-success'>Restored <strong>$restoredCount</strong> leave credit records</div>";
            
            // ============================================
            // STEP 4: Fix system_settings duplicates (ROOT CAUSE)
            // ============================================
            echo "<h2>Step 4: Fixing system_settings (Root Cause)</h2>";
            
            // Count duplicates
            $stmt = $pdo->query("SELECT setting_key, COUNT(*) as cnt FROM system_settings GROUP BY setting_key HAVING cnt > 1");
            $dupes = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (count($dupes) > 0) {
                echo "<div class='alert alert-danger'>Found duplicate keys: ";
                foreach ($dupes as $d) echo "<strong>{$d['setting_key']}</strong> ({$d['cnt']} rows) ";
                echo "</div>";
                
                // For each duplicate key, keep only the LATEST row
                foreach ($dupes as $d) {
                    $key = $d['setting_key'];
                    $stmt = $pdo->prepare("SELECT MAX(id) as max_id FROM system_settings WHERE setting_key = ?");
                    $stmt->execute([$key]);
                    $maxId = $stmt->fetchColumn();
                    
                    $stmt = $pdo->prepare("DELETE FROM system_settings WHERE setting_key = ? AND id != ?");
                    $stmt->execute([$key, $maxId]);
                    $deleted = $stmt->rowCount();
                    echo "<p>Deleted <strong>$deleted</strong> duplicate rows for <code>$key</code> (kept id=$maxId)</p>";
                }
            } else {
                echo "<div class='alert alert-info'>No duplicate system_settings rows found.</div>";
            }
            
            // Add UNIQUE index to prevent future duplicates
            try {
                $pdo->exec("ALTER TABLE system_settings ADD UNIQUE INDEX idx_setting_key_unique (setting_key)");
                echo "<div class='alert alert-success'>Added UNIQUE index on setting_key</div>";
            } catch (Exception $e) {
                if (strpos($e->getMessage(), 'Duplicate') !== false) {
                    // Still have duplicates — aggressive cleanup
                    $stmt = $pdo->query("SELECT DISTINCT setting_key FROM system_settings");
                    $keys = $stmt->fetchAll(PDO::FETCH_COLUMN);
                    foreach ($keys as $key) {
                        $stmt = $pdo->prepare("SELECT MAX(id) as max_id FROM system_settings WHERE setting_key = ?");
                        $stmt->execute([$key]);
                        $maxId = $stmt->fetchColumn();
                        $pdo->prepare("DELETE FROM system_settings WHERE setting_key = ? AND id != ?")->execute([$key, $maxId]);
                    }
                    try {
                        $pdo->exec("ALTER TABLE system_settings ADD UNIQUE INDEX idx_setting_key_unique (setting_key)");
                        echo "<div class='alert alert-success'>Added UNIQUE index (2nd attempt)</div>";
                    } catch (Exception $e2) {
                        echo "<div class='alert alert-warning'>Index note: " . htmlspecialchars($e2->getMessage()) . "</div>";
                    }
                } else if (strpos($e->getMessage(), 'Duplicate key name') !== false) {
                    echo "<div class='alert alert-info'>UNIQUE index already exists</div>";
                } else {
                    echo "<div class='alert alert-warning'>Index note: " . htmlspecialchars($e->getMessage()) . "</div>";
                }
            }
            
            // Set last_auto_accrual_month = 1 (Jan was last properly processed; Feb NOT yet done)
            $pdo->exec("UPDATE system_settings SET setting_value = '1', updated_at = NOW() WHERE setting_key = 'last_auto_accrual_month'");
            echo "<p>Set last_auto_accrual_month = 1 (Feb marked as unprocessed)</p>";
            
            // Disable auto-accrual until fixed code is deployed
            $pdo->exec("UPDATE system_settings SET setting_value = '0', updated_at = NOW() WHERE setting_key = 'auto_accrual_enabled'");
            echo "<p>Disabled auto-accrual (re-enable after deploying code fix)</p>";
            
            $pdo->commit();
            
            echo "<div class='alert alert-success' style='font-size:18px;'><strong>ALL CHANGES COMMITTED SUCCESSFULLY!</strong></div>";
            echo "<div class='alert alert-warning'>";
            echo "<strong>Next Steps:</strong><br>";
            echo "1. Deploy fixed <code>process_auto_accrual.php</code> to production<br>";
            echo "2. Deploy fixed <code>auto_accrual_cron.php</code> to production<br>";
            echo "3. Re-enable auto-accrual from admin dashboard<br>";
            echo "4. System will process ONE proper February accrual (+1.25 VL only) on the next last-day-of-month<br>";
            echo "</div>";
            
        } catch (Exception $e) {
            $pdo->rollBack();
            echo "<div class='alert alert-danger'>ROLLED BACK! Error: " . htmlspecialchars($e->getMessage()) . "</div>";
        }
    } else {
        echo "<h2>Step 3: Ready to Execute</h2>";
        echo "<div class='alert alert-warning'>";
        echo "<strong>Review the plan above.</strong> When ready, run with:<br>";
        echo "<code>?confirm=yes</code>";
        echo "</div>";
    }
    
} catch (PDOException $e) {
    echo "<div class='alert alert-danger'>Database Error: " . htmlspecialchars($e->getMessage()) . "</div>";
}

echo "</body></html>";
?>
