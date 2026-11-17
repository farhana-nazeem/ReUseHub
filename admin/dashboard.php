<?php
/**
 * ReUseHub - Administrator Control Dashboard
 */

$pageTitle = 'Admin Dashboard';
$activePage = 'admin_dashboard';
require_once __DIR__ . '/../includes/header.php';

requireAdmin();
$user = currentUser();
$pdo = getDB();

// Handle quick listing approval/rejection from dashboard
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    verify_csrf_token();
    $prodId = (int)(isset($_POST['product_id']) ? $_POST['product_id'] : 0);
    $action = $_POST['action'];

    $prodStmt = $pdo->prepare("SELECT title, owner_id FROM products WHERE id = ?");
    $prodStmt->execute(array($prodId));
    $prod = $prodStmt->fetch();

    if ($prod) {
        if ($action === 'approve_product') {
            $pdo->prepare("UPDATE products SET status = 'approved', updated_at = NOW() WHERE id = ?")->execute(array($prodId));
            createNotification(
                $prod['owner_id'],
                'Product Listing Approved! 🎉',
                "Your item '{$prod['title']}' has been approved by the admin team and is now live on the marketplace.",
                'product-details.php?id=' . $prodId
            );
            setFlash('success', "Product '{$prod['title']}' approved.");
        } elseif ($action === 'reject_product') {
            $reason = trim(isset($_POST['reject_reason']) ? $_POST['reject_reason'] : 'Did not meet listing guidelines');
            $pdo->prepare("UPDATE products SET status = 'rejected', updated_at = NOW() WHERE id = ?")->execute(array($prodId));
            createNotification(
                $prod['owner_id'],
                'Product Listing Declined',
                "Your item '{$prod['title']}' was declined. Reason: {$reason}",
                'owner/my-products.php'
            );
            setFlash('info', "Product '{$prod['title']}' declined.");
        }
    }
    header("Location: " . url('admin/dashboard.php'));
    exit();
}

// 1. Fetch Real Database Metrics
$totalUsers = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$totalProducts = (int)$pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
$totalBookings = (int)$pdo->query("SELECT COUNT(*) FROM bookings")->fetchColumn();
$totalTransactions = (float)($pdo->query("SELECT SUM(amount + deposit_amount) FROM payments WHERE payment_status = 'paid'")->fetchColumn() ?: 0.00);
$platformEarnings = (float)($pdo->query("SELECT SUM(service_fee) FROM bookings WHERE status IN ('approved', 'active', 'completed')")->fetchColumn() ?: 0.00);
$pendingProductsCount = (int)$pdo->query("SELECT COUNT(*) FROM products WHERE status = 'pending'")->fetchColumn();
$pendingReportsCount = (int)$pdo->query("SELECT COUNT(*) FROM reports WHERE status = 'pending'")->fetchColumn();

