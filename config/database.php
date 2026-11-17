<?php
/**
 * ReUseHub - Database & Environment Configuration
 * Peer-to-peer Rental Marketplace
 */

// PHP 5.3 session handling
if (!defined('PHP_SESSION_NONE')) {
    define('PHP_SESSION_NONE', 1);
    define('PHP_SESSION_ACTIVE', 2);
    define('PHP_SESSION_DISABLED', 0);
}
if (!function_exists('session_status')) {
    function session_status() {
        if (session_id() === '') {
            return PHP_SESSION_NONE;
        }
        return PHP_SESSION_ACTIVE;
    }
}

// Start session if not started
if (session_id() === '') {
    session_start();
}

// Database Credentials
define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3306');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'reusehub_db');

// Platform Identity
define('APP_NAME', 'ReUseHub');
define('APP_TAGLINE', 'Use More. Own Less.');
define('CURRENCY_SYMBOL', '₹');
define('PLATFORM_FEE_PERCENT', 3.0); // 3% nominal marketplace service fee

// Determine dynamic base URL for seamless WAMP / Apache / PHP CLI server support
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)) ? "https://" : "http://";
$host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';

// Compute project base path relative to webroot
$docRoot = str_replace('\\', '/', realpath(isset($_SERVER['DOCUMENT_ROOT']) ? $_SERVER['DOCUMENT_ROOT'] : 'C:/wamp/www'));
$projectRoot = str_replace('\\', '/', realpath(__DIR__ . '/..'));

$relativePath = '';
if (strpos($projectRoot, $docRoot) === 0) {
    $relativePath = trim(substr($projectRoot, strlen($docRoot)), '/');
} else {
    // If running from separate drive (e.g., D:\ReUseHub with localhost/ReUseHub or php -S)
    $scriptDir = str_replace('\\', '/', dirname(isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : ''));
    if (strpos($scriptDir, '/admin') !== false || strpos($scriptDir, '/owner') !== false || strpos($scriptDir, '/user') !== false) {
        $relativePath = trim(dirname($scriptDir), '/');
    } else {
        $relativePath = trim($scriptDir, '/');
    }
}

if (!empty($relativePath)) {
    define('BASE_URL', rtrim($protocol . $host . '/' . $relativePath, '/') . '/');
} else {
    define('BASE_URL', rtrim($protocol . $host, '/') . '/');
}

define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('UPLOAD_URL', BASE_URL . 'uploads/');

/**
 * Returns a singleton PDO connection
 * @return PDO
 */
function getDB() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8";
            $options = array(
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            );
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            die("<div style='font-family:sans-serif;padding:30px;background:#fff1f2;border:1px solid #f43f5e;color:#881337;border-radius:8px;max-width:600px;margin:40px auto;'>" .
                "<h3 style='margin-top:0;'>ReUseHub Database Connection Error</h3>" .
                "<p>Could not connect to MySQL server. Please ensure WAMP MySQL service is running.</p>" .
                "<p style='font-size:13px;color:#9f1239;'>Details: " . htmlspecialchars($e->getMessage()) . "</p>" .
                "</div>");
        }
    }
    return $pdo;
}
