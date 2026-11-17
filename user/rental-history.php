<?php
/**
 * ReUseHub - Renter History & Savings Summary
 */

$pageTitle = 'Rental History & Savings';
$activePage = 'rental_history';
require_once __DIR__ . '/../includes/header.php';

requireLogin();
$user = currentUser();
$pdo = getDB();

$renterId = $user['id'];

// Completed Bookings
$stmt = $pdo->prepare("SELECT b.*, p.title as product_title, p.original_price, p.location as product_location,
                       u.name as owner_name,
                       (SELECT image_path FROM product_images WHERE product_id = p.id ORDER BY is_primary DESC, id ASC LIMIT 1) as primary_image,
                       (SELECT rating FROM reviews WHERE booking_id = b.id LIMIT 1) as my_rating
                       FROM bookings b
                       JOIN products p ON b.product_id = p.id
                       JOIN users u ON b.owner_id = u.id
                       WHERE b.renter_id = ? AND b.status = 'completed'
                       ORDER BY b.end_date DESC");
$stmt->execute(array($renterId));
$history = $stmt->fetchAll();

// Calculations
$totalCompleted = count($history);
$totalSpent = 0;
$totalSaved = 0;

foreach ($history as $h) {
    $totalSpent += (float)$h['rental_amount'];
    $savings = max(0, (float)$h['original_price'] - (float)$h['rental_amount']);
    $totalSaved += $savings;
}
?>

<div class="py-4" style="background-color: var(--bg-main);">
  <div class="container">
    <div class="dashboard-container">
      <!-- Sidebar -->
      <aside class="dashboard-sidebar rounded-4 shadow-sm me-lg-4 mb-4 mb-lg-0">
        <ul class="sidebar-menu">
          <li class="sidebar-menu-item">
            <a href="<?php echo  url('user/dashboard.php'); ?>" class="sidebar-link">
              <i class="fa-solid fa-gauge"></i> Renter Overview
            </a>
          </li>
          <li class="sidebar-menu-item">
            <a href="<?php echo  url('user/my-bookings.php'); ?>" class="sidebar-link">
              <i class="fa-solid fa-calendar-check"></i> My Bookings
            </a>
          </li>
          <li class="sidebar-menu-item">
            <a href="<?php echo  url('user/rental-history.php'); ?>" class="sidebar-link active">
              <i class="fa-solid fa-clock-rotate-left"></i> Rental History
            </a>
          </li>
          <li class="sidebar-menu-item">
            <a href="<?php echo  url('wishlist.php'); ?>" class="sidebar-link">
              <i class="fa-solid fa-heart"></i> My Wishlist
            </a>
          </li>
          <li class="sidebar-menu-item">
            <a href="<?php echo  url('user/profile.php'); ?>" class="sidebar-link">
              <i class="fa-solid fa-user-gear"></i> Profile Settings
            </a>
          </li>
        </ul>
      </aside>

      <!-- Main Content -->
      <main class="dashboard-main rounded-4 shadow-sm p-4 bg-white flex-grow-1">
        <div class="d-flex justify-content-between align-items-center mb-4">
          <div>
            <h3 class="fw-bold text-dark mb-1">Rental History & Savings</h3>
            <p class="text-muted small mb-0">Record of your successfully completed rentals and financial savings on ReUseHub.</p>
          </div>
        </div>

        <!-- Savings Highlights -->
        <div class="row g-3 mb-4">
          <div class="col-md-4">
            <div class="card border rounded-4 p-3 bg-light">
              <small class="text-muted fw-bold">Completed Rentals</small>
              <div class="fs-3 fw-bold text-dark"><?php echo  $totalCompleted; ?></div>
            </div>
          </div>
          <div class="col-md-4">
            <div class="card border rounded-4 p-3 bg-light">
              <small class="text-muted fw-bold">Total Rental Charges Paid</small>
              <div class="fs-3 fw-bold text-primary"><?php echo  formatPrice($totalSpent); ?></div>
            </div>
          </div>
          <div class="col-md-4">
            <div class="card border rounded-4 p-3 bg-success-subtle border-success">
              <small class="text-success fw-bold">Estimated Savings vs Retail Buy</small>
              <div class="fs-3 fw-bold text-success"><?php echo  formatPrice($totalSaved); ?></div>
            </div>
          </div>
        </div>

        <?php if (empty($history)): ?>
          <div class="card border-0 shadow-sm rounded-4 p-5 text-center bg-light-subtle my-3">
            <i class="fa-solid fa-receipt text-muted fs-1 mb-3"></i>
            <h5 class="fw-bold text-dark">No Completed Rentals Yet</h5>
            <p class="text-muted small mb-3">Your completed rental items will appear here with savings reports once returned.</p>
            <div>
              <a href="<?php echo  url('explore.php'); ?>" class="btn btn-primary-custom btn-sm px-4">Rent Items Now</a>
            </div>
          </div>
        <?php else: ?>
          <div class="table-responsive">
            <table class="custom-table">
              <thead>
                <tr>
                  <th>Rental Code</th>
                  <th>Product</th>
                  <th>Dates</th>
                  <th>Rental Charge</th>
                  <th>Retail Value</th>
                  <th>Your Savings</th>
                  <th>Your Rating</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($history as $h): ?>
                  <?php
                    $hImg = $h['primary_image'] ?: 'assets/images/placeholder.jpg';
                    if (!str_starts_with($hImg, 'http://') && !str_starts_with($hImg, 'https://')) {
                      $hImg = url($hImg);
                    }
                    $savings = max(0, (float)$h['original_price'] - (float)$h['rental_amount']);
                  ?>
                  <tr>
                    <td>
                      <span class="font-monospace fw-bold text-primary"><?php echo  e($h['booking_code']); ?></span>
                    </td>
                    <td>
                      <div class="d-flex align-items-center gap-2">
                        <img src="<?php echo  e($hImg); ?>" class="rounded-2" width="40" height="34" style="object-fit: cover;">
                        <div>
                          <a href="<?php echo  url('product-details.php?id=' . $h['product_id']); ?>" class="fw-bold text-dark text-decoration-none">
                            <?php echo  e($h['product_title']); ?>
                          </a>
                          <small class="text-muted d-block">Owner: <?php echo  e($h['owner_name']); ?></small>
                        </div>
                      </div>
                    </td>
                    <td>
                      <div class="small fw-semibold"><?php echo  formatDate($h['start_date']); ?> &rarr; <?php echo  formatDate($h['end_date']); ?></div>
                      <small class="text-muted"><?php echo  $h['total_days']; ?> days</small>
                    </td>
                    <td>
                      <span class="fw-bold text-dark"><?php echo  formatPrice($h['rental_amount']); ?></span>
                    </td>
                    <td>
                      <span class="text-muted"><?php echo  formatPrice($h['original_price']); ?></span>
                    </td>
                    <td>
                      <span class="badge bg-success-subtle text-success fw-bold px-2 py-1">+ <?php echo  formatPrice($savings); ?></span>
                    </td>
                    <td>
                      <?php if (!empty($h['my_rating'])): ?>
                        <span class="text-warning fw-bold small"><i class="fa-solid fa-star"></i> <?php echo  $h['my_rating']; ?>/5</span>
                      <?php else: ?>
                        <span class="text-muted small">Not rated</span>
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </main>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

