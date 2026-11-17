<?php
/**
 * ReUseHub - Owner Incoming Booking Requests Management
 */

$pageTitle = 'Booking Requests';
$activePage = 'owner_requests';
require_once __DIR__ . '/../includes/header.php';

requireOwner();
$user = currentUser();
$pdo = getDB();

$ownerId = $user['id'];
$statusFilter = trim(isset($_GET['status']) ? $_GET['status'] : 'all');

// Process Owner Actions: Approve, Reject, Handover (Active), Complete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    verify_csrf_token();
    $bookingId = (int)(isset($_POST['booking_id']) ? $_POST['booking_id'] : 0);
    $action = $_POST['action'];

    // Verify ownership of the booked item
    $check = $pdo->prepare("SELECT b.*, p.title as product_title, u.name as renter_name, u.id as renter_user_id 
                           FROM bookings b 
                           JOIN products p ON b.product_id = p.id 
                           JOIN users u ON b.renter_id = u.id 
                           WHERE b.id = ? AND b.owner_id = ?");
    $check->execute(array($bookingId, $ownerId));
    $bk = $check->fetch();

    if ($bk) {
        if ($action === 'approve') {
            $pdo->prepare("UPDATE bookings SET status = 'approved', updated_at = NOW() WHERE id = ?")->execute(array($bookingId));
            createNotification(
                $bk['renter_user_id'],
                'Booking Request Approved!',
                "Great news! Your booking #{$bk['booking_code']} for '{$bk['product_title']}' has been accepted by {$user['name']}.",
                'user/my-bookings.php'
            );
            setFlash('success', "Booking #{$bk['booking_code']} approved.");
        } elseif ($action === 'reject') {
            $reason = trim(isset($_POST['reject_reason']) ? $_POST['reject_reason'] : 'Owner unavailable on selected dates');
            $pdo->prepare("UPDATE bookings SET status = 'rejected', cancellation_reason = ?, updated_at = NOW() WHERE id = ?")->execute(array($reason, $bookingId));
            $pdo->prepare("UPDATE payments SET payment_status = 'refunded' WHERE booking_id = ?")->execute(array($bookingId));
            createNotification(
                $bk['renter_user_id'],
                'Booking Request Declined',
                "Your booking #{$bk['booking_code']} for '{$bk['product_title']}' could not be accepted. Reason: {$reason}",
                'user/my-bookings.php'
            );
            setFlash('info', "Booking #{$bk['booking_code']} declined.");
        } elseif ($action === 'handover') {
            $pdo->prepare("UPDATE bookings SET status = 'active', updated_at = NOW() WHERE id = ?")->execute(array($bookingId));
            createNotification(
                $bk['renter_user_id'],
                'Rental Started (Item Handed Over)',
                "You have received '{$bk['product_title']}'. Enjoy your rental!",
                'user/my-bookings.php'
            );
            setFlash('success', "Booking #{$bk['booking_code']} is now active.");
        } elseif ($action === 'complete') {
            $pdo->prepare("UPDATE bookings SET status = 'completed', updated_at = NOW() WHERE id = ?")->execute(array($bookingId));
            createNotification(
                $bk['renter_user_id'],
                'Rental Completed',
                "Product '{$bk['product_title']}' has been returned safely and security deposit processed. Please leave a review!",
                'user/my-bookings.php'
            );
            setFlash('success', "Rental #{$bk['booking_code']} marked as completed.");
        }

        header("Location: " . url('owner/booking-requests.php'));
        exit();
    } else {
        setFlash('danger', 'Booking not found or access denied.');
    }
}

// Fetch Booking Requests
$where = array("b.owner_id = ?");
$params = array($ownerId);

if ($statusFilter !== 'all' && in_array($statusFilter, array('pending', 'approved', 'active', 'completed', 'rejected', 'cancelled'))) {
    $where[] = "b.status = ?";
    $params[] = $statusFilter;
}

$whereSql = implode(" AND ", $where);

