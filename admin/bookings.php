<?php
/**
 * ReUseHub - Admin Bookings Oversight
 */

$pageTitle = 'Manage Platform Bookings';
$activePage = 'admin_bookings';
require_once __DIR__ . '/../includes/header.php';

requireAdmin();
$user = currentUser();
$pdo = getDB();

$statusFilter = trim(isset($_GET['status']) ? $_GET['status'] : 'all');
$search = trim(isset($_GET['q']) ? $_GET['q'] : '');

$where = array("1=1");
$params = array();

if ($statusFilter !== 'all' && in_array($statusFilter, array('pending', 'approved', 'active', 'completed', 'cancelled', 'rejected'))) {
    $where[] = "b.status = ?";
    $params[] = $statusFilter;
}

if (!empty($search)) {
    $where[] = "(b.booking_code LIKE ? OR p.title LIKE ? OR r.name LIKE ? OR o.name LIKE ?)";
    $like = "%{$search}%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

$whereSql = implode(" AND ", $where);

$stmt = $pdo->prepare("SELECT b.*, p.title as product_title, r.name as renter_name, r.email as renter_email,
                       o.name as owner_name, o.email as owner_email,
                       pay.transaction_id, pay.payment_method, pay.payment_status
                       FROM bookings b
                       JOIN products p ON b.product_id = p.id
                       JOIN users r ON b.renter_id = r.id
                       JOIN users o ON b.owner_id = o.id
                       LEFT JOIN payments pay ON pay.booking_id = b.id
                       WHERE {$whereSql}
                       ORDER BY b.created_at DESC");
$stmt->execute($params);
$bookings = $stmt->fetchAll();
?>

<div class="py-4" style="background-color: var(--bg-main);">
  <div class="container">
    <div class="dashboard-container">
      <!-- Admin Sidebar -->
      <aside class="dashboard-sidebar rounded-4 shadow-sm me-lg-4 mb-4 mb-lg-0">
        <ul class="sidebar-menu">
          <li class="sidebar-menu-item"><a href="<?php echo  url('admin/dashboard.php'); ?>" class="sidebar-link"><i class="fa-solid fa-gauge-high"></i> Admin Overview</a></li>
          <li class="sidebar-menu-item"><a href="<?php echo  url('admin/products.php'); ?>" class="sidebar-link"><i class="fa-solid fa-boxes-stacked"></i> Manage Products</a></li>
          <li class="sidebar-menu-item"><a href="<?php echo  url('admin/users.php'); ?>" class="sidebar-link"><i class="fa-solid fa-users"></i> Registered Users</a></li>
          <li class="sidebar-menu-item"><a href="<?php echo  url('admin/bookings.php'); ?>" class="sidebar-link active"><i class="fa-solid fa-calendar-days"></i> All Bookings</a></li>
          <li class="sidebar-menu-item"><a href="<?php echo  url('admin/categories.php'); ?>" class="sidebar-link"><i class="fa-solid fa-tags"></i> Categories</a></li>
          <li class="sidebar-menu-item"><a href="<?php echo  url('admin/reports.php'); ?>" class="sidebar-link"><i class="fa-solid fa-flag"></i> Reports</a></li>
          <li class="sidebar-menu-item mt-4 pt-3 border-top"><a href="<?php echo  url('explore.php'); ?>" class="sidebar-link text-primary fw-bold"><i class="fa-solid fa-store"></i> View Live Site</a></li>
        </ul>
      </aside>

      <!-- Main Content -->
      <main class="dashboard-main rounded-4 shadow-sm p-4 bg-white flex-grow-1">
        <div class="d-flex justify-content-between align-items-center mb-4">
          <div>
            <h3 class="fw-bold text-dark mb-1">Platform Rental Bookings (<?php echo  count($bookings); ?>)</h3>
            <p class="text-muted small mb-0">Full audit trail of bookings, financial transactions, and dispute statuses.</p>
          </div>
        </div>

        <!-- Filter Bar -->
        <div class="card border rounded-3 p-3 bg-light mb-4">
          <form action="<?php echo  url('admin/bookings.php'); ?>" method="GET" class="row g-2 align-items-center">
            <div class="col-md-7">
              <input type="text" name="q" class="form-control form-control-sm" placeholder="Search booking code, product, renter, owner..." value="<?php echo  e($search); ?>">
            </div>
            <div class="col-md-3">
              <select name="status" class="form-select form-select-sm">
                <option value="all">All Statuses</option>
                <option value="pending" <?php echo  $statusFilter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                <option value="approved" <?php echo  $statusFilter === 'approved' ? 'selected' : ''; ?>>Approved</option>
                <option value="active" <?php echo  $statusFilter === 'active' ? 'selected' : ''; ?>>Active</option>
                <option value="completed" <?php echo  $statusFilter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                <option value="cancelled" <?php echo  $statusFilter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                <option value="rejected" <?php echo  $statusFilter === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
              </select>
            </div>
            <div class="col-md-2">
              <button type="submit" class="btn btn-primary-custom btn-sm w-100">Filter</button>
            </div>
          </form>
        </div>

        <div class="table-responsive">
          <table class="custom-table">
            <thead>
              <tr>
                <th>Booking Code</th>
                <th>Item</th>
                <th>Renter</th>
                <th>Owner</th>
                <th>Rental Dates</th>
                <th>Breakdown</th>
                <th>Payment</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($bookings as $b): ?>
                <tr>
                  <td>
                    <span class="font-monospace fw-bold text-primary small"><?php echo  e($b['booking_code']); ?></span>
                    <small class="text-muted d-block" style="font-size: 0.7rem;"><?php echo  formatDate($b['created_at'], 'd M Y'); ?></small>
                  </td>
                  <td>
                    <a href="<?php echo  url('product-details.php?id=' . $b['product_id']); ?>" target="_blank" class="fw-bold text-dark text-decoration-none">
                      <?php echo  e($b['product_title']); ?>
                    </a>
                  </td>
                  <td>
                    <div class="small fw-semibold"><?php echo  e($b['renter_name']); ?></div>
                    <small class="text-muted"><?php echo  e($b['renter_email']); ?></small>
                  </td>
                  <td>
                    <div class="small fw-semibold"><?php echo  e($b['owner_name']); ?></div>
                    <small class="text-muted"><?php echo  e($b['owner_email']); ?></small>
                  </td>
                  <td>
                    <div class="small fw-semibold"><?php echo  formatDate($b['start_date']); ?> &rarr; <?php echo  formatDate($b['end_date']); ?></div>
                    <small class="text-muted"><?php echo  $b['total_days']; ?> days</small>
                  </td>
                  <td>
                    <div class="fw-bold text-dark"><?php echo  formatPrice($b['total_amount']); ?></div>
                    <small class="text-muted d-block" style="font-size: 0.7rem;">Fee: <?php echo  formatPrice($b['service_fee']); ?> | Dep: <?php echo  formatPrice($b['security_deposit']); ?></small>
                  </td>
                  <td>
                    <span class="badge bg-light text-dark border">
                      <?php echo  e(isset($b['payment_method']) ? $b['payment_method'] : 'Demo'); ?> &bull; <?php echo  e(isset($b['payment_status']) ? $b['payment_status'] : 'paid'); ?>
                    </span>
                  </td>
                  <td>
                    <span class="badge-status <?php echo  e($b['status']); ?>"><?php echo  e($b['status']); ?></span>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </main>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

