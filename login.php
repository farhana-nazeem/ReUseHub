<?php
/**
 * ReUseHub - User Login
 */

$pageTitle = 'Sign In';
$activePage = 'login';
require_once __DIR__ . '/includes/header.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header("Location: " . url());
    exit();
}

$next = isset($_GET['next']) ? $_GET['next'] : '';
$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_token();

    $email = trim(isset($_POST['email']) ? $_POST['email'] : '');
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $next = isset($_POST['next']) ? $_POST['next'] : '';

    if (empty($email) || empty($password)) {
        $error = 'Please enter both your email address and password.';
    } else {
        $pdo = getDB();
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute(array($email));
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            if (!$user['is_active']) {
                $error = 'Your account has been suspended. Please contact platform support.';
            } else {
                loginUser($user);
                setFlash('success', 'Welcome back, ' . $user['name'] . '!');

                if (!empty($next) && !str_starts_with($next, 'http')) {
                    header("Location: " . url($next));
                } else {
                    if ($user['role'] === 'admin') {
                        header("Location: " . url('admin/dashboard.php'));
                    } elseif ($user['role'] === 'owner') {
                        header("Location: " . url('owner/dashboard.php'));
                    } else {
                        header("Location: " . url('user/dashboard.php'));
                    }
                }
                exit();
            }
        } else {
            $error = 'Invalid email or password. Please try again.';
        }
    }
}
?>

<div class="py-5" style="background-color: var(--bg-main);">
  <div class="container py-3">
    <div class="row justify-content-center">
      <div class="col-lg-5 col-md-7">
        <div class="card border-0 shadow-lg rounded-4 overflow-hidden mb-4">
          <div class="card-body p-4 p-md-5">
            <div class="text-center mb-4">
              <div class="brand-icon mx-auto mb-3" style="width: 48px; height: 48px; border-radius: 12px; background: linear-gradient(135deg, var(--primary), var(--secondary)); color: #fff; display: flex; align-items: center; justify-content: center; font-size: 1.4rem;">
                <i class="fa-solid fa-arrows-rotate"></i>
              </div>
              <h3 class="fw-bold text-dark">Welcome Back</h3>
              <p class="text-muted small">Sign in to manage your rentals, listings and bookings.</p>
            </div>

            <?php if (!empty($error)): ?>
              <div class="alert alert-danger shadow-sm rounded-3 py-2 small mb-4">
                <i class="fa-solid fa-triangle-exclamation me-1"></i> <?php echo e($error); ?>
              </div>
            <?php endif; ?>

            <form action="<?php echo url('login.php'); ?>" method="POST">
              <?php echo csrf_field(); ?>
              <input type="hidden" name="next" value="<?php echo e($next); ?>">

              <div class="mb-3">
                <label class="form-label fw-semibold small text-dark">Email Address</label>
                <div class="input-group">
                  <span class="input-group-text bg-light text-muted"><i class="fa-solid fa-envelope"></i></span>
                  <input type="email" name="email" id="login-email" class="form-control" placeholder="name@example.com" value="<?php echo e($email); ?>" required>
                </div>
              </div>

              <div class="mb-3">
                <div class="d-flex justify-content-between">
                  <label class="form-label fw-semibold small text-dark">Password</label>
                  <a href="<?php echo url('contact.php'); ?>" class="text-muted small text-decoration-none">Forgot?</a>
                </div>
                <div class="input-group">
                  <span class="input-group-text bg-light text-muted"><i class="fa-solid fa-lock"></i></span>
                  <input type="password" name="password" id="login-password" class="form-control" placeholder="Your password" required>
                </div>
              </div>

              <div class="form-check mb-4">
                <input class="form-check-input" type="checkbox" id="rememberMe" checked>
                <label class="form-check-label small text-muted" for="rememberMe">
                  Remember me on this device
                </label>
              </div>

              <button type="submit" class="btn btn-primary-custom w-100 py-3 mb-3">
                <i class="fa-solid fa-arrow-right-to-bracket me-1"></i> Sign In to ReUseHub
              </button>

              <div class="text-center">
                <span class="text-muted small">Don't have an account? </span>
                <a href="<?php echo url('register.php'); ?>" class="fw-bold text-success text-decoration-none small">Register now</a>
              </div>
            </form>
          </div>
        </div>

        <!-- 1-Click Demo Login Helper -->
        <div class="card border rounded-3 p-3 bg-white shadow-sm">
          <div class="d-flex align-items-center gap-2 mb-2">
            <i class="fa-solid fa-bolt text-warning"></i>
            <span class="fw-bold small text-dark">Quick Demo Credentials (1-Click Fill)</span>
          </div>
          <p class="text-muted small mb-2" style="font-size: 0.78rem;">Click any role below to pre-populate credentials (password is <code>Password123</code>):</p>
          <div class="d-flex gap-2">
            <button type="button" class="btn btn-sm btn-outline-danger flex-grow-1" onclick="quickFill('admin@reusehub.com', 'Password123')">
              <i class="fa-solid fa-shield-halved me-1"></i> Admin
            </button>
            <button type="button" class="btn btn-sm btn-outline-success flex-grow-1" onclick="quickFill('rahul@gmail.com', 'Password123')">
              <i class="fa-solid fa-store me-1"></i> Owner
            </button>
            <button type="button" class="btn btn-sm btn-outline-primary flex-grow-1" onclick="quickFill('sneha@gmail.com', 'Password123')">
              <i class="fa-solid fa-user me-1"></i> Renter
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
function quickFill(email, pass) {
  document.getElementById('login-email').value = email;
  document.getElementById('login-password').value = pass;
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
