<?php
/**
 * CSRF Token Helper Functions
 * Centralized CSRF protection for the Timekeeping System
 */

/**
 * Generate a new CSRF token and store it in session
 * @param int $timeout Token timeout in seconds (default: 1800 = 30 minutes)
 * @return string The generated token
 */
function generate_csrf_token($timeout = 1800) {
    $token = bin2hex(random_bytes(32));
    $_SESSION['csrf_token'] = $token;
    $_SESSION['csrf_token_time'] = time();
    $_SESSION['csrf_token_timeout'] = $timeout;
    $_SESSION['csrf_token_regenerated'] = false;
    
    error_log("CSRF: New token generated - " . substr($token, 0, 8) . "...");
    return $token;
}

/**
 * Get current CSRF token, generating one if needed
 * @param int $timeout Token timeout in seconds (default: 1800 = 30 minutes)
 * @return string The current token
 */
function get_csrf_token($timeout = 1800) {
    // Check if token exists and is valid
    if (empty($_SESSION['csrf_token']) || 
        !isset($_SESSION['csrf_token_time']) || 
        (time() - $_SESSION['csrf_token_time'] > $timeout)) {
        return generate_csrf_token($timeout);
    }
    
    return $_SESSION['csrf_token'];
}

/**
 * Validate CSRF token from POST data
 * @param bool $regenerate_on_fail Whether to regenerate token on validation failure
 * @return array ['valid' => bool, 'error' => string]
 */
function validate_csrf_token($regenerate_on_fail = true) {
    $token = $_POST['csrf_token'] ?? '';
    $session_token = $_SESSION['csrf_token'] ?? '';
    $token_time = $_SESSION['csrf_token_time'] ?? 0;
    $timeout = $_SESSION['csrf_token_timeout'] ?? 1800;
    
    // Check if token exists in POST
    if (empty($token)) {
        error_log("SECURITY: CSRF token missing from POST - IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
        if ($regenerate_on_fail) {
            generate_csrf_token($timeout);
        }
        return ['valid' => false, 'error' => 'missing_csrf_token'];
    }
    
    // Check if session token exists
    if (empty($session_token)) {
        error_log("SECURITY: Session CSRF token missing - IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
        if ($regenerate_on_fail) {
            generate_csrf_token($timeout);
        }
        return ['valid' => false, 'error' => 'session_expired'];
    }
    
    // Check if token is expired
    if ((time() - $token_time) > $timeout) {
        error_log("SECURITY: CSRF token expired - Age: " . (time() - $token_time) . " seconds");
        if ($regenerate_on_fail) {
            generate_csrf_token($timeout);
        }
        return ['valid' => false, 'error' => 'csrf_expired'];
    }
    
    // Validate token using secure comparison
    if (!hash_equals($session_token, $token)) {
        error_log("SECURITY: CSRF token mismatch - IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
        error_log("SECURITY: Expected: " . substr($session_token, 0, 8) . "..., Received: " . substr($token, 0, 8) . "...");
        if ($regenerate_on_fail) {
            generate_csrf_token($timeout);
        }
        return ['valid' => false, 'error' => 'invalid_csrf_token'];
    }
    
    return ['valid' => true, 'error' => null];
}

/**
 * Generate CSRF token HTML input field
 * @return string HTML input field with CSRF token
 */
function csrf_token_field() {
    $token = get_csrf_token();
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
}

/**
 * Handle CSRF validation failure with redirect
 * @param string $error_type The type of error that occurred
 * @param string $redirect_url URL to redirect to (default: current page)
 */
function handle_csrf_failure($error_type, $redirect_url = null) {
    if ($redirect_url === null) {
        $redirect_url = $_SERVER['PHP_SELF'] ?? 'login.php';
    }
    
    // Ensure the URL includes the error parameter
    $separator = strpos($redirect_url, '?') !== false ? '&' : '?';
    $full_url = $redirect_url . $separator . 'error=' . urlencode($error_type);
    
    header("Location: " . $full_url);
    exit;
}

/**
 * Regenerate CSRF token (use sparingly to avoid breaking user experience)
 * @param int $timeout Token timeout in seconds
 */
function regenerate_csrf_token($timeout = 1800) {
    // Only regenerate if not already regenerated in this request
    if (!isset($_SESSION['csrf_token_regenerated']) || !$_SESSION['csrf_token_regenerated']) {
        generate_csrf_token($timeout);
        $_SESSION['csrf_token_regenerated'] = true;
        error_log("CSRF: Token regenerated after successful operation");
    }
}

/**
 * Initialize CSRF protection for a page
 * @param int $timeout Token timeout in seconds
 */
function init_csrf_protection($timeout = 1800) {
    // Ensure session is started
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Get or generate token
    get_csrf_token($timeout);
    
    // Reset regeneration flag for new page loads
    if (!isset($_POST) || empty($_POST)) {
        $_SESSION['csrf_token_regenerated'] = false;
    }
}
?>