<?php
/**
 * ReUseHub - Wishlist System (Page & AJAX Toggle)
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

$pdo = getDB();

// Handle AJAX toggle request
if (isset($_GET['action']) && $_GET['action'] === 'toggle_ajax') {
    header('Content-Type: application/json');

    if (!isLoggedIn()) {
        echo json_encode(array('status' => 'unauthorized', 'message' => 'Please sign in first.'));
        exit();
    }

    $userId = $_SESSION['user_id'];
    $productId = (int)(isset($_GET['product_id']) ? $_GET['product_id'] : 0);

    if ($productId <= 0) {
        echo json_encode(array('status' => 'error', 'message' => 'Invalid product.'));
        exit();
    }

    $checkStmt = $pdo->prepare("SELECT id FROM wishlist WHERE user_id = ? AND product_id = ?");
    $checkStmt->execute(array($userId, $productId));
    $existing = $checkStmt->fetch();

    if ($existing) {
        $del = $pdo->prepare("DELETE FROM wishlist WHERE id = ?");
        $del->execute(array($existing['id']));
        echo json_encode(array('status' => 'success', 'action' => 'removed'));
    } else {
        $ins = $pdo->prepare("INSERT INTO wishlist (user_id, product_id) VALUES (?, ?)");
        $ins->execute(array($userId, $productId));
        echo json_encode(array('status' => 'success', 'action' => 'added'));
    }
    exit();
}

// Handle non-AJAX remove action
if (isset($_GET['remove'])) {
    requireLogin();
    $removeId = (int)$_GET['remove'];
    $del = $pdo->prepare("DELETE FROM wishlist WHERE user_id = ? AND product_id = ?");
    $del->execute(array($_SESSION['user_id'], $removeId));
    setFlash('info', 'Item removed from your wishlist.');
    header("Location: " . url('wishlist.php'));
    exit();
}

// Regular Wishlist Page
$pageTitle = 'My Saved Items (Wishlist)';
$activePage = 'wishlist';
require_once __DIR__ . '/includes/header.php';
requireLogin();

$user = currentUser();

// Fetch wishlisted items
$stmt = $pdo->prepare("SELECT p.*, c.name as category_name, c.slug as category_slug,
                       (SELECT image_path FROM product_images WHERE product_id = p.id ORDER BY is_primary DESC, id ASC LIMIT 1) as primary_image,
                       (SELECT AVG(rating) FROM reviews WHERE product_id = p.id) as avg_rating
                       FROM wishlist w
                       JOIN products p ON w.product_id = p.id
                       JOIN categories c ON p.category_id = c.id
                       WHERE w.user_id = ? AND p.status = 'approved'
                       ORDER BY w.created_at DESC");
$stmt->execute(array($user['id']));
$wishlistItems = $stmt->fetchAll();
?>

<div class="py-5" style="background-color: var(--bg-main);">
  <div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h2 class="fw-bold mb-1"><i class="fa-solid fa-heart text-danger me-2"></i> My Wishlist</h2>
        <p class="text-muted small mb-0">Items you've bookmarked for your upcoming trips, events, and projects.</p>
      </div>
      <a href="<?php echo  url('explore.php'); ?>" class="btn btn-outline-custom btn-sm">
        <i class="fa-solid fa-magnifying-glass me-1"></i> Explore More
      </a>
    </div>

    <?php if (empty($wishlistItems)): ?>
      <div class="card border-0 shadow-sm rounded-4 p-5 text-center bg-white my-4">
        <div class="rounded-circle bg-danger-subtle text-danger d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 70px; height: 70px; font-size: 1.8rem;">
          <i class="fa-regular fa-heart"></i>
        </div>
        <h4 class="fw-bold text-dark">Your Wishlist is Empty</h4>
        <p class="text-muted small mb-4">When browsing through rentals, click the heart icon on any product to save it here for later.</p>
        <div>
          <a href="<?php echo  url('explore.php'); ?>" class="btn btn-primary-custom px-4 py-2">
            Start Exploring Rentals
          </a>
        </div>
      </div>
    <?php else: ?>
      <div class="row g-4">
        <?php foreach ($wishlistItems as $item): ?>
          <?php
            $imgUrl = $item['primary_image'] ?: 'assets/images/placeholder.jpg';
            if (!str_starts_with($imgUrl, 'http://') && !str_starts_with($imgUrl, 'https://')) {
              $imgUrl = url($imgUrl);
            }
            $itemRating = $item['avg_rating'] ? round((float)$item['avg_rating'], 1) : 5.0;
          ?>
          <div class="col-xl-3 col-lg-4 col-md-6">
            <div class="product-card">
              <div class="product-image-wrapper">
                <img src="<?php echo  e($imgUrl); ?>" alt="<?php echo  e($item['title']); ?>">
                <span class="product-badge-condition"><?php echo  e($item['condition_grade']); ?></span>
                <a href="<?php echo  url('wishlist.php?remove=' . $item['id']); ?>" class="wishlist-btn active" title="Remove from Wishlist">
                  <i class="fa-solid fa-trash text-danger"></i>
                </a>
              </div>

              <div class="product-card-body">
                <div class="product-category-meta">
                  <span class="product-category-name"><?php echo  e($item['category_name']); ?></span>
                  <span class="product-rating">
                    <i class="fa-solid fa-star"></i> <?php echo  number_format($itemRating, 1); ?>
                  </span>
                </div>

                <h5 class="product-card-title">
                  <a href="<?php echo  url('product-details.php?id=' . $item['id']); ?>" class="text-dark text-decoration-none">
                    <?php echo  e($item['title']); ?>
                  </a>
                </h5>

                <div class="product-location">
                  <i class="fa-solid fa-location-dot text-success"></i>
                  <span class="text-truncate"><?php echo  e($item['location']); ?></span>
                </div>

                <div class="product-card-footer">
                  <div class="product-price-box">
                    <span class="product-price-daily"><?php echo  formatPrice($item['daily_price']); ?></span>
                    <span class="product-price-label">per day</span>
                  </div>
                  <a href="<?php echo  url('product-details.php?id=' . $item['id']); ?>" class="btn btn-primary-custom btn-sm">
                    Rent Now
                  </a>
                </div>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

