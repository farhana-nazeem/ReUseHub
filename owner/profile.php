<?php
/**
 * ReUseHub - User Profile Management
 */

$pageTitle = 'Profile & Account Settings';
$activePage = 'profile';
require_once __DIR__ . '/../includes/header.php';

requireLogin();
$user = currentUser();
$pdo = getDB();
$errors = array();
$successMsg = '';

// Handle Profile Updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_profile') {
    verify_csrf_token();

    $name = trim(isset($_POST['name']) ? $_POST['name'] : '');
    $phone = trim(isset($_POST['phone']) ? $_POST['phone'] : '');
    $city = trim(isset($_POST['city']) ? $_POST['city'] : 'Kochi');
    $pincode = trim(isset($_POST['pincode']) ? $_POST['pincode'] : '');
    $address = trim(isset($_POST['address']) ? $_POST['address'] : '');
    $bio = trim(isset($_POST['bio']) ? $_POST['bio'] : '');

    if (empty($name)) {
        $errors[] = 'Full name cannot be empty.';
    }
    if (empty($phone)) {
        $errors[] = 'Phone number is required.';
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("UPDATE users SET name = ?, phone = ?, city = ?, pincode = ?, address = ?, bio = ?, updated_at = NOW() WHERE id = ?");
        if ($stmt->execute(array($name, $phone, $city, $pincode, $address, $bio, $user['id']))) {
            $_SESSION['user_name'] = $name;
            setFlash('success', 'Your profile details have been updated successfully.');
            header("Location: " . url('user/profile.php'));
            exit();
        } else {
            $errors[] = 'Failed to update profile. Please try again.';
        }
    }
}

// Handle Password Change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'change_password') {
    verify_csrf_token();

    $currentPass = (isset($_POST['current_password']) ? $_POST['current_password'] : '');
    $newPass = (isset($_POST['new_password']) ? $_POST['new_password'] : '');
    $confirmPass = (isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '');

    if (!password_verify($currentPass, $user['password'])) {
        $errors[] = 'Your current password is incorrect.';
    } elseif (strlen($newPass) < 6) {
        $errors[] = 'New password must be at least 6 characters.';
    } elseif ($newPass !== $confirmPass) {
        $errors[] = 'New password and confirmation do not match.';
    } else {
        $hashed = password_hash($newPass, PASSWORD_BCRYPT);
        $stmt = $pdo->prepare("UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?");
        if ($stmt->execute(array($hashed, $user['id']))) {
            setFlash('success', 'Your password has been changed securely.');
            header("Location: " . url('user/profile.php'));
            exit();
        }
    }
}
?>

