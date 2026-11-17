<?php
/**
 * ReUseHub - Admin Products Management
 */

$pageTitle = 'Manage Products';
$activePage = 'admin_products';
require_once __DIR__ . '/../includes/header.php';

requireAdmin();
$user = currentUser();
$pdo = getDB();

$statusFilter = trim(isset($_GET['status']) ? $_GET['status'] : 'all');
$catFilter = (int)(isset($_GET['category']) ? $_GET['category'] : 0);
$search = trim(isset($_GET['q']) ? $_GET['q'] : '');

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    verify_csrf_token();
    $prodId = (int)(isset($_POST['product_id']) ? $_POST['product_id'] : 0);
    $action = $_POST['action'];

    $pInfo = $pdo->prepare("SELECT title, owner_id FROM products WHERE id = ?");
    $pInfo->execute(array($prodId));
    $prod = $pInfo->fetch();

    if ($prod) {
        if ($action === 'approve') {
            $pdo->prepare("UPDATE products SET status = 'approved', updated_at = NOW() WHERE id = ?")->execute(array($prodId));
            createNotification($prod['owner_id'], 'Listing Approved', "Your product '{$prod['title']}' has been approved and is now live.", 'product-details.php?id=' . $prodId);
            setFlash('success', "Product '{$prod['title']}' approved.");
        } elseif ($action === 'reject') {
            $reason = trim(isset($_POST['reject_reason']) ? $_POST['reject_reason'] : 'Listing policy violation');
            $pdo->prepare("UPDATE products SET status = 'rejected', updated_at = NOW() WHERE id = ?")->execute(array($prodId));
            createNotification($prod['owner_id'], 'Listing Rejected', "Your product '{$prod['title']}' was rejected. Reason: {$reason}", 'owner/my-products.php');
            setFlash('info', "Product '{$prod['title']}' rejected.");
        } elseif ($action === 'toggle_featured') {
            $pdo->prepare("UPDATE products SET featured = IF(featured = 1, 0, 1), updated_at = NOW() WHERE id = ?")->execute(array($prodId));
            setFlash('success', "Featured status updated for '{$prod['title']}'.");
        } elseif ($action === 'delete') {
            $pdo->prepare("DELETE FROM products WHERE id = ?")->execute(array($prodId));
            setFlash('success', "Product '{$prod['title']}' deleted from marketplace.");
        }
    }
    header("Location: " . url('admin/products.php?' . http_build_query($_GET)));
    exit();
}

// Build Filter Query
$where = array("1=1");
$params = array();

if ($statusFilter !== 'all' && in_array($statusFilter, array('pending', 'approved', 'rejected', 'inactive'))) {
    $where[] = "p.status = ?";
    $params[] = $statusFilter;
}

if ($catFilter > 0) {
    $where[] = "p.category_id = ?";
    $params[] = $catFilter;
}

