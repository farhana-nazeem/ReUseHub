<?php
// Database Configuration and Setup Script for ReUseHub
require_once __DIR__ . '/database.php';

$host = DB_HOST;
$port = DB_PORT;
$user = DB_USER;
$pass = DB_PASS;

echo "Connecting to MySQL server at {$host}:{$port}...\n";

try {
    $pdo = new PDO("mysql:host={$host};port={$port}", $user, $pass, array(
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::MYSQL_ATTR_MULTI_STATEMENTS => true
    ));
    echo "Connected successfully!\n";

    $sqlPath = __DIR__ . '/../database/reusehub_db.sql';
    if (!file_exists($sqlPath)) {
        die("SQL file not found at {$sqlPath}\n");
    }

    echo "Reading schema file...\n";
    $sql = file_get_contents($sqlPath);

    echo "Executing database migration and seed data...\n";
    $pdo->exec($sql);

    echo "ReUseHub database (`reusehub_db`) successfully created and populated!\n";

    // Verify row counts
    $pdo->exec("USE `reusehub_db`");
    $tables = array('users', 'categories', 'products', 'product_images', 'bookings', 'payments', 'reviews', 'wishlist', 'notifications', 'reports');
    foreach ($tables as $table) {
        $count = $pdo->query("SELECT COUNT(*) FROM `{$table}`")->fetchColumn();
        echo " - Table `{$table}`: {$count} rows\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
