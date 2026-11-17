<?php
/**
 * ReUseHub - Automated System & Integration Test Suite
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

echo "====================================================\n";
echo "   REUSEHUB - AUTOMATED INTEGRATION TEST SUITE     \n";
echo "====================================================\n\n";

$pdo = getDB();
$passed = 0;
$failed = 0;

function assertTest($description, $condition) {
    global $passed, $failed;
    if ($condition) {
        echo " [PASS] {$description}\n";
        $passed++;
    } else {
        echo " [FAIL] {$description}\n";
        $failed++;
    }
}

// TEST 1: Database Connection & Seed Data Verification
$usersCount = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
assertTest("Database connectivity and users table populated (count >= 5)", $usersCount >= 5);

$productsCount = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
assertTest("Products table populated with realistic seed data (count >= 10)", $productsCount >= 10);

$categoriesCount = $pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn();
assertTest("Categories table populated with 8 core categories", $categoriesCount == 8);

// TEST 2: Password Hashing & Verification
$testPass = 'Password123';
$testHash = password_hash($testPass, PASSWORD_BCRYPT);
assertTest("Password hashing creates valid BCRYPT hash", password_verify($testPass, $testHash));

// Verify seed user login
$adminUser = $pdo->query("SELECT * FROM users WHERE email = 'admin@reusehub.com'")->fetch();
assertTest("Admin account exists and password verifies", $adminUser && password_verify('Password123', $adminUser['password']));

// TEST 3: Rental Price & Cost Breakdown Calculation
$calc = calculateRental(1400.00, '2026-12-01', '2026-12-03', 5000.00);
// Dec 1 to Dec 3 is 3 inclusive days: 1400 * 3 = 4200. Deposit = 5000. Service fee (3%) = 126. Total = 9326.
assertTest("Rental duration calculated correctly (3 inclusive days)", $calc['days'] === 3);
assertTest("Rental amount calculated correctly (3 x ₹1400 = ₹4200)", $calc['rental_amount'] == 4200.00);
assertTest("Platform service fee calculated accurately (3% = ₹126)", $calc['service_fee'] == 126.00);
assertTest("Total amount with refundable deposit correct (₹9,326)", $calc['total_amount'] == 9326.00);

// TEST 4: Date Availability & Overlapping Booking Prevention
$prodId = 1; // Sony Alpha A7 III has a seed booking for 2026-11-15 to 2026-11-17
$isConflict = isDateRangeAvailable($prodId, '2026-11-16', '2026-11-18');
assertTest("Overlapping booking correctly detected and blocked", $isConflict === false);

$isFree = isDateRangeAvailable($prodId, '2026-12-10', '2026-12-15');
assertTest("Non-overlapping date range correctly allowed", $isFree === true);

// TEST 5: Booking Code Generation
$code = generateBookingCode();
assertTest("Booking code generated matches format (RUH-YYYY-XXXXX)", str_starts_with($code, 'RUH-' . date('Y') . '-'));

// TEST 6: Notifications System
$testNotifUser = 5; // Sneha
createNotification($testNotifUser, 'Test Notice', 'System test notification message', 'user/dashboard.php');
$unread = getUnreadNotificationsCount($testNotifUser);
assertTest("Notification created and unread count query works", $unread > 0);

// TEST 7: Wishlist System
$isWish = isWishlisted(5, 1);
assertTest("Seed wishlist entry detected for user 5 and product 1", $isWish === true);

// TEST 8: Reviews & Rating Aggregation
$rating = getProductRating(3); // Quechua tent has seed review with 5 stars
assertTest("Product rating query returns numeric average rating", $rating['avg_rating'] >= 4.0);

// TEST 9: Security Checks - CSRF Token Generation
$token1 = csrf_token();
$token2 = csrf_token();
assertTest("CSRF token is generated with 64-char hex", strlen($token1) === 64 && $token1 === $token2);

// TEST 10: XSS Escaping Helper
$dirtyHtml = '<script>alert("hack")</script>';
$clean = e($dirtyHtml);
assertTest("XSS sanitization escapes script tags safely", strpos($clean, '<script>') === false && strpos($clean, '&lt;script&gt;') !== false);

echo "\n----------------------------------------------------\n";
echo " TEST SUMMARY: {$passed} Passed, {$failed} Failed\n";
echo "----------------------------------------------------\n";

if ($failed === 0) {
    echo " ALL TESTS PASSED! ReUseHub core logic is robust and verified.\n";
    exit(0);
} else {
    echo " SOME TESTS FAILED.\n";
    exit(1);
}
