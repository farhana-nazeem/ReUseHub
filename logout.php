<?php
/**
 * ReUseHub - User Logout Handler
 */

require_once __DIR__ . '/includes/auth.php';

logoutUser();
setFlash('info', 'You have been successfully signed out of ReUseHub. See you again!');
header("Location: " . url());
exit();

