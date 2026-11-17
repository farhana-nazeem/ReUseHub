<?php
/**
 * ReUseHub - User Registration
 */

$pageTitle = 'Create an Account';
$activePage = 'register';
require_once __DIR__ . '/includes/header.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header("Location: " . url());
    exit();
}

$errors = array();
$name = '';
$email = '';
$phone = '';
$city = 'Kochi';
$role = 'renter';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_token();

    $name = trim(isset($_POST['name']) ? $_POST['name'] : '');
    $email = trim(isset($_POST['email']) ? $_POST['email'] : '');
    $phone = trim(isset($_POST['phone']) ? $_POST['phone'] : '');
    $city = trim(isset($_POST['city']) ? $_POST['city'] : 'Kochi');
    $rolePost = isset($_POST['role']) ? $_POST['role'] : '';
    $role = in_array($rolePost, array('renter', 'owner')) ? $rolePost : 'renter';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $confirmPassword = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';

    // Validation
    if (empty($name)) {
        $errors[] = 'Full name is required.';
    }
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'A valid email address is required.';
    }
    if (empty($phone)) {
        $errors[] = 'Phone number is required for rental verification.';
    }
    if (strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters long.';
    }
    if ($password !== $confirmPassword) {
        $errors[] = 'Passwords do not match.';
    }

    // Check if email already exists
    if (empty($errors)) {
        $pdo = getDB();
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute(array($email));
        if ($stmt->fetch()) {
            $errors[] = 'An account with this email address already exists. Please log in.';
        }
    }

    // Register user
    if (empty($errors)) {
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $pdo->prepare("INSERT INTO users (name, email, phone, password, role, city, state) VALUES (?, ?, ?, ?, ?, ?, 'Kerala')");
        if ($stmt->execute(array($name, $email, $phone, $hashedPassword, $role, $city))) {
            $userId = $pdo->lastInsertId();

            // Send welcome notification
            createNotification(
                $userId,
                'Welcome to ReUseHub!',
                'Your account has been created. Start exploring items or list what you own to start earning.',
                'explore.php'
            );

            // Auto log in new user
            $userStmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
            $userStmt->execute(array($userId));
            $newUser = $userStmt->fetch();
            loginUser($newUser);

            setFlash('success', 'Welcome to ReUseHub, ' . $name . '! Your account is active.');
            
            if ($role === 'owner') {
                header("Location: " . url('owner/dashboard.php'));
            } else {
                header("Location: " . url('explore.php'));
            }
            exit();
        } else {
            $errors[] = 'Failed to create your account. Please try again.';
        }
    }
}
?>

