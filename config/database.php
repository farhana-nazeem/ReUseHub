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

// Helper to read configuration from $_SERVER, $_ENV, or getenv() (crucial for Apache/FPM on OpenShift/Docker)
if (!function_exists('get_cfg_env')) {
    function get_cfg_env($key, $default = '') {
        if (isset($_SERVER[$key]) && $_SERVER[$key] !== '') return $_SERVER[$key];
        if (isset($_ENV[$key]) && $_ENV[$key] !== '') return $_ENV[$key];
        $val = getenv($key);
        if ($val !== false && $val !== '') return $val;
        return $default;
    }
}

// Database Credentials (supports OpenShift, Docker, Kubernetes environment variables and local WAMP)
$envHost = get_cfg_env('DB_HOST', get_cfg_env('MYSQL_SERVICE_HOST', get_cfg_env('MYSQL_HOST', '127.0.0.1')));
$envPort = get_cfg_env('DB_PORT', get_cfg_env('MYSQL_SERVICE_PORT', get_cfg_env('MYSQL_PORT', '3306')));
$envUser = get_cfg_env('DB_USER', get_cfg_env('MYSQL_USER', 'root'));
$envPass = get_cfg_env('DB_PASS', get_cfg_env('MYSQL_PASSWORD', ''));
$envName = get_cfg_env('DB_NAME', get_cfg_env('MYSQL_DATABASE', 'reusehub_db'));

if (!defined('DB_HOST')) define('DB_HOST', $envHost);
if (!defined('DB_PORT')) define('DB_PORT', $envPort);
if (!defined('DB_USER')) define('DB_USER', $envUser);
if (!defined('DB_PASS')) define('DB_PASS', $envPass);
if (!defined('DB_NAME')) define('DB_NAME', $envName);

// Platform Identity
if (!defined('APP_NAME')) define('APP_NAME', 'ReUseHub');
if (!defined('APP_TAGLINE')) define('APP_TAGLINE', 'Use More. Own Less.');
if (!defined('CURRENCY_SYMBOL')) define('CURRENCY_SYMBOL', '₹');
if (!defined('PLATFORM_FEE_PERCENT')) define('PLATFORM_FEE_PERCENT', 3.0); // 3% nominal marketplace service fee

// Determine dynamic base URL for seamless WAMP / Apache / OpenShift / reverse-proxy support
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)
    || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
$protocol = $isHttps ? "https://" : "http://";
$host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';

// Compute project base path relative to webroot using filesystem comparison only.
// Using SCRIPT_NAME to derive path is unreliable (causes double-folder URLs like
// /ReUseHub/ReUseHub/list-item.php when navigating between pages).
$docRoot    = str_replace('\\', '/', realpath(isset($_SERVER['DOCUMENT_ROOT']) ? $_SERVER['DOCUMENT_ROOT'] : 'C:/wamp/www'));
$projectRoot = str_replace('\\', '/', realpath(__DIR__ . '/..'));

$relativePath = '';
if ($docRoot !== false && $projectRoot !== false && strpos($projectRoot, $docRoot) === 0) {
    // Standard case: project lives inside the webroot
    $relativePath = trim(substr($projectRoot, strlen($docRoot)), '/');
}
// If docRoot detection fails (e.g., different drive), $relativePath stays ''
// and BASE_URL falls back to http://localhost/ — safe for WAMP default setup.

if (!defined('BASE_URL')) {
    if (!empty($relativePath)) {
        define('BASE_URL', rtrim($protocol . $host . '/' . $relativePath, '/') . '/');
    } else {
        define('BASE_URL', rtrim($protocol . $host, '/') . '/');
    }
}

if (!defined('UPLOAD_DIR')) define('UPLOAD_DIR', __DIR__ . '/../uploads/');
if (!defined('UPLOAD_URL')) define('UPLOAD_URL', BASE_URL . 'uploads/');

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
                "<p>Could not connect to MySQL server at <code>" . htmlspecialchars(DB_HOST) . ":" . htmlspecialchars(DB_PORT) . "</code> as user <code>" . htmlspecialchars(DB_USER) . "</code>.</p>" .
                "<p style='font-size:13px;color:#9f1239;'>Details: " . htmlspecialchars($e->getMessage()) . "</p>" .
                "</div>");
        }
    }
    return $pdo;
}
