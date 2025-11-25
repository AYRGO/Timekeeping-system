<?php
// Simple script to run the cache setup SQL
require_once __DIR__ . '/Public/config/db.php';

echo "<h1>Running Cache Setup SQL...</h1>";
echo "<style>body{font-family:Arial;padding:20px;} .success{color:green;} .error{color:red;} pre{background:#f4f4f4;padding:10px;}</style>";

try {
    // Read SQL file
    $sqlFile = __DIR__ . '/create_daily_schedule_cache.sql';
    if (!file_exists($sqlFile)) {
        die("<p class='error'>SQL file not found: $sqlFile</p>");
    }
    
    $sql = file_get_contents($sqlFile);
    
    echo "<p>Executing SQL script...</p>";
    
    // Execute directly with mysql command line
    $host = 'localhost';
    $dbname = 'rss';
    $username = 'root';
    $password = '';
    
    // Save to temp file
    $tempFile = sys_get_temp_dir() . '/cache_setup.sql';
    file_put_contents($tempFile, $sql);
    
    // Run mysql command
    $command = "mysql -h $host -u $username $dbname < \"$tempFile\" 2>&1";
    $output = [];
    $returnVar = 0;
    exec($command, $output, $returnVar);
    
    if ($returnVar === 0) {
        echo "<p class='success'>✅ SQL executed successfully!</p>";
        
        // Verify table exists
        $check = $pdo->query("SHOW TABLES LIKE 'employee_daily_schedule_cache'")->fetch();
        if ($check) {
            echo "<p class='success'>✅ Table created successfully!</p>";
            
            // Check if data was populated
            $count = $pdo->query("SELECT COUNT(*) as cnt FROM employee_daily_schedule_cache")->fetch();
            echo "<p class='success'>✅ Cache has {$count['cnt']} records</p>";
            
            echo "<hr>";
            echo "<h2>Next Steps:</h2>";
            echo "<p><a href='test_cache_working.php' style='display:inline-block;padding:10px 20px;background:#4CAF50;color:white;text-decoration:none;border-radius:5px;'>Test Cache →</a></p>";
            echo "<p><a href='Public/views/employee-edit.php?id=1' style='display:inline-block;padding:10px 20px;background:#2196F3;color:white;text-decoration:none;border-radius:5px;'>View Calendar →</a></p>";
        } else {
            echo "<p class='error'>⚠️ Table not created. Output:</p>";
            echo "<pre>" . implode("\n", $output) . "</pre>";
        }
    } else {
        echo "<p class='error'>❌ Error executing SQL. Output:</p>";
        echo "<pre>" . implode("\n", $output) . "</pre>";
    }
    
    // Clean up
    @unlink($tempFile);
    
} catch (Exception $e) {
    echo "<p class='error'>Error: " . $e->getMessage() . "</p>";
}
?>
