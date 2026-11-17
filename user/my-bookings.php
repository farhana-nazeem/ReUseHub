<?php
/**
 * ReUseHub - Renter Bookings Management & Review Submission
 */

$pageTitle = 'My Bookings';
$activePage = 'my_bookings';
require_once __DIR__ . '/../includes/header.php';

requireLogin();
$user = currentUser();
$pdo = getDB();

$renterId = $user['id'];
$statusFilter = trim(isset($_GET['status']) ? $_GET['status'] : 'all');

// 1. Handle Booking Cancellation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'cancel_booking') {
    verify_csrf_token();
    $bookingId = (int)(isset($_POST['booking_id']) ? $_POST['booking_id'] : 0);
    $reason = trim(isset($_POST['reason']) ? $_POST['reason'] : 'Cancelled by renter');

    $check = $pdo->prepare("SELECT * FROM bookings WHERE id = ? AND renter_id = ? AND status IN ('pending', 'approved')");
    $check->execute(array($bookingId, $renterId));
    $bk = $check->fetch();

    if ($bk) {
        $update = $pdo->prepare("UPDATE bookings SET status = 'cancelled', cancellation_reason = ?, updated_at = NOW() WHERE id = ?");
        $update->execute(array($reason, $bookingId));

        // Refund demo payment
        $pdo->prepare("UPDATE payments SET payment_status = 'refunded' WHERE booking_id = ?")->execute(array($bookingId));

        // Notify owner
        createNotification(
            $bk['owner_id'],
            'Booking Cancelled',
            "Renter {$user['name']} cancelled booking #{$bk['booking_code']}. Reason: {$reason}",
            'owner/booking-requests.php'
        );

        setFlash('info', "Booking #{$bk['booking_code']} has been cancelled and refundable amount processed.");
        header("Location: " . url('user/my-bookings.php'));
        exit();
    } else {
        setFlash('danger', 'Unable to cancel booking. It may have already started or been completed.');
    }
}

// 2. Handle Review & Rating Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'submit_review') {
    verify_csrf_token();
    $bookingId = (int)(isset($_POST['booking_id']) ? $_POST['booking_id'] : 0);
    $productId = (int)(isset($_POST['product_id']) ? $_POST['product_id'] : 0);
    $rating = max(1, min(5, (int)(isset($_POST['rating']) ? $_POST['rating'] : 5)));
    $comment = trim(isset($_POST['comment']) ? $_POST['comment'] : '');

    // Verify booking belongs to renter and is completed
    $chk = $pdo->prepare("SELECT id FROM bookings WHERE id = ? AND renter_id = ? AND status = 'completed'");
    $chk->execute(array($bookingId, $renterId));
    if ($chk->fetch()) {
        // Check if already reviewed
        $existing = $pdo->prepare("SELECT id FROM reviews WHERE booking_id = ?");
        $existing->execute(array($bookingId));
        if (!$existing->fetch()) {
            $ins = $pdo->prepare("INSERT INTO reviews (booking_id, product_id, user_id, rating, comment) VALUES (?, ?, ?, ?, ?)");
            $ins->execute(array($bookingId, $productId, $renterId, $rating, $comment));

            // Notify owner
            $ownerIdStmt = $pdo->prepare("SELECT owner_id, title FROM products WHERE id = ?");
            $ownerIdStmt->execute(array($productId));
            $prod = $ownerIdStmt->fetch();

            createNotification(
                $prod['owner_id'],
                'New Product Review Received',
                "{$user['name']} rated your item '{$prod['title']}' {$rating}/5 stars: \"{$comment}\"",
                'product-details.php?id=' . $productId
            );

            setFlash('success', 'Thank you! Your rating and review have been published.');
            header("Location: " . url('user/my-bookings.php'));
            exit();
        }
    }
}

// 3. Query Bookings with Filters
$where = array("b.renter_id = ?");
$params = array($renterId);

if ($statusFilter !== 'all' && in_array($statusFilter, array('pending', 'approved', 'active', 'completed', 'cancelled'))) {
    $where[] = "b.status = ?";
    $params[] = $statusFilter;
}

$whereSql = implode(" AND ", $where);

