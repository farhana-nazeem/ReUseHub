<?php
/**
 * ReUseHub - Global Helper Functions
 */

require_once __DIR__ . '/../config/database.php';

// ========================================================
// PHP 5.3 Compatibility Polyfills
// ========================================================

if (!defined('PASSWORD_BCRYPT')) {
    define('PASSWORD_BCRYPT', 1);
    define('PASSWORD_DEFAULT', 1);
}

if (!function_exists('str_starts_with')) {
    function str_starts_with($haystack, $needle) {
        return (string)$needle === '' || strncmp($haystack, $needle, strlen($needle)) === 0;
    }
}

if (!function_exists('str_ends_with')) {
    function str_ends_with($haystack, $needle) {
        return $needle === '' || substr($haystack, -strlen($needle)) === $needle;
    }
}

if (!function_exists('str_contains')) {
    function str_contains($haystack, $needle) {
        return $needle === '' || strpos($haystack, $needle) !== false;
    }
}

if (!function_exists('hash_equals')) {
    function hash_equals($known_string, $user_string) {
        if (!is_string($known_string) || !is_string($user_string)) {
            return false;
        }
        $len1 = strlen($known_string);
        $len2 = strlen($user_string);
        if ($len1 !== $len2) {
            return false;
        }
        $status = 0;
        for ($i = 0; $i < $len1; $i++) {
            $status |= (ord($known_string[$i]) ^ ord($user_string[$i]));
        }
        return $status === 0;
    }
}

if (!function_exists('random_bytes')) {
    function random_bytes($length) {
        if (function_exists('openssl_random_pseudo_bytes')) {
            $strong = false;
            $bytes = openssl_random_pseudo_bytes($length, $strong);
            if ($bytes !== false && $strong) {
                return $bytes;
            }
        }
        $bytes = '';
        for ($i = 0; $i < $length; $i++) {
            $bytes .= chr(mt_rand(0, 255));
        }
        return $bytes;
    }
}

if (!function_exists('http_response_code')) {
    function http_response_code($code = null) {
        static $currentCode = 200;
        if ($code !== null) {
            $currentCode = (int)$code;
            header('X-PHP-Response-Code: ' . $currentCode, true, $currentCode);
        }
        return $currentCode;
    }
}

if (!function_exists('password_verify')) {
    function password_verify($password, $hash) {
        if (!is_string($password) || !is_string($hash)) {
            return false;
        }
        // Normalize $2y$ (PHP 5.3.7+) to $2a$ for PHP 5.3.5 blowfish support
        $compatHash = (substr($hash, 0, 4) === '$2y$') ? '$2a$' . substr($hash, 4) : $hash;
        $ret = crypt($password, $compatHash);
        if (!is_string($ret) || strlen($ret) <= 13) {
            return false;
        }
        return hash_equals($compatHash, $ret);
    }
}

if (!function_exists('password_hash')) {
    function password_hash($password, $algo = PASSWORD_BCRYPT, $options = array()) {
        $cost = isset($options['cost']) ? (int)$options['cost'] : 10;
        if ($cost < 4 || $cost > 31) {
            $cost = 10;
        }
        $saltChars = './ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
        $salt = '';
        for ($i = 0; $i < 22; $i++) {
            $salt .= $saltChars[mt_rand(0, 63)];
        }
        $prefix = sprintf('$2a$%02d$', $cost);
        return crypt($password, $prefix . $salt);
    }
}

// ========================================================
// ReUseHub Helper Functions
// ========================================================

/**
 * Generate full URL
 */
function url($path = '') {
    return BASE_URL . ltrim($path, '/');
}

/**
 * Generate asset URL
 */
function asset($path) {
    return url('assets/' . ltrim($path, '/'));
}

/**
 * Escape HTML output for XSS protection
 */
function e($string) {
    return htmlspecialchars((string)($string !== null ? $string : ''), ENT_QUOTES, 'UTF-8');
}

/**
 * Format Indian currency
 */
function formatPrice($amount) {
    return CURRENCY_SYMBOL . ' ' . number_format((float)$amount, 2);
}

/**
 * Format date nicely
 */
function formatDate($dateStr, $format = 'd M Y') {
    if (empty($dateStr)) return '-';
    $time = strtotime($dateStr);
    return date($format, $time);
}

/**
 * CSRF Token Generation
 */
function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Render CSRF hidden input field
 */
function csrf_field() {
    return '<input type="hidden" name="csrf_token" value="' . csrf_token() . '">';
}

/**
 * Verify CSRF Token
 */
function verify_csrf_token($token = null) {
    $token = $token !== null ? $token : (isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '');
    if (empty($token) || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        die("<div style='font-family:sans-serif;padding:30px;background:#fef2f2;border:1px solid #ef4444;color:#991b1b;border-radius:8px;max-width:500px;margin:50px auto;'>" .
            "<h3>Invalid Security Token (CSRF)</h3><p>Your session may have expired or this request is unauthorized. Please go back and try again.</p><p><a href='" . url() . "' style='color:#174C3C;font-weight:bold;'>Return to Home</a></p></div>");
    }
    return true;
}

/**
 * Flash messaging
 */
function setFlash($type, $message) {
    $_SESSION['flash'] = array(
        'type' => $type, // 'success', 'danger', 'warning', 'info'
        'message' => $message
    );
}

function getFlash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

