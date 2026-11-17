<?php
/**
 * ReUseHub - Renter Dashboard
 */

$pageTitle = 'Renter Dashboard';
$activePage = 'renter_dashboard';
require_once __DIR__ . '/../includes/header.php';

requireLogin();
$user = currentUser();
$pdo = getDB();

// 1. Calculate Real Metrics from Database
$renterId = $user['id'];

// Total Rentals
$stmtTotal = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE renter_id = ?");
$stmtTotal->execute(array($renterId));
$totalRentals = (int)$stmtTotal->fetchColumn();

// Pending Bookings
$stmtPending = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE renter_id = ? AND status = 'pending'");
$stmtPending->execute(array($renterId));
$pendingCount = (int)$stmtPending->fetchColumn();

// Active Rentals
$stmtActive = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE renter_id = ? AND status = 'active'");
$stmtActive->execute(array($renterId));
$activeCount = (int)$stmtActive->fetchColumn();

// Completed Rentals
$stmtCompleted = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE renter_id = ? AND status = 'completed'");
$stmtCompleted->execute(array($renterId));
$completedCount = (int)$stmtCompleted->fetchColumn();

// Estimated Total Savings (sum of original_price - rental_amount for completed bookings)
$stmtSavings = $pdo->prepare("SELECT SUM(GREATEST(0, p.original_price - b.rental_amount)) 
                             FROM bookings b 
                             JOIN products p ON b.product_id = p.id 
                             WHERE b.renter_id = ? AND b.status = 'completed'");
$stmtSavings->execute(array($renterId));
$totalSavings = (float)($stmtSavings->fetchColumn() ?: 0);

// Recent Bookings (limit 5)
$stmtRecent = $pdo->prepare("SELECT b.*, p.title as product_title, p.location as product_location, u.name as owner_name,
                            (SELECT image_path FROM product_images WHERE product_id = p.id ORDER BY is_primary DESC, id ASC LIMIT 1) as primary_image
                            FROM bookings b
                            JOIN products p ON b.product_id = p.id
                            JOIN users u ON b.owner_id = u.id
                            WHERE b.renter_id = ?
                            ORDER BY b.created_at DESC
                            LIMIT 5");
$stmtRecent->execute(array($renterId));
$recentBookings = $stmtRecent->fetchAll();
?>

<div class="py-4" style="background-color: var(--bg-main);">
  <div class="container">
    <div class="dashboard-container">
      <!-- Dashboard Sidebar -->
      <aside class="dashboard-sidebar rounded-4 shadow-sm me-lg-4 mb-4 mb-lg-0">
        <div class="text-center pb-3 border-bottom mb-3">
          <img src="<?php echo  asset('images/default-avatar.png'); ?>" alt="Avatar" class="rounded-circle border border-2 border-success shadow-sm mb-2" width="64" height="64">
          <h6 class="fw-bold text-dark mb-0"><?php echo  e($user['name']); ?></h6>
          <small class="text-muted"><?php echo  e($user['email']); ?></small>
          <div class="mt-2">
            <span class="badge bg-success-subtle text-success text-capitalize px-3"><?php echo  e($user['role']); ?></span>
          </div>
        </div>

        <ul class="sidebar-menu">
          <li class="sidebar-menu-item">
            <a href="<?php echo  url('user/dashboard.php'); ?>" class="sidebar-link active">
              <i class="fa-solid fa-gauge"></i> Renter Overview
            </a>
          </li>
          <li class="sidebar-menu-item">
            <a href="<?php echo  url('user/my-bookings.php'); ?>" class="sidebar-link">
              <i class="fa-solid fa-calendar-check"></i> My Bookings
              <?php if ($pendingCount > 0): ?>
                <span class="badge bg-warning text-dark ms-auto"><?php echo  $pendingCount; ?></span>
              <?php endif; ?>
            </a>
          </li>
          <li class="sidebar-menu-item">
            <a href="<?php echo  url('user/rental-history.php'); ?>" class="sidebar-link">
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

          <!-- Switch to Owner -->
          <li class="sidebar-menu-item mt-4 pt-3 border-top">
            <a href="<?php echo  url('owner/dashboard.php'); ?>" class="sidebar-link text-success fw-bold">
              <i class="fa-solid fa-store text-success"></i> Switch to Owner Portal
            </a>
          </li>
        </ul>
      </aside>

      <!-- Main Content -->
      <main class="dashboard-main rounded-4 shadow-sm p-4 bg-white flex-grow-1">
        <!-- Header -->
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
          <div>
            <?php $nameParts = explode(' ', $user['name']); ?>
            <h3 class="fw-bold text-dark mb-1">Welcome back, <?php echo  e($nameParts[0]); ?>! 👋</h3>
            <p class="text-muted small mb-0">Track your active rentals, pending approvals, and rental savings.</p>
          </div>
          <a href="<?php echo  url('explore.php'); ?>" class="btn btn-primary-custom btn-sm">
            <i class="fa-solid fa-magnifying-glass me-1"></i> Rent Another Item
          </a>
        </div>

        <!-- Metrics Cards -->
        <div class="row g-3 mb-4">
          <div class="col-xl-3 col-sm-6">
            <div class="metric-card">
              <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="text-muted small fw-bold">Total Rentals</span>
                <div class="metric-icon-box bg-primary-light text-primary">
                  <i class="fa-solid fa-receipt"></i>
                </div>
              </div>
              <div class="metric-number"><?php echo  $totalRentals; ?></div>
              <small class="text-muted">All-time rental requests</small>
            </div>
          </div>

          <div class="col-xl-3 col-sm-6">
            <div class="metric-card">
              <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="text-muted small fw-bold">Pending Review</span>
                <div class="metric-icon-box bg-warning-subtle text-warning">
                  <i class="fa-solid fa-clock"></i>
                </div>
              </div>
              <div class="metric-number"><?php echo  $pendingCount; ?></div>
              <small class="text-muted">Awaiting owner response</small>
            </div>
          </div>

          <div class="col-xl-3 col-sm-6">
            <div class="metric-card">
              <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="text-muted small fw-bold">Active In-Hand</span>
                <div class="metric-icon-box bg-info-subtle text-info">
                  <i class="fa-solid fa-box-open"></i>
                </div>
              </div>
              <div class="metric-number"><?php echo  $activeCount; ?></div>
              <small class="text-muted">Currently in your possession</small>
            </div>
          </div>

          <div class="col-xl-3 col-sm-6">
            <div class="metric-card">
              <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="text-muted small fw-bold">Pocket Savings</span>
                <div class="metric-icon-box bg-success-subtle text-success">
                  <i class="fa-solid fa-piggy-bank"></i>
                </div>
              </div>
              <div class="metric-number text-success"><?php echo  formatPrice($totalSavings); ?></div>
              <small class="text-muted">Saved vs buying retail</small>
            </div>
          </div>
        </div>

        <!-- Recent Bookings Table -->
        <div class="card border-0 shadow-sm rounded-4 p-4 bg-light-subtle">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-bold text-dark mb-0">Recent Rental Activities</h5>
            <a href="<?php echo  url('user/my-bookings.php'); ?>" class="btn btn-outline-custom btn-sm">
              View All Bookings <i class="fa-solid fa-arrow-right ms-1"></i>
            </a>
          </div>

          <?php if (empty($recentBookings)): ?>
            <div class="text-center py-5 bg-white rounded-3 border">
              <i class="fa-solid fa-calendar-xmark text-muted fs-1 mb-2"></i>
              <h6 class="fw-bold text-dark">No Bookings Placed Yet</h6>
              <p class="text-muted small mb-3">Explore items near you across Kerala and place your first rental request!</p>
              <a href="<?php echo  url('explore.php'); ?>" class="btn btn-primary-custom btn-sm">Explore Items</a>
            </div>
          <?php else: ?>
            <div class="table-responsive">
              <table class="custom-table">
                <thead>
                  <tr>
                    <th>Booking Code</th>
                    <th>Product</th>
                    <th>Dates</th>
                    <th>Total Paid</th>
                    <th>Status</th>
                    <th>Action</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($recentBookings as $bk): ?>
                    <?php
                      $bkImg = $bk['primary_image'] ?: 'assets/images/placeholder.jpg';
                      if (!str_starts_with($bkImg, 'http://') && !str_starts_with($bkImg, 'https://')) {
                        $bkImg = url($bkImg);
                      }
                    ?>
                    <tr>
                      <td>
                        <span class="fw-bold font-monospace text-primary"><?php echo  e($bk['booking_code']); ?></span>
                      </td>
                      <td>
                        <div class="d-flex align-items-center gap-2">
                          <img src="<?php echo  e($bkImg); ?>" class="rounded-2" width="40" height="34" style="object-fit: cover;">
                          <div>
                            <div class="fw-bold text-dark text-truncate" style="max-width: 200px;"><?php echo  e($bk['product_title']); ?></div>
                            <small class="text-muted">Owner: <?php echo  e($bk['owner_name']); ?></small>
                          </div>
                        </div>
                      </td>
                      <td>
                        <div class="small fw-semibold text-dark"><?php echo  formatDate($bk['start_date']); ?> &rarr; <?php echo  formatDate($bk['end_date']); ?></div>
                        <small class="text-muted"><?php echo  $bk['total_days']; ?> days</small>
                      </td>
                      <td>
                        <div class="fw-bold text-dark"><?php echo  formatPrice($bk['total_amount']); ?></div>
                        <small class="text-success" style="font-size: 0.72rem;">Deposit: <?php echo  formatPrice($bk['security_deposit']); ?></small>
                      </td>
                      <td>
                        <span class="badge-status <?php echo  e($bk['status']); ?>">
                          <?php echo  e($bk['status']); ?>
                        </span>
                      </td>
                      <td>
                        <a href="<?php echo  url('user/my-bookings.php'); ?>" class="btn btn-sm btn-outline-secondary">
                          Details
                        </a>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php endif; ?>
        </div>
      </main>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

