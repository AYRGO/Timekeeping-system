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

// Spreadsheet data - EXACT values from user's final list
// VL = Column 2, SL = Column 3
// CAPIRAL GABRIEL = Probationary, PATAWARAN SHERRY & FERNANDEZ MARIANNE = Terminated (skip)
$spreadsheetData = [
    ['name' => 'AGAS, JILLIAN LAO', 'vl' => 6.25, 'sl' => 4, 'spl' => null],
    ['name' => 'AGUILAR, IAN MYCO', 'vl' => 6.25, 'sl' => 4, 'spl' => null],
    ['name' => 'ALIMURONG, JOEL LUSUNG', 'vl' => 5.75, 'sl' => 5, 'spl' => null],
    ['name' => 'ALVAREZ, JOHN BRYAN', 'vl' => 5.75, 'sl' => 4, 'spl' => null],
    ['name' => 'ANTONIO, VINCENT KEVIN SANTOS', 'vl' => 3.25, 'sl' => 4, 'spl' => null, 'db_lname' => 'Santos', 'db_fname' => 'Vincent Kevin'],
    ['name' => 'ANGELES, CHRISTINE', 'vl' => 1.75, 'sl' => 5, 'spl' => null],
    ['name' => 'ARNIGO, CEDRICK', 'vl' => 5.25, 'sl' => 4, 'spl' => null],
    ['name' => 'AUSTRIA, LOUIS FERNAND BALUYOT', 'vl' => 4.75, 'sl' => 5, 'spl' => null],
    ['name' => 'BACONGALLO, NIKA NUEVA', 'vl' => 2.00, 'sl' => 5, 'spl' => null],
    ['name' => 'BANSIL, KRISTIAN DAVID', 'vl' => 1.75, 'sl' => 1, 'spl' => null],
    ['name' => 'BAUTISTA, OLIVE SANTOS', 'vl' => 5.75, 'sl' => 5, 'spl' => 7.25],
    ['name' => 'BALDERAS, GLORY ANN GARCIA', 'vl' => 1.75, 'sl' => 5, 'spl' => null],
    ['name' => 'BENALLA, RENNECA VILLAPAÑA', 'vl' => 6.25, 'sl' => 5, 'spl' => null, 'db_lname' => 'Benalla', 'db_fname' => 'Reneeca Villapaña'],
    ['name' => 'BONDOC, FRANCIS EUGENE AGUHAYON', 'vl' => 6.25, 'sl' => 5, 'spl' => null],
    ['name' => 'BRIONES, JOHN MICHAEL', 'vl' => 3.25, 'sl' => 5, 'spl' => null],
    ['name' => 'CAMERINO, YRIS GAELLE PARREÑAS', 'vl' => 5.25, 'sl' => 4, 'spl' => null, 'db_lname' => 'Camerino', 'db_fname' => 'Yris Gaelle'],
    ['name' => 'CAPATI, ALLEN SOBREPENA', 'vl' => 2.50, 'sl' => 4, 'spl' => null],
    // CAPIRAL, GABRIEL - Probationary (skip)
    ['name' => 'CARAAN, SARAH', 'vl' => 5.75, 'sl' => 5, 'spl' => null],
    ['name' => 'CASTRO, AIZEL SANTOS', 'vl' => 6.25, 'sl' => 5, 'spl' => 6.25],
    ['name' => 'CATOLOGO, MARNIE', 'vl' => 1.75, 'sl' => 5, 'spl' => null, 'db_lname' => 'Catalogo', 'db_fname' => 'Marnie Perez'],
    ['name' => 'CELESTE, LOVELAINE GUDOY', 'vl' => 6.25, 'sl' => 5, 'spl' => null],
    ['name' => 'COLIS, REYMARK BRYAN SILVANO', 'vl' => 2.25, 'sl' => 5, 'spl' => null],
    ['name' => 'CRISANTO, ELRITZ T', 'vl' => 3.25, 'sl' => 5, 'spl' => null],
    ['name' => 'DAVID, RYAN ARWIN', 'vl' => 5.25, 'sl' => 5, 'spl' => null],
    ['name' => 'DIMLA, JHOSUA', 'vl' => 6.25, 'sl' => 5, 'spl' => null],
    ['name' => 'DELA CRUZ, JONAS', 'vl' => 5.50, 'sl' => 5, 'spl' => null],
    ['name' => 'DELA CRUZ, SHAINA DIMAYUGO', 'vl' => 2.00, 'sl' => 5, 'spl' => null],
    ['name' => 'DOLLENTES, MARIA NINA', 'vl' => 4.75, 'sl' => 5, 'spl' => null, 'db_lname' => 'Dollentes Cruz', 'db_fname' => 'Maria Nina'],
    ['name' => 'ESTANIO, ANGELICA ROSARIO', 'vl' => 7.50, 'sl' => 5, 'spl' => null],
    ['name' => 'FERNANDEZ, FRANCIS EMMANUEL VELOSO', 'vl' => 6.25, 'sl' => 5, 'spl' => null],
    // FERNANDEZ, MARIANNE JAE - Terminated (skip)
    ['name' => 'GATBONTON, ANALIZA TALOBAN', 'vl' => 1.25, 'sl' => 4, 'spl' => null],
    ['name' => 'GATBONTON, BEVERLY TALOBAN', 'vl' => 2.00, 'sl' => 5, 'spl' => null],
    ['name' => 'GUECO, JOHANA ROSE PEREZ', 'vl' => 2.25, 'sl' => 5, 'spl' => 8.25],
    ['name' => 'GUILLERMO, ALFIE', 'vl' => 6.25, 'sl' => 5, 'spl' => null],
    ['name' => 'JABINAL, ADONIS DEL MUNDO', 'vl' => 3.50, 'sl' => 5, 'spl' => null],
    ['name' => 'JOSAFAT, RENALYN', 'vl' => 1.75, 'sl' => 4, 'spl' => null],
    ['name' => 'LOZANO, ALDWIN JOHN', 'vl' => 3.75, 'sl' => 4, 'spl' => null, 'db_lname' => 'Arceo Lozano', 'db_fname' => 'Aldwin John'],
    ['name' => 'MACAPAGAL, JEFFRY TUAZON', 'vl' => 6.25, 'sl' => 5, 'spl' => null],
    ['name' => 'MACLANG, JULIE ANNE GUINTO', 'vl' => 4.75, 'sl' => 5, 'spl' => null],
    ['name' => 'MAKABENTA, ALTHEA', 'vl' => 6.25, 'sl' => 5, 'spl' => null],
    ['name' => 'MALINAO, ROGELIO DELA PENA', 'vl' => 3.00, 'sl' => 4, 'spl' => null, 'db_lname' => 'Malinao Jr', 'db_fname' => 'Rogelio'],
    ['name' => 'MANALILI, JOSHUA', 'vl' => 7.50, 'sl' => null, 'spl' => null],
    ['name' => 'MATAGA, EDITH DAVID', 'vl' => 6.25, 'sl' => 2.5, 'spl' => null, 'db_lname' => 'David Mataga', 'db_fname' => 'Edith'],
    ['name' => 'MAR, CHRISTIAN NIODA', 'vl' => 6.25, 'sl' => 5, 'spl' => null],
    ['name' => 'MCGREGOR, TRISHA MAE', 'vl' => 1.75, 'sl' => 5, 'spl' => null],
    ['name' => 'MENDOZA, SEAN JUSTINE F', 'vl' => 5.50, 'sl' => 5, 'spl' => null],
    ['name' => 'MONIS, JOSHWEA MERCADO', 'vl' => 6.25, 'sl' => 5, 'spl' => null],
    ['name' => 'NAVALON, EVANEL CAACBAY', 'vl' => 1.25, 'sl' => 4, 'spl' => null, 'db_lname' => 'Navalon', 'db_fname' => 'Evanel'],
    ['name' => 'NUÑEZ, IVY', 'vl' => 6.25, 'sl' => 4, 'spl' => null],
    ['name' => 'NUNEZA, DOU LESTER SABANDO', 'vl' => 5.75, 'sl' => 5, 'spl' => null],
    ['name' => 'OCAMPO, ALFRED NAGUIT', 'vl' => 2.75, 'sl' => 5, 'spl' => null],
    ['name' => 'OCAMPO, GODWIN', 'vl' => 1.75, 'sl' => 4, 'spl' => null],
    ['name' => 'OTSUKA, SHIGERU CENTINA', 'vl' => 3.75, 'sl' => 5, 'spl' => null],
    ['name' => 'PANGAN, CRISTINA MIRANDA', 'vl' => 5.25, 'sl' => 5, 'spl' => null],
    ['name' => 'PANGILINAN, ROI DANE', 'vl' => 7.50, 'sl' => null, 'spl' => null],
    // PATAWARAN, SHERRY - Terminated (skip)
    ['name' => 'YAP, APRYL', 'vl' => 1.75, 'sl' => 3, 'spl' => null],
    ['name' => 'PATRIMONIO, RYAN REX', 'vl' => 2.25, 'sl' => 3, 'spl' => null],
    ['name' => 'PINEDA, ERIKA SERIOSA', 'vl' => 2.50, 'sl' => 4, 'spl' => null],
    ['name' => 'PLATERO, MA. CHARISMA', 'vl' => 6.25, 'sl' => 5, 'spl' => null],
    ['name' => 'QUIZON, SHIRMILEY', 'vl' => 5.75, 'sl' => 5, 'spl' => null],
    ['name' => 'RONQUILLO, RHEGENE INGAT', 'vl' => 5.75, 'sl' => 5, 'spl' => null],
    ['name' => 'SAMODIO, JHUNEL CARLO TRAFALGAR', 'vl' => 5.75, 'sl' => 4, 'spl' => null],
    ['name' => 'SOLAYAO, JANETH SEDON', 'vl' => 2.50, 'sl' => 4, 'spl' => null],
    ['name' => 'SINGH, RAY JINDER VILLENA', 'vl' => 3.25, 'sl' => 4, 'spl' => null],
    ['name' => 'SORIANO, MARY ANN VALLEJOS', 'vl' => 2.50, 'sl' => 5, 'spl' => null],
    ['name' => 'TAYAO, ALEXANDER', 'vl' => 1.25, 'sl' => 4, 'spl' => null],
    ['name' => 'TOLOMIA, RICA JOY VIRAY', 'vl' => 6.25, 'sl' => 5, 'spl' => null],
    ['name' => 'TRINIDAD, JENNIFER M', 'vl' => 2.00, 'sl' => 4, 'spl' => null],
    ['name' => 'YULO, BRITTANY', 'vl' => 1.50, 'sl' => 4, 'spl' => null],
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
        // Check if we have explicit database names
        if (isset($emp['db_lname']) && isset($emp['db_fname'])) {
            $lastName = $emp['db_lname'];
            $firstName = $emp['db_fname'];
        } else {
            $nameParts = explode(', ', $emp['name']);
            $lastName = trim($nameParts[0]);
            
            // Handle first name with middle name
            $firstNameParts = isset($nameParts[1]) ? explode(' ', trim($nameParts[1])) : [''];
            $firstName = $firstNameParts[0];
        }
        
        // Strategy 1: Exact match on last name, first word of first name
        $stmt = $pdo->prepare("
            SELECT id, fname, lname, CONCAT(fname, ' ', lname) as full_name 
            FROM employees 
            WHERE UPPER(lname) = UPPER(?) 
            AND UPPER(fname) LIKE UPPER(?)
            AND status = 'active'
            LIMIT 1
        ");
        $stmt->execute([$lastName, $firstName . '%']);
        $employee = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Strategy 2: Last name match, first name contains
        if (!$employee) {
            $stmt = $pdo->prepare("
                SELECT id, fname, lname, CONCAT(fname, ' ', lname) as full_name 
                FROM employees 
                WHERE UPPER(lname) = UPPER(?) 
                AND UPPER(fname) LIKE UPPER(?)
                AND status = 'active'
                LIMIT 1
            ");
            $stmt->execute([$lastName, '%' . $firstName . '%']);
            $employee = $stmt->fetch(PDO::FETCH_ASSOC);
        }
        
        // Strategy 3: Match on full name pattern (handles compound last names)
        if (!$employee) {
            $fullSearchName = str_replace(', ', ' ', $emp['name']);
            $stmt = $pdo->prepare("
                SELECT id, fname, lname, CONCAT(fname, ' ', lname) as full_name 
                FROM employees 
                WHERE UPPER(CONCAT(fname, ' ', lname)) LIKE UPPER(?)
                AND status = 'active'
                LIMIT 1
            ");
            $stmt->execute(['%' . $lastName . '%' . $firstName . '%']);
            $employee = $stmt->fetch(PDO::FETCH_ASSOC);
        }
        
        // Strategy 4: SURNAME ONLY match (if first name doesn't match)
        if (!$employee) {
            $stmt = $pdo->prepare("
                SELECT id, fname, lname, CONCAT(fname, ' ', lname) as full_name 
                FROM employees 
                WHERE UPPER(lname) = UPPER(?)
                AND status = 'active'
                LIMIT 1
            ");
            $stmt->execute([$lastName]);
            $employee = $stmt->fetch(PDO::FETCH_ASSOC);
        }
        
        // Strategy 5: Partial surname match (for compound names like DELA CRUZ)
        if (!$employee && strpos($lastName, ' ') !== false) {
            $lastNameParts = explode(' ', $lastName);
            $lastPart = end($lastNameParts);
            $stmt = $pdo->prepare("
                SELECT id, fname, lname, CONCAT(fname, ' ', lname) as full_name 
                FROM employees 
                WHERE UPPER(lname) LIKE UPPER(?)
                AND status = 'active'
                LIMIT 1
            ");
            $stmt->execute(['%' . $lastPart . '%']);
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