function renderFlash() {
    $flash = getFlash();
    if ($flash) {
        $icon = 'fa-info-circle';
        switch ($flash['type']) {
            case 'success':
                $icon = 'fa-check-circle';
                break;
            case 'danger':
                $icon = 'fa-exclamation-triangle';
                break;
            case 'warning':
                $icon = 'fa-exclamation-circle';
                break;
            default:
                $icon = 'fa-info-circle';
                break;
        }
        echo '<div class="alert alert-' . e($flash['type']) . ' alert-dismissible fade show shadow-sm" role="alert">
                <i class="fa-solid ' . $icon . ' me-2"></i> ' . e($flash['message']) . '
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
              </div>';
    }
}

/**
 * Generate unique human-readable booking code
 */
function generateBookingCode() {
    return 'RUH-' . date('Y') . '-' . strtoupper(substr(uniqid(), -5));
}

/**
 * Calculate rental pricing breakdown
 */
function calculateRental($dailyPrice, $startDate, $endDate, $securityDeposit = 0) {
    // Use strtotime() difference — avoids PHP 5.3.x DateInterval::$days bug
    // where diff()->days returns a garbage value (e.g. 6016 for a 3-day range).
    $startTs = strtotime($startDate);
    $endTs   = strtotime($endDate);

    // Inclusive rental days (e.g. 19th to 21st = 3 days)
    $days = (int)floor(($endTs - $startTs) / 86400) + 1;
    if ($days < 1) $days = 1;

    $rentalAmount = round($dailyPrice * $days, 2);
    $serviceFee = round($rentalAmount * (PLATFORM_FEE_PERCENT / 100), 2);
    $totalAmount = round($rentalAmount + $securityDeposit + $serviceFee, 2);

    return array(
        'days' => $days,
        'daily_rate' => (float)$dailyPrice,
        'rental_amount' => $rentalAmount,
        'security_deposit' => (float)$securityDeposit,
        'service_fee' => $serviceFee,
        'total_amount' => $totalAmount
    );
}

/**
 * Check if a date range overlaps with existing confirmed/active bookings
 */
function isDateRangeAvailable($productId, $startDate, $endDate, $excludeBookingId = null) {
    $pdo = getDB();
    $sql = "SELECT COUNT(*) FROM bookings 
            WHERE product_id = ? 
            AND status IN ('pending', 'approved', 'active') 
            AND (
                (start_date <= ? AND end_date >= ?) OR
                (start_date <= ? AND end_date >= ?) OR
                (start_date >= ? AND end_date <= ?)
            )";
    $params = array($productId, $startDate, $startDate, $endDate, $endDate, $startDate, $endDate);

    if ($excludeBookingId) {
        $sql .= " AND id != ?";
        $params[] = $excludeBookingId;
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return ($stmt->fetchColumn() == 0);
}

/**
 * Get booked date ranges for a product (for frontend calendar disablement)
 */
function getBookedDates($productId) {
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT start_date, end_date FROM bookings 
                           WHERE product_id = ? AND status IN ('approved', 'active')");
    $stmt->execute(array($productId));
    return $stmt->fetchAll();
}

/**
 * Notification helper
 */
function createNotification($userId, $title, $message, $link = null) {
    try {
        $pdo = getDB();
        $stmt = $pdo->prepare("INSERT INTO notifications (user_id, title, message, link) VALUES (?, ?, ?, ?)");
        $stmt->execute(array($userId, $title, $message, $link));
        return true;
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Get unread notification count
 */
function getUnreadNotificationsCount($userId) {
    if (!$userId) return 0;
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmt->execute(array($userId));
    return (int)$stmt->fetchColumn();
}

/**
 * Get product rating and reviews count
 */
function getProductRating($productId) {
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT AVG(rating) as avg_rating, COUNT(*) as review_count FROM reviews WHERE product_id = ?");
    $stmt->execute(array($productId));
    $res = $stmt->fetch();
    return array(
        'avg_rating' => $res['avg_rating'] ? round((float)$res['avg_rating'], 1) : 5.0,
        'review_count' => (int)(isset($res['review_count']) ? $res['review_count'] : 0)
    );
}

/**
 * Check if product is in user wishlist
 */
function isWishlisted($userId, $productId) {
    if (!$userId) return false;
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM wishlist WHERE user_id = ? AND product_id = ?");
    $stmt->execute(array($userId, $productId));
    return ($stmt->fetchColumn() > 0);
}

/**
 * Get product images
 */
function getProductImages($productId) {
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT * FROM product_images WHERE product_id = ? ORDER BY is_primary DESC, id ASC");
    $stmt->execute(array($productId));
    return $stmt->fetchAll();
}

/**
 * Primary image helper
 */
function getProductPrimaryImage($productId, $fallback = 'assets/images/placeholder.jpg') {
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT image_path FROM product_images WHERE product_id = ? ORDER BY is_primary DESC, id ASC LIMIT 1");
    $stmt->execute(array($productId));
    $path = $stmt->fetchColumn();
    if ($path) {
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }
        return url($path);
    }
    return url($fallback);
}

/**
 * Slugify text
 */
function slugify($text) {
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
    $text = preg_replace('~[^-\w]+~', '', $text);
    $text = trim($text, '-');
    $text = preg_replace('~-+~', '-', $text);
    return strtolower($text ?: 'item-' . time());
}
