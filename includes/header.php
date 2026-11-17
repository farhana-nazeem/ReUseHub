<?php
/**
 * ReUseHub - Header Component
 */
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/auth.php';

$pageTitle = isset($pageTitle) ? $pageTitle : 'Rent Instead of Buy';
$activePage = isset($activePage) ? $activePage : '';
$user = currentUser();
$unreadCount = $user ? getUnreadNotificationsCount($user['id']) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="ReUseHub – Peer-to-peer rental marketplace in Kerala, India. Rent cameras, gadgets, tents, tools, party wear & more instead of buying.">
  <meta name="base-url" content="<?php echo url(); ?>">
  <title><?php echo e($pageTitle); ?> | <?php echo APP_NAME; ?> – <?php echo APP_TAGLINE; ?></title>

  <!-- Google Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

  <!-- Bootstrap 5.3 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">

  <!-- Font Awesome 6.5 -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

  <!-- ReUseHub Custom Design System -->
  <link rel="stylesheet" href="<?php echo asset('css/style.css'); ?>">
</head>
<body>

<?php require_once __DIR__ . '/navbar.php'; ?>

<!-- Flash Messages -->
<div class="container mt-3">
  <?php renderFlash(); ?>
</div>

<main>