// 2. Fetch Pending Listings for Quick Moderation
$pendingStmt = $pdo->query("SELECT p.*, c.name as category_name, u.name as owner_name, u.email as owner_email,
                            (SELECT image_path FROM product_images WHERE product_id = p.id ORDER BY is_primary DESC, id ASC LIMIT 1) as primary_image
                            FROM products p
                            JOIN categories c ON p.category_id = c.id
                            JOIN users u ON p.owner_id = u.id
                            WHERE p.status = 'pending'
                            ORDER BY p.created_at ASC
                            LIMIT 5");
$pendingProducts = $pendingStmt->fetchAll();

// 3. Fetch Recent Platform Bookings
$recentBkStmt = $pdo->query("SELECT b.*, p.title as product_title, r.name as renter_name, o.name as owner_name 
                             FROM bookings b
                             JOIN products p ON b.product_id = p.id
                             JOIN users r ON b.renter_id = r.id
                             JOIN users o ON b.owner_id = o.id
                             ORDER BY b.created_at DESC
                             LIMIT 5");
$recentBookings = $recentBkStmt->fetchAll();
?>

<div class="py-4" style="background-color: var(--bg-main);">
  <div class="container">
    <div class="dashboard-container">
      <!-- Admin Sidebar -->
      <aside class="dashboard-sidebar rounded-4 shadow-sm me-lg-4 mb-4 mb-lg-0">
        <div class="text-center pb-3 border-bottom mb-3">
          <div class="rounded-circle bg-danger-subtle text-danger mx-auto mb-2 d-flex align-items-center justify-content-center" style="width: 54px; height: 54px; font-size: 1.4rem;">
            <i class="fa-solid fa-shield-halved"></i>
          </div>
          <h6 class="fw-bold text-dark mb-0"><?php echo  e($user['name']); ?></h6>
          <span class="badge bg-danger text-white px-3 mt-1">Platform Administrator</span>
        </div>

        <ul class="sidebar-menu">
          <li class="sidebar-menu-item">
            <a href="<?php echo  url('admin/dashboard.php'); ?>" class="sidebar-link active">
              <i class="fa-solid fa-gauge-high"></i> Admin Overview
            </a>
          </li>
          <li class="sidebar-menu-item">
            <a href="<?php echo  url('admin/products.php'); ?>" class="sidebar-link">
              <i class="fa-solid fa-boxes-stacked"></i> Manage Products
              <?php if ($pendingProductsCount > 0): ?>
                <span class="badge bg-warning text-dark ms-auto"><?php echo  $pendingProductsCount; ?></span>
              <?php endif; ?>
            </a>
          </li>
          <li class="sidebar-menu-item">
            <a href="<?php echo  url('admin/users.php'); ?>" class="sidebar-link">
              <i class="fa-solid fa-users"></i> Registered Users
            </a>
          </li>
          <li class="sidebar-menu-item">
            <a href="<?php echo  url('admin/bookings.php'); ?>" class="sidebar-link">
              <i class="fa-solid fa-calendar-days"></i> All Bookings
            </a>
          </li>
          <li class="sidebar-menu-item">
            <a href="<?php echo  url('admin/categories.php'); ?>" class="sidebar-link">
              <i class="fa-solid fa-tags"></i> Categories
            </a>
          </li>
          <li class="sidebar-menu-item">
            <a href="<?php echo  url('admin/reports.php'); ?>" class="sidebar-link">
              <i class="fa-solid fa-flag"></i> Reports
              <?php if ($pendingReportsCount > 0): ?>
                <span class="badge bg-danger ms-auto"><?php echo  $pendingReportsCount; ?></span>
              <?php endif; ?>
            </a>
          </li>
          <li class="sidebar-menu-item mt-4 pt-3 border-top">
            <a href="<?php echo  url('explore.php'); ?>" class="sidebar-link text-primary fw-bold">
              <i class="fa-solid fa-store"></i> View Live Marketplace
            </a>
          </li>
        </ul>
      </aside>

      <!-- Main Admin Content -->
      <main class="dashboard-main rounded-4 shadow-sm p-4 bg-white flex-grow-1">
        <div class="d-flex justify-content-between align-items-center mb-4">
          <div>
            <h3 class="fw-bold text-dark mb-1">Administrative Control Center 🛡️</h3>
            <p class="text-muted small mb-0">Platform oversight, user verification, listing moderation, and revenue tracking.</p>
          </div>
          <span class="badge bg-success-subtle text-success py-2 px-3 fw-bold">
            <i class="fa-solid fa-circle-check me-1"></i> System Operational
          </span>
        </div>

        <!-- Metric Cards -->
        <div class="row g-3 mb-4">
          <div class="col-xl-3 col-sm-6">
            <div class="metric-card">
              <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="text-muted small fw-bold">Total Users</span>
                <div class="metric-icon-box bg-primary-light text-primary">
                  <i class="fa-solid fa-users"></i>
                </div>
              </div>
              <div class="metric-number"><?php echo  $totalUsers; ?></div>
              <small class="text-muted">Registered members</small>
            </div>
          </div>

          <div class="col-xl-3 col-sm-6">
            <div class="metric-card">
              <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="text-muted small fw-bold">Total Products</span>
                <div class="metric-icon-box bg-info-subtle text-info">
                  <i class="fa-solid fa-box-archive"></i>
                </div>
              </div>
              <div class="metric-number"><?php echo  $totalProducts; ?></div>
              <small class="text-muted">Listed across Kerala</small>
            </div>
          </div>

          <div class="col-xl-3 col-sm-6">
            <div class="metric-card">
              <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="text-muted small fw-bold">Platform Gross GMV</span>
                <div class="metric-icon-box bg-success-subtle text-success">
                  <i class="fa-solid fa-money-bill-wave"></i>
                </div>
              </div>
              <div class="metric-number text-success"><?php echo  formatPrice($totalTransactions); ?></div>
              <small class="text-muted">Total transaction volume</small>
            </div>
          </div>

          <div class="col-xl-3 col-sm-6">
            <div class="metric-card">
              <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="text-muted small fw-bold">Platform Service Fees</span>
                <div class="metric-icon-box bg-warning-subtle text-warning">
                  <i class="fa-solid fa-coins"></i>
                </div>
              </div>
              <div class="metric-number text-warning"><?php echo  formatPrice($platformEarnings); ?></div>
              <small class="text-muted">3% platform revenue</small>
            </div>
          </div>
        </div>

        <!-- Pending Moderation Queue -->
        <div class="card border-0 shadow-sm rounded-4 p-4 bg-light-subtle mb-4">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
              <h5 class="fw-bold text-dark mb-0"><i class="fa-solid fa-hourglass-half text-warning me-2"></i> Pending Product Approvals (<?php echo  $pendingProductsCount; ?>)</h5>
              <small class="text-muted">Review new listings before they become searchable on the marketplace.</small>
            </div>
            <a href="<?php echo  url('admin/products.php?status=pending'); ?>" class="btn btn-outline-custom btn-sm">
              View All Pending
            </a>
          </div>

          <?php if (empty($pendingProducts)): ?>
            <div class="text-center py-4 bg-white rounded-3 border">
              <i class="fa-solid fa-circle-check text-success fs-2 mb-2 d-block"></i>
              <span class="text-muted small">All clear! No listings currently waiting for moderation.</span>
            </div>
          <?php else: ?>
            <div class="table-responsive">
              <table class="custom-table">
                <thead>
                  <tr>
                    <th>Product</th>
                    <th>Category</th>
                    <th>Owner</th>
                    <th>Daily Rate</th>
                    <th>Deposit</th>
                    <th>Action</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($pendingProducts as $p): ?>
                    <?php
                      $pImg = $p['primary_image'] ?: 'assets/images/placeholder.jpg';
                      if (!str_starts_with($pImg, 'http://') && !str_starts_with($pImg, 'https://')) {
                        $pImg = url($pImg);
                      }
                    ?>
                    <tr>
                      <td>
                        <div class="d-flex align-items-center gap-2">
                          <img src="<?php echo  e($pImg); ?>" class="rounded-2" width="44" height="38" style="object-fit: cover;">
                          <div>
                            <a href="<?php echo  url('product-details.php?id=' . $p['id']); ?>" target="_blank" class="fw-bold text-dark text-decoration-none">
                              <?php echo  e($p['title']); ?> <i class="fa-solid fa-arrow-up-right-from-square small text-muted"></i>
                            </a>
                            <small class="text-muted d-block"><?php echo  e($p['condition_grade']); ?> &bull; <?php echo  e($p['location']); ?></small>
                          </div>
                        </div>
                      </td>
                      <td>
                        <span class="badge bg-light text-dark border"><?php echo  e($p['category_name']); ?></span>
                      </td>
                      <td>
                        <div class="small fw-semibold"><?php echo  e($p['owner_name']); ?></div>
                        <small class="text-muted"><?php echo  e($p['owner_email']); ?></small>
                      </td>
                      <td>
                        <span class="fw-bold text-primary"><?php echo  formatPrice($p['daily_price']); ?></span>
                      </td>
                      <td>
                        <span class="text-muted small"><?php echo  formatPrice($p['security_deposit']); ?></span>
                      </td>
                      <td>
                        <div class="d-flex gap-1">
                          <form action="<?php echo  url('admin/dashboard.php'); ?>" method="POST" class="d-inline">
                            <?php echo  csrf_field(); ?>
                            <input type="hidden" name="action" value="approve_product">
                            <input type="hidden" name="product_id" value="<?php echo  $p['id']; ?>">
                            <button type="submit" class="btn btn-sm btn-success px-2 py-1" title="Approve Listing">
                              <i class="fa-solid fa-check"></i> Approve
                            </button>
                          </form>
                          <button type="button" class="btn btn-sm btn-outline-danger px-2 py-1" data-bs-toggle="modal" data-bs-target="#rejectModal<?php echo  $p['id']; ?>">
                            <i class="fa-solid fa-xmark"></i>
                          </button>
                        </div>

                        <!-- Reject Modal -->
                        <div class="modal fade" id="rejectModal<?php echo  $p['id']; ?>" tabindex="-1" aria-hidden="true">
                          <div class="modal-dialog">
                            <div class="modal-content rounded-4 border-0">
                              <div class="modal-header">
                                <h5 class="modal-title fw-bold text-dark">Reject Listing</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                              </div>
                              <form action="<?php echo  url('admin/dashboard.php'); ?>" method="POST">
                                <?php echo  csrf_field(); ?>
                                <input type="hidden" name="action" value="reject_product">
                                <input type="hidden" name="product_id" value="<?php echo  $p['id']; ?>">
                                <div class="modal-body text-start">
                                  <label class="form-label small fw-bold">Reason for Rejection</label>
                                  <textarea name="reject_reason" class="form-control" rows="2" placeholder="Unclear photos, inappropriate pricing, restricted item..." required></textarea>
                                </div>
                                <div class="modal-footer">
                                  <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                                  <button type="submit" class="btn btn-danger">Reject Listing</button>
                                </div>
                              </form>
                            </div>
                          </div>
                        </div>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php endif; ?>
        </div>

        <!-- Recent Platform Bookings -->
        <div class="card border-0 shadow-sm rounded-4 p-4 bg-light-subtle">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-bold text-dark mb-0"><i class="fa-solid fa-chart-pie text-primary me-2"></i> Recent Platform Bookings</h5>
            <a href="<?php echo  url('admin/bookings.php'); ?>" class="btn btn-outline-custom btn-sm">
              View All (<?php echo  $totalBookings; ?>)
            </a>
          </div>

          <div class="table-responsive">
            <table class="custom-table">
              <thead>
                <tr>
                  <th>Booking</th>
                  <th>Product</th>
                  <th>Renter</th>
                  <th>Owner</th>
                  <th>Dates</th>
                  <th>Total</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($recentBookings as $b): ?>
                  <tr>
                    <td>
                      <span class="font-monospace fw-bold text-primary small"><?php echo  e($b['booking_code']); ?></span>
                    </td>
                    <td>
                      <span class="fw-semibold text-dark text-truncate d-inline-block" style="max-width: 160px;"><?php echo  e($b['product_title']); ?></span>
                    </td>
                    <td><span class="small"><?php echo  e($b['renter_name']); ?></span></td>
                    <td><span class="small"><?php echo  e($b['owner_name']); ?></span></td>
                    <td><span class="small text-muted"><?php echo  formatDate($b['start_date']); ?> &rarr; <?php echo  formatDate($b['end_date']); ?></span></td>
                    <td><span class="fw-bold text-success"><?php echo  formatPrice($b['total_amount']); ?></span></td>
                    <td><span class="badge-status <?php echo  e($b['status']); ?>"><?php echo  e($b['status']); ?></span></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      </main>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

