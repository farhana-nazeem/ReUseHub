<?php
/**
 * ReUseHub - Owner Dashboard
 */

$pageTitle = 'Owner Dashboard';
$activePage = 'owner_dashboard';
require_once __DIR__ . '/../includes/header.php';

requireOwner();
$user = currentUser();
$pdo = getDB();

$ownerId = $user['id'];

// 1. Calculate Real Metrics from Database
// Total Listed Products
$stmtTotal = $pdo->prepare("SELECT COUNT(*) FROM products WHERE owner_id = ?");
$stmtTotal->execute(array($ownerId));
$totalProducts = (int)$stmtTotal->fetchColumn();

// Active Live Listings (Approved & Available)
$stmtActiveListings = $pdo->prepare("SELECT COUNT(*) FROM products WHERE owner_id = ? AND status = 'approved' AND is_available = 1");
$stmtActiveListings->execute(array($ownerId));
$activeListings = (int)$stmtActiveListings->fetchColumn();

// Pending Booking Requests
$stmtPending = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE owner_id = ? AND status = 'pending'");
$stmtPending->execute(array($ownerId));
$pendingRequests = (int)$stmtPending->fetchColumn();

// Approved Bookings
$stmtApproved = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE owner_id = ? AND status = 'approved'");
$stmtApproved->execute(array($ownerId));
$approvedBookings = (int)$stmtApproved->fetchColumn();

// Completed Rentals
$stmtCompleted = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE owner_id = ? AND status = 'completed'");
$stmtCompleted->execute(array($ownerId));
$completedRentals = (int)$stmtCompleted->fetchColumn();

// Total Rental Earnings (sum of rental_amount from completed or approved bookings)
$stmtEarnings = $pdo->prepare("SELECT SUM(rental_amount) FROM bookings WHERE owner_id = ? AND status IN ('completed', 'approved', 'active')");
$stmtEarnings->execute(array($ownerId));
$totalEarnings = (float)($stmtEarnings->fetchColumn() ?: 0.00);

