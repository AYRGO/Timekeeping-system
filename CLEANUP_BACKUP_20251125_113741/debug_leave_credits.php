<?php
// Debug script to check leave credits data
session_start();
include('Public/config/db.php');

echo "<h2>Leave Credits Debug Information</h2>";
echo "<hr>";

// Check if user is logged in
$current_user_id = $_SESSION['employee']['id'] ?? null;
echo "<p><strong>Current User ID:</strong> " . ($current_user_id ?? 'NOT LOGGED IN') . "</p>";

if (!$current_user_id) {
    echo "<p style='color: red;'>ERROR: No user logged in!</p>";
    exit;
}

// Check leave_credits table structure
echo "<h3>1. Leave Credits Table Structure</h3>";
try {
    $stmt = $pdo->query("DESCRIBE leave_credits");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    foreach ($columns as $col) {
        echo "<tr>";
        echo "<td>" . $col['Field'] . "</td>";
        echo "<td>" . $col['Type'] . "</td>";
        echo "<td>" . $col['Null'] . "</td>";
        echo "<td>" . $col['Key'] . "</td>";
        echo "<td>" . ($col['Default'] ?? 'NULL') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} catch (PDOException $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}

// Check what's in the table for current user
echo "<h3>2. Current User's Leave Credits (All Years)</h3>";
try {
    $stmt = $pdo->prepare("SELECT * FROM leave_credits WHERE employee_id = ? ORDER BY year DESC, leave_type");
    $stmt->execute([$current_user_id]);
    $all_credits = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($all_credits)) {
        echo "<p style='color: orange;'>No leave credits found for employee ID: $current_user_id</p>";
    } else {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr>";
        foreach (array_keys($all_credits[0]) as $column) {
            echo "<th>$column</th>";
        }
        echo "</tr>";
        foreach ($all_credits as $row) {
            echo "<tr>";
            foreach ($row as $value) {
                echo "<td>" . ($value ?? 'NULL') . "</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
    }
} catch (PDOException $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}

// Check what the current query returns
echo "<h3>3. Current Query Results (Year: " . date('Y') . ")</h3>";
try {
    $stmt = $pdo->prepare("SELECT * FROM leave_credits WHERE employee_id = ? AND year = ? AND leave_type IN ('sick', 'vacation', 'paternity', 'maternity', 'solo_parent', 'bereavement')");
    $stmt->execute([$current_user_id, date('Y')]);
    $credits = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($credits)) {
        echo "<p style='color: orange;'>No credits found for year " . date('Y') . "</p>";
    } else {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr>";
        foreach (array_keys($credits[0]) as $column) {
            echo "<th>$column</th>";
        }
        echo "</tr>";
        foreach ($credits as $row) {
            echo "<tr>";
            foreach ($row as $value) {
                echo "<td>" . ($value ?? 'NULL') . "</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
        
        // Calculate total
        $totalDays = 0;
        foreach ($credits as $row) {
            if (isset($row['balance'])) {
                $totalDays += floatval($row['balance']);
            } elseif (isset($row['days'])) {
                $totalDays += floatval($row['days']);
            } elseif (isset($row['amount'])) {
                $totalDays += floatval($row['amount']);
            }
        }
        echo "<p><strong>Total Days Calculated:</strong> $totalDays</p>";
    }
} catch (PDOException $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}

// Check if there are any leave credits at all in the database
echo "<h3>4. Sample Leave Credits from Database (First 10 rows)</h3>";
try {
    $stmt = $pdo->query("SELECT * FROM leave_credits LIMIT 10");
    $sample = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($sample)) {
        echo "<p style='color: red;'>No leave credits found in entire database!</p>";
    } else {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr>";
        foreach (array_keys($sample[0]) as $column) {
            echo "<th>$column</th>";
        }
        echo "</tr>";
        foreach ($sample as $row) {
            echo "<tr>";
            foreach ($row as $value) {
                echo "<td>" . ($value ?? 'NULL') . "</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
    }
} catch (PDOException $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><em>Debug complete. Check the data above to identify the issue.</em></p>";
?>

<style>
    body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
    h2 { color: #333; }
    h3 { color: #666; margin-top: 20px; }
    table { background: white; margin: 10px 0; border-collapse: collapse; }
    th { background: #4CAF50; color: white; padding: 8px; }
    td { padding: 8px; }
    tr:nth-child(even) { background: #f9f9f9; }
</style>
