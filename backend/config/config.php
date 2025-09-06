<?php
/**
 * Main Configuration File for R-SEC Academy
 */

// Error Reporting (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Session Configuration (only for web requests)
if (php_sapi_name() !== 'cli') {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_secure', 0); // Set to 1 in production with HTTPS
    ini_set('session.use_only_cookies', 1);
    session_start();
}

// Timezone
date_default_timezone_set('Europe/Warsaw');

// Application Constants
define('APP_NAME', 'R-SEC Academy');
define('APP_VERSION', '1.0.0');
define('BASE_URL', 'http://localhost/RSec'); // Change in production
define('API_BASE_URL', BASE_URL . '/backend/api');
define('UPLOAD_PATH', __DIR__ . '/../uploads/');
define('UPLOAD_URL', BASE_URL . '/backend/uploads/');

// Security Constants
define('JWT_SECRET_KEY', 'your-super-secret-jwt-key-change-in-production');
define('PASSWORD_PEPPER', 'your-password-pepper-change-in-production');
define('ENCRYPTION_KEY', 'your-encryption-key-32-chars-long!!'); // 32 characters

// File Upload Limits
define('MAX_FILE_SIZE', 50 * 1024 * 1024); // 50MB
define('ALLOWED_VIDEO_TYPES', ['mp4', 'webm', 'ogg']);
define('ALLOWED_DOCUMENT_TYPES', ['pdf', 'doc', 'docx', 'txt', 'xlsx']);
define('ALLOWED_IMAGE_TYPES', ['jpg', 'jpeg', 'png', 'gif', 'webp']);

// Email Configuration (for notifications)
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'your-email@gmail.com');
define('SMTP_PASSWORD', 'your-email-password');
define('SMTP_FROM_EMAIL', 'noreply@r-sec.pl');
define('SMTP_FROM_NAME', 'R-SEC Academy');

// Payment Configuration
define('STRIPE_PUBLISHABLE_KEY', 'pk_test_your_stripe_publishable_key');
define('STRIPE_SECRET_KEY', 'sk_test_your_stripe_secret_key');

// Course Settings
define('DEFAULT_COURSE_CURRENCY', 'PLN');
define('FREE_COURSE_PRICE', 0.00);
define('CERTIFICATE_ENABLED', true);

// Pagination
define('DEFAULT_PAGE_SIZE', 12);
define('MAX_PAGE_SIZE', 50);

// Cache Settings
define('CACHE_ENABLED', false); // Enable in production
define('CACHE_DURATION', 3600); // 1 hour

/**
 * Helper Functions
 */

/**
 * Get environment variable with default value
 */
function env($key, $default = null) {
    $value = getenv($key);
    return $value !== false ? $value : $default;
}

/**
 * Generate CSRF token
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 */
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Sanitize input data
 */
function sanitizeInput($data) {
    if (is_array($data)) {
        return array_map('sanitizeInput', $data);
    }
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

/**
 * Validate email address
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Generate random string
 */
function generateRandomString($length = 32) {
    return bin2hex(random_bytes($length / 2));
}

/**
 * Hash password with pepper
 */
function hashPassword($password) {
    return password_hash($password . PASSWORD_PEPPER, PASSWORD_ARGON2ID, [
        'memory_cost' => 65536, // 64 MB
        'time_cost' => 4,       // 4 iterations
        'threads' => 3,         // 3 threads
    ]);
}

/**
 * Verify password with pepper
 */
function verifyPassword($password, $hash) {
    return password_verify($password . PASSWORD_PEPPER, $hash);
}

/**
 * Log activity
 */
function logActivity($userId, $action, $resourceType = null, $resourceId = null, $additionalData = null) {
    try {
        $database = new Database();
        $db = $database->getConnection();
        
        $stmt = $db->prepare("
            INSERT INTO activity_logs (user_id, action, resource_type, resource_id, ip_address, user_agent, additional_data) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
        $jsonData = $additionalData ? json_encode($additionalData) : null;
        
        $stmt->execute([$userId, $action, $resourceType, $resourceId, $ipAddress, $userAgent, $jsonData]);
        
    } catch (Exception $e) {
        error_log("Failed to log activity: " . $e->getMessage());
    }
}

/**
 * Send JSON response
 */
function sendJsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
    
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Handle CORS preflight requests (only in web context)
 */
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
    header('Access-Control-Max-Age: 86400'); // 24 hours
    exit(0);
}

/**
 * Auto-load classes
 */
spl_autoload_register(function ($className) {
    $directories = [
        __DIR__ . '/../models/',
        __DIR__ . '/../controllers/',
        __DIR__ . '/../services/',
        __DIR__ . '/../utils/',
    ];
    
    foreach ($directories as $directory) {
        $file = $directory . $className . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

// Include database configuration
require_once __DIR__ . '/database.php';

?>