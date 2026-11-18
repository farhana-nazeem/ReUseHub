<?php
/**
 * ReUseHub - Rental Booking & Demo Payment Flow
 */

$pageTitle = 'Confirm Rental & Payment';
$activePage = 'booking';
require_once __DIR__ . '/includes/header.php';

// Must be signed in to book
requireLogin();
$user = currentUser();
$pdo = getDB();

$productId = (int)(isset($_GET['product_id']) ? $_GET['product_id'] : (isset($_POST['product_id']) ? $_POST['product_id'] : 0));
if ($productId <= 0) {
    setFlash('warning', 'Please select an item to rent.');
    header("Location: " . url('explore.php'));
    exit();
}

// Fetch product details
$stmt = $pdo->prepare("SELECT p.*, c.name as category_name, u.name as owner_name, u.email as owner_email, u.phone as owner_phone,
                       (SELECT image_path FROM product_images WHERE product_id = p.id ORDER BY is_primary DESC, id ASC LIMIT 1) as primary_image
                       FROM products p 
                       JOIN categories c ON p.category_id = c.id
                       JOIN users u ON p.owner_id = u.id
                       WHERE p.id = ? AND p.status = 'approved'");
$stmt->execute(array($productId));
$product = $stmt->fetch();

if (!$product) {
    setFlash('danger', 'The product you are trying to rent is not available.');
    header("Location: " . url('explore.php'));
    exit();
}

// Booking Rule: Owner cannot rent own product
if ($product['owner_id'] == $user['id']) {
    setFlash('warning', 'You cannot rent your own product listing.');
    header("Location: " . url('product-details.php?id=' . $productId));
    exit();
}

// Booking Rule: Check availability status
if (!$product['is_available']) {
    setFlash('warning', 'This item is currently rented out or marked unavailable by the owner.');
    header("Location: " . url('product-details.php?id=' . $productId));
    exit();
}

// Read dates
$startDate = trim(isset($_GET['start_date']) ? $_GET['start_date'] : (isset($_POST['start_date']) ? $_POST['start_date'] : date('Y-m-d', strtotime('+1 day'))));
$endDate = trim(isset($_GET['end_date']) ? $_GET['end_date'] : (isset($_POST['end_date']) ? $_POST['end_date'] : date('Y-m-d', strtotime('+3 days'))));
$notes = trim(isset($_POST['notes']) ? $_POST['notes'] : '');

// Server-side validation
$today = date('Y-m-d');
$errors = array();

if ($startDate < $today) {
    $errors[] = 'Rental start date cannot be in the past.';
}
if ($endDate < $startDate) {
    $errors[] = 'Rental end date cannot be earlier than start date.';
}

// Verify overlapping bookings
if (empty($errors)) {
    if (!isDateRangeAvailable($productId, $startDate, $endDate)) {
        $errors[] = 'The selected date range conflicts with an existing approved booking. Please choose different dates.';
    }
}

// Perform Server-Side Calculation (Never trust client pricing inputs)
$calculation = calculateRental($product['daily_price'], $startDate, $endDate, $product['security_deposit']);
$days = $calculation['days'];
$rentalAmount = $calculation['rental_amount'];
$depositAmount = $calculation['security_deposit'];
$serviceFee = $calculation['service_fee'];
$totalAmount = $calculation['total_amount'];

// Handle Booking & Demo Payment Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'confirm_booking') {
    verify_csrf_token();

    $paymentMethod = trim(isset($_POST['payment_method']) ? $_POST['payment_method'] : 'UPI');
    $upiId = trim(isset($_POST['upi_id']) ? $_POST['upi_id'] : '');

    if (empty($errors)) {
        $bookingCode = generateBookingCode();
        $ownerId = $product['owner_id'];

        // Begin transaction
        $pdo->beginTransaction();

        try {
            // Insert Booking
            $bookStmt = $pdo->prepare("INSERT INTO bookings 
                (booking_code, product_id, renter_id, owner_id, start_date, end_date, total_days, daily_rate, rental_amount, security_deposit, service_fee, total_amount, status, notes) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?)");
            $bookStmt->execute(array(
                $bookingCode,
                $productId,
                $user['id'],
                $ownerId,
                $startDate,
                $endDate,
                $days,
                $product['daily_price'],
                $rentalAmount,
                $depositAmount,
                $serviceFee,
                $totalAmount,
                $notes
            ));
            $bookingId = $pdo->lastInsertId();

            // Insert Demo Payment Transaction
            $txnId = 'TXN_RUH_' . strtoupper(bin2hex(random_bytes(6)));
            $payStmt = $pdo->prepare("INSERT INTO payments 
                (booking_id, transaction_id, payment_method, amount, deposit_amount, payment_status, payment_gateway_ref, payment_date) 
                VALUES (?, ?, ?, ?, ?, 'paid', 'DEMO_PAYMENT_SUCCESS', NOW())");
            $payStmt->execute(array(
                $bookingId,
                $txnId,
                $paymentMethod,
                $rentalAmount + $serviceFee,
                $depositAmount
            ));

            // Create notification for Owner
            createNotification(
                $ownerId,
                'New Rental Request Received',
                "{$user['name']} has requested to rent '{$product['title']}' for {$days} days ({$startDate} to {$endDate}).",
                'owner/booking-requests.php'
            );

            // Create notification for Renter
            createNotification(
                $user['id'],
                'Rental Request Placed Successfully',
                "Your booking request #{$bookingCode} for '{$product['title']}' was sent to {$product['owner_name']}.",
                'user/my-bookings.php'
            );

            $pdo->commit();

            setFlash('success', "Rental booking #{$bookingCode} submitted! The owner will review and confirm your request.");
            header("Location: " . url('user/my-bookings.php'));
            exit();
        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = 'Booking failed: ' . $e->getMessage();
        }
    }
}

$primaryImg = $product['primary_image'] ?: 'assets/images/placeholder.jpg';
if (!str_starts_with($primaryImg, 'http://') && !str_starts_with($primaryImg, 'https://')) {
    $primaryImg = url($primaryImg);
}
?>

<div class="py-5" style="background-color: var(--bg-main);">
  <div class="container py-2">
    <!-- Header -->
    <div class="mb-4">
      <h2 class="fw-bold mb-1">Confirm Rental & Payment</h2>
      <p class="text-muted small mb-0">Review your rental dates, price breakdown, and choose your preferred demo payment option.</p>
    </div>

    <?php if (!empty($errors)): ?>
      <div class="alert alert-danger shadow-sm rounded-3 mb-4">
        <ul class="mb-0 ps-3 small">
          <?php foreach ($errors as $err): ?>
            <li><?php echo  e($err); ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <div class="row g-4">
      <!-- Item Summary & Dates Review (Left Column) -->
      <div class="col-lg-7">
        <div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-4">
          <h5 class="fw-bold text-dark mb-3"><i class="fa-solid fa-box text-success me-2"></i> Item Selected</h5>

          <div class="d-flex gap-3 align-items-center mb-4 p-3 rounded-3 bg-light border">
            <img src="<?php echo  e($primaryImg); ?>" alt="<?php echo  e($product['title']); ?>" class="rounded-3" style="width: 90px; height: 80px; object-fit: cover;">
            <div>
              <span class="badge bg-success-subtle text-success small mb-1"><?php echo  e($product['category_name']); ?></span>
              <h5 class="fw-bold text-dark mb-1"><?php echo  e($product['title']); ?></h5>
              <div class="text-muted small">
                <i class="fa-solid fa-location-dot text-success me-1"></i> <?php echo  e($product['location']); ?> &bull; Owner: <strong><?php echo  e($product['owner_name']); ?></strong>
              </div>
            </div>
          </div>

          <!-- Date Adjuster -->
          <form action="<?php echo  url('booking.php'); ?>" method="GET" class="row g-3 mb-3" id="dateAdjusterForm">
            <input type="hidden" name="product_id" value="<?php echo  $product['id']; ?>">

            <div class="col-md-5">
              <label class="form-label small fw-bold text-dark">Rental Start Date</label>
              <input type="date" name="start_date" id="startDateInput" class="form-control form-control-sm" value="<?php echo  e($startDate); ?>" min="<?php echo  $today; ?>" onchange="onStartDateChange(this)">
            </div>

            <div class="col-md-5">
              <label class="form-label small fw-bold text-dark">Rental End Date</label>
              <input type="date" name="end_date" id="endDateInput" class="form-control form-control-sm" value="<?php echo  e($endDate); ?>" min="<?php echo  e($startDate); ?>" onchange="this.form.submit()">
            </div>

            <div class="col-md-2 d-flex align-items-end">
              <button type="submit" class="btn btn-outline-secondary btn-sm w-100">Update</button>
            </div>
          </form>

          <script>
          function onStartDateChange(startInput) {
            var endInput = document.getElementById('endDateInput');
            var newStart = startInput.value;

            // Update end date's minimum to the new start date
            endInput.min = newStart;

            // If end date is now before or equal to start date, push it forward by 1 day
            if (!endInput.value || endInput.value <= newStart) {
              var d = new Date(newStart);
              d.setDate(d.getDate() + 1);
              var yyyy = d.getFullYear();
              var mm = ('0' + (d.getMonth() + 1)).slice(-2);
              var dd = ('0' + d.getDate()).slice(-2);
              endInput.value = yyyy + '-' + mm + '-' + dd;
            }

            startInput.form.submit();
          }
          </script>

          <div class="p-3 rounded-3 bg-light-subtle border">
            <div class="d-flex justify-content-between align-items-center">
              <span class="text-muted small"><i class="fa-solid fa-calendar-days text-primary me-2"></i> Calculated Rental Period:</span>
              <span class="fw-bold text-dark"><?php echo  formatDate($startDate); ?> &rarr; <?php echo  formatDate($endDate); ?> (<?php echo  $days; ?> <?php echo  $days === 1 ? 'day' : 'days'; ?>)</span>
            </div>
          </div>
        </div>

        <!-- Payment Method Selection -->
        <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-bold text-dark mb-0"><i class="fa-solid fa-credit-card text-success me-2"></i> Payment Method</h5>
            <span class="badge bg-warning text-dark fw-bold">Demo Mode Active</span>
          </div>

          <div class="alert alert-light border small text-muted mb-4">
            <i class="fa-solid fa-circle-info text-primary me-1"></i> <strong>Demo Payment Environment:</strong> This interface provides a realistic transaction flow without connecting to a live bank account or debiting real money. Designed for smooth production integration with Razorpay / Cashfree.
          </div>

          <form action="<?php echo  url('booking.php'); ?>" method="POST" id="confirm-booking-form">
            <?php echo  csrf_field(); ?>
            <input type="hidden" name="action" value="confirm_booking">
            <input type="hidden" name="product_id" value="<?php echo  $product['id']; ?>">
            <input type="hidden" name="start_date" value="<?php echo  e($startDate); ?>">
            <input type="hidden" name="end_date" value="<?php echo  e($endDate); ?>">

            <!-- Payment Options Radio -->
            <div class="mb-4">
              <div class="form-check p-3 border rounded-3 mb-2 bg-light-subtle">
                <input class="form-check-input ms-0 me-3" type="radio" name="payment_method" id="payMethodUPI" value="UPI" checked>
                <label class="form-check-label fw-bold text-dark w-100 cursor-pointer" for="payMethodUPI">
                  <div class="d-flex justify-content-between align-items-center">
                    <span><i class="fa-brands fa-google-pay text-primary fs-5 me-2"></i> UPI (Google Pay / PhonePe / Paytm)</span>
                    <span class="badge bg-success-subtle text-success">Recommended</span>
                  </div>
                  <div class="text-muted fw-normal small mt-1">Instant digital confirmation via UPI ID simulation.</div>
                </label>
              </div>

              <div class="form-check p-3 border rounded-3 mb-2 bg-light-subtle">
                <input class="form-check-input ms-0 me-3" type="radio" name="payment_method" id="payMethodCard" value="Card">
                <label class="form-check-label fw-bold text-dark w-100 cursor-pointer" for="payMethodCard">
                  <div class="d-flex justify-content-between align-items-center">
                    <span><i class="fa-solid fa-credit-card text-secondary fs-5 me-2"></i> Credit / Debit Card (Demo)</span>
                    <span class="text-muted small">Visa / Mastercard / RuPay</span>
                  </div>
                  <div class="text-muted fw-normal small mt-1">Simulated test card verification without storing card credentials.</div>
                </label>
              </div>

              <div class="form-check p-3 border rounded-3 mb-2 bg-light-subtle">
                <input class="form-check-input ms-0 me-3" type="radio" name="payment_method" id="payMethodCOD" value="Cash on Pickup">
                <label class="form-check-label fw-bold text-dark w-100 cursor-pointer" for="payMethodCOD">
                  <div>
                    <span><i class="fa-solid fa-hand-holding-dollar text-success fs-5 me-2"></i> Pay on Pickup</span>
                  </div>
                  <div class="text-muted fw-normal small mt-1">Pay rental amount & security deposit directly to the owner at handover.</div>
                </label>
              </div>
            </div>

            <div class="mb-4">
              <label class="form-label small fw-bold text-dark">Message or Note for Owner (Optional)</label>
              <textarea name="notes" class="form-control" rows="2" placeholder="e.g. Need this for a 2-day outdoor wedding shoot in Fort Kochi..."><?php echo  e($notes); ?></textarea>
            </div>

            <button type="submit" class="btn btn-primary-custom w-100 py-3 fs-5 shadow">
              <i class="fa-solid fa-lock me-2"></i> Confirm Booking & Pay <?php echo  formatPrice($totalAmount); ?>
            </button>
          </form>
        </div>
      </div>

      <!-- Right Column: Price Breakdown Summary -->
      <div class="col-lg-5">
        <div class="card border-0 shadow-lg rounded-4 p-4 bg-white sticky-top" style="top: 85px;">
          <h5 class="fw-bold text-dark mb-4 pb-2 border-bottom">Booking Charges Summary</h5>

          <div class="d-flex justify-content-between mb-3 text-muted small">
            <span>Daily Rate</span>
            <span class="fw-bold text-dark"><?php echo  formatPrice($product['daily_price']); ?></span>
          </div>

          <div class="d-flex justify-content-between mb-3 text-muted small">
            <span>Rental Duration</span>
            <span class="fw-bold text-dark"><?php echo  $days; ?> <?php echo  $days === 1 ? 'day' : 'days'; ?></span>
          </div>

          <div class="d-flex justify-content-between mb-3 text-muted small">
            <span>Rental Charges (<?php echo  formatPrice($product['daily_price']); ?> &times; <?php echo  $days; ?>)</span>
            <span class="fw-bold text-dark"><?php echo  formatPrice($rentalAmount); ?></span>
          </div>

          <div class="d-flex justify-content-between mb-3 text-muted small">
            <span>Refundable Security Deposit</span>
            <span class="fw-bold text-success"><?php echo  formatPrice($depositAmount); ?></span>
          </div>

          <div class="d-flex justify-content-between mb-3 text-muted small">
            <span>Marketplace Platform Fee (3%)</span>
            <span class="fw-bold text-dark"><?php echo  formatPrice($serviceFee); ?></span>
          </div>

          <hr class="my-3">

          <div class="d-flex justify-content-between align-items-baseline mb-4">
            <div>
              <span class="fs-5 fw-bold text-dark">Total Amount</span>
              <div class="text-muted" style="font-size: 0.75rem;">Includes refundable deposit</div>
            </div>
            <span class="fs-3 fw-bold text-primary"><?php echo  formatPrice($totalAmount); ?></span>
          </div>

          <div class="p-3 rounded-3 bg-light border small text-muted">
            <div class="d-flex align-items-center gap-2 mb-1 text-dark fw-bold">
              <i class="fa-solid fa-shield text-success"></i> ReUseHub Protection Guarantee
            </div>
            Your security deposit of <?php echo  formatPrice($depositAmount); ?> is held safely until you return the item in good order.
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

