<?php
/**
 * Environment Configuration Loader
 * Loads environment variables from .env file
 */

if (!class_exists('EnvLoader')) {
    class EnvLoader {
        private static $loaded = false;
        
        public static function load($file = '.env') {
            if (self::$loaded) {
                return true;
            }
            
            // Look for .env file in multiple possible locations
            $possiblePaths = [
                $_SERVER['DOCUMENT_ROOT'] . '/' . $file, // Document root (public_html)
                dirname(__DIR__, 2) . '/' . $file,  // Project root (timekeeping system root)
                __DIR__ . '/../../' . $file,        // Original path
                getcwd() . '/' . $file,             // Current working directory
            ];
            
            $envPath = null;
            foreach ($possiblePaths as $path) {
                if (file_exists($path)) {
                    $envPath = $path;
                    break;
                }
            }
            
            if (!$envPath) {
                return false;
            }
            
            $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            
            foreach ($lines as $line) {
                // Skip comments
                if (strpos($line, '#') === 0) {
                    continue;
                }
                
                // Parse key=value pairs
                if (strpos($line, '=') !== false) {
                    list($key, $value) = explode('=', $line, 2);
                    $key = trim($key);
                    $value = trim($value);
                    
                    // Remove quotes if present
                    if (preg_match('/^"(.*)"$/', $value, $matches)) {
                        $value = $matches[1];
                    } elseif (preg_match("/^'(.*)'$/", $value, $matches)) {
                        $value = $matches[1];
                    }
                    
                    // Set environment variable
                    $_ENV[$key] = $value;
                    putenv("$key=$value");
                    
                }
            }
            
            self::$loaded = true;
            return true;
        }
        
        public static function get($key, $default = null) {
            $value = $_ENV[$key] ?? getenv($key) ?: $default;
            
            return $value;
        }
        
        /**
         * Get a required environment variable. 
         * Throws an exception if not found.
         */
        public static function getRequired($key) {
            $value = self::get($key);
            
            if ($value === null || $value === '') {
                throw new Exception("Required environment variable '$key' is not set. Please check your .env file.");
            }
            
            return $value;
        }
    }
}

// Load environment variables once
EnvLoader::load();
?>
