<?php
/**
 * UPDATE LEAVE CREDITS FROM SPREADSHEET DATA
 * 
 * This script updates leave_credits based on actual January 2026 data
 * Data source: User's spreadsheet showing VL/SL balances after January usage
 * 
 * IMPORTANT: Run with ?confirm=yes to execute
 */

// Hostinger production settings
$host = 'localhost';
$dbname = 'u816220874_calendartype';
$username = 'u816220874_calendartype';
$password = 'Gr33n$$wRf';

echo "<h2>UPDATE LEAVE CREDITS FROM SPREADSHEET - January 2026</h2>";
echo "<p>Date: " . date('Y-m-d H:i:s') . "</p><hr>";

// Safety check
if (!isset($_GET['confirm']) || $_GET['confirm'] !== 'yes') {
    echo "<p style='color:red;'><strong>⚠️ SAFETY CHECK:</strong> This will update leave credits based on spreadsheet data.</p>";
    echo "<p>Add <strong>?confirm=yes</strong> to the URL to execute.</p>";
    echo "<p>Example: update_leave_credits_from_spreadsheet.php?confirm=yes</p>";
    exit;
}

// Spreadsheet data: [Last Name, First Name] => ['vl' => X, 'sl' => Y, 'spl' => Z]
// Data extracted from user's spreadsheet - alphabetical by last name
$spreadsheetData = [
    ['name' => 'AGAS, JILLIAN LAO', 'vl' => 6.25, 'sl' => 5.25, 'spl' => null],
    ['name' => 'AGUILAR, IAN MYCO', 'vl' => 6.25, 'sl' => 5.25, 'spl' => null],
    ['name' => 'ALIMURONG, JOEL LUSUNG', 'vl' => 5.75, 'sl' => 6.25, 'spl' => null],
    ['name' => 'ALVAREZ, JOHN BRYAN', 'vl' => 5.75, 'sl' => 5.25, 'spl' => null],
    ['name' => 'ANTONIO, VINCENT KEVIN SANTOS', 'vl' => 3.25, 'sl' => 5.25, 'spl' => null],
    ['name' => 'ANGELES, CHRISTINE', 'vl' => 3.75, 'sl' => 6.25, 'spl' => null],
    ['name' => 'ARNISO, CEDRICK', 'vl' => 5.25, 'sl' => 6.25, 'spl' => null],
    ['name' => 'AUSTRIA, LOUIS FERNAND BALUYOT', 'vl' => 4.75, 'sl' => 6.25, 'spl' => null],
    ['name' => 'BACONGALLO, NINA NUEVA', 'vl' => 2.00, 'sl' => 6.25, 'spl' => null],
    ['name' => 'BARRIL, KRISTIAN DAVID', 'vl' => 1.75, 'sl' => 2.25, 'spl' => null],
    ['name' => 'BAUTISTA, OLIVE SANTOS', 'vl' => 5.75, 'sl' => 6.25, 'spl' => 7.25],
    ['name' => 'BALDERAS, GLORY ANN GARCIA', 'vl' => 1.75, 'sl' => 6.25, 'spl' => null],
    ['name' => 'BENALLA, REMECA VIRGINIA', 'vl' => 6.25, 'sl' => 6.25, 'spl' => null],
    ['name' => 'BONIOG, FRANCIS EUGENE AGUHAYION', 'vl' => 6.25, 'sl' => 6.25, 'spl' => null],
    ['name' => 'BRIONES, JOHN MICHAEL', 'vl' => 3.25, 'sl' => 6.25, 'spl' => null],
    ['name' => 'CAMERINO, YRIS GAELLE PARREÑAS', 'vl' => 5.25, 'sl' => 5.25, 'spl' => null],
    ['name' => 'CAPATI, ALLEN SOBREPEÑA', 'vl' => 2.50, 'sl' => 5.25, 'spl' => null],
    // CABRAL, GABRIEL - Yellow highlighted (Probationary - skip)
    ['name' => 'CARAAN, SARAH', 'vl' => 5.75, 'sl' => 6.25, 'spl' => null],
    ['name' => 'CASTRO, AIZEL SANTOS', 'vl' => 5.25, 'sl' => 6.25, 'spl' => 6.25],
    ['name' => 'CATOLOGO, MARINE', 'vl' => 1.75, 'sl' => 6.25, 'spl' => null],
    ['name' => 'CELESTE, LOVERAINE GUDOY', 'vl' => 6.25, 'sl' => 6.25, 'spl' => null],
    ['name' => 'COLIS, REYMARK BRYAN SILVANO', 'vl' => 2.25, 'sl' => 6.25, 'spl' => null],
    ['name' => 'CRISANTO, ELRITZ T', 'vl' => 3.25, 'sl' => 6.25, 'spl' => null],
    ['name' => 'DAVID, RYAN ARVIN', 'vl' => 5.25, 'sl' => 6.25, 'spl' => null],
    ['name' => 'DIMLA, JHONA', 'vl' => 6.25, 'sl' => 6.25, 'spl' => null],
    ['name' => 'DELA CRUZ, JONAS', 'vl' => 5.50, 'sl' => 6.25, 'spl' => null],
    ['name' => 'DELA CRUZ, SHAINA DIMAYUGO', 'vl' => 2.00, 'sl' => 6.25, 'spl' => null],
    ['name' => 'DOLLENTES, MARKI NINA', 'vl' => 4.75, 'sl' => 6.25, 'spl' => null],
    ['name' => 'ESTASIO, ANGELICA ROSARIO', 'vl' => 7.50, 'sl' => 6.25, 'spl' => null],
    ['name' => 'FERNANDEZ, FRANCIS EMMANUEL VELOSO', 'vl' => 6.25, 'sl' => 6.25, 'spl' => null],
    // FERNANDEZ, MARIANNE JAE - Red highlighted (Terminated - skip)
    ['name' => 'GATBONTON, ANALIZA TALOBAN', 'vl' => 1.25, 'sl' => 5.25, 'spl' => null],
    ['name' => 'GATBONTON, BEVERLY TALOBAN', 'vl' => 7.00, 'sl' => 6.25, 'spl' => null],
    ['name' => 'GUECO, JOHANA ROSE PEREZ', 'vl' => 2.25, 'sl' => 6.25, 'spl' => 8.25],
    ['name' => 'GUILLERMO, ALFIE', 'vl' => 6.25, 'sl' => 6.25, 'spl' => null],
    ['name' => 'JASALAL, ADONIS DEL MUNDO', 'vl' => 3.50, 'sl' => 6.25, 'spl' => null],
    ['name' => 'JONATA, RIVALYN', 'vl' => 1.75, 'sl' => 5.25, 'spl' => null],
    ['name' => 'LOZANO, ALDWIN JOHN', 'vl' => 3.75, 'sl' => 5.25, 'spl' => null],
    ['name' => 'MACAPAGAL, JEFFRY TUAZON', 'vl' => 6.25, 'sl' => 6.25, 'spl' => null],
    ['name' => 'MACLANG, JULIE ANNE GUINTO', 'vl' => 4.75, 'sl' => 6.25, 'spl' => null],
    ['name' => 'MAKARINTAL, ALTHEA', 'vl' => 6.25, 'sl' => 6.25, 'spl' => null],
    ['name' => 'MALINAO, ROGELIO DELA PEÑA', 'vl' => 3.00, 'sl' => 5.25, 'spl' => null],
    ['name' => 'MANALILI, JOSHUA', 'vl' => 7.50, 'sl' => null, 'spl' => null],
    ['name' => 'MATAGA, EDITH DAVID', 'vl' => 6.25, 'sl' => 3.75, 'spl' => null],
    ['name' => 'MAY, CHRISTIAN MODA', 'vl' => 6.25, 'sl' => 6.25, 'spl' => null],
    ['name' => 'MCGREGOR, TRISHA MAE', 'vl' => 1.75, 'sl' => 6.25, 'spl' => null],
    ['name' => 'MENDOZA, SEAN JUSTINE F', 'vl' => 5.50, 'sl' => 6.25, 'spl' => null],
    ['name' => 'MONIS, JOSHWEA MERCADO', 'vl' => 6.25, 'sl' => 6.25, 'spl' => null],
    ['name' => 'NAVAILON, DANIEL CAMCIAY', 'vl' => 3.25, 'sl' => 5.25, 'spl' => null],
    ['name' => 'NUÑEZ, IVY', 'vl' => 6.25, 'sl' => 5.25, 'spl' => null],
    ['name' => 'NUÑEZA, DOU LESTER SABONDO', 'vl' => 5.75, 'sl' => 6.25, 'spl' => null],
    ['name' => 'OCAMPO, ALFRED NAGUIT', 'vl' => 2.75, 'sl' => 6.25, 'spl' => null],
    ['name' => 'OCAMPO, GODWIN', 'vl' => 1.75, 'sl' => 5.25, 'spl' => null],
    ['name' => 'OTSUKA, SHIGERU CENTINA', 'vl' => 3.75, 'sl' => 6.25, 'spl' => null],
    ['name' => 'PANGAN, CRISTINA MIRANDA', 'vl' => 5.25, 'sl' => 6.25, 'spl' => null],
    ['name' => 'PADIGILAN, ROI DANE', 'vl' => 7.50, 'sl' => null, 'spl' => null],
    // PATAWARAN, SHERRY - Red highlighted (Terminated - skip)
    ['name' => 'YAP, APRYL', 'vl' => 1.75, 'sl' => 4.25, 'spl' => null],
    ['name' => 'PATRIMONIO, RYAN REX', 'vl' => 2.25, 'sl' => 4.25, 'spl' => null],
    ['name' => 'PINEDA, ERIKA SEROSA', 'vl' => 2.50, 'sl' => 5.25, 'spl' => null],
    ['name' => 'PLATERO, MA. CHARISMA', 'vl' => 6.25, 'sl' => 6.25, 'spl' => null],
    ['name' => 'QUIZON, SHIRMILEY', 'vl' => 5.75, 'sl' => 6.25, 'spl' => null],
    ['name' => 'RONQUILLO, RHEGENE INGAT', 'vl' => 5.75, 'sl' => 6.25, 'spl' => null],
    ['name' => 'SAMIYOD, JHINEL CARLO TRAFALGAR', 'vl' => 5.75, 'sl' => 5.25, 'spl' => null],
    ['name' => 'SOLANO, JANETH SOION', 'vl' => 3.50, 'sl' => 5.25, 'spl' => null],
    ['name' => 'SINGH, RAY JINDER VILLENA', 'vl' => 3.25, 'sl' => 5.25, 'spl' => null],
    ['name' => 'SORIANO, MARY ANN VALLEJOS', 'vl' => 2.50, 'sl' => 6.25, 'spl' => null],
    ['name' => 'TAYAO, ALEXANDER', 'vl' => 3.75, 'sl' => 5.25, 'spl' => null],
    ['name' => 'TOGONON, RICA JOY VIRAY', 'vl' => 6.25, 'sl' => 6.25, 'spl' => null],
    ['name' => 'TRINIDAD, JENNIFER M', 'vl' => 2.00, 'sl' => 5.25, 'spl' => null],
    ['name' => 'YULO, BRITTANY', 'vl' => 1.50, 'sl' => 5.25, 'spl' => null],
];

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<p style='color:green;'>✅ Connected to database</p>";
    
    // Start transaction
    $pdo->beginTransaction();
    
    $updated = 0;
    $notFound = [];
    $errors = [];
    
    echo "<h3>Processing Updates:</h3>";
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
    echo "<tr style='background:#333; color:white;'><th>Name</th><th>Employee ID</th><th>VL (New)</th><th>SL (New)</th><th>SPL</th><th>Status</th></tr>";
    
    foreach ($spreadsheetData as $emp) {
        $nameParts = explode(', ', $emp['name']);
        $lastName = trim($nameParts[0]);
        
        // Handle first name with middle name
        $firstNameParts = isset($nameParts[1]) ? explode(' ', trim($nameParts[1])) : [''];
        $firstName = $firstNameParts[0];
        
        // Find employee by name (fuzzy match on last name and first name)
        $stmt = $pdo->prepare("
            SELECT id, fname, lname, CONCAT(fname, ' ', lname) as full_name 
            FROM employees 
            WHERE UPPER(lname) LIKE UPPER(?) 
            AND UPPER(fname) LIKE UPPER(?)
            AND status = 'active'
            LIMIT 1
        ");
        $stmt->execute([$lastName . '%', $firstName . '%']);
        $employee = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$employee) {
            // Try reverse search (first name, last name in different order)
            $stmt = $pdo->prepare("
                SELECT id, fname, lname, CONCAT(fname, ' ', lname) as full_name 
                FROM employees 
                WHERE UPPER(CONCAT(lname, ', ', fname)) LIKE UPPER(?)
                AND status = 'active'
                LIMIT 1
            ");
            $stmt->execute(['%' . $lastName . '%']);
            $employee = $stmt->fetch(PDO::FETCH_ASSOC);
        }
        
        if ($employee) {
            $empId = $employee['id'];
            
            // Update VL (vacation)
            if ($emp['vl'] !== null) {
                $updateVL = $pdo->prepare("
                    UPDATE leave_credits 
                    SET balance = ?, updated_at = NOW() 
                    WHERE employee_id = ? AND leave_type = 'vacation' AND year = 2026
                ");
                $updateVL->execute([$emp['vl'], $empId]);
            }
            
            // Update SL (sick)
            if ($emp['sl'] !== null) {
                $updateSL = $pdo->prepare("
                    UPDATE leave_credits 
                    SET balance = ?, updated_at = NOW() 
                    WHERE employee_id = ? AND leave_type = 'sick' AND year = 2026
                ");
                $updateSL->execute([$emp['sl'], $empId]);
            }
            
            // Update SPL (solo_parent) if applicable
            if ($emp['spl'] !== null) {
                $updateSPL = $pdo->prepare("
                    UPDATE leave_credits 
                    SET balance = ?, updated_at = NOW() 
                    WHERE employee_id = ? AND leave_type = 'solo_parent' AND year = 2026
                ");
                $updateSPL->execute([$emp['spl'], $empId]);
            }
            
            echo "<tr>";
            echo "<td>{$emp['name']}</td>";
            echo "<td>{$empId}</td>";
            echo "<td>" . ($emp['vl'] ?? '-') . "</td>";
            echo "<td>" . ($emp['sl'] ?? '-') . "</td>";
            echo "<td>" . ($emp['spl'] ?? '-') . "</td>";
            echo "<td style='color:green;'>✅ Updated</td>";
            echo "</tr>";
            
            $updated++;
            
        } else {
            $notFound[] = $emp['name'];
            echo "<tr style='background:#ffeeee;'>";
            echo "<td>{$emp['name']}</td>";
            echo "<td>-</td>";
            echo "<td>" . ($emp['vl'] ?? '-') . "</td>";
            echo "<td>" . ($emp['sl'] ?? '-') . "</td>";
            echo "<td>" . ($emp['spl'] ?? '-') . "</td>";
            echo "<td style='color:red;'>❌ NOT FOUND</td>";
            echo "</tr>";
        }
    }
    
    echo "</table>";
    
    echo "<hr>";
    echo "<h3>Summary:</h3>";
    echo "<p>✅ Updated: <strong>$updated</strong> employees</p>";
    echo "<p>❌ Not Found: <strong>" . count($notFound) . "</strong> employees</p>";
    
    if (!empty($notFound)) {
        echo "<p><strong>Not Found Names:</strong></p><ul>";
        foreach ($notFound as $name) {
            echo "<li>$name</li>";
        }
        echo "</ul>";
    }
    
    // Commit transaction
    $pdo->commit();
    echo "<p style='color:green; font-size:18px;'><strong>✅ ALL CHANGES COMMITTED!</strong></p>";
    
} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "<p style='color:red;'>❌ DATABASE ERROR: " . $e->getMessage() . "</p>";
}
?>
