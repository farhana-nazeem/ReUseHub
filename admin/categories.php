<?php
/**
 * ReUseHub - Admin Categories Management
 */

$pageTitle = 'Manage Categories';
$activePage = 'admin_categories';
require_once __DIR__ . '/../includes/header.php';

requireAdmin();
$user = currentUser();
$pdo = getDB();

// Handle Actions: Add, Edit, Delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    verify_csrf_token();
    $action = $_POST['action'];

    if ($action === 'add_category') {
        $name = trim(isset($_POST['name']) ? $_POST['name'] : '');
        $icon = trim(isset($_POST['icon']) ? $_POST['icon'] : 'fa-box');
        $desc = trim(isset($_POST['description']) ? $_POST['description'] : '');
        $isFeatured = isset($_POST['is_featured']) ? 1 : 0;
        $slug = slugify($name);

        if (!empty($name)) {
            $stmt = $pdo->prepare("INSERT INTO categories (name, slug, icon, description, is_featured) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute(array($name, $slug, $icon, $desc, $isFeatured));
            setFlash('success', "Category '{$name}' created.");
        }
    } elseif ($action === 'delete_category') {
        $catId = (int)(isset($_POST['category_id']) ? $_POST['category_id'] : 0);
        // Check for attached products
        $hasProd = $pdo->prepare("SELECT COUNT(*) FROM products WHERE category_id = ?");
        $hasProd->execute(array($catId));
        if ($hasProd->fetchColumn() > 0) {
            setFlash('danger', 'Cannot delete this category because products are assigned to it.');
        } else {
            $pdo->prepare("DELETE FROM categories WHERE id = ?")->execute(array($catId));
            setFlash('info', 'Category removed.');
        }
    }
    header("Location: " . url('admin/categories.php'));
    exit();
}

// Fetch categories with product counts
$categories = $pdo->query("SELECT c.*, COUNT(p.id) as product_count 
                           FROM categories c 
                           LEFT JOIN products p ON c.id = p.category_id 
                           GROUP BY c.id 
                           ORDER BY c.id ASC")->fetchAll();
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
          <li class="sidebar-menu-item"><a href="<?php echo  url('admin/categories.php'); ?>" class="sidebar-link active"><i class="fa-solid fa-tags"></i> Categories</a></li>
          <li class="sidebar-menu-item"><a href="<?php echo  url('admin/reports.php'); ?>" class="sidebar-link"><i class="fa-solid fa-flag"></i> Reports</a></li>
          <li class="sidebar-menu-item mt-4 pt-3 border-top"><a href="<?php echo  url('explore.php'); ?>" class="sidebar-link text-primary fw-bold"><i class="fa-solid fa-store"></i> View Live Site</a></li>
        </ul>
      </aside>

      <!-- Main Content -->
      <main class="dashboard-main rounded-4 shadow-sm p-4 bg-white flex-grow-1">
        <div class="d-flex justify-content-between align-items-center mb-4">
          <div>
            <h3 class="fw-bold text-dark mb-1">Marketplace Categories</h3>
            <p class="text-muted small mb-0">Manage rental taxonomy, icon assignments and homepage featured categories.</p>
          </div>
          <button class="btn btn-primary-custom btn-sm" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
            <i class="fa-solid fa-plus me-1"></i> Add Category
          </button>
        </div>

        <div class="table-responsive">
          <table class="custom-table">
            <thead>
              <tr>
                <th>Icon</th>
                <th>Category Name</th>
                <th>Slug</th>
                <th>Products</th>
                <th>Featured</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($categories as $cat): ?>
                <tr>
                  <td>
                    <div class="rounded-2 bg-light text-success d-inline-flex align-items-center justify-content-center" style="width: 36px; height: 36px; font-size: 1.1rem;">
                      <i class="fa-solid <?php echo  e($cat['icon']); ?>"></i>
                    </div>
                  </td>
                  <td>
                    <div class="fw-bold text-dark"><?php echo  e($cat['name']); ?></div>
                    <small class="text-muted text-truncate d-inline-block" style="max-width: 260px;"><?php echo  e($cat['description']); ?></small>
                  </td>
                  <td><code><?php echo  e($cat['slug']); ?></code></td>
                  <td>
                    <span class="badge bg-light text-dark border"><?php echo  $cat['product_count']; ?> products</span>
                  </td>
                  <td>
                    <span class="badge <?php echo  $cat['is_featured'] ? 'bg-success' : 'bg-secondary'; ?>">
                      <?php echo  $cat['is_featured'] ? 'Yes' : 'No'; ?>
                    </span>
                  </td>
                  <td>
                    <form action="<?php echo  url('admin/categories.php'); ?>" method="POST" class="d-inline" onsubmit="return confirm('Remove category <?php echo  e($cat['name']); ?>?')">
                      <?php echo  csrf_field(); ?>
                      <input type="hidden" name="action" value="delete_category">
                      <input type="hidden" name="category_id" value="<?php echo  $cat['id']; ?>">
                      <button type="submit" class="btn btn-sm btn-outline-danger px-2 py-1" <?php echo  $cat['product_count'] > 0 ? 'disabled' : ''; ?> title="Delete">
                        <i class="fa-solid fa-trash"></i>
                      </button>
                    </form>
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

<!-- Add Category Modal -->
<div class="modal fade" id="addCategoryModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content rounded-4 border-0">
      <div class="modal-header">
        <h5 class="modal-title fw-bold text-dark">Add New Category</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form action="<?php echo  url('admin/categories.php'); ?>" method="POST">
        <?php echo  csrf_field(); ?>
        <input type="hidden" name="action" value="add_category">
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label small fw-bold">Category Name</label>
            <input type="text" name="name" class="form-control" placeholder="e.g. Musical Instruments" required>
          </div>
          <div class="mb-3">
            <label class="form-label small fw-bold">Font Awesome Icon Class</label>
            <input type="text" name="icon" class="form-control" value="fa-guitar" required>
          </div>
          <div class="mb-3">
            <label class="form-label small fw-bold">Short Description</label>
            <textarea name="description" class="form-control" rows="2" placeholder="Guitars, keyboards, drums..."></textarea>
          </div>
          <div class="form-check">
            <input class="form-check-input" type="checkbox" name="is_featured" id="featCatCheck" checked>
            <label class="form-check-label small" for="featCatCheck">Feature on Homepage</label>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary-custom">Create Category</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

