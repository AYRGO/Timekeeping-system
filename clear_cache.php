<?php
// Clear OPcache for specific files
if (function_exists('opcache_reset')) {
    opcache_reset();
    echo "✅ OPcache cleared successfully\n";
} else {
    echo "⚠️ OPcache not enabled\n";
}

// Clear specific file from opcache
$filesToClear = [
    __DIR__ . '/Public/module/recent_activity_card.php',
    __DIR__ . '/Public/module/time_log_create.php'
];

foreach ($filesToClear as $file) {
    if (function_exists('opcache_invalidate')) {
        if (file_exists($file)) {
            opcache_invalidate($file, true);
            echo "✅ Invalidated cache for: $file\n";
        } else {
            echo "❌ File not found: $file\n";
        }
    }
}

echo "\n✨ Cache cleared! Please refresh your browser with Ctrl+Shift+R\n";
?>
