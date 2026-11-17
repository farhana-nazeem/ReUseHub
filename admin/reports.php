<?php
/**
 * ReUseHub - Admin Product Reports & Moderation
 */

$pageTitle = 'Reported Listings';
$activePage = 'admin_reports';
require_once __DIR__ . '/../includes/header.php';

requireAdmin();
$user = currentUser();
$pdo = getDB();

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    verify_csrf_token();
    $reportId = (int)(isset($_POST['report_id']) ? $_POST['report_id'] : 0);
    $action = $_POST['action'];

    if ($action === 'dismiss') {
        $pdo->prepare("UPDATE reports SET status = 'dismissed' WHERE id = ?")->execute(array($reportId));
        setFlash('info', 'Report dismissed.');
    } elseif ($action === 'resolve') {
        $pdo->prepare("UPDATE reports SET status = 'resolved' WHERE id = ?")->execute(array($reportId));
        setFlash('success', 'Report marked as resolved.');
    } elseif ($action === 'take_down_product') {
        $prodId = (int)(isset($_POST['product_id']) ? $_POST['product_id'] : 0);
        $pdo->prepare("UPDATE products SET status = 'rejected' WHERE id = ?")->execute(array($prodId));
        $pdo->prepare("UPDATE reports SET status = 'resolved', admin_notes = 'Product delisted by moderator' WHERE id = ?")->execute(array($reportId));
        setFlash('warning', 'Product was taken down and report resolved.');
    }
    header("Location: " . url('admin/reports.php'));
    exit();
}

$stmt = $pdo->query("SELECT r.*, p.title as product_title, p.status as product_status, u.name as reporter_name, u.email as reporter_email 
                     FROM reports r
                     JOIN products p ON r.product_id = p.id
                     JOIN users u ON r.user_id = u.id
                     ORDER BY r.created_at DESC");
$reports = $stmt->fetchAll();
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
          <li class="sidebar-menu-item"><a href="<?php echo  url('admin/bookings.php'); ?>" class="sidebar-link"><i class="fa-solid fa-calendar-days"></i> All Bookings</a></li>
          <li class="sidebar-menu-item"><a href="<?php echo  url('admin/categories.php'); ?>" class="sidebar-link"><i class="fa-solid fa-tags"></i> Categories</a></li>
          <li class="sidebar-menu-item"><a href="<?php echo  url('admin/reports.php'); ?>" class="sidebar-link active"><i class="fa-solid fa-flag"></i> Reports</a></li>
          <li class="sidebar-menu-item mt-4 pt-3 border-top"><a href="<?php echo  url('explore.php'); ?>" class="sidebar-link text-primary fw-bold"><i class="fa-solid fa-store"></i> View Live Site</a></li>
        </ul>
      </aside>

      <!-- Main Content -->
      <main class="dashboard-main rounded-4 shadow-sm p-4 bg-white flex-grow-1">
        <div class="d-flex justify-content-between align-items-center mb-4">
          <div>
            <h3 class="fw-bold text-dark mb-1">User Reported Listings (<?php echo  count($reports); ?>)</h3>
            <p class="text-muted small mb-0">Investigate community flags for inappropriate content, fraud, or damaged products.</p>
          </div>
        </div>

        <?php if (empty($reports)): ?>
          <div class="card border-0 shadow-sm rounded-4 p-5 text-center bg-light-subtle my-3">
            <i class="fa-solid fa-shield-heart text-success fs-1 mb-2"></i>
            <h5 class="fw-bold text-dark">No Active Reports</h5>
            <p class="text-muted small mb-0">The marketplace is completely compliant. No user reports pending review.</p>
          </div>
        <?php else: ?>
          <div class="table-responsive">
            <table class="custom-table">
              <thead>
                <tr>
                  <th>Product</th>
                  <th>Reporter</th>
                  <th>Reason</th>
                  <th>Details</th>
                  <th>Status</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($reports as $rp): ?>
                  <tr>
                    <td>
                      <a href="<?php echo  url('product-details.php?id=' . $rp['product_id']); ?>" target="_blank" class="fw-bold text-dark text-decoration-none">
                        <?php echo  e($rp['product_title']); ?> <i class="fa-solid fa-arrow-up-right-from-square small text-muted"></i>
                      </a>
                      <small class="text-muted d-block">Status: <?php echo  e($rp['product_status']); ?></small>
                    </td>
                    <td>
                      <div class="small fw-semibold"><?php echo  e($rp['reporter_name']); ?></div>
                      <small class="text-muted"><?php echo  e($rp['reporter_email']); ?></small>
                    </td>
                    <td>
                      <span class="badge bg-warning-subtle text-warning fw-bold"><?php echo  e($rp['reason']); ?></span>
                    </td>
                    <td>
                      <small class="text-muted" style="max-width: 200px; display: inline-block;"><?php echo  e($rp['details']); ?></small>
                    </td>
                    <td>
                      <span class="badge bg-light text-dark border text-capitalize"><?php echo  e($rp['status']); ?></span>
                    </td>
                    <td>
                      <div class="d-flex gap-1">
                        <?php if ($rp['status'] === 'pending'): ?>
                          <form action="<?php echo  url('admin/reports.php'); ?>" method="POST" class="d-inline">
                            <?php echo  csrf_field(); ?>
                            <input type="hidden" name="action" value="resolve">
                            <input type="hidden" name="report_id" value="<?php echo  $rp['id']; ?>">
                            <button type="submit" class="btn btn-sm btn-success px-2 py-1" title="Resolve">
                              <i class="fa-solid fa-check"></i>
                            </button>
                          </form>

                          <form action="<?php echo  url('admin/reports.php'); ?>" method="POST" class="d-inline">
                            <?php echo  csrf_field(); ?>
                            <input type="hidden" name="action" value="dismiss">
                            <input type="hidden" name="report_id" value="<?php echo  $rp['id']; ?>">
                            <button type="submit" class="btn btn-sm btn-secondary px-2 py-1" title="Dismiss">
                              <i class="fa-solid fa-xmark"></i>
                            </button>
                          </form>

                          <?php if ($rp['product_status'] === 'approved'): ?>
                            <form action="<?php echo  url('admin/reports.php'); ?>" method="POST" class="d-inline" onsubmit="return confirm('Take down this product immediately?')">
                              <?php echo  csrf_field(); ?>
                              <input type="hidden" name="action" value="take_down_product">
                              <input type="hidden" name="report_id" value="<?php echo  $rp['id']; ?>">
                              <input type="hidden" name="product_id" value="<?php echo  $rp['product_id']; ?>">
                              <button type="submit" class="btn btn-sm btn-danger px-2 py-1" title="Take Down Product">
                                <i class="fa-solid fa-ban"></i>
                              </button>
                            </form>
                          <?php endif; ?>
                        <?php else: ?>
                          <span class="text-muted small">Closed</span>
                        <?php endif; ?>
                      </div>
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