$stmt = $pdo->prepare("SELECT b.*, p.title as product_title, p.daily_price, p.location as product_location, 
                       u.name as owner_name, u.phone as owner_phone, u.email as owner_email,
                       (SELECT image_path FROM product_images WHERE product_id = p.id ORDER BY is_primary DESC, id ASC LIMIT 1) as primary_image,
                       (SELECT id FROM reviews WHERE booking_id = b.id LIMIT 1) as review_id
                       FROM bookings b
                       JOIN products p ON b.product_id = p.id
                       JOIN users u ON b.owner_id = u.id
                       WHERE {$whereSql}
                       ORDER BY b.created_at DESC");
$stmt->execute($params);
$bookings = $stmt->fetchAll();
?>

<div class="py-4" style="background-color: var(--bg-main);">
  <div class="container">
    <div class="dashboard-container">
      <!-- Sidebar -->
      <aside class="dashboard-sidebar rounded-4 shadow-sm me-lg-4 mb-4 mb-lg-0">
        <ul class="sidebar-menu">
          <li class="sidebar-menu-item">
            <a href="<?php echo  url('user/dashboard.php'); ?>" class="sidebar-link">
              <i class="fa-solid fa-gauge"></i> Renter Overview
            </a>
          </li>
          <li class="sidebar-menu-item">
            <a href="<?php echo  url('user/my-bookings.php'); ?>" class="sidebar-link active">
              <i class="fa-solid fa-calendar-check"></i> My Bookings
            </a>
          </li>
          <li class="sidebar-menu-item">
            <a href="<?php echo  url('user/rental-history.php'); ?>" class="sidebar-link">
              <i class="fa-solid fa-clock-rotate-left"></i> Rental History
            </a>
          </li>
          <li class="sidebar-menu-item">
            <a href="<?php echo  url('wishlist.php'); ?>" class="sidebar-link">
              <i class="fa-solid fa-heart"></i> My Wishlist
            </a>
          </li>
          <li class="sidebar-menu-item">
            <a href="<?php echo  url('user/profile.php'); ?>" class="sidebar-link">
              <i class="fa-solid fa-user-gear"></i> Profile Settings
            </a>
          </li>
        </ul>
      </aside>

      <!-- Main Content -->
      <main class="dashboard-main rounded-4 shadow-sm p-4 bg-white flex-grow-1">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
          <div>
            <h3 class="fw-bold text-dark mb-1">My Rental Bookings</h3>
            <p class="text-muted small mb-0">Manage current rental dates, track status approvals, and rate completed rentals.</p>
          </div>
          <a href="<?php echo  url('explore.php'); ?>" class="btn btn-outline-custom btn-sm">
            <i class="fa-solid fa-plus me-1"></i> New Rental
          </a>
        </div>

        <!-- Filter Tabs -->
        <div class="d-flex flex-wrap gap-2 mb-4 pb-2 border-bottom">
          <a href="<?php echo  url('user/my-bookings.php'); ?>" class="btn btn-sm <?php echo  $statusFilter === 'all' ? 'btn-primary-custom' : 'btn-light border'; ?>">
            All Bookings
          </a>
          <a href="<?php echo  url('user/my-bookings.php?status=pending'); ?>" class="btn btn-sm <?php echo  $statusFilter === 'pending' ? 'btn-primary-custom' : 'btn-light border'; ?>">
            Pending Approval
          </a>
          <a href="<?php echo  url('user/my-bookings.php?status=approved'); ?>" class="btn btn-sm <?php echo  $statusFilter === 'approved' ? 'btn-primary-custom' : 'btn-light border'; ?>">
            Approved
          </a>
          <a href="<?php echo  url('user/my-bookings.php?status=active'); ?>" class="btn btn-sm <?php echo  $statusFilter === 'active' ? 'btn-primary-custom' : 'btn-light border'; ?>">
            Active In-Hand
          </a>
          <a href="<?php echo  url('user/my-bookings.php?status=completed'); ?>" class="btn btn-sm <?php echo  $statusFilter === 'completed' ? 'btn-primary-custom' : 'btn-light border'; ?>">
            Completed
          </a>
          <a href="<?php echo  url('user/my-bookings.php?status=cancelled'); ?>" class="btn btn-sm <?php echo  $statusFilter === 'cancelled' ? 'btn-primary-custom' : 'btn-light border'; ?>">
            Cancelled
          </a>
        </div>

        <?php if (empty($bookings)): ?>
          <div class="card border-0 shadow-sm rounded-4 p-5 text-center bg-light-subtle my-3">
            <i class="fa-solid fa-calendar-xmark text-muted fs-1 mb-3"></i>
            <h5 class="fw-bold text-dark">No Bookings Found</h5>
            <p class="text-muted small mb-3">You don't have any rental bookings under the selected filter.</p>
            <div>
              <a href="<?php echo  url('explore.php'); ?>" class="btn btn-primary-custom btn-sm px-4">Browse Available Rentals</a>
            </div>
          </div>
        <?php else: ?>
          <div class="d-flex flex-column gap-3">
            <?php foreach ($bookings as $bk): ?>
              <?php
                $bkImg = $bk['primary_image'] ?: 'assets/images/placeholder.jpg';
                if (!str_starts_with($bkImg, 'http://') && !str_starts_with($bkImg, 'https://')) {
                  $bkImg = url($bkImg);
                }
              ?>
              <div class="card border rounded-4 p-4 shadow-sm bg-white">
                <div class="row align-items-center g-3">
                  <!-- Product Image & Details -->
                  <div class="col-md-2 col-4">
                    <img src="<?php echo  e($bkImg); ?>" alt="<?php echo  e($bk['product_title']); ?>" class="rounded-3 w-100" style="height: 90px; object-fit: cover;">
                  </div>

                  <div class="col-md-4 col-8">
                    <div class="d-flex align-items-center gap-2 mb-1">
                      <span class="font-monospace fw-bold text-primary small"><?php echo  e($bk['booking_code']); ?></span>
                      <span class="badge-status <?php echo  e($bk['status']); ?>"><?php echo  e($bk['status']); ?></span>
                    </div>
                    <h5 class="fw-bold text-dark mb-1">
                      <a href="<?php echo  url('product-details.php?id=' . $bk['product_id']); ?>" class="text-dark text-decoration-none">
                        <?php echo  e($bk['product_title']); ?>
                      </a>
                    </h5>
                    <div class="text-muted small">
                      <i class="fa-solid fa-user me-1 text-success"></i> Owner: <strong><?php echo  e($bk['owner_name']); ?></strong> (<?php echo  e($bk['owner_phone']); ?>)
                    </div>
                  </div>

                  <!-- Dates & Duration -->
                  <div class="col-md-3 col-6">
                    <small class="text-muted d-block fw-bold">Rental Dates</small>
                    <span class="text-dark fw-semibold small">
                      <?php echo  formatDate($bk['start_date']); ?> &rarr; <?php echo  formatDate($bk['end_date']); ?>
                    </span>
                    <small class="text-muted d-block"><?php echo  $bk['total_days']; ?> days rental</small>
                  </div>

                  <!-- Total Price & Deposit -->
                  <div class="col-md-3 col-6 text-md-end">
                    <div class="fs-5 fw-bold text-primary"><?php echo  formatPrice($bk['total_amount']); ?></div>
                    <small class="text-success d-block" style="font-size: 0.75rem;">Deposit: <?php echo  formatPrice($bk['security_deposit']); ?> (Refundable)</small>
                    <small class="text-muted d-block" style="font-size: 0.72rem;">Rental: <?php echo  formatPrice($bk['rental_amount']); ?></small>
                  </div>
                </div>

                <!-- Footer Actions -->
                <div class="d-flex flex-wrap justify-content-between align-items-center pt-3 mt-3 border-top gap-2">
                  <div class="small text-muted">
                    <i class="fa-solid fa-clock me-1"></i> Booked on <?php echo  formatDate($bk['created_at'], 'd M Y, H:i'); ?>
                    <?php if (!empty($bk['cancellation_reason'])): ?>
                      <span class="text-danger ms-2">(Reason: <?php echo  e($bk['cancellation_reason']); ?>)</span>
                    <?php endif; ?>
                  </div>

                  <div class="d-flex gap-2">
                    <a href="<?php echo  url('product-details.php?id=' . $bk['product_id']); ?>" class="btn btn-sm btn-outline-secondary">
                      View Item
                    </a>

                    <!-- Cancel button if pending or approved -->
                    <?php if (in_array($bk['status'], array('pending', 'approved'))): ?>
                      <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#cancelModal<?php echo  $bk['id']; ?>">
                        Cancel Booking
                      </button>

                      <!-- Cancel Modal -->
                      <div class="modal fade" id="cancelModal<?php echo  $bk['id']; ?>" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog">
                          <div class="modal-content rounded-4 border-0">
                            <div class="modal-header">
                              <h5 class="modal-title fw-bold text-dark">Cancel Booking #<?php echo  e($bk['booking_code']); ?></h5>
                              <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <form action="<?php echo  url('user/my-bookings.php'); ?>" method="POST">
                              <?php echo  csrf_field(); ?>
                              <input type="hidden" name="action" value="cancel_booking">
                              <input type="hidden" name="booking_id" value="<?php echo  $bk['id']; ?>">
                              <div class="modal-body">
                                <p class="text-muted small">Are you sure you want to cancel this booking? Your refundable payment will be released.</p>
                                <label class="form-label small fw-bold">Reason for Cancellation</label>
                                <textarea name="reason" class="form-control" rows="2" placeholder="e.g. Schedule changed / no longer required" required></textarea>
                              </div>
                              <div class="modal-footer">
                                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Keep Booking</button>
                                <button type="submit" class="btn btn-danger">Confirm Cancellation</button>
                              </div>
                            </form>
                          </div>
                        </div>
                      </div>
                    <?php endif; ?>

                    <!-- Leave Review if Completed and not reviewed -->
                    <?php if ($bk['status'] === 'completed' && empty($bk['review_id'])): ?>
                      <button class="btn btn-sm btn-warning text-dark fw-bold" data-bs-toggle="modal" data-bs-target="#reviewModal<?php echo  $bk['id']; ?>">
                        <i class="fa-solid fa-star me-1"></i> Leave Review
                      </button>

                      <!-- Review Modal -->
                      <div class="modal fade" id="reviewModal<?php echo  $bk['id']; ?>" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog">
                          <div class="modal-content rounded-4 border-0">
                            <div class="modal-header">
                              <h5 class="modal-title fw-bold text-dark">Rate & Review Item</h5>
                              <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <form action="<?php echo  url('user/my-bookings.php'); ?>" method="POST">
                              <?php echo  csrf_field(); ?>
                              <input type="hidden" name="action" value="submit_review">
                              <input type="hidden" name="booking_id" value="<?php echo  $bk['id']; ?>">
                              <input type="hidden" name="product_id" value="<?php echo  $bk['product_id']; ?>">
                              <div class="modal-body">
                                <div class="mb-3 text-center">
                                  <label class="form-label small fw-bold d-block">Your Rating</label>
                                  <select name="rating" class="form-select form-select-lg text-center fw-bold text-warning" required>
                                    <option value="5">⭐⭐⭐⭐⭐ (5 / 5 - Exceptional)</option>
                                    <option value="4">⭐⭐⭐⭐ (4 / 5 - Very Good)</option>
                                    <option value="3">⭐⭐⭐ (3 / 5 - Average)</option>
                                    <option value="2">⭐⭐ (2 / 5 - Below Average)</option>
                                    <option value="1">⭐ (1 / 5 - Poor)</option>
                                  </select>
                                </div>
                                <div class="mb-3">
                                  <label class="form-label small fw-bold">Your Review & Feedback</label>
                                  <textarea name="comment" class="form-control" rows="3" placeholder="Share how the product performed, owner's communication, and overall experience..." required></textarea>
                                </div>
                              </div>
                              <div class="modal-footer">
                                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-primary-custom">Publish Review</button>
                              </div>
                            </form>
                          </div>
                        </div>
                      </div>
                    <?php elseif (!empty($bk['review_id'])): ?>
                      <span class="badge bg-light text-muted border py-2 px-3 small">
                        <i class="fa-solid fa-check text-success me-1"></i> Reviewed
                      </span>
                    <?php endif; ?>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </main>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

