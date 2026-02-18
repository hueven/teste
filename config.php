<?php
/**
 * Configuration and Utility Functions
 *
 * This file loads environment variables and provides shared utility functions
 * for the application. Database credentials are loaded from .env file for security.
 */

// Load environment variables from .env file
function loadEnv($filePath = __DIR__ . '/.env') {
    if (!file_exists($filePath)) {
        throw new Exception("Configuration file not found: $filePath");
    }

    $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') === false || strpos($line, '#') === 0) {
            continue;
        }

        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);

        // Remove quotes if present
        if ((strpos($value, '"') === 0 && substr($value, -1) === '"') ||
            (strpos($value, "'") === 0 && substr($value, -1) === "'")) {
            $value = substr($value, 1, -1);
        }

        $_ENV[$key] = $value;
        putenv("$key=$value");
    }
}

// Load configuration
loadEnv();

// Database Configuration
define('DB_HOST', $_ENV['DB_HOST'] ?? 'localhost');
define('DB_USER', $_ENV['DB_USER'] ?? '');
define('DB_PASS', $_ENV['DB_PASS'] ?? '');
define('DB_NAME', $_ENV['DB_NAME'] ?? '');
define('DB_CHARSET', $_ENV['DB_CHARSET'] ?? 'utf8mb4');

// Application Configuration
define('APP_ENV', $_ENV['APP_ENV'] ?? 'production');
define('APP_DEBUG', $_ENV['APP_DEBUG'] === 'true' ? true : false);
define('APP_URL', $_ENV['APP_URL'] ?? 'http://localhost');
define('TIMEZONE', $_ENV['TIMEZONE'] ?? 'UTC');

// Session Configuration
define('SESSION_SECURE', $_ENV['SESSION_SECURE'] === 'true' ? true : false);
define('SESSION_HTTP_ONLY', $_ENV['SESSION_HTTP_ONLY'] === 'true' ? true : false);
define('SESSION_SAME_SITE', $_ENV['SESSION_SAME_SITE'] ?? 'Lax');

// Rate Limiting
define('RATE_LIMIT_ENABLED', $_ENV['RATE_LIMIT_ENABLED'] === 'true' ? true : false);
define('RATE_LIMIT_LOGIN_ATTEMPTS', (int)($_ENV['RATE_LIMIT_LOGIN_ATTEMPTS'] ?? 5));
define('RATE_LIMIT_LOGIN_WINDOW', (int)($_ENV['RATE_LIMIT_LOGIN_WINDOW'] ?? 900));

// File Upload Configuration
define('MAX_FILE_SIZE', (int)($_ENV['MAX_FILE_SIZE'] ?? 10485760)); // 10MB
define('UPLOAD_DIR', $_ENV['UPLOAD_DIR'] ?? __DIR__ . '/uploads');

// Initialize Database Connection
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET,
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );

    // Set timezone
    $pdo->exec("SET time_zone='" . htmlspecialchars(TIMEZONE) . "'");
} catch (PDOException $e) {
    if (APP_DEBUG) {
        die("Database Error: " . htmlspecialchars($e->getMessage()));
    } else {
        die("An error occurred. Please contact support.");
    }
}

// ============================================================================
// SESSION CONFIGURATION
// ============================================================================

// Configure session settings securely
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => '',
    'secure' => SESSION_SECURE,
    'httponly' => SESSION_HTTP_ONLY,
    'samesite' => SESSION_SAME_SITE
]);

/**
 * Initialize secure session
 */
function initSession() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

/**
 * Regenerate session ID for security
 */
function regenerateSessionId($deleteOld = true) {
    session_regenerate_id($deleteOld);
}

// ============================================================================
// RESPONSE FUNCTIONS
// ============================================================================

/**
 * Send JSON response
 *
 * @param bool $success
 * @param array $data
 * @param int $statusCode
 */