$stmt = $pdo->prepare("SELECT b.*, p.title as product_title, p.location as product_location,
                       u.name as renter_name, u.phone as renter_phone, u.email as renter_email, u.city as renter_city,
                       (SELECT image_path FROM product_images WHERE product_id = p.id ORDER BY is_primary DESC, id ASC LIMIT 1) as primary_image
                       FROM bookings b
                       JOIN products p ON b.product_id = p.id
                       JOIN users u ON b.renter_id = u.id
                       WHERE {$whereSql}
                       ORDER BY b.created_at DESC");
$stmt->execute($params);
$requests = $stmt->fetchAll();
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
            <a href="<?php echo  url('owner/my-products.php'); ?>" class="sidebar-link">
              <i class="fa-solid fa-box-open"></i> My Listed Products
            </a>
          </li>
          <li class="sidebar-menu-item">
            <a href="<?php echo  url('list-item.php'); ?>" class="sidebar-link">
              <i class="fa-solid fa-circle-plus"></i> Add New Product
            </a>
          </li>
          <li class="sidebar-menu-item">
            <a href="<?php echo  url('owner/booking-requests.php'); ?>" class="sidebar-link active">
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
            <h3 class="fw-bold text-dark mb-1">Incoming Booking Requests</h3>
            <p class="text-muted small mb-0">Approve or decline requests, communicate with renters, and track active handovers.</p>
          </div>
        </div>

        <!-- Filter Pills -->
        <div class="d-flex flex-wrap gap-2 mb-4 pb-2 border-bottom">
          <a href="<?php echo  url('owner/booking-requests.php'); ?>" class="btn btn-sm <?php echo  $statusFilter === 'all' ? 'btn-primary-custom' : 'btn-light border'; ?>">
            All Requests
          </a>
          <a href="<?php echo  url('owner/booking-requests.php?status=pending'); ?>" class="btn btn-sm <?php echo  $statusFilter === 'pending' ? 'btn-primary-custom' : 'btn-light border'; ?>">
            Pending Action
          </a>
          <a href="<?php echo  url('owner/booking-requests.php?status=approved'); ?>" class="btn btn-sm <?php echo  $statusFilter === 'approved' ? 'btn-primary-custom' : 'btn-light border'; ?>">
            Approved (Awaiting Handover)
          </a>
          <a href="<?php echo  url('owner/booking-requests.php?status=active'); ?>" class="btn btn-sm <?php echo  $statusFilter === 'active' ? 'btn-primary-custom' : 'btn-light border'; ?>">
            Active In-Hand
          </a>
          <a href="<?php echo  url('owner/booking-requests.php?status=completed'); ?>" class="btn btn-sm <?php echo  $statusFilter === 'completed' ? 'btn-primary-custom' : 'btn-light border'; ?>">
            Completed
          </a>
        </div>

        <?php if (empty($requests)): ?>
          <div class="card border-0 shadow-sm rounded-4 p-5 text-center bg-light-subtle my-3">
            <i class="fa-solid fa-inbox text-muted fs-1 mb-3"></i>
            <h5 class="fw-bold text-dark">No Booking Requests</h5>
            <p class="text-muted small mb-0">There are no rental requests matching this filter right now.</p>
          </div>
        <?php else: ?>
          <div class="d-flex flex-column gap-3">
            <?php foreach ($requests as $rq): ?>
              <?php
                $rqImg = $rq['primary_image'] ?: 'assets/images/placeholder.jpg';
                if (!str_starts_with($rqImg, 'http://') && !str_starts_with($rqImg, 'https://')) {
                  $rqImg = url($rqImg);
                }
              ?>
              <div class="card border rounded-4 p-4 shadow-sm bg-white">
                <div class="row align-items-center g-3">
                  <div class="col-md-2 col-4">
                    <img src="<?php echo  e($rqImg); ?>" alt="<?php echo  e($rq['product_title']); ?>" class="rounded-3 w-100" style="height: 90px; object-fit: cover;">
                  </div>

                  <div class="col-md-4 col-8">
                    <div class="d-flex align-items-center gap-2 mb-1">
                      <span class="font-monospace fw-bold text-primary small"><?php echo  e($rq['booking_code']); ?></span>
                      <span class="badge-status <?php echo  e($rq['status']); ?>"><?php echo  e($rq['status']); ?></span>
                    </div>
                    <h5 class="fw-bold text-dark mb-1"><?php echo  e($rq['product_title']); ?></h5>
                    <div class="small text-muted">
                      <i class="fa-solid fa-user text-success me-1"></i> Renter: <strong><?php echo  e($rq['renter_name']); ?></strong> (<?php echo  e($rq['renter_city']); ?>)
                    </div>
                    <div class="small text-muted">
                      <i class="fa-solid fa-phone me-1"></i> <a href="tel:<?php echo  e($rq['renter_phone']); ?>" class="text-decoration-none"><?php echo  e($rq['renter_phone']); ?></a>
                    </div>
                  </div>

                  <div class="col-md-3 col-6">
                    <small class="text-muted fw-bold d-block">Requested Dates</small>
                    <span class="fw-semibold text-dark small">
                      <?php echo  formatDate($rq['start_date']); ?> &rarr; <?php echo  formatDate($rq['end_date']); ?>
                    </span>
                    <small class="text-muted d-block"><?php echo  $rq['total_days']; ?> days duration</small>
                    <?php if (!empty($rq['notes'])): ?>
                      <div class="mt-2 p-2 rounded bg-light border small text-muted fst-italic">
                        "<?php echo  e($rq['notes']); ?>"
                      </div>
                    <?php endif; ?>
                  </div>

                  <div class="col-md-3 col-6 text-md-end">
                    <small class="text-muted fw-bold d-block">Your Rental Earnings</small>
                    <div class="fs-4 fw-bold text-success"><?php echo  formatPrice($rq['rental_amount']); ?></div>
                    <small class="text-muted d-block" style="font-size: 0.72rem;">Security Deposit: <?php echo  formatPrice($rq['security_deposit']); ?></small>
                  </div>
                </div>

                <!-- Action Bar -->
                <div class="d-flex flex-wrap justify-content-between align-items-center pt-3 mt-3 border-top gap-2">
                  <div class="small text-muted">
                    Received on <?php echo  formatDate($rq['created_at'], 'd M Y, H:i'); ?>
                  </div>

                  <div class="d-flex gap-2">
                    <?php if ($rq['status'] === 'pending'): ?>
                      <!-- Approve Button -->
                      <form action="<?php echo  url('owner/booking-requests.php'); ?>" method="POST" class="d-inline">
                        <?php echo  csrf_field(); ?>
                        <input type="hidden" name="action" value="approve">
                        <input type="hidden" name="booking_id" value="<?php echo  $rq['id']; ?>">
                        <button type="submit" class="btn btn-sm btn-success px-3">
                          <i class="fa-solid fa-check me-1"></i> Accept Request
                        </button>
                      </form>

                      <!-- Reject Button with Modal -->
                      <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#rejectModal<?php echo  $rq['id']; ?>">
                        <i class="fa-solid fa-xmark me-1"></i> Decline
                      </button>

                      <div class="modal fade" id="rejectModal<?php echo  $rq['id']; ?>" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog">
                          <div class="modal-content rounded-4 border-0">
                            <div class="modal-header">
                              <h5 class="modal-title fw-bold text-dark">Decline Booking #<?php echo  e($rq['booking_code']); ?></h5>
                              <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <form action="<?php echo  url('owner/booking-requests.php'); ?>" method="POST">
                              <?php echo  csrf_field(); ?>
                              <input type="hidden" name="action" value="reject">
                              <input type="hidden" name="booking_id" value="<?php echo  $rq['id']; ?>">
                              <div class="modal-body text-start">
                                <label class="form-label small fw-bold">Reason for declining</label>
                                <textarea name="reject_reason" class="form-control" rows="2" placeholder="Item in repair, personal use on these dates, etc." required></textarea>
                              </div>
                              <div class="modal-footer">
                                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-danger">Decline Request</button>
                              </div>
                            </form>
                          </div>
                        </div>
                      </div>

                    <?php elseif ($rq['status'] === 'approved'): ?>
                      <!-- Handover Button -->
                      <form action="<?php echo  url('owner/booking-requests.php'); ?>" method="POST" class="d-inline">
                        <?php echo  csrf_field(); ?>
                        <input type="hidden" name="action" value="handover">
                        <input type="hidden" name="booking_id" value="<?php echo  $rq['id']; ?>">
                        <button type="submit" class="btn btn-sm btn-primary px-3">
                          <i class="fa-solid fa-handshake me-1"></i> Mark as Handed Over (Active)
                        </button>
                      </form>

                    <?php elseif ($rq['status'] === 'active'): ?>
                      <!-- Complete Button -->
                      <form action="<?php echo  url('owner/booking-requests.php'); ?>" method="POST" class="d-inline">
                        <?php echo  csrf_field(); ?>
                        <input type="hidden" name="action" value="complete">
                        <input type="hidden" name="booking_id" value="<?php echo  $rq['id']; ?>">
                        <button type="submit" class="btn btn-sm btn-success px-3">
                          <i class="fa-solid fa-circle-check me-1"></i> Item Returned Safely (Complete)
                        </button>
                      </form>
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

