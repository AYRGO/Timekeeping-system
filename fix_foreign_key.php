<?php
// Fix Foreign Key Constraint Issue
// This removes the foreign key constraints that's preventing schedule overrides

include('Public/config/db.php');

try {
    echo "Removing foreign key constraints from calendar_schedule_overrides table...\n\n";
    
    // Drop the foreign key constraint on override_schedule_id
    try {
        $pdo->exec("ALTER TABLE calendar_schedule_overrides DROP FOREIGN KEY calendar_schedule_overrides_ibfk_3");
        echo "✅ Removed constraint on override_schedule_id\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), '1091') !== false) {
            echo "ℹ️ Constraint on override_schedule_id already removed\n";
        } else {
            throw $e;
        }
    }
    
    // Drop the foreign key constraint on original_schedule_id
    try {
        $pdo->exec("ALTER TABLE calendar_schedule_overrides DROP FOREIGN KEY calendar_schedule_overrides_ibfk_2");
        echo "✅ Removed constraint on original_schedule_id\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), '1091') !== false) {
            echo "ℹ️ Constraint on original_schedule_id already removed\n";
        } else {
            throw $e;
        }
    }
    
    echo "\n✅ All foreign key constraints removed successfully!\n\n";
    
    echo "The override_schedule_id column can now accept:\n";
    echo "- NULL (for OFF/Rest days)\n";
    echo "- Any schedule number 1-22 (direct schedule reference)\n";
    echo "- No foreign key validation required\n\n";
    
    echo "✅ You can now create schedule overrides without errors!\n";
    
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
