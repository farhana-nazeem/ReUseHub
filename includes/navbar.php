<?php
/**
 * ReUseHub - Main Navigation Bar
 */
?>
<nav class="navbar navbar-expand-lg navbar-reusehub sticky-top">
  <div class="container">
    <!-- Brand Logo -->
    <a class="navbar-brand-logo" href="<?php echo url(); ?>">
      <div class="brand-icon">
        <i class="fa-solid fa-arrows-rotate"></i>
      </div>
      <span>ReUse<span class="accent">Hub</span></span>
    </a>

    <!-- Mobile Toggle Button -->
    <button class="navbar-toggler border-0 shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent" aria-controls="navbarContent" aria-expanded="false" aria-label="Toggle navigation">
      <i class="fa-solid fa-bars text-dark fs-4"></i>
    </button>

    <!-- Navbar Links -->
    <div class="collapse navbar-collapse" id="navbarContent">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0 ms-lg-4">
        <li class="nav-item">
          <a class="nav-link <?php echo ($activePage === 'home') ? 'active' : ''; ?>" href="<?php echo url(); ?>">Home</a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?php echo ($activePage === 'explore') ? 'active' : ''; ?>" href="<?php echo url('explore.php'); ?>">Explore</a>
        </li>
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle <?php echo ($activePage === 'categories') ? 'active' : ''; ?>" href="#" id="navbarCategoriesDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
            Categories
          </a>
          <ul class="dropdown-menu shadow-sm border-0 rounded-3 mt-2" aria-labelledby="navbarCategoriesDropdown">
            <li><a class="dropdown-item py-2" href="<?php echo url('explore.php?category=electronics-gadgets'); ?>"><i class="fa-solid fa-laptop text-success me-2"></i> Electronics & Gadgets</a></li>
            <li><a class="dropdown-item py-2" href="<?php echo url('explore.php?category=fashion-accessories'); ?>"><i class="fa-solid fa-shirt text-success me-2"></i> Fashion & Accessories</a></li>
            <li><a class="dropdown-item py-2" href="<?php echo url('explore.php?category=photography-equipment'); ?>"><i class="fa-solid fa-camera text-success me-2"></i> Photography Equipment</a></li>
            <li><a class="dropdown-item py-2" href="<?php echo url('explore.php?category=camping-travel'); ?>"><i class="fa-solid fa-campground text-success me-2"></i> Camping & Travel</a></li>
            <li><a class="dropdown-item py-2" href="<?php echo url('explore.php?category=tools-appliances'); ?>"><i class="fa-solid fa-screwdriver-wrench text-success me-2"></i> Tools & Appliances</a></li>
            <li><a class="dropdown-item py-2" href="<?php echo url('explore.php?category=sports-fitness'); ?>"><i class="fa-solid fa-dumbbell text-success me-2"></i> Sports & Fitness</a></li>
            <li><a class="dropdown-item py-2" href="<?php echo url('explore.php?category=event-party-equipment'); ?>"><i class="fa-solid fa-champagne-glasses text-success me-2"></i> Event & Party</a></li>
            <li><a class="dropdown-item py-2" href="<?php echo url('explore.php?category=books-educational'); ?>"><i class="fa-solid fa-book-open text-success me-2"></i> Books & Educational</a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item py-2 text-primary fw-bold" href="<?php echo url('explore.php'); ?>"><i class="fa-solid fa-arrow-right me-2"></i> Browse All Categories</a></li>
          </ul>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="<?php echo url('#how-it-works'); ?>">How It Works</a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?php echo ($activePage === 'contact') ? 'active' : ''; ?>" href="<?php echo url('contact.php'); ?>">Contact</a>
        </li>
      </ul>

      <!-- Right Actions -->
      <div class="d-flex align-items-center gap-2 mt-3 mt-lg-0">
        <!-- List Your Item CTA -->
        <a href="<?php echo url('list-item.php'); ?>" class="btn btn-outline-custom py-2 px-3 me-2">
          <i class="fa-solid fa-plus-circle"></i> List Your Item
        </a>

        <?php if ($user): ?>
          <!-- Wishlist Link -->
          <a href="<?php echo url('wishlist.php'); ?>" class="btn btn-light rounded-circle p-2 position-relative text-dark" title="My Wishlist">
            <i class="fa-regular fa-heart"></i>
          </a>

          <!-- Notifications Dropdown -->
          <div class="dropdown">
            <a href="#" class="btn btn-light rounded-circle p-2 position-relative text-dark" id="notifDropdown" data-bs-toggle="dropdown" aria-expanded="false" title="Notifications">
              <i class="fa-regular fa-bell"></i>
              <?php if ($unreadCount > 0): ?>
                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: 0.65rem;">
                  <?php echo $unreadCount; ?>
                </span>
              <?php endif; ?>
            </a>
            <ul class="dropdown-menu dropdown-menu-end shadow border-0 rounded-3 p-2" style="width: 320px;" aria-labelledby="notifDropdown">
              <li class="d-flex justify-content-between align-items-center px-2 py-1 mb-2 border-bottom">
                <span class="fw-bold fs-6">Notifications</span>
                <span class="badge bg-success-subtle text-success"><?php echo $unreadCount; ?> new</span>
              </li>
              <?php
                $notifs = getDB()->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 4");
                $notifs->execute(array($user['id']));
                $notifList = $notifs->fetchAll();
              ?>
              <?php if (empty($notifList)): ?>
                <li class="text-center py-3 text-muted small">No new notifications</li>
              <?php else: ?>
                <?php foreach ($notifList as $ntf): ?>
                  <li class="mb-1">
                    <a class="dropdown-item rounded-2 py-2 px-2 <?php echo $ntf['is_read'] ? '' : 'bg-light'; ?>" href="<?php echo url($ntf['link'] ? $ntf['link'] : 'user/dashboard.php'); ?>">
                      <div class="d-flex align-items-start gap-2">
                        <i class="fa-solid fa-circle-dot text-success mt-1" style="font-size: 0.6rem;"></i>
                        <div>
                          <div class="fw-semibold text-dark small"><?php echo e($ntf['title']); ?></div>
                          <div class="text-muted text-truncate" style="max-width: 240px; font-size: 0.78rem;"><?php echo e($ntf['message']); ?></div>
                          <small class="text-muted" style="font-size: 0.7rem;"><?php echo formatDate($ntf['created_at'], 'd M, H:i'); ?></small>
                        </div>
                      </div>
                    </a>
                  </li>
                <?php endforeach; ?>
              <?php endif; ?>
            </ul>
          </div>

          <!-- User Menu Dropdown -->
          <div class="dropdown">
            <button class="btn btn-light d-flex align-items-center gap-2 border rounded-pill px-3 py-1 shadow-sm" type="button" id="userMenuDropdown" data-bs-toggle="dropdown" aria-expanded="false">
              <img src="<?php echo asset('images/default-avatar.png'); ?>" alt="Avatar" class="rounded-circle" style="width: 28px; height: 28px; object-fit: cover;">
              <?php $nameParts = explode(' ', $user['name']); ?>
              <span class="fw-bold text-dark small"><?php echo e($nameParts[0]); ?></span>
              <i class="fa-solid fa-chevron-down text-muted" style="font-size: 0.75rem;"></i>
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow border-0 rounded-3 py-2 mt-2" style="min-width: 220px;" aria-labelledby="userMenuDropdown">
              <li class="px-3 py-2 border-bottom">
                <div class="fw-bold text-dark"><?php echo e($user['name']); ?></div>
                <div class="text-muted small"><?php echo e($user['email']); ?></div>
                <div class="mt-1">
                  <span class="badge bg-success-subtle text-success text-capitalize"><?php echo e($user['role']); ?></span>
                </div>
              </li>
              
              <!-- Renter Links -->
              <li><a class="dropdown-item py-2" href="<?php echo url('user/dashboard.php'); ?>"><i class="fa-solid fa-gauge text-muted me-2"></i> Renter Dashboard</a></li>
              <li><a class="dropdown-item py-2" href="<?php echo url('user/my-bookings.php'); ?>"><i class="fa-solid fa-calendar-check text-muted me-2"></i> My Bookings</a></li>
              
              <!-- Owner Links -->
              <li><hr class="dropdown-divider"></li>
              <li><a class="dropdown-item py-2" href="<?php echo url('owner/dashboard.php'); ?>"><i class="fa-solid fa-store text-muted me-2"></i> Owner Dashboard</a></li>
              <li><a class="dropdown-item py-2" href="<?php echo url('owner/my-products.php'); ?>"><i class="fa-solid fa-box-open text-muted me-2"></i> My Listed Items</a></li>
              <li><a class="dropdown-item py-2" href="<?php echo url('owner/booking-requests.php'); ?>"><i class="fa-solid fa-inbox text-muted me-2"></i> Booking Requests</a></li>

              <!-- Admin Link if Admin -->
              <?php if ($user['role'] === 'admin'): ?>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item py-2 text-danger fw-bold" href="<?php echo url('admin/dashboard.php'); ?>"><i class="fa-solid fa-shield-halved me-2"></i> Admin Control Panel</a></li>
              <?php endif; ?>

              <li><hr class="dropdown-divider"></li>
              <li><a class="dropdown-item py-2" href="<?php echo url('user/profile.php'); ?>"><i class="fa-solid fa-user-gear text-muted me-2"></i> Profile Settings</a></li>
              <li><a class="dropdown-item py-2 text-danger" href="<?php echo url('logout.php'); ?>"><i class="fa-solid fa-right-from-bracket me-2"></i> Sign Out</a></li>
            </ul>
          </div>

        <?php else: ?>
          <!-- Guest Links -->
          <a href="<?php echo url('login.php'); ?>" class="btn btn-outline-secondary border-0 fw-semibold px-3">Log In</a>
          <a href="<?php echo url('register.php'); ?>" class="btn btn-primary-custom py-2 px-3">Sign Up</a>
        <?php endif; ?>
      </div>
    </div>
  </div>
</nav>
