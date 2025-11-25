<?php
// Test schedule switch database insertion
error_reporting(E_ALL);
ini_set('display_errors', 1);

include('Public/config/db.php');

echo "<h2>Schedule Switch Database Test</h2>";

// Test 1: Check if table exists
echo "<h3>Test 1: Check if table exists</h3>";
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'schedule_switch_requests'");
    $exists = $stmt->rowCount() > 0;
    if ($exists) {
        echo "<p style='color: green;'>✅ Table exists</p>";
    } else {
        echo "<p style='color: red;'>❌ Table does NOT exist!</p>";
        echo "<p>Run: <code>mysql -u root -p rss < create_schedule_switch_table.sql</code></p>";
        exit;
    }
} catch (PDOException $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
    exit;
}

// Test 2: Check table structure
echo "<h3>Test 2: Table Structure</h3>";
try {
    $stmt = $pdo->query("DESCRIBE schedule_switch_requests");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th></tr>";
    foreach ($columns as $col) {
        echo "<tr>";
        echo "<td>{$col['Field']}</td>";
        echo "<td>{$col['Type']}</td>";
        echo "<td>{$col['Null']}</td>";
        echo "<td>{$col['Key']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} catch (PDOException $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}

// Test 3: Try to insert a test record
echo "<h3>Test 3: Insert Test Record</h3>";
try {
    $stmt = $pdo->prepare("
        INSERT INTO schedule_switch_requests (
            employee_id, source_date, target_date, reason, attachment_path, 
            status, created_at
        ) VALUES (?, ?, ?, ?, ?, 'pending', NOW())
    ");
    
    $result = $stmt->execute([
        1, // test employee_id
        '2025-11-20', // source_date
        '2025-11-25', // target_date
        'Test insertion from debug script', // reason
        '../uploads/test.pdf' // attachment_path
    ]);
    
    if ($result) {
        $id = $pdo->lastInsertId();
        echo "<p style='color: green;'>✅ Successfully inserted test record with ID: $id</p>";
    } else {
        echo "<p style='color: red;'>❌ Insert failed but no exception thrown</p>";
    }
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Insert Error: " . $e->getMessage() . "</p>";
    echo "<p>Error Code: " . $e->getCode() . "</p>";
}

// Test 4: Check if record was inserted
echo "<h3>Test 4: Verify Records</h3>";
try {
    $stmt = $pdo->query("SELECT * FROM schedule_switch_requests ORDER BY created_at DESC LIMIT 5");
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($records) > 0) {
        echo "<p style='color: green;'>✅ Found " . count($records) . " record(s)</p>";
        echo "<table border='1' cellpadding='5'>";
        echo "<tr>";
        foreach (array_keys($records[0]) as $key) {
            echo "<th>{$key}</th>";
        }
        echo "</tr>";
        foreach ($records as $record) {
            echo "<tr>";
            foreach ($record as $value) {
                echo "<td>" . htmlspecialchars($value ?? 'NULL') . "</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: orange;'>⚠️ No records found in table</p>";
    }
} catch (PDOException $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<h3>Next Steps:</h3>";
echo "<ol>";
echo "<li>If table doesn't exist: Run the SQL file to create it</li>";
echo "<li>If test insert worked: Check your form submission</li>";
echo "<li>Check error logs at: <code>C:\\xampp\\apache\\logs\\error.log</code></li>";
echo "<li>Try submitting the form again and check this page</li>";
echo "</ol>";
?>
