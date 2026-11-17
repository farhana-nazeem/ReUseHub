<?php
/**
 * ReUseHub - Owner Listed Products Management
 */

$pageTitle = 'My Listed Products';
$activePage = 'owner_products';
require_once __DIR__ . '/../includes/header.php';

requireOwner();
$user = currentUser();
$pdo = getDB();

$ownerId = $user['id'];

// 1. Handle Toggle Availability
if (isset($_GET['toggle_avail'])) {
    $prodId = (int)$_GET['toggle_avail'];
    $stmt = $pdo->prepare("UPDATE products SET is_available = IF(is_available = 1, 0, 1), updated_at = NOW() WHERE id = ? AND owner_id = ?");
    $stmt->execute(array($prodId, $ownerId));
    setFlash('info', 'Product availability status updated.');
    header("Location: " . url('owner/my-products.php'));
    exit();
}

// 2. Handle Delete Product
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_product') {
    verify_csrf_token();
    $prodId = (int)(isset($_POST['product_id']) ? $_POST['product_id'] : 0);

    // Verify ownership
    $chk = $pdo->prepare("SELECT id, title FROM products WHERE id = ? AND owner_id = ?");
    $chk->execute(array($prodId, $ownerId));
    $prod = $chk->fetch();

    if ($prod) {
        // Check for active bookings
        $activeBk = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE product_id = ? AND status IN ('approved', 'active')");
        $activeBk->execute(array($prodId));
        if ($activeBk->fetchColumn() > 0) {
            setFlash('danger', 'Cannot delete this product because it has active or approved future rentals.');
        } else {
            $del = $pdo->prepare("DELETE FROM products WHERE id = ? AND owner_id = ?");
            $del->execute(array($prodId, $ownerId));
            setFlash('success', "Listing '{$prod['title']}' was deleted.");
        }
    }
    header("Location: " . url('owner/my-products.php'));
    exit();
}

