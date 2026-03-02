<?php
/**
 * ADD 1.25 VL TO ALL EMPLOYEES — March 2, 2026
 * Adds +1.25 to vacation leave balance for all listed employees.
 * VL cap: 15.00 (will not exceed this).
 * 
 * USAGE:
 *   Dry run:  add_vl_accrual_mar2.php
 *   Execute:  add_vl_accrual_mar2.php?confirm=yes
 */

$host = 'localhost';
$dbname = 'u816220874_calendartype';
$username = 'u816220874_calendartype';
$password = 'Gr33n$$wRf';

date_default_timezone_set('Asia/Manila');

echo "<html><head><title>Add +1.25 VL — Mar 2, 2026</title>";
echo "<style>
body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
h2 { color: #333; border-bottom: 2px solid #2196F3; padding-bottom: 10px; }
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
.capped { background: #fff3cd !important; }
.notfound { background: #f8d7da !important; }
</style></head><body>";

echo "<h2>Add +1.25 VL to All Employees — March 2, 2026</h2>";
echo "<p><strong>Script Run:</strong> " . date('Y-m-d H:i:s') . "</p>";

$isDryRun = !isset($_GET['confirm']) || $_GET['confirm'] !== 'yes';
$VL_INCREMENT = 1.25;
$VL_MAX = 15.00;

if ($isDryRun) {
    echo "<div class='alert alert-warning'><strong>DRY RUN MODE</strong> — No changes will be made. Add <code>?confirm=yes</code> to execute.</div>";
} else {
    echo "<div class='alert alert-danger'><strong>LIVE MODE</strong> — Changes WILL be applied!</div>";
}

// Employee list (same as restore script)
$employeeNames = [
    ['AGAS', 'JILLIAN'],
    ['AGUILAR', 'IAN MYCO'],
    ['ALIMURONG', 'JOEL'],
    ['ALVAREZ', 'JOHN BRYAN'],
    ['SANTOS', 'VINCENT KEVIN'],
    ['ANGELES', 'CHRISTINE'],
    ['ARNIGO', 'CEDRICK'],
    ['AUSTRIA', 'LOUIS'],
    ['BACONGALLO', 'NIKA'],
    ['BANSIL', 'KRISTIAN'],
    ['BAUTISTA', 'OLIV'],
    ['BALDERAS', 'GLORY'],
    ['BENALLA', 'RENE'],
    ['BONDOC', 'FRANCIS'],
    ['BRIONES', 'JOHN MICHAEL'],
    ['CAMERINO', 'YRIS'],
    ['CAPATI', 'ALLEN'],
    ['CAPIRAL', 'GABRIEL'],
    ['CARAAN', 'SARAH'],
    ['CASTRO', 'AIZEL'],
    ['CATALOGO', 'MARNIE'],
    ['CELESTE', 'LOVELAINE'],
    ['COLIS', 'REYMARK'],
    ['CRISANTO', 'ELRITZ'],
    ['DACQUIL', 'KIMBERLY'],
    ['DAVID', 'REBECCA'],
    ['DAVID', 'RYAN ARWIN'],
    ['DIMLA', 'JOHSUA'],
    ['DELA CRUZ', 'JONAS'],
    ['DELA CRUZ', 'SHAINA'],
    ['DOLLENTES', 'MARIA'],
    ['ESTANIO', 'ANGELICA'],
    ['FERNANDEZ', 'FRANCIS EMMANUEL'],
    ['FERNANDEZ', 'MARIANNE'],
    ['GATBONTON', 'ANALIZA'],
    ['GATBONTON', 'BEVERLY'],
    ['GUECO', 'JOHANA'],
    ['GUILLERMO', 'ALFIE'],
    ['JABINAL', 'ADONIS'],
    ['JOSAFAT', 'RENALYN'],
    ['LOZANO', 'ALDWIN'],
    ['MACAPAGAL', 'JEFFRY'],
    ['MACLANG', 'JULIE'],
    ['MAKABENTA', 'ALTHEA'],
    ['MALINAO', 'ROGELIO'],
    ['MANALILI', 'JOSHUA'],
    ['MATAGA', 'EDITH'],
    ['MAR', 'CHRISTIAN'],
    ['MCGREGOR', 'TRISHA'],
    ['MENDOZA', 'SEAN'],
    ['MONIS', 'JOSHWEA'],
    ['NAVALON', 'EVANEL'],
    ['NU', 'IVY'],
    ['NU', 'DOU LESTER'],
    ['OCAMPO', 'ALFRED'],
    ['OCAMPO', 'GODWIN'],
    ['OTSUKA', 'SHIGERU'],
    ['PANGAN', 'CRISTINA'],
    ['PANGILINAN', 'ROI'],
    ['PATAWARAN', 'SHERRY'],
    ['YAP', 'APRYL'],
    ['PATRIMONIO', 'R'],
    ['PINEDA', 'ERIKA'],
    ['PLATERO', 'MA'],
    ['QUIZON', 'SHIRMILEY'],
    ['RONQUILLO', 'RHEGENE'],
    ['SAMODIO', 'JHUNEL'],
    ['SOLAYAO', 'JANETH'],
    ['SINGH', 'RAY'],
    ['SORIANO', 'MARY ANN'],
    ['TAYAO', 'ALEXANDER'],
    ['TOLOMIA', 'RICA'],
    ['TRINIDAD', 'JENNIFER'],
    ['YULO', 'BRITTANY'],
];

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<div class='alert alert-success'>Connected to database</div>";
    
    // Get all employees
    $stmt = $pdo->query("SELECT id, fname, lname, CONCAT(fname, ' ', lname) as full_name, Emp_Type FROM employees ORDER BY lname, fname");
    $allEmployees = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get current 2026 VL balances
    $stmt = $pdo->query("
        SELECT lc.id, lc.employee_id, lc.balance
        FROM leave_credits lc 
        WHERE lc.year = 2026 AND lc.leave_type = 'vacation'
    ");
    $vlCredits = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $vlCredits[$row['employee_id']] = $row;
    }
    
    echo "<h2>VL +$VL_INCREMENT Plan</h2>";
    echo "<table>";
    echo "<tr><th>#</th><th>Employee</th><th>ID</th><th>Type</th><th>Current VL</th><th>+1.25</th><th>New VL</th><th>Status</th></tr>";
    
    $updateActions = [];
    $matchedCount = 0;
    $notFoundCount = 0;
    $cappedCount = 0;
    $rowNum = 0;
    
    foreach ($employeeNames as $data) {
        $searchLname = $data[0];
        $searchFname = $data[1];
        
        // Find employee by name match (same logic as restore script)
        $matchedEmp = null;
        foreach ($allEmployees as $emp) {
            $empLname = strtoupper($emp['lname']);
            $empFname = strtoupper($emp['fname']);
            $sLname = strtoupper($searchLname);
            $sFname = strtoupper($searchFname);
            
            $lnameMatch = false;
            if (strpos($empLname, $sLname) !== false || strpos($sLname, $empLname) !== false) {
                $lnameMatch = true;
            }
            if (!$lnameMatch) {
                $empLnameWords = explode(' ', $empLname);
                $sLnameWords = explode(' ', $sLname);
                foreach ($empLnameWords as $ew) {
                    foreach ($sLnameWords as $sw) {
                        if (strlen($ew) >= 3 && strlen($sw) >= 3 && ($ew === $sw || strpos($ew, $sw) === 0 || strpos($sw, $ew) === 0)) {
                            $lnameMatch = true;
                            break 2;
                        }
                    }
                }
            }
            if (!$lnameMatch) continue;
            
            $fnameMatch = false;
            $sFnameShort = substr($sFname, 0, 3);
            if (strpos($empFname, $sFname) === 0 || strpos($empFname, $sFnameShort) === 0 || strpos($sFname, substr($empFname, 0, 3)) === 0) {
                $fnameMatch = true;
            }
            if ($fnameMatch) {
                $matchedEmp = $emp;
                break;
            }
        }
        
        $rowNum++;
        
        if (!$matchedEmp) {
            $notFoundCount++;
            echo "<tr class='notfound'><td>$rowNum</td><td>$searchLname, $searchFname</td><td colspan='6'><strong>NOT FOUND</strong></td></tr>";
            continue;
        }
        
        $empId = $matchedEmp['id'];
        $matchedCount++;
        
        $vlRecord = $vlCredits[$empId] ?? null;
        $currentVL = $vlRecord ? floatval($vlRecord['balance']) : 0;
        $newVL = $currentVL + $VL_INCREMENT;
        $capped = false;
        
        if ($newVL > $VL_MAX) {
            $newVL = $VL_MAX;
            $capped = true;
            $cappedCount++;
        }
        
        $rowClass = $capped ? 'capped' : 'changed';
        $status = $capped ? "CAPPED at $VL_MAX" : "+$VL_INCREMENT";
        
        echo "<tr class='$rowClass'><td>$rowNum</td><td>{$matchedEmp['full_name']}</td><td>$empId</td><td>{$matchedEmp['Emp_Type']}</td>";
        echo "<td>$currentVL</td><td>+$VL_INCREMENT</td><td><strong>$newVL</strong></td><td>$status</td></tr>";
        
        if ($vlRecord) {
            $updateActions[] = ['id' => $vlRecord['id'], 'newBalance' => $newVL, 'name' => $matchedEmp['full_name'], 'emp_id' => $empId];
        } else {
            $updateActions[] = ['id' => null, 'newBalance' => $newVL, 'name' => $matchedEmp['full_name'], 'emp_id' => $empId, 'insert' => true];
        }
    }
    echo "</table>";
    
    echo "<div class='alert alert-info'>";
    echo "Employees matched: <strong>$matchedCount</strong> / " . count($employeeNames) . "<br>";
    echo "Will update: <strong>" . count($updateActions) . "</strong> VL records<br>";
    echo "Capped at $VL_MAX: <strong>$cappedCount</strong><br>";
    if ($notFoundCount > 0) echo "Not found: <strong>$notFoundCount</strong><br>";
    echo "</div>";
    
    // Execute
    if (!$isDryRun) {
        echo "<h2>Executing...</h2>";
        
        $pdo->beginTransaction();
        try {
            $updated = 0;
            $inserted = 0;
            
            foreach ($updateActions as $action) {
                if (isset($action['insert'])) {
                    $stmt = $pdo->prepare("INSERT INTO leave_credits (employee_id, leave_type, balance, carry_over, year, updated_at) VALUES (?, 'vacation', ?, 0, 2026, NOW())");
                    $stmt->execute([$action['emp_id'], $action['newBalance']]);
                    $inserted++;
                } else {
                    $stmt = $pdo->prepare("UPDATE leave_credits SET balance = ?, updated_at = NOW() WHERE id = ?");
                    $stmt->execute([$action['newBalance'], $action['id']]);
                    $updated++;
                }
            }
            
            $pdo->commit();
            
            echo "<div class='alert alert-success' style='font-size:18px;'><strong>DONE!</strong> Updated $updated, Inserted $inserted VL records (+$VL_INCREMENT each)</div>";
            
        } catch (Exception $e) {
            $pdo->rollBack();
            echo "<div class='alert alert-danger'>ROLLED BACK! Error: " . htmlspecialchars($e->getMessage()) . "</div>";
        }
    } else {
        echo "<h2>Ready to Execute</h2>";
        echo "<div class='alert alert-warning'>Add <code>?confirm=yes</code> to the URL to add +$VL_INCREMENT VL to all employees above.</div>";
    }
    
} catch (PDOException $e) {
    echo "<div class='alert alert-danger'>Database Error: " . htmlspecialchars($e->getMessage()) . "</div>";
}

echo "</body></html>";
?>