function jsonResponse($success = true, $data = [], $statusCode = 200) {
    header('Content-Type: application/json; charset=utf-8');
    http_response_code($statusCode);

    $response = [
        'success' => (bool)$success,
        'data' => $data
    ];

    if (!APP_DEBUG) {
        // Remove sensitive error details in production
        if (!$success && isset($response['data']['error'])) {
            // Keep user-friendly error, remove technical details
            if (strpos($response['data']['error'], 'SQLSTATE') === 0) {
                $response['data']['error'] = 'An error occurred. Please try again later.';
            }
        }
    }

    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

// ============================================================================
// SANITIZATION FUNCTIONS
// ============================================================================

/**
 * Sanitize collection name
 * Removes accents, special characters, and converts to lowercase with hyphens
 *
 * @param string $name
 * @return string
 */
function sanitizeCollectionName($name) {
    if (!is_string($name)) {
        return '';
    }

    // Convert to lowercase
    $sanitized = mb_strtolower($name, 'UTF-8');

    // Remove accents
    $replacements = [
        '/[áàãâä]/u' => 'a',
        '/[éèêë]/u' => 'e',
        '/[íìîï]/u' => 'i',
        '/[óòôõö]/u' => 'o',
        '/[úùûü]/u' => 'u',
        '/[ç]/u' => 'c',
        '/[ñ]/u' => 'n',
    ];

    foreach ($replacements as $pattern => $replacement) {
        $sanitized = preg_replace($pattern, $replacement, $sanitized);
    }

    // Remove non-alphanumeric characters except hyphens and underscores
    $sanitized = preg_replace('/[^a-z0-9\-_]/', '', $sanitized);

    // Replace multiple hyphens with single hyphen
    $sanitized = preg_replace('/-+/', '-', $sanitized);

    // Remove leading/trailing hyphens
    $sanitized = trim($sanitized, '-');

    return $sanitized ?: 'untitled';
}

/**
 * Generate unique filename for uploads
 *
 * @param string $originalFilename
 * @return string
 */
function generateUniqueFilename($originalFilename) {
    $pathInfo = pathinfo($originalFilename);
    $extension = strtolower($pathInfo['extension']);
    $timestamp = time();
    $random = bin2hex(random_bytes(4));

    return "{$timestamp}_{$random}.{$extension}";
}

/**
 * Sanitize filename
 *
 * @param string $filename
 * @return string
 */
function sanitizeFilename($filename) {
    $filename = basename($filename);
    $filename = preg_replace('/[^a-zA-Z0-9._\-]/', '', $filename);

    return $filename ?: 'file';
}

// ============================================================================
// CSRF TOKEN FUNCTIONS
// ============================================================================

/**
 * Generate CSRF token
 *
 * @return string
 */
function generateCsrfToken() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 *
 * @param string $token
 * @return bool
 */
function verifyCsrfToken($token = null) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if ($token === null) {
        $token = $_POST['csrf_token'] ?? $_REQUEST['csrf_token'] ?? null;
    }

    if (empty($token) || empty($_SESSION['csrf_token'])) {
        return false;
    }

    return hash_equals($_SESSION['csrf_token'], $token);
}

// ============================================================================
// AUTHENTICATION FUNCTIONS
// ============================================================================

/**
 * Check if user is authenticated
 *
 * @return bool
 */
function isAuthenticated() {
    initSession();
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Require authentication
 * Dies with error if user is not authenticated
 */
function requireAuth() {
    if (!isAuthenticated()) {
        http_response_code(401);
        jsonResponse(false, ['error' => 'Unauthorized'], 401);
    }
}

/**
 * Get current user ID
 *
 * @return int|null
 */
function getCurrentUserId() {
    initSession();
    return $_SESSION['user_id'] ?? null;
}

/**
 * Hash password
 *
 * @param string $password
 * @return string
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

/**
 * Verify password
 *
 * @param string $password
 * @param string $hash
 * @return bool
 */
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

// ============================================================================
// VALIDATION FUNCTIONS
// ============================================================================

/**
 * Validate email format
 *
 * @param string $email
 * @return bool
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validate URL format
 *
 * @param string $url
 * @return bool
 */
function isValidUrl($url) {
    return filter_var($url, FILTER_VALIDATE_URL) !== false;
}

/**
 * Validate that value is a positive integer
 *
 * @param mixed $value
 * @return bool
 */
function isPositiveInt($value) {
    return filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) !== false;
}

// ============================================================================
// LOGGING AND ERROR HANDLING
// ============================================================================

/**
 * Log message safely (avoid sensitive data)
 *
 * @param string $message
 * @param string $level
 */
function safeLog($message, $level = 'INFO') {
    if (!APP_DEBUG) {
        return; // Disable logging in production
    }

    // Only log in development environment
    $logFile = __DIR__ . '/logs/app.log';
    $logDir = dirname($logFile);

    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }

    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] [$level] $message\n";

    file_put_contents($logFile, $logMessage, FILE_APPEND);
}

/**
 * Handle API errors safely
 *
 * @param Exception $e
 * @param string $userMessage
 */
function handleApiError($e, $userMessage = 'An error occurred') {
    if (APP_DEBUG) {
        safeLog("Error: " . $e->getMessage(), 'ERROR');
    }

    http_response_code(500);
    jsonResponse(false, ['error' => $userMessage], 500);
}