<div class="py-5" style="background-color: var(--bg-main);">
  <div class="container py-3">
    <div class="row justify-content-center">
      <div class="col-lg-6 col-md-8">
        <div class="card border-0 shadow-lg rounded-4 overflow-hidden">
          <div class="card-body p-4 p-md-5">
            <div class="text-center mb-4">
              <div class="brand-icon mx-auto mb-3" style="width: 48px; height: 48px; border-radius: 12px; background: linear-gradient(135deg, var(--primary), var(--secondary)); color: #fff; display: flex; align-items: center; justify-content: center; font-size: 1.4rem;">
                <i class="fa-solid fa-arrows-rotate"></i>
              </div>
              <h3 class="fw-bold text-dark">Join ReUseHub</h3>
              <p class="text-muted small">Rent quality products, save money, and earn passive income.</p>
            </div>

            <?php if (!empty($errors)): ?>
              <div class="alert alert-danger shadow-sm rounded-3">
                <ul class="mb-0 ps-3 small">
                  <?php foreach ($errors as $err): ?>
                    <li><?php echo e($err); ?></li>
                  <?php endforeach; ?>
                </ul>
              </div>
            <?php endif; ?>

            <form action="<?php echo url('register.php'); ?>" method="POST" class="needs-validation">
              <?php echo csrf_field(); ?>

              <div class="mb-3">
                <label class="form-label fw-semibold small text-dark">Full Name</label>
                <div class="input-group">
                  <span class="input-group-text bg-light text-muted"><i class="fa-solid fa-user"></i></span>
                  <input type="text" name="name" class="form-control" placeholder="e.g. Rahul Menon" value="<?php echo e($name); ?>" required>
                </div>
              </div>

              <div class="row g-3 mb-3">
                <div class="col-md-6">
                  <label class="form-label fw-semibold small text-dark">Email Address</label>
                  <div class="input-group">
                    <span class="input-group-text bg-light text-muted"><i class="fa-solid fa-envelope"></i></span>
                    <input type="email" name="email" class="form-control" placeholder="name@example.com" value="<?php echo e($email); ?>" required>
                  </div>
                </div>
                <div class="col-md-6">
                  <label class="form-label fw-semibold small text-dark">Phone Number</label>
                  <div class="input-group">
                    <span class="input-group-text bg-light text-muted"><i class="fa-solid fa-phone"></i></span>
                    <input type="tel" name="phone" class="form-control" placeholder="+91 98470 12345" value="<?php echo e($phone); ?>" required>
                  </div>
                </div>
              </div>

              <div class="row g-3 mb-3">
                <div class="col-md-6">
                  <label class="form-label fw-semibold small text-dark">Primary City (Kerala)</label>
                  <select name="city" class="form-select">
                    <option value="Kochi" <?php echo $city === 'Kochi' ? 'selected' : ''; ?>>Kochi (Ernakulam)</option>
                    <option value="Trivandrum" <?php echo $city === 'Trivandrum' ? 'selected' : ''; ?>>Trivandrum</option>
                    <option value="Kozhikode" <?php echo $city === 'Kozhikode' ? 'selected' : ''; ?>>Kozhikode</option>
                    <option value="Thrissur" <?php echo $city === 'Thrissur' ? 'selected' : ''; ?>>Thrissur</option>
                    <option value="Kottayam" <?php echo $city === 'Kottayam' ? 'selected' : ''; ?>>Kottayam</option>
                    <option value="Kannur" <?php echo $city === 'Kannur' ? 'selected' : ''; ?>>Kannur</option>
                    <option value="Alappuzha" <?php echo $city === 'Alappuzha' ? 'selected' : ''; ?>>Alappuzha</option>
                  </select>
                </div>

                <div class="col-md-6">
                  <label class="form-label fw-semibold small text-dark">Primary Intent</label>
                  <select name="role" class="form-select">
                    <option value="renter" <?php echo $role === 'renter' ? 'selected' : ''; ?>>I want to Rent Items</option>
                    <option value="owner" <?php echo $role === 'owner' ? 'selected' : ''; ?>>I want to List & Rent Out Items</option>
                  </select>
                </div>
              </div>

              <div class="row g-3 mb-4">
                <div class="col-md-6">
                  <label class="form-label fw-semibold small text-dark">Password</label>
                  <div class="input-group">
                    <span class="input-group-text bg-light text-muted"><i class="fa-solid fa-lock"></i></span>
                    <input type="password" name="password" class="form-control" placeholder="Min. 6 characters" required>
                  </div>
                </div>
                <div class="col-md-6">
                  <label class="form-label fw-semibold small text-dark">Confirm Password</label>
                  <div class="input-group">
                    <span class="input-group-text bg-light text-muted"><i class="fa-solid fa-lock"></i></span>
                    <input type="password" name="confirm_password" class="form-control" placeholder="Re-enter password" required>
                  </div>
                </div>
              </div>

              <div class="form-check mb-4">
                <input class="form-check-input" type="checkbox" id="termsCheck" required checked>
                <label class="form-check-label small text-muted" for="termsCheck">
                  I agree to ReUseHub's <a href="<?php echo url('contact.php'); ?>">Terms of Service</a> and <a href="<?php echo url('contact.php'); ?>">Rental Protection Policy</a>.
                </label>
              </div>

              <button type="submit" class="btn btn-primary-custom w-100 py-3 mb-3">
                <i class="fa-solid fa-user-plus me-1"></i> Create My Account
              </button>

              <div class="text-center">
                <span class="text-muted small">Already have an account? </span>
                <a href="<?php echo url('login.php'); ?>" class="fw-bold text-success text-decoration-none small">Log In here</a>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
