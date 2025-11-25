<?php
// Deployment preparation script
// Run this to prepare files for Hostinger upload

echo "🚀 PREPARING FILES FOR HOSTINGER DEPLOYMENT\n";
echo str_repeat("=", 50) . "\n";

$sourceDir = __DIR__;
$deployDir = $sourceDir . '/hostinger_deploy';

// Create deployment directory
if (!file_exists($deployDir)) {
    mkdir($deployDir, 0755, true);
    echo "✅ Created deployment directory\n";
}

// Files to copy for deployment
$filesToCopy = [
    'Public/index.php',
    'Public/config/db_production.php' => 'config/db.php',
    'Public/module/leave_credits.php',
    'Public/module/monthly_leave_monitor.php',
    'Public/module/live_leave_credits.php',
    'Public/module/live_leave_credits_ajax.php',
    'Public/cron/hostinger_monthly_accrual.php',
    '.env.production' => '.env',
    'hostinger_database_export.sql'
];

// Directories to copy
$dirsToCopy = [
    'Public/asset',
    'Public/views',
    'Public/controller',
    'Public/uploads',
    'vendor'
];

echo "📁 Copying files...\n";

foreach ($filesToCopy as $source => $dest) {
    if (is_numeric($source)) {
        $source = $dest;
    }
    
    $sourcePath = $sourceDir . '/' . $source;
    $destPath = $deployDir . '/' . $dest;
    
    if (file_exists($sourcePath)) {
        // Create destination directory if needed
        $destDirPath = dirname($destPath);
        if (!file_exists($destDirPath)) {
            mkdir($destDirPath, 0755, true);
        }
        
        copy($sourcePath, $destPath);
        echo "  ✅ $source → $dest\n";
    } else {
        echo "  ⚠️ Missing: $source\n";
    }
}

echo "\n📂 Copying directories...\n";

foreach ($dirsToCopy as $dir) {
    $sourceDir = __DIR__ . '/' . $dir;
    $destDir = $deployDir . '/' . basename($dir);
    
    if (is_dir($sourceDir)) {
        copyDirectory($sourceDir, $destDir);
        echo "  ✅ $dir\n";
    } else {
        echo "  ⚠️ Missing directory: $dir\n";
    }
}

// Create necessary directories
$dirsToCreate = ['logs', 'uploads/profile_images', 'uploads/attachments'];

echo "\n📁 Creating directories...\n";
foreach ($dirsToCreate as $dir) {
    $path = $deployDir . '/' . $dir;
    if (!file_exists($path)) {
        mkdir($path, 0755, true);
        echo "  ✅ Created: $dir\n";
    }
}

// Create .htaccess files for security
$htaccessFiles = [
    'config/.htaccess' => "Order Deny,Allow\nDeny from all",
    'logs/.htaccess' => "Order Deny,Allow\nDeny from all", 
    'cron/.htaccess' => "Order Deny,Allow\nDeny from all",
    '.htaccess' => "RewriteEngine On\nRewriteCond %{HTTPS} off\nRewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]\n\n<Files \".env\">\nOrder Allow,Deny\nDeny from all\n</Files>"
];

echo "\n🔒 Creating security files...\n";
foreach ($htaccessFiles as $file => $content) {
    $path = $deployDir . '/' . $file;
    $dir = dirname($path);
    if (!file_exists($dir)) {
        mkdir($dir, 0755, true);
    }
    file_put_contents($path, $content);
    echo "  ✅ $file\n";
}

// Create deployment instructions
$instructions = "# HOSTINGER DEPLOYMENT PACKAGE\n\n";
$instructions .= "This package contains all files ready for Hostinger deployment.\n\n";
$instructions .= "## Quick Setup:\n";
$instructions .= "1. Upload all files to public_html/ directory\n";
$instructions .= "2. Edit .env file with your database credentials\n";
$instructions .= "3. Import hostinger_database_export.sql to your database\n";
$instructions .= "4. Set up cron job for hostinger_monthly_accrual.php\n";
$instructions .= "5. Test the system\n\n";
$instructions .= "See HOSTINGER_DEPLOYMENT_COMPLETE.md for detailed instructions.\n";

file_put_contents($deployDir . '/README.txt', $instructions);

echo "\n📋 Creating file list...\n";
$fileList = "DEPLOYMENT PACKAGE CONTENTS:\n";
$fileList .= str_repeat("=", 30) . "\n";
$fileList .= listDirectory($deployDir, $deployDir);

file_put_contents($deployDir . '/FILE_LIST.txt', $fileList);

echo "\n🎉 DEPLOYMENT PACKAGE READY!\n";
echo "Location: $deployDir\n";
echo "Files prepared for Hostinger upload.\n";
echo "\nNext steps:\n";
echo "1. Zip the hostinger_deploy folder\n";
echo "2. Upload to Hostinger File Manager\n";
echo "3. Follow the deployment instructions\n";

function copyDirectory($source, $dest) {
    if (!is_dir($dest)) {
        mkdir($dest, 0755, true);
    }
    
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($source),
        RecursiveIteratorIterator::SELF_FIRST
    );
    
    foreach ($files as $file) {
        $filename = $file->getFilename();
        if ($filename === '.' || $filename === '..') continue;
        
        $sourcePath = $file->getPathname();
        $relativePath = substr($sourcePath, strlen($source) + 1);
        $destPath = $dest . DIRECTORY_SEPARATOR . $relativePath;
        
        if ($file->isDir()) {
            if (!is_dir($destPath)) {
                mkdir($destPath, 0755, true);
            }
        } else {
            $destDir = dirname($destPath);
            if (!is_dir($destDir)) {
                mkdir($destDir, 0755, true);
            }
            copy($sourcePath, $destPath);
        }
    }
}

function listDirectory($dir, $baseDir, $prefix = '') {
    $list = '';
    $items = scandir($dir);
    
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') continue;
        
        $path = $dir . '/' . $item;
        $relativePath = str_replace($baseDir . '/', '', $path);
        
        if (is_dir($path)) {
            $list .= $prefix . "📁 $relativePath/\n";
            $list .= listDirectory($path, $baseDir, $prefix . '  ');
        } else {
            $size = filesize($path);
            $sizeStr = $size > 1024 ? round($size/1024, 1) . 'KB' : $size . 'B';
            $list .= $prefix . "📄 $relativePath ($sizeStr)\n";
        }
    }
    
    return $list;
}
?>