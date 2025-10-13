<?php
// File: stop_accrual_system.php
// Emergency stop script to disable all leave accrual processing
include('../config/db.php');

echo "🛑 EMERGENCY STOP - LEAVE ACCRUAL SYSTEM\n";
echo str_repeat("=", 50) . "\n";
echo "⏰ Stop Time: " . date('Y-m-d H:i:s') . "\n\n";

try {
    // 1. Set system mode to disabled
    echo "1. Disabling accrual system in database...\n";
    
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS system_settings (
            id INT PRIMARY KEY AUTO_INCREMENT,
            setting_key VARCHAR(255) UNIQUE NOT NULL,
            setting_value TEXT NOT NULL,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )
    ");

    $stmt = $pdo->prepare("
        INSERT INTO system_settings (setting_key, setting_value) 
        VALUES ('accrual_mode', 'disabled') 
        ON DUPLICATE KEY UPDATE setting_value = 'disabled', updated_at = NOW()
    ");
    $stmt->execute();
    echo "   ✅ Accrual mode set to DISABLED in database\n\n";

    // 2. Add system stop flag
    echo "2. Adding system stop flag...\n";
    $stmt = $pdo->prepare("
        INSERT INTO system_settings (setting_key, setting_value) 
        VALUES ('system_stop', 'true') 
        ON DUPLICATE KEY UPDATE setting_value = 'true', updated_at = NOW()
    ");
    $stmt->execute();
    echo "   ✅ System stop flag activated\n\n";

    // 3. Log the stop action
    echo "3. Logging stop action...\n";
    $stmt = $pdo->prepare("
        INSERT INTO system_settings (setting_key, setting_value) 
        VALUES ('last_stop_action', ?) 
        ON DUPLICATE KEY UPDATE setting_value = ?, updated_at = NOW()
    ");
    $stopTime = date('Y-m-d H:i:s');
    $stmt->execute([$stopTime, $stopTime]);
    echo "   ✅ Stop action logged at: {$stopTime}\n\n";

    echo str_repeat("=", 50) . "\n";
    echo "✅ ACCRUAL SYSTEM COMPLETELY STOPPED!\n\n";
    
    echo "📋 ACTIONS COMPLETED:\n";
    echo "   ✓ Database mode set to 'disabled'\n";
    echo "   ✓ System stop flag activated\n";
    echo "   ✓ Stop action logged\n\n";
    
    echo "🔧 NEXT STEPS TO COMPLETE SHUTDOWN:\n";
    echo "   1. Remove/disable cron job in Hostinger control panel\n";
    echo "   2. Delete accrual script files if desired\n";
    echo "   3. Any existing running processes will stop on next check\n\n";
    
    echo "🌐 HOSTINGER CRON JOB REMOVAL:\n";
    echo "   • Login to Hostinger control panel\n";
    echo "   • Go to Advanced → Cron Jobs\n";
    echo "   • Delete any cron jobs running PHP leave accrual scripts\n";
    echo "   • Look for jobs containing 'leave_accrual' or 'monthly_leave'\n\n";
    
} catch (Exception $e) {
    echo "❌ ERROR STOPPING SYSTEM: " . $e->getMessage() . "\n";
    echo "📍 File: " . $e->getFile() . " Line: " . $e->getLine() . "\n\n";
    
    echo "🔧 MANUAL STEPS TO STOP SYSTEM:\n";
    echo "   1. Go to Hostinger control panel\n";
    echo "   2. Navigate to Advanced → Cron Jobs\n";
    echo "   3. Delete all cron jobs related to leave accrual\n";
    echo "   4. Delete the following files from your server:\n";
    echo "      - Public/cron/monthly_leave_accrual_scheduler.php\n";
    echo "      - Public/cron/hostinger_leave_accrual.php\n";
    echo "      - Public/cron/smart_leave_accrual_scheduler.php\n";
    echo "      - Any other accrual-related files\n\n";
}

echo "⚠️  IMPORTANT: Even after running this script, you MUST remove\n";
echo "    the cron job from Hostinger's control panel to completely\n";
echo "    stop the system from running!\n\n";
?>