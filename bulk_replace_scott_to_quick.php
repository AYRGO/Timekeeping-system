<?php
// Bulk replacement script for scott -> quick

$files = [
    'Public/Bugardi/quick-approval-system/email-notifications/quick_notifications.php',
    'Public/Bugardi/quick-approval-system/email-notifications/send_quick_notification.php',
    'Public/Bugardi/quick-approval-system/email-notifications/test_quick_email.php'
];

$replacements = [
    'Scott' => 'Quick',
    'scott' => 'quick',
    'ScottNotificationSystem' => 'QuickNotificationSystem',
    'scottEmail' => 'quickEmail',
    'Scott notification' => 'Quick notification',
    'scott-ot-quick-approval-2025' => 'quick-ot-quick-approval-2025',
    'scott-ot-approval-bugardi-temporary-2025' => 'quick-ot-approval-bugardi-temporary-2025',
    'scott-temporary' => 'quick-temporary',
    'scott_ot_approval.php' => 'quick-approval-system/approval-pages/quick_ot_approval.php',
    'notifyScottNewOTRequest' => 'notifyQuickNewOTRequest',
    'send_scott_notification.php' => 'send_quick_notification.php',
    'test_scott_email.php' => 'test_quick_email.php'
];

foreach ($files as $file) {
    $fullPath = $file;
    
    if (file_exists($fullPath)) {
        echo "Processing: $fullPath\n";
        $content = file_get_contents($fullPath);
        
        foreach ($replacements as $search => $replace) {
            $content = str_replace($search, $replace, $content);
        }
        
        file_put_contents($fullPath, $content);
        echo "  ✓ Updated successfully\n";
    } else {
        echo "  ✗ File not found: $fullPath\n";
    }
}

echo "\n✅ Bulk replacement completed!\n";
?>
