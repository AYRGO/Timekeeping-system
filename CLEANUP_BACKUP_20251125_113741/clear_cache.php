<?php
// Clear PHP OpCache
if (function_exists('opcache_reset')) {
    opcache_reset();
    echo "OpCache cleared!<br>";
} else {
    echo "OpCache not enabled<br>";
}

// Clear any other caches
if (function_exists('apc_clear_cache')) {
    apc_clear_cache();
    echo "APC cache cleared!<br>";
}

echo "<br>All caches cleared. Please try again.<br>";
echo "<a href='Public/views/employee-edit.php?id=1009'>Go back to employee edit page</a>";
?>
