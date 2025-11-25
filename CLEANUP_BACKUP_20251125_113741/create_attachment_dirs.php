<?php
// Create attachment directories for all request types
$directories = [
    'Public/uploads/leave_attachments',
    'Public/uploads/overtime_attachments',
    'Public/uploads/schedule_attachments'
];

foreach ($directories as $dir) {
    if (!is_dir($dir)) {
        if (mkdir($dir, 0755, true)) {
            echo "✅ Created directory: $dir\n";
        } else {
            echo "❌ Failed to create directory: $dir\n";
        }
    } else {
        echo "📁 Directory already exists: $dir\n";
    }
}

echo "\n✨ Directory setup complete!\n";
?>