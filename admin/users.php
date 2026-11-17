<?php
/**
 * ReUseHub - Admin User Management
 */

$pageTitle = 'Manage Users';
$activePage = 'admin_users';
require_once __DIR__ . '/../includes/header.php';

requireAdmin();
$currentUser = currentUser();
$pdo = getDB();

$search = trim(isset($_GET['q']) ? $_GET['q'] : '');
$roleFilter = trim(isset($_GET['role']) ? $_GET['role'] : 'all');

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    verify_csrf_token();
    $targetId = (int)(isset($_POST['target_user_id']) ? $_POST['target_user_id'] : 0);
    $action = $_POST['action'];

    // Prevent modifying self
    if ($targetId === $currentUser['id'] && in_array($action, array('toggle_status', 'delete_user'))) {
        setFlash('danger', 'You cannot suspend or delete your own admin account.');
    } else {
        if ($action === 'toggle_status') {
            $pdo->prepare("UPDATE users SET is_active = IF(is_active = 1, 0, 1), updated_at = NOW() WHERE id = ?")->execute(array($targetId));
            setFlash('info', 'User status toggled.');
        } elseif ($action === 'change_role') {
            $newRole = in_array((isset($_POST['new_role']) ? $_POST['new_role'] : ''), array('renter', 'owner', 'admin')) ? $_POST['new_role'] : 'renter';
            $pdo->prepare("UPDATE users SET role = ?, updated_at = NOW() WHERE id = ?")->execute(array($newRole, $targetId));
            setFlash('success', 'User role updated.');
        } elseif ($action === 'delete_user') {
            $pdo->prepare("DELETE FROM users WHERE id = ?")->execute(array($targetId));
            setFlash('success', 'User permanently removed from platform.');
        }
    }
    header("Location: " . url('admin/users.php?' . http_build_query($_GET)));
    exit();
}

// Build query
$where = array("1=1");
$params = array();

if ($roleFilter !== 'all' && in_array($roleFilter, array('renter', 'owner', 'admin'))) {
    $where[] = "role = ?";
    $params[] = $roleFilter;
}