if (!empty($search)) {
    $where[] = "(p.title LIKE ? OR u.name LIKE ? OR p.location LIKE ?)";
    $like = "%{$search}%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

$whereSql = implode(" AND ", $where);

$stmt = $pdo->prepare("SELECT p.*, c.name as category_name, u.name as owner_name, u.email as owner_email,
                       (SELECT image_path FROM product_images WHERE product_id = p.id ORDER BY is_primary DESC, id ASC LIMIT 1) as primary_image
                       FROM products p
                       JOIN categories c ON p.category_id = c.id
                       JOIN users u ON p.owner_id = u.id
                       WHERE {$whereSql}
                       ORDER BY p.created_at DESC");
$stmt->execute($params);
$products = $stmt->fetchAll();

$categories = $pdo->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll();
?>

<div class="py-4" style="background-color: var(--bg-main);">
  <div class="container">
    <div class="dashboard-container">
      <!-- Admin Sidebar -->
      <aside class="dashboard-sidebar rounded-4 shadow-sm me-lg-4 mb-4 mb-lg-0">
        <ul class="sidebar-menu">
          <li class="sidebar-menu-item"><a href="<?php echo  url('admin/dashboard.php'); ?>" class="sidebar-link"><i class="fa-solid fa-gauge-high"></i> Admin Overview</a></li>
          <li class="sidebar-menu-item"><a href="<?php echo  url('admin/products.php'); ?>" class="sidebar-link active"><i class="fa-solid fa-boxes-stacked"></i> Manage Products</a></li>
          <li class="sidebar-menu-item"><a href="<?php echo  url('admin/users.php'); ?>" class="sidebar-link"><i class="fa-solid fa-users"></i> Registered Users</a></li>
          <li class="sidebar-menu-item"><a href="<?php echo  url('admin/bookings.php'); ?>" class="sidebar-link"><i class="fa-solid fa-calendar-days"></i> All Bookings</a></li>
          <li class="sidebar-menu-item"><a href="<?php echo  url('admin/categories.php'); ?>" class="sidebar-link"><i class="fa-solid fa-tags"></i> Categories</a></li>
          <li class="sidebar-menu-item"><a href="<?php echo  url('admin/reports.php'); ?>" class="sidebar-link"><i class="fa-solid fa-flag"></i> Reports</a></li>
          <li class="sidebar-menu-item mt-4 pt-3 border-top"><a href="<?php echo  url('explore.php'); ?>" class="sidebar-link text-primary fw-bold"><i class="fa-solid fa-store"></i> View Live Site</a></li>
        </ul>
      </aside>

      <!-- Main Content -->
      <main class="dashboard-main rounded-4 shadow-sm p-4 bg-white flex-grow-1">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
          <div>
            <h3 class="fw-bold text-dark mb-1">Manage Products (<?php echo  count($products); ?>)</h3>
            <p class="text-muted small mb-0">Review new submissions, verify photos & descriptions, manage featured items.</p>
          </div>
        </div>

        <!-- Filter Bar -->
        <div class="card border rounded-3 p-3 bg-light mb-4">
          <form action="<?php echo  url('admin/products.php'); ?>" method="GET" class="row g-2 align-items-center">
            <div class="col-md-4">
              <input type="text" name="q" class="form-control form-control-sm" placeholder="Search title, owner..." value="<?php echo  e($search); ?>">
            </div>
            <div class="col-md-3">
              <select name="status" class="form-select form-select-sm">
                <option value="all">All Statuses</option>
                <option value="pending" <?php echo  $statusFilter === 'pending' ? 'selected' : ''; ?>>Pending Approval</option>
                <option value="approved" <?php echo  $statusFilter === 'approved' ? 'selected' : ''; ?>>Approved</option>
                <option value="rejected" <?php echo  $statusFilter === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
              </select>
            </div>
            <div class="col-md-3">
              <select name="category" class="form-select form-select-sm">
                <option value="0">All Categories</option>
                <?php foreach ($categories as $c): ?>
                  <option value="<?php echo  $c['id']; ?>" <?php echo  $catFilter == $c['id'] ? 'selected' : ''; ?>><?php echo  e($c['name']); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-2">
              <button type="submit" class="btn btn-primary-custom btn-sm w-100">Filter</button>
            </div>
          </form>
        </div>

        <!-- Table -->
        <div class="table-responsive">
          <table class="custom-table">
            <thead>
              <tr>
                <th>Product</th>
                <th>Category</th>
                <th>Owner</th>
                <th>Pricing</th>
                <th>Status</th>
                <th>Featured</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($products as $p): ?>
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
                          <?php echo  e($p['title']); ?>
                        </a>
                        <small class="text-muted d-block"><?php echo  e($p['condition_grade']); ?> &bull; <?php echo  e($p['location']); ?></small>
                      </div>
                    </div>
                  </td>
                  <td><span class="badge bg-light text-dark border"><?php echo  e($p['category_name']); ?></span></td>
                  <td>
                    <div class="small fw-semibold"><?php echo  e($p['owner_name']); ?></div>
                    <small class="text-muted"><?php echo  e($p['owner_email']); ?></small>
                  </td>
                  <td>
                    <div class="fw-bold text-primary"><?php echo  formatPrice($p['daily_price']); ?>/day</div>
                    <small class="text-muted">Dep: <?php echo  formatPrice($p['security_deposit']); ?></small>
                  </td>
                  <td>
                    <span class="badge-status <?php echo  e($p['status']); ?>"><?php echo  e($p['status']); ?></span>
                  </td>
                  <td>
                    <form action="<?php echo  url('admin/products.php'); ?>" method="POST" class="d-inline">
                      <?php echo  csrf_field(); ?>
                      <input type="hidden" name="action" value="toggle_featured">
                      <input type="hidden" name="product_id" value="<?php echo  $p['id']; ?>">
                      <button type="submit" class="btn btn-sm <?php echo  $p['featured'] ? 'btn-warning text-dark' : 'btn-light border'; ?>" title="Toggle Featured on Homepage">
                        <i class="fa-solid fa-star"></i> <?php echo  $p['featured'] ? 'Featured' : 'Standard'; ?>
                      </button>
                    </form>
                  </td>
                  <td>
                    <div class="d-flex gap-1">
                      <?php if ($p['status'] !== 'approved'): ?>
                        <form action="<?php echo  url('admin/products.php'); ?>" method="POST" class="d-inline">
                          <?php echo  csrf_field(); ?>
                          <input type="hidden" name="action" value="approve">
                          <input type="hidden" name="product_id" value="<?php echo  $p['id']; ?>">
                          <button type="submit" class="btn btn-sm btn-success px-2 py-1" title="Approve">
                            <i class="fa-solid fa-check"></i>
                          </button>
                        </form>
                      <?php endif; ?>

                      <?php if ($p['status'] !== 'rejected'): ?>
                        <form action="<?php echo  url('admin/products.php'); ?>" method="POST" class="d-inline">
                          <?php echo  csrf_field(); ?>
                          <input type="hidden" name="action" value="reject">
                          <input type="hidden" name="product_id" value="<?php echo  $p['id']; ?>">
                          <button type="submit" class="btn btn-sm btn-outline-danger px-2 py-1" title="Reject">
                            <i class="fa-solid fa-xmark"></i>
                          </button>
                        </form>
                      <?php endif; ?>

                      <form action="<?php echo  url('admin/products.php'); ?>" method="POST" class="d-inline" onsubmit="return confirm('Delete this listing permanently?')">
                        <?php echo  csrf_field(); ?>
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="product_id" value="<?php echo  $p['id']; ?>">
                        <button type="submit" class="btn btn-sm btn-danger px-2 py-1" title="Delete">
                          <i class="fa-solid fa-trash"></i>
                        </button>
                      </form>
                    </div>
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