// Recent Booking Requests
$stmtRecent = $pdo->prepare("SELECT b.*, p.title as product_title, u.name as renter_name, u.phone as renter_phone,
                            (SELECT image_path FROM product_images WHERE product_id = p.id ORDER BY is_primary DESC, id ASC LIMIT 1) as primary_image
                            FROM bookings b
                            JOIN products p ON b.product_id = p.id
                            JOIN users u ON b.renter_id = u.id
                            WHERE b.owner_id = ?
                            ORDER BY b.created_at DESC
                            LIMIT 5");
$stmtRecent->execute(array($ownerId));
$recentRequests = $stmtRecent->fetchAll();
?>

<div class="py-4" style="background-color: var(--bg-main);">
  <div class="container">
    <div class="dashboard-container">
      <!-- Owner Sidebar -->
      <aside class="dashboard-sidebar rounded-4 shadow-sm me-lg-4 mb-4 mb-lg-0">
        <div class="text-center pb-3 border-bottom mb-3">
          <img src="<?php echo  asset('images/default-avatar.png'); ?>" alt="Avatar" class="rounded-circle border border-2 border-success shadow-sm mb-2" width="64" height="64">
          <h6 class="fw-bold text-dark mb-0"><?php echo  e($user['name']); ?></h6>
          <small class="text-muted"><?php echo  e($user['email']); ?></small>
          <div class="mt-2">
            <span class="badge bg-success text-white px-3">Product Owner</span>
          </div>
        </div>

        <ul class="sidebar-menu">
          <li class="sidebar-menu-item">
            <a href="<?php echo  url('owner/dashboard.php'); ?>" class="sidebar-link active">
              <i class="fa-solid fa-chart-line"></i> Owner Overview
            </a>
          </li>
          <li class="sidebar-menu-item">
            <a href="<?php echo  url('owner/my-products.php'); ?>" class="sidebar-link">
              <i class="fa-solid fa-box-open"></i> My Listed Products
            </a>
          </li>
          <li class="sidebar-menu-item">
            <a href="<?php echo  url('list-item.php'); ?>" class="sidebar-link">
              <i class="fa-solid fa-circle-plus"></i> Add New Product
            </a>
          </li>
          <li class="sidebar-menu-item">
            <a href="<?php echo  url('owner/booking-requests.php'); ?>" class="sidebar-link">
              <i class="fa-solid fa-inbox"></i> Booking Requests
              <?php if ($pendingRequests > 0): ?>
                <span class="badge bg-danger ms-auto"><?php echo  $pendingRequests; ?></span>
              <?php endif; ?>
            </a>
          </li>

          <!-- Switch to Renter -->
          <li class="sidebar-menu-item mt-4 pt-3 border-top">
            <a href="<?php echo  url('user/dashboard.php'); ?>" class="sidebar-link text-primary fw-bold">
              <i class="fa-solid fa-user text-primary"></i> Switch to Renter Portal
            </a>
          </li>
        </ul>
      </aside>

      <!-- Main Content -->
      <main class="dashboard-main rounded-4 shadow-sm p-4 bg-white flex-grow-1">
        <!-- Header -->
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
          <div>
            <h3 class="fw-bold text-dark mb-1">Owner Dashboard & Earnings 💰</h3>
            <p class="text-muted small mb-0">Overview of your items, passive rental earnings, and pending renter requests.</p>
          </div>
          <a href="<?php echo  url('list-item.php'); ?>" class="btn btn-primary-custom btn-sm">
            <i class="fa-solid fa-plus me-1"></i> Add Another Product
          </a>
        </div>

        <!-- Metric Cards -->
        <div class="row g-3 mb-4">
          <div class="col-xl-3 col-sm-6">
            <div class="metric-card">
              <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="text-muted small fw-bold">Total Earnings</span>
                <div class="metric-icon-box bg-success-subtle text-success">
                  <i class="fa-solid fa-wallet"></i>
                </div>
              </div>
              <div class="metric-number text-success"><?php echo  formatPrice($totalEarnings); ?></div>
              <small class="text-muted">Generated from rentals</small>
            </div>
          </div>

          <div class="col-xl-3 col-sm-6">
            <div class="metric-card">
              <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="text-muted small fw-bold">Active Listings</span>
                <div class="metric-icon-box bg-primary-light text-primary">
                  <i class="fa-solid fa-box-check"></i>
                </div>
              </div>
              <div class="metric-number"><?php echo  $activeListings; ?> <span class="fs-6 text-muted font-normal">/ <?php echo  $totalProducts; ?></span></div>
              <small class="text-muted">Currently live & searchable</small>
            </div>
          </div>

          <div class="col-xl-3 col-sm-6">
            <div class="metric-card">
              <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="text-muted small fw-bold">Pending Requests</span>
                <div class="metric-icon-box bg-warning-subtle text-warning">
                  <i class="fa-solid fa-hourglass-half"></i>
                </div>
              </div>
              <div class="metric-number text-warning"><?php echo  $pendingRequests; ?></div>
              <small class="text-muted">Needs your confirmation</small>
            </div>
          </div>

          <div class="col-xl-3 col-sm-6">
            <div class="metric-card">
              <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="text-muted small fw-bold">Completed Rentals</span>
                <div class="metric-icon-box bg-info-subtle text-info">
                  <i class="fa-solid fa-circle-check"></i>
                </div>
              </div>
              <div class="metric-number"><?php echo  $completedRentals; ?></div>
              <small class="text-muted">Safely returned rentals</small>
            </div>
          </div>
        </div>

        <!-- Recent Booking Requests Table -->
        <div class="card border-0 shadow-sm rounded-4 p-4 bg-light-subtle">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-bold text-dark mb-0">Recent Booking Inquiries</h5>
            <a href="<?php echo  url('owner/booking-requests.php'); ?>" class="btn btn-outline-custom btn-sm">
              Manage All Requests <i class="fa-solid fa-arrow-right ms-1"></i>
            </a>
          </div>

          <?php if (empty($recentRequests)): ?>
            <div class="text-center py-5 bg-white rounded-3 border">
              <i class="fa-solid fa-inbox text-muted fs-1 mb-2"></i>
              <h6 class="fw-bold text-dark">No Booking Requests Yet</h6>
              <p class="text-muted small mb-0">When renters submit requests for your products, they will appear here for your approval.</p>
            </div>
          <?php else: ?>
            <div class="table-responsive">
              <table class="custom-table">
                <thead>
                  <tr>
                    <th>Booking Code</th>
                    <th>Product</th>
                    <th>Renter Details</th>
                    <th>Dates</th>
                    <th>Earnings</th>
                    <th>Status</th>
                    <th>Action</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($recentRequests as $rq): ?>
                    <?php
                      $rqImg = $rq['primary_image'] ?: 'assets/images/placeholder.jpg';
                      if (!str_starts_with($rqImg, 'http://') && !str_starts_with($rqImg, 'https://')) {
                        $rqImg = url($rqImg);
                      }
                    ?>
                    <tr>
                      <td>
                        <span class="fw-bold font-monospace text-primary"><?php echo  e($rq['booking_code']); ?></span>
                      </td>
                      <td>
                        <div class="d-flex align-items-center gap-2">
                          <img src="<?php echo  e($rqImg); ?>" class="rounded-2" width="40" height="34" style="object-fit: cover;">
                          <div class="fw-bold text-dark text-truncate" style="max-width: 180px;"><?php echo  e($rq['product_title']); ?></div>
                        </div>
                      </td>
                      <td>
                        <div class="fw-semibold text-dark small"><?php echo  e($rq['renter_name']); ?></div>
                        <small class="text-muted"><?php echo  e($rq['renter_phone']); ?></small>
                      </td>
                      <td>
                        <div class="small fw-semibold"><?php echo  formatDate($rq['start_date']); ?> &rarr; <?php echo  formatDate($rq['end_date']); ?></div>
                        <small class="text-muted"><?php echo  $rq['total_days']; ?> days</small>
                      </td>
                      <td>
                        <span class="fw-bold text-success"><?php echo  formatPrice($rq['rental_amount']); ?></span>
                      </td>
                      <td>
                        <span class="badge-status <?php echo  e($rq['status']); ?>"><?php echo  e($rq['status']); ?></span>
                      </td>
                      <td>
                        <a href="<?php echo  url('owner/booking-requests.php'); ?>" class="btn btn-sm btn-outline-success">
                          Respond
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

