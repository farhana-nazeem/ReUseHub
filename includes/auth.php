<?php
/**
 * ReUseHub - Authentication & Access Control System
 */

require_once __DIR__ . '/functions.php';

/**
 * Check if a user is logged in
 */
function isLoggedIn() {
    return !empty($_SESSION['user_id']);
}

/**
 * Get currently logged-in user record from database
 */
function currentUser() {
    static $user = null;
    if ($user !== null) {
        return $user;
    }
    if (!isLoggedIn()) {
        return null;
    }
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND is_active = 1");
    $stmt->execute(array($_SESSION['user_id']));
    $user = $stmt->fetch();
    if (!$user) {
        // User account was disabled or deleted
        logoutUser();
        return null;
    }
    return $user;
}

/**
 * Log in a user and set session
 */
function loginUser($user) {
    // Regenerate session id to protect against session fixation
    session_regenerate_id(true);
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_name'] = $user['name'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_role'] = $user['role'];
    $_SESSION['last_login'] = time();
}

/**
 * Log out user safely
 */
function logoutUser() {
    $_SESSION = array();
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
}

/**
 * Require user to be logged in, otherwise redirect to login page
 */
function requireLogin($redirectBack = true) {
    if (!isLoggedIn()) {
        setFlash('warning', 'Please sign in to access that page.');
        $redirectUrl = url('login.php');
        if ($redirectBack && !empty($_SERVER['REQUEST_URI'])) {
            $redirectUrl .= '?next=' . urlencode($_SERVER['REQUEST_URI']);
        }
        header("Location: " . $redirectUrl);
        exit();
    }
}

/**
 * Require specific user role (admin, owner, or renter)
 */
function requireRole($role) {
    requireLogin();
    $user = currentUser();
    if (!$user) {
        header("Location: " . url('login.php'));
        exit();
    }

    if ($role === 'admin' && $user['role'] !== 'admin') {
        setFlash('danger', 'Unauthorized access! Administrative privileges required.');
        header("Location: " . url('index.php'));
        exit();
    }

    if ($role === 'owner' && !in_array($user['role'], array('owner', 'admin'))) {
        // User can seamlessly upgrade/switch to owner when listing items
        $pdo = getDB();
        $stmt = $pdo->prepare("UPDATE users SET role = 'owner' WHERE id = ?");
        $stmt->execute(array($user['id']));
        $_SESSION['user_role'] = 'owner';
    }
}

/**
 * Guard for Admin pages
 */
function requireAdmin() {
    requireRole('admin');
}

/**
 * Guard for Owner pages
 */
function requireOwner() {
    requireRole('owner');
}