<div class="py-5" style="background-color: var(--bg-main);">
  <div class="container py-2">
    <div class="row g-4">
      <!-- Profile Card Summary -->
      <div class="col-lg-4">
        <div class="card border-0 shadow-sm rounded-4 p-4 text-center bg-white mb-4">
          <div class="position-relative mx-auto mb-3" style="width: 90px; height: 90px;">
            <img src="<?php echo  asset('images/default-avatar.png'); ?>" alt="Avatar" class="rounded-circle border border-3 border-success shadow-sm" style="width: 100%; height: 100%; object-fit: cover;">
          </div>
          <h5 class="fw-bold text-dark mb-1"><?php echo  e($user['name']); ?></h5>
          <p class="text-muted small mb-2"><?php echo  e($user['email']); ?></p>
          <div class="mb-3">
            <span class="badge bg-success-subtle text-success text-capitalize px-3 py-1"><?php echo  e($user['role']); ?> Member</span>
          </div>
          <p class="text-muted small border-top pt-3 mb-0">
            <?php echo  e($user['bio'] ?: 'No bio added yet. Add a short bio to build trust with owners and renters.'); ?>
          </p>
        </div>

        <!-- Navigation Links -->
        <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
          <ul class="sidebar-menu">
            <li class="sidebar-menu-item">
              <a href="<?php echo  url('user/dashboard.php'); ?>" class="sidebar-link">
                <i class="fa-solid fa-gauge"></i> Renter Dashboard
              </a>
            </li>
            <li class="sidebar-menu-item">
              <a href="<?php echo  url('user/my-bookings.php'); ?>" class="sidebar-link">
                <i class="fa-solid fa-calendar-check"></i> My Bookings
              </a>
            </li>
            <li class="sidebar-menu-item">
              <a href="<?php echo  url('wishlist.php'); ?>" class="sidebar-link">
                <i class="fa-solid fa-heart"></i> My Wishlist
              </a>
            </li>
            <li class="sidebar-menu-item">
              <a href="<?php echo  url('owner/dashboard.php'); ?>" class="sidebar-link">
                <i class="fa-solid fa-store"></i> Owner Dashboard
              </a>
            </li>
            <li class="sidebar-menu-item">
              <a href="<?php echo  url('user/profile.php'); ?>" class="sidebar-link active">
                <i class="fa-solid fa-user-gear"></i> Profile & Settings
              </a>
            </li>
          </ul>
        </div>
      </div>

      <!-- Profile Form and Password -->
      <div class="col-lg-8">
        <?php if (!empty($errors)): ?>
          <div class="alert alert-danger shadow-sm rounded-3 mb-4">
            <ul class="mb-0 ps-3 small">
              <?php foreach ($errors as $err): ?>
                <li><?php echo  e($err); ?></li>
              <?php endforeach; ?>
            </ul>
          </div>
        <?php endif; ?>

        <!-- Basic Info Card -->
        <div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-4">
          <h4 class="fw-bold text-dark mb-4"><i class="fa-solid fa-user-pen text-success me-2"></i> Account Details</h4>

          <form action="<?php echo  url('user/profile.php'); ?>" method="POST">
            <?php echo  csrf_field(); ?>
            <input type="hidden" name="action" value="update_profile">

            <div class="row g-3 mb-3">
              <div class="col-md-6">
                <label class="form-label fw-semibold small text-dark">Full Name</label>
                <input type="text" name="name" class="form-control" value="<?php echo  e($user['name']); ?>" required>
              </div>
              <div class="col-md-6">
                <label class="form-label fw-semibold small text-dark">Email Address</label>
                <input type="email" class="form-control bg-light" value="<?php echo  e($user['email']); ?>" disabled>
                <small class="text-muted" style="font-size: 0.72rem;">Email cannot be modified directly.</small>
              </div>
            </div>

            <div class="row g-3 mb-3">
              <div class="col-md-6">
                <label class="form-label fw-semibold small text-dark">Phone Number</label>
                <input type="tel" name="phone" class="form-control" value="<?php echo  e($user['phone']); ?>" required>
              </div>
              <div class="col-md-6">
                <label class="form-label fw-semibold small text-dark">City (Kerala)</label>
                <select name="city" class="form-select">
                  <option value="Kochi" <?php echo  $user['city'] === 'Kochi' ? 'selected' : ''; ?>>Kochi</option>
                  <option value="Trivandrum" <?php echo  $user['city'] === 'Trivandrum' ? 'selected' : ''; ?>>Trivandrum</option>
                  <option value="Kozhikode" <?php echo  $user['city'] === 'Kozhikode' ? 'selected' : ''; ?>>Kozhikode</option>
                  <option value="Thrissur" <?php echo  $user['city'] === 'Thrissur' ? 'selected' : ''; ?>>Thrissur</option>
                  <option value="Kottayam" <?php echo  $user['city'] === 'Kottayam' ? 'selected' : ''; ?>>Kottayam</option>
                  <option value="Kannur" <?php echo  $user['city'] === 'Kannur' ? 'selected' : ''; ?>>Kannur</option>
                </select>
              </div>
            </div>

            <div class="row g-3 mb-3">
              <div class="col-md-8">
                <label class="form-label fw-semibold small text-dark">Address / Locality</label>
                <input type="text" name="address" class="form-control" placeholder="House/Flat name, Street, Landmark" value="<?php echo  e(isset($user['address']) ? $user['address'] : ''); ?>">
              </div>
              <div class="col-md-4">
                <label class="form-label fw-semibold small text-dark">PIN Code</label>
                <input type="text" name="pincode" class="form-control" placeholder="682001" value="<?php echo  e(isset($user['pincode']) ? $user['pincode'] : ''); ?>">
              </div>
            </div>

            <div class="mb-4">
              <label class="form-label fw-semibold small text-dark">Short Bio / Introduction</label>
              <textarea name="bio" class="form-control" rows="3" placeholder="Tell other members what you do, what hobbies you have, or what items you usually look for."><?php echo  e(isset($user['bio']) ? $user['bio'] : ''); ?></textarea>
            </div>

            <button type="submit" class="btn btn-primary-custom px-4 py-2">
              <i class="fa-solid fa-floppy-disk me-1"></i> Save Changes
            </button>
          </form>
        </div>

        <!-- Change Password Card -->
        <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
          <h4 class="fw-bold text-dark mb-4"><i class="fa-solid fa-lock text-success me-2"></i> Change Password</h4>

          <form action="<?php echo  url('user/profile.php'); ?>" method="POST">
            <?php echo  csrf_field(); ?>
            <input type="hidden" name="action" value="change_password">

            <div class="mb-3">
              <label class="form-label fw-semibold small text-dark">Current Password</label>
              <input type="password" name="current_password" class="form-control" placeholder="Enter your current password" required>
            </div>

            <div class="row g-3 mb-4">
              <div class="col-md-6">
                <label class="form-label fw-semibold small text-dark">New Password</label>
                <input type="password" name="new_password" class="form-control" placeholder="Min. 6 characters" required>
              </div>
              <div class="col-md-6">
                <label class="form-label fw-semibold small text-dark">Confirm New Password</label>
                <input type="password" name="confirm_password" class="form-control" placeholder="Re-type new password" required>
              </div>
            </div>

            <button type="submit" class="btn btn-outline-custom px-4 py-2">
              <i class="fa-solid fa-key me-1"></i> Update Password
            </button>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