// 3. Fetch all products owned by user
$stmt = $pdo->prepare("SELECT p.*, c.name as category_name,
                       (SELECT image_path FROM product_images WHERE product_id = p.id ORDER BY is_primary DESC, id ASC LIMIT 1) as primary_image,
                       (SELECT COUNT(*) FROM bookings WHERE product_id = p.id) as booking_count
                       FROM products p
                       JOIN categories c ON p.category_id = c.id
                       WHERE p.owner_id = ?
                       ORDER BY p.created_at DESC");
$stmt->execute(array($ownerId));
$products = $stmt->fetchAll();
?>

<div class="py-4" style="background-color: var(--bg-main);">
  <div class="container">
    <div class="dashboard-container">
      <!-- Owner Sidebar -->
      <aside class="dashboard-sidebar rounded-4 shadow-sm me-lg-4 mb-4 mb-lg-0">
        <ul class="sidebar-menu">
          <li class="sidebar-menu-item">
            <a href="<?php echo  url('owner/dashboard.php'); ?>" class="sidebar-link">
              <i class="fa-solid fa-chart-line"></i> Owner Overview
            </a>
          </li>
          <li class="sidebar-menu-item">
            <a href="<?php echo  url('owner/my-products.php'); ?>" class="sidebar-link active">
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
            </a>
          </li>
          <li class="sidebar-menu-item mt-4 pt-3 border-top">
            <a href="<?php echo  url('user/dashboard.php'); ?>" class="sidebar-link text-primary fw-bold">
              <i class="fa-solid fa-user text-primary"></i> Switch to Renter Portal
            </a>
          </li>
        </ul>
      </aside>

      <!-- Main Content -->
      <main class="dashboard-main rounded-4 shadow-sm p-4 bg-white flex-grow-1">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
          <div>
            <h3 class="fw-bold text-dark mb-1">My Listed Products (<?php echo  count($products); ?>)</h3>
            <p class="text-muted small mb-0">Control pricing, toggle immediate availability, edit descriptions, and monitor views.</p>
          </div>
          <a href="<?php echo  url('list-item.php'); ?>" class="btn btn-primary-custom btn-sm">
            <i class="fa-solid fa-plus me-1"></i> Add New Item
          </a>
        </div>

        <?php if (empty($products)): ?>
          <div class="card border-0 shadow-sm rounded-4 p-5 text-center bg-light-subtle my-3">
            <i class="fa-solid fa-box-open text-muted fs-1 mb-3"></i>
            <h5 class="fw-bold text-dark">You Have No Products Listed</h5>
            <p class="text-muted small mb-3">List your DSLR camera, camping tent, gaming console, party wear, or tools to start earning passive income.</p>
            <div>
              <a href="<?php echo  url('list-item.php'); ?>" class="btn btn-primary-custom px-4">List Your First Product</a>
            </div>
          </div>
        <?php else: ?>
          <div class="table-responsive">
            <table class="custom-table">
              <thead>
                <tr>
                  <th>Product</th>
                  <th>Category</th>
                  <th>Daily Rate</th>
                  <th>Deposit</th>
                  <th>Status</th>
                  <th>Available</th>
                  <th>Views</th>
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
                        <img src="<?php echo  e($pImg); ?>" class="rounded-2" width="48" height="40" style="object-fit: cover;">
                        <div>
                          <a href="<?php echo  url('product-details.php?id=' . $p['id']); ?>" class="fw-bold text-dark text-decoration-none text-truncate d-inline-block" style="max-width: 180px;">
                            <?php echo  e($p['title']); ?>
                          </a>
                          <small class="text-muted d-block"><?php echo  e($p['condition_grade']); ?></small>
                        </div>
                      </div>
                    </td>
                    <td>
                      <span class="badge bg-light text-dark border"><?php echo  e($p['category_name']); ?></span>
                    </td>
                    <td>
                      <span class="fw-bold text-primary"><?php echo  formatPrice($p['daily_price']); ?></span>
                    </td>
                    <td>
                      <span class="text-muted small"><?php echo  formatPrice($p['security_deposit']); ?></span>
                    </td>
                    <td>
                      <span class="badge-status <?php echo  e($p['status']); ?>"><?php echo  e($p['status']); ?></span>
                    </td>
                    <td>
                      <a href="<?php echo  url('owner/my-products.php?toggle_avail=' . $p['id']); ?>" class="badge <?php echo  $p['is_available'] ? 'bg-success' : 'bg-secondary'; ?> text-decoration-none" title="Click to toggle availability">
                        <?php echo  $p['is_available'] ? '<i class="fa-solid fa-check"></i> Live' : '<i class="fa-solid fa-ban"></i> Hidden'; ?>
                      </a>
                    </td>
                    <td>
                      <span class="small text-muted"><i class="fa-solid fa-eye me-1"></i> <?php echo  $p['views_count']; ?></span>
                    </td>
                    <td>
                      <div class="d-flex gap-1">
                        <a href="<?php echo  url('owner/edit-product.php?id=' . $p['id']); ?>" class="btn btn-sm btn-outline-primary" title="Edit Listing">
                          <i class="fa-solid fa-pen"></i>
                        </a>
                        <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteProdModal<?php echo  $p['id']; ?>" title="Delete Listing">
                          <i class="fa-solid fa-trash"></i>
                        </button>
                      </div>

                      <!-- Delete Modal -->
                      <div class="modal fade" id="deleteProdModal<?php echo  $p['id']; ?>" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog">
                          <div class="modal-content rounded-4 border-0">
                            <div class="modal-header">
                              <h5 class="modal-title fw-bold text-dark">Delete Listing</h5>
                              <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <form action="<?php echo  url('owner/my-products.php'); ?>" method="POST">
                              <?php echo  csrf_field(); ?>
                              <input type="hidden" name="action" value="delete_product">
                              <input type="hidden" name="product_id" value="<?php echo  $p['id']; ?>">
                              <div class="modal-body text-start">
                                <p class="text-muted small mb-0">Are you sure you want to permanently delete <strong><?php echo  e($p['title']); ?></strong>? This action cannot be undone.</p>
                              </div>
                              <div class="modal-footer">
                                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-danger">Confirm Delete</button>
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
      </main>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

