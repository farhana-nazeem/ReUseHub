<?php
/**
 * ReUseHub - Product Details & Live Booking Interface
 */

require_once __DIR__ . '/includes/header.php';

$productId = (int)(isset($_GET['id']) ? $_GET['id'] : 0);
if ($productId <= 0) {
    header("Location: " . url('explore.php'));
    exit();
}

$pdo = getDB();

// Fetch product with category & owner details
$stmt = $pdo->prepare("SELECT p.*, c.name as category_name, c.slug as category_slug, 
                       u.id as owner_user_id, u.name as owner_name, u.city as owner_city, u.avatar as owner_avatar, u.phone as owner_phone, u.created_at as owner_since,
                       (SELECT COUNT(*) FROM products WHERE owner_id = u.id AND status = 'approved') as owner_total_items
                       FROM products p
                       JOIN categories c ON p.category_id = c.id
                       JOIN users u ON p.owner_id = u.id
                       WHERE p.id = ?");
$stmt->execute(array($productId));
$product = $stmt->fetch();

if (!$product) {
    setFlash('danger', 'The requested rental item was not found.');
    header("Location: " . url('explore.php'));
    exit();
}

// Increment view counter
$pdo->prepare("UPDATE products SET views_count = views_count + 1 WHERE id = ?")->execute(array($productId));

// Fetch product gallery images
$images = getProductImages($productId);
$primaryImage = !empty($images) ? $images[0]['image_path'] : 'assets/images/placeholder.jpg';
if (!str_starts_with($primaryImage, 'http://') && !str_starts_with($primaryImage, 'https://')) {
    $primaryImage = url($primaryImage);
}

// Fetch Reviews & Ratings
$revStmt = $pdo->prepare("SELECT r.*, u.name as reviewer_name, u.city as reviewer_city 
                         FROM reviews r 
                         JOIN users u ON r.user_id = u.id 
                         WHERE r.product_id = ? 
                         ORDER BY r.created_at DESC");
$revStmt->execute(array($productId));
$reviews = $revStmt->fetchAll();

$ratingInfo = getProductRating($productId);
$avgRating = $ratingInfo['avg_rating'];
$reviewCount = $ratingInfo['review_count'];

// Booked dates for calendar awareness
$bookedDates = getBookedDates($productId);
$user = currentUser();
$isOwner = ($user && $user['id'] === $product['owner_id']);
$userWishlisted = ($user && isWishlisted($user['id'], $productId));

// Default suggested rental dates: Tomorrow to 3 days after
$defaultStart = date('Y-m-d', strtotime('+1 day'));
$defaultEnd = date('Y-m-d', strtotime('+3 days'));
?>

<div class="py-4" style="background-color: var(--bg-main);">
  <div class="container">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-3">
      <ol class="breadcrumb small">
        <li class="breadcrumb-item"><a href="<?php echo  url(); ?>" class="text-secondary text-decoration-none">Home</a></li>
        <li class="breadcrumb-item"><a href="<?php echo  url('explore.php'); ?>" class="text-secondary text-decoration-none">Explore</a></li>
        <li class="breadcrumb-item"><a href="<?php echo  url('explore.php?category=' . urlencode($product['category_slug'])); ?>" class="text-secondary text-decoration-none"><?php echo  e($product['category_name']); ?></a></li>
        <li class="breadcrumb-item active text-truncate" style="max-width: 300px;" aria-current="page"><?php echo  e($product['title']); ?></li>
      </ol>
    </nav>

    <div class="row g-4">
      <!-- ================= LEFT COLUMN: IMAGES & PRODUCT DETAILS ================= -->
      <div class="col-lg-8">
        <!-- Main Image Card -->
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white mb-4">
          <div class="position-relative" style="max-height: 480px; background: #000; overflow: hidden; display: flex; align-items: center; justify-content: center;">
            <img src="<?php echo  e($primaryImage); ?>" id="main-gallery-img" alt="<?php echo  e($product['title']); ?>" style="width: 100%; max-height: 480px; object-fit: contain;">
            <span class="product-badge-condition" style="top: 18px; left: 18px; font-size: 0.85rem; padding: 0.4rem 0.9rem;">
              <?php echo  e($product['condition_grade']); ?>
            </span>
            <button type="button" class="wishlist-btn <?php echo  $userWishlisted ? 'active' : ''; ?>" data-product-id="<?php echo  $product['id']; ?>" style="top: 18px; right: 18px; width: 44px; height: 44px; font-size: 1.2rem;" title="Save to Wishlist">
              <i class="<?php echo  $userWishlisted ? 'fa-solid' : 'fa-regular'; ?> fa-heart"></i>
            </button>
          </div>

          <!-- Thumbnail Strip -->
          <?php if (count($images) > 1): ?>
            <div class="p-3 bg-light border-top">
              <div class="d-flex gap-2 overflow-auto">
                <?php foreach ($images as $idx => $img): ?>
                  <?php
                    $imgPath = $img['image_path'];
                    if (!str_starts_with($imgPath, 'http://') && !str_starts_with($imgPath, 'https://')) {
                        $imgPath = url($imgPath);
                    }
                  ?>
                  <div class="gallery-thumb-item rounded-3 border overflow-hidden <?php echo  $idx === 0 ? 'active border-success' : ''; ?>" data-full-img="<?php echo  e($imgPath); ?>" style="width: 80px; height: 60px; cursor: pointer; flex-shrink: 0;">
                    <img src="<?php echo  e($imgPath); ?>" alt="Thumb" style="width: 100%; height: 100%; object-fit: cover;">
                  </div>
                <?php endforeach; ?>
              </div>
            </div>
          <?php endif; ?>
        </div>

        <!-- Product Overview -->
        <div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-4">
          <div class="d-flex justify-content-between align-items-start mb-2">
            <div>
              <span class="badge bg-success-subtle text-success fw-bold text-uppercase mb-2"><?php echo  e($product['category_name']); ?></span>
              <h2 class="fw-bold text-dark"><?php echo  e($product['title']); ?></h2>
            </div>
            <div class="text-end">
              <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#reportModal" title="Report Listing">
                <i class="fa-regular fa-flag me-1"></i> Report
              </button>
            </div>
          </div>

          <!-- Meta Badges -->
          <div class="d-flex flex-wrap align-items-center gap-3 py-2 border-bottom mb-4 text-muted small">
            <div>
              <i class="fa-solid fa-location-dot text-success me-1"></i>
              <span class="text-dark fw-semibold"><?php echo  e($product['location']); ?> (<?php echo  e($product['city']); ?>)</span>
            </div>
            <div>&bull;</div>
            <div>
              <i class="fa-solid fa-star text-warning me-1"></i>
              <span class="text-dark fw-bold"><?php echo  number_format($avgRating, 1); ?></span>
              <span>(<?php echo  $reviewCount; ?> reviews)</span>
            </div>
            <div>&bull;</div>
            <div>
              <i class="fa-solid fa-eye text-muted me-1"></i>
              <span><?php echo  number_format($product['views_count']); ?> views</span>
            </div>
            <div>&bull;</div>
            <div>
              <span class="badge <?php echo  $product['is_available'] ? 'bg-success' : 'bg-secondary'; ?>">
                <?php echo  $product['is_available'] ? 'Available Now' : 'Currently Rented'; ?>
              </span>
            </div>
          </div>

          <!-- Description -->
          <h5 class="fw-bold text-dark mb-3">Item Description</h5>
          <div class="text-secondary-emphasis mb-4" style="line-height: 1.8;">
            <?php echo  nl2br(e($product['description'])); ?>
          </div>

          <!-- Rental Guidelines -->
          <?php if (!empty($product['rental_instructions'])): ?>
            <div class="p-3 rounded-3 bg-light border mb-4">
              <h6 class="fw-bold text-dark mb-2"><i class="fa-solid fa-circle-info text-primary me-2"></i> Owner's Rental Guidelines & Pickup Instructions</h6>
              <p class="text-muted small mb-0"><?php echo  nl2br(e($product['rental_instructions'])); ?></p>
            </div>
          <?php endif; ?>

          <!-- Product Highlights Grid -->
          <div class="row g-3">
            <div class="col-md-4 col-6">
              <div class="border rounded-3 p-3 bg-light-subtle">
                <small class="text-muted d-block">Condition Grade</small>
                <span class="fw-bold text-dark"><?php echo  e($product['condition_grade']); ?></span>
              </div>
            </div>
            <div class="col-md-4 col-6">
              <div class="border rounded-3 p-3 bg-light-subtle">
                <small class="text-muted d-block">Security Deposit</small>
                <span class="fw-bold text-success"><?php echo  formatPrice($product['security_deposit']); ?> (Refundable)</span>
              </div>
            </div>
            <div class="col-md-4 col-12">
              <div class="border rounded-3 p-3 bg-light-subtle">
                <small class="text-muted d-block">Estimated Retail Value</small>
                <span class="fw-bold text-dark"><?php echo  formatPrice($product['original_price']); ?></span>
              </div>
            </div>
          </div>
        </div>

        <!-- ================= INNOVATIVE FEATURE: ITEM SAVINGS COMPARISON ================= -->
        <div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-4">
          <div class="d-flex align-items-center gap-2 mb-3">
            <div class="rounded-circle bg-warning-subtle text-warning d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
              <i class="fa-solid fa-calculator"></i>
            </div>
            <h5 class="fw-bold text-dark mb-0">Rental Savings for this Item</h5>
          </div>

          <p class="text-muted small mb-3">
            Purchasing this item brand new would cost you approximately <strong><?php echo  formatPrice($product['original_price']); ?></strong>. Compare your savings when renting for your specific needs:
          </p>

          <div class="row g-3 text-center">
            <?php
              $origVal = (float)$product['original_price'];
              $dayRate = (float)$product['daily_price'];
            ?>
            <div class="col-md-4">
              <div class="p-3 rounded-3 border bg-light">
                <small class="text-muted fw-bold">2 Days Rental</small>
                <div class="fs-5 fw-bold text-primary mt-1"><?php echo  formatPrice($dayRate * 2); ?></div>
                <div class="text-success small fw-bold mt-1">Save <?php echo  formatPrice(max(0, $origVal - ($dayRate * 2))); ?></div>
              </div>
            </div>
            <div class="col-md-4">
              <div class="p-3 rounded-3 border bg-light">
                <small class="text-muted fw-bold">4 Days Rental</small>
                <div class="fs-5 fw-bold text-primary mt-1"><?php echo  formatPrice($dayRate * 4); ?></div>
                <div class="text-success small fw-bold mt-1">Save <?php echo  formatPrice(max(0, $origVal - ($dayRate * 4))); ?></div>
              </div>
            </div>
            <div class="col-md-4">
              <div class="p-3 rounded-3 border bg-light">
                <small class="text-muted fw-bold">1 Week Rental</small>
                <div class="fs-5 fw-bold text-primary mt-1"><?php echo  formatPrice($dayRate * 7); ?></div>
                <div class="text-success small fw-bold mt-1">Save <?php echo  formatPrice(max(0, $origVal - ($dayRate * 7))); ?></div>
              </div>
            </div>
          </div>
        </div>

        <!-- ================= REVIEWS & RATINGS SECTION ================= -->
        <div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-4">
          <div class="d-flex justify-content-between align-items-center mb-4">
            <h5 class="fw-bold text-dark mb-0">Ratings & Reviews (<?php echo  $reviewCount; ?>)</h5>
            <div class="d-flex align-items-center gap-1 text-warning fs-5">
              <i class="fa-solid fa-star"></i>
              <span class="fw-bold text-dark"><?php echo  number_format($avgRating, 1); ?></span>
              <span class="text-muted small">/ 5.0</span>
            </div>
          </div>

          <?php if (empty($reviews)): ?>
            <div class="text-center py-4 text-muted">
              <i class="fa-regular fa-comment-dots fs-2 mb-2 d-block"></i>
              <p class="small mb-0">No reviews yet for this product. Be the first to rent and leave feedback!</p>
            </div>
          <?php else: ?>
            <div class="d-flex flex-column gap-3">
              <?php foreach ($reviews as $rev): ?>
                <div class="p-3 rounded-3 border bg-light-subtle">
                  <div class="d-flex justify-content-between align-items-center mb-2">
                    <div class="d-flex align-items-center gap-2">
                      <img src="<?php echo  asset('images/default-avatar.png'); ?>" alt="Avatar" class="rounded-circle" width="32" height="32">
                      <div>
                        <div class="fw-bold text-dark small"><?php echo  e($rev['reviewer_name']); ?></div>
                        <small class="text-muted" style="font-size: 0.72rem;"><?php echo  e($rev['reviewer_city']); ?> &bull; <?php echo  formatDate($rev['created_at']); ?></small>
                      </div>
                    </div>
                    <div class="text-warning small">
                      <?php for ($s = 1; $s <= 5; $s++): ?>
                        <i class="fa-solid fa-star <?php echo  $s <= $rev['rating'] ? '' : 'text-muted'; ?>"></i>
                      <?php endfor; ?>
                    </div>
                  </div>
                  <p class="text-muted small mb-0"><?php echo  nl2br(e($rev['comment'])); ?></p>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- ================= RIGHT COLUMN: LIVE BOOKING WIDGET & OWNER CARD ================= -->
      <div class="col-lg-4">
        <!-- Live Booking Calculator Widget -->
        <div class="card border-0 shadow-lg rounded-4 p-4 bg-white sticky-top mb-4" style="top: 85px;">
          <div class="d-flex justify-content-between align-items-baseline mb-3">
            <div>
              <span class="fs-2 fw-bold text-primary"><?php echo  formatPrice($product['daily_price']); ?></span>
              <span class="text-muted small">/ day</span>
            </div>
            <span class="badge bg-success-subtle text-success fw-bold">Instant Request</span>
          </div>

          <div class="alert alert-info py-2 px-3 small rounded-3 mb-3">
            <i class="fa-solid fa-shield-halved me-1"></i> Refundable Deposit: <strong><?php echo  formatPrice($product['security_deposit']); ?></strong>
          </div>

          <?php if ($isOwner): ?>
            <!-- Owner warning -->
            <div class="alert alert-warning small rounded-3 mb-3">
              <i class="fa-solid fa-circle-exclamation me-1"></i> You are the owner of this item. You cannot rent your own item.
            </div>
            <a href="<?php echo  url('owner/edit-product.php?id=' . $product['id']); ?>" class="btn btn-outline-custom w-100 py-2">
              <i class="fa-solid fa-pen-to-square me-1"></i> Edit This Listing
            </a>
          <?php elseif (!$product['is_available']): ?>
            <!-- Unavailable warning -->
            <div class="alert alert-secondary small rounded-3 mb-3">
              <i class="fa-solid fa-lock me-1"></i> This item is currently unavailable or actively rented out.
            </div>
            <button class="btn btn-secondary w-100 py-2" disabled>Currently Unavailable</button>
          <?php else: ?>
            <!-- Booking Form -->
            <form action="<?php echo  url('booking.php'); ?>" method="GET" id="booking-request-form">
              <input type="hidden" name="product_id" value="<?php echo  $product['id']; ?>">
              <input type="hidden" id="product-daily-rate" value="<?php echo  (float)$product['daily_price']; ?>">
              <input type="hidden" id="product-deposit-rate" value="<?php echo  (float)$product['security_deposit']; ?>">
              <input type="hidden" name="total_days" id="hidden-total-days" value="3">

              <div class="row g-2 mb-3">
                <div class="col-6">
                  <label class="form-label small fw-bold text-dark">Rental Start</label>
                  <input type="date" name="start_date" id="booking-start-date" class="form-control form-control-sm" value="<?php echo  $defaultStart; ?>" required>
                </div>
                <div class="col-6">
                  <label class="form-label small fw-bold text-dark">Return Date</label>
                  <input type="date" name="end_date" id="booking-end-date" class="form-control form-control-sm" value="<?php echo  $defaultEnd; ?>" required>
                </div>
              </div>

              <!-- Real-time Cost Breakdown -->
              <div class="border rounded-3 p-3 bg-light mb-3" id="cost-breakdown-box">
                <div class="d-flex justify-content-between small text-muted mb-2">
                  <span>Duration:</span>
                  <span class="fw-bold text-dark" id="calc-days-display">3 days</span>
                </div>
                <div class="d-flex justify-content-between small text-muted mb-2">
                  <span>Rental Charge:</span>
                  <span class="fw-bold text-dark" id="calc-rental-charge"><?php echo  formatPrice($product['daily_price'] * 3); ?></span>
                </div>
                <div class="d-flex justify-content-between small text-muted mb-2">
                  <span>Refundable Security Deposit:</span>
                  <span class="fw-bold text-dark" id="calc-deposit-charge"><?php echo  formatPrice($product['security_deposit']); ?></span>
                </div>
                <div class="d-flex justify-content-between small text-muted mb-2">
                  <span>Marketplace Platform Fee (3%):</span>
                  <span class="fw-bold text-dark" id="calc-service-fee"><?php echo  formatPrice(round(($product['daily_price'] * 3) * 0.03, 2)); ?></span>
                </div>
                <hr class="my-2">
                <div class="d-flex justify-content-between fw-bold text-dark">
                  <span>Total Estimated:</span>
                  <span class="fs-5 text-primary" id="calc-total-amount">
                    <?php echo  formatPrice(($product['daily_price'] * 3) + $product['security_deposit'] + round(($product['daily_price'] * 3) * 0.03, 2)); ?>
                  </span>
                </div>
              </div>

              <button type="submit" class="btn btn-primary-custom w-100 py-3 shadow">
                <i class="fa-solid fa-calendar-check me-1"></i> Request to Rent
              </button>
              <small class="text-muted d-block text-center mt-2" style="font-size: 0.75rem;">
                <i class="fa-solid fa-lock text-success me-1"></i> You will review payment details before booking is placed.
              </small>
            </form>
          <?php endif; ?>
        </div>

        <!-- Owner Profile Card -->
        <div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-4">
          <h6 class="fw-bold text-muted text-uppercase small mb-3">Item Owner</h6>
          <div class="d-flex align-items-center gap-3 mb-3">
            <img src="<?php echo  asset('images/default-avatar.png'); ?>" alt="Owner Avatar" class="rounded-circle border" width="56" height="56">
            <div>
              <h5 class="fw-bold text-dark mb-0"><?php echo  e($product['owner_name']); ?></h5>
              <small class="text-muted"><i class="fa-solid fa-location-dot me-1 text-success"></i> <?php echo  e($product['owner_city']); ?>, Kerala</small>
              <div class="mt-1">
                <span class="badge bg-success-subtle text-success small">Verified Owner</span>
              </div>
            </div>
          </div>
          <div class="border-top pt-3">
            <div class="d-flex justify-content-between small text-muted mb-2">
              <span>Member Since:</span>
              <span class="fw-semibold text-dark"><?php echo  formatDate($product['owner_since'], 'M Y'); ?></span>
            </div>
            <div class="d-flex justify-content-between small text-muted mb-3">
              <span>Active Listings:</span>
              <span class="fw-semibold text-dark"><?php echo  $product['owner_total_items']; ?> items</span>
            </div>
            <a href="tel:<?php echo  e($product['owner_phone']); ?>" class="btn btn-outline-custom w-100 btn-sm">
              <i class="fa-solid fa-phone me-1"></i> Contact Owner
            </a>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- ================= REPORT LISTING MODAL ================= -->
<div class="modal fade" id="reportModal" tabindex="-1" aria-labelledby="reportModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content rounded-4 border-0">
      <div class="modal-header border-0 pb-0">
        <h5 class="modal-title fw-bold text-dark" id="reportModalLabel">Report Listing</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="<?php echo  url('contact.php'); ?>" method="POST">
        <?php echo  csrf_field(); ?>
        <input type="hidden" name="action" value="report_product">
        <input type="hidden" name="product_id" value="<?php echo  $product['id']; ?>">

        <div class="modal-body">
          <p class="text-muted small">Help us maintain a safe community. Why are you reporting this product?</p>
          <div class="mb-3">
            <label class="form-label small fw-bold">Reason</label>
            <select name="reason" class="form-select" required>
              <option value="Misleading Information">Misleading or False Information</option>
              <option value="Inappropriate Content">Inappropriate Photos or Content</option>
              <option value="Suspected Fraud">Suspected Fraud or Scam</option>
              <option value="Damaged Item">Reported Item Damaged/Unusable</option>
              <option value="Other">Other Issues</option>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label small fw-bold">Additional Details</label>
            <textarea name="details" class="form-control" rows="3" placeholder="Provide extra details for our moderation team..." required></textarea>
          </div>
        </div>
        <div class="modal-footer border-0 pt-0">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-danger">Submit Report</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