if (!empty($search)) {
    $where[] = "(name LIKE ? OR email LIKE ? OR phone LIKE ? OR city LIKE ?)";
    $like = "%{$search}%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

$whereSql = implode(" AND ", $where);
$stmt = $pdo->prepare("SELECT u.*, 
                       (SELECT COUNT(*) FROM products WHERE owner_id = u.id) as listed_count,
                       (SELECT COUNT(*) FROM bookings WHERE renter_id = u.id) as rental_count
                       FROM users u
                       WHERE {$whereSql}
                       ORDER BY u.created_at DESC");
$stmt->execute($params);
$usersList = $stmt->fetchAll();
?>

<div class="py-4" style="background-color: var(--bg-main);">
  <div class="container">
    <div class="dashboard-container">
      <!-- Admin Sidebar -->
      <aside class="dashboard-sidebar rounded-4 shadow-sm me-lg-4 mb-4 mb-lg-0">
        <ul class="sidebar-menu">
          <li class="sidebar-menu-item"><a href="<?php echo  url('admin/dashboard.php'); ?>" class="sidebar-link"><i class="fa-solid fa-gauge-high"></i> Admin Overview</a></li>
          <li class="sidebar-menu-item"><a href="<?php echo  url('admin/products.php'); ?>" class="sidebar-link"><i class="fa-solid fa-boxes-stacked"></i> Manage Products</a></li>
          <li class="sidebar-menu-item"><a href="<?php echo  url('admin/users.php'); ?>" class="sidebar-link active"><i class="fa-solid fa-users"></i> Registered Users</a></li>
          <li class="sidebar-menu-item"><a href="<?php echo  url('admin/bookings.php'); ?>" class="sidebar-link"><i class="fa-solid fa-calendar-days"></i> All Bookings</a></li>
          <li class="sidebar-menu-item"><a href="<?php echo  url('admin/categories.php'); ?>" class="sidebar-link"><i class="fa-solid fa-tags"></i> Categories</a></li>
          <li class="sidebar-menu-item"><a href="<?php echo  url('admin/reports.php'); ?>" class="sidebar-link"><i class="fa-solid fa-flag"></i> Reports</a></li>
          <li class="sidebar-menu-item mt-4 pt-3 border-top"><a href="<?php echo  url('explore.php'); ?>" class="sidebar-link text-primary fw-bold"><i class="fa-solid fa-store"></i> View Live Site</a></li>
        </ul>
      </aside>

      <!-- Main Content -->
      <main class="dashboard-main rounded-4 shadow-sm p-4 bg-white flex-grow-1">
        <div class="d-flex justify-content-between align-items-center mb-4">
          <div>
            <h3 class="fw-bold text-dark mb-1">Registered Platform Users (<?php echo  count($usersList); ?>)</h3>
            <p class="text-muted small mb-0">Manage roles, suspend fraudulent accounts, and view user activities.</p>
          </div>
        </div>

        <!-- Filter -->
        <div class="card border rounded-3 p-3 bg-light mb-4">
          <form action="<?php echo  url('admin/users.php'); ?>" method="GET" class="row g-2 align-items-center">
            <div class="col-md-6">
              <input type="text" name="q" class="form-control form-control-sm" placeholder="Search by name, email, phone, city..." value="<?php echo  e($search); ?>">
            </div>
            <div class="col-md-4">
              <select name="role" class="form-select form-select-sm">
                <option value="all">All Roles</option>
                <option value="renter" <?php echo  $roleFilter === 'renter' ? 'selected' : ''; ?>>Renter</option>
                <option value="owner" <?php echo  $roleFilter === 'owner' ? 'selected' : ''; ?>>Owner</option>
                <option value="admin" <?php echo  $roleFilter === 'admin' ? 'selected' : ''; ?>>Admin</option>
              </select>
            </div>
            <div class="col-md-2">
              <button type="submit" class="btn btn-primary-custom btn-sm w-100">Search</button>
            </div>
          </form>
        </div>

        <div class="table-responsive">
          <table class="custom-table">
            <thead>
              <tr>
                <th>User</th>
                <th>Contact</th>
                <th>Role</th>
                <th>City</th>
                <th>Activity</th>
                <th>Status</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($usersList as $u): ?>
                <tr>
                  <td>
                    <div class="d-flex align-items-center gap-2">
                      <img src="<?php echo  asset('images/default-avatar.png'); ?>" class="rounded-circle" width="36" height="36">
                      <div>
                        <div class="fw-bold text-dark"><?php echo  e($u['name']); ?></div>
                        <small class="text-muted">Joined <?php echo  formatDate($u['created_at']); ?></small>
                      </div>
                    </div>
                  </td>
                  <td>
                    <div class="small fw-semibold"><?php echo  e($u['email']); ?></div>
                    <small class="text-muted"><?php echo  e($u['phone']); ?></small>
                  </td>
                  <td>
                    <form action="<?php echo  url('admin/users.php'); ?>" method="POST" class="d-inline">
                      <?php echo  csrf_field(); ?>
                      <input type="hidden" name="action" value="change_role">
                      <input type="hidden" name="target_user_id" value="<?php echo  $u['id']; ?>">
                      <select name="new_role" class="form-select form-select-sm" style="width: 100px; font-size: 0.8rem;" onchange="this.form.submit()">
                        <option value="renter" <?php echo  $u['role'] === 'renter' ? 'selected' : ''; ?>>Renter</option>
                        <option value="owner" <?php echo  $u['role'] === 'owner' ? 'selected' : ''; ?>>Owner</option>
                        <option value="admin" <?php echo  $u['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                      </select>
                    </form>
                  </td>
                  <td><span class="small"><?php echo  e($u['city']); ?>, KL</span></td>
                  <td>
                    <div class="small text-muted">
                      <span><?php echo  $u['listed_count']; ?> listed</span> &bull; 
                      <span><?php echo  $u['rental_count']; ?> rented</span>
                    </div>
                  </td>
                  <td>
                    <span class="badge <?php echo  $u['is_active'] ? 'bg-success' : 'bg-danger'; ?>">
                      <?php echo  $u['is_active'] ? 'Active' : 'Suspended'; ?>
                    </span>
                  </td>
                  <td>
                    <div class="d-flex gap-1">
                      <?php if ($u['id'] !== $currentUser['id']): ?>
                        <form action="<?php echo  url('admin/users.php'); ?>" method="POST" class="d-inline">
                          <?php echo  csrf_field(); ?>
                          <input type="hidden" name="action" value="toggle_status">
                          <input type="hidden" name="target_user_id" value="<?php echo  $u['id']; ?>">
                          <button type="submit" class="btn btn-sm <?php echo  $u['is_active'] ? 'btn-outline-warning' : 'btn-outline-success'; ?> px-2 py-1" title="<?php echo  $u['is_active'] ? 'Suspend' : 'Activate'; ?>">
                            <i class="fa-solid <?php echo  $u['is_active'] ? 'fa-user-slash' : 'fa-user-check'; ?>"></i>
                          </button>
                        </form>

                        <form action="<?php echo  url('admin/users.php'); ?>" method="POST" class="d-inline" onsubmit="return confirm('Permanently delete user <?php echo  e($u['name']); ?>?')">
                          <?php echo  csrf_field(); ?>
                          <input type="hidden" name="action" value="delete_user">
                          <input type="hidden" name="target_user_id" value="<?php echo  $u['id']; ?>">
                          <button type="submit" class="btn btn-sm btn-danger px-2 py-1" title="Delete">
                            <i class="fa-solid fa-trash"></i>
                          </button>
                        </form>
                      <?php else: ?>
                        <span class="badge bg-light text-muted border">You</span>
                      <?php endif; ?>
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

