<?php
/**
 * ReUseHub - Contact, Support, Community Guidelines & Report Processor
 */

$pageTitle = 'Contact Us & Community Guidelines';
$activePage = 'contact';
require_once __DIR__ . '/includes/header.php';

$pdo = getDB();
$successMsg = '';
$errorMsg = '';

// Process Report submission from product modal
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'report_product') {
    verify_csrf_token();
    $productId = (int)(isset($_POST['product_id']) ? $_POST['product_id'] : 0);
    $reason = trim(isset($_POST['reason']) ? $_POST['reason'] : '');
    $details = trim(isset($_POST['details']) ? $_POST['details'] : '');
    $userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 1; // Default to user or platform guest

    if ($productId > 0 && !empty($reason)) {
        $stmt = $pdo->prepare("INSERT INTO reports (product_id, user_id, reason, details) VALUES (?, ?, ?, ?)");
        $stmt->execute(array($productId, $userId, $reason, $details));
        setFlash('success', 'Thank you for your report. Our community moderation team will review this listing promptly.');
        header("Location: " . url('product-details.php?id=' . $productId));
        exit();
    }
}

// Process Contact message
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'send_message') {
    verify_csrf_token();
    $name = trim(isset($_POST['name']) ? $_POST['name'] : '');
    $email = trim(isset($_POST['email']) ? $_POST['email'] : '');
    $subject = trim(isset($_POST['subject']) ? $_POST['subject'] : '');
    $message = trim(isset($_POST['message']) ? $_POST['message'] : '');

    if (!empty($name) && !empty($email) && !empty($message)) {
        setFlash('success', 'Thank you, ' . $name . '! Your inquiry has been received. Our team will get back to you within 24 hours.');
        header("Location: " . url('contact.php'));
        exit();
    } else {
        $errorMsg = 'Please complete all fields.';
    }
}
?>

<div class="py-5" style="background-color: var(--bg-main);">
  <div class="container py-2">
    <!-- Header -->
    <div class="text-center max-w-700 mx-auto mb-5">
      <span class="text-secondary fw-bold text-uppercase small tracking-wide">Get in Touch</span>
      <h2 class="fw-bold mt-1">We're Here to Help</h2>
      <p class="text-muted">Have a question about renting, listing products, security deposits, or partnership in Kerala? Reach out to our dedicated team.</p>
    </div>

    <?php if (!empty($errorMsg)): ?>
      <div class="alert alert-danger shadow-sm rounded-3 mb-4">
        <?php echo e($errorMsg); ?>
      </div>
    <?php endif; ?>

    <div class="row g-4 mb-5">
      <!-- Contact Cards -->
      <div class="col-lg-4">
        <div class="card border-0 shadow-sm rounded-4 p-4 bg-white h-100">
          <div class="rounded-3 bg-primary-light text-primary p-3 mb-3 d-inline-flex align-items-center justify-content-center" style="width: 50px; height: 50px; font-size: 1.4rem;">
            <i class="fa-solid fa-location-dot"></i>
          </div>
          <h5 class="fw-bold text-dark">Our Headquarters</h5>
          <p class="text-muted small mb-2">ReUseHub Kerala Technologies</p>
          <p class="text-secondary-subtle small mb-0">Panampilly Nagar, Main Avenue, Kochi, Kerala – 682036, India</p>
        </div>
      </div>

      <div class="col-lg-4">
        <div class="card border-0 shadow-sm rounded-4 p-4 bg-white h-100">
          <div class="rounded-3 bg-primary-light text-primary p-3 mb-3 d-inline-flex align-items-center justify-content-center" style="width: 50px; height: 50px; font-size: 1.4rem;">
            <i class="fa-solid fa-envelope"></i>
          </div>
          <h5 class="fw-bold text-dark">Email Support</h5>
          <p class="text-muted small mb-2">General: support@reusehub.in</p>
          <p class="text-muted small mb-0">Verification: trust@reusehub.in</p>
        </div>
      </div>

      <div class="col-lg-4">
        <div class="card border-0 shadow-sm rounded-4 p-4 bg-white h-100">
          <div class="rounded-3 bg-primary-light text-primary p-3 mb-3 d-inline-flex align-items-center justify-content-center" style="width: 50px; height: 50px; font-size: 1.4rem;">
            <i class="fa-solid fa-phone"></i>
          </div>
          <h5 class="fw-bold text-dark">Direct Phone Line</h5>
          <p class="text-muted small mb-2">+91 98460 00001</p>
          <p class="text-secondary-subtle small mb-0">Monday to Saturday: 9:00 AM – 7:00 PM IST</p>
        </div>
      </div>
    </div>

    <!-- Contact Form & Community FAQs -->
    <div class="row g-5">
      <div class="col-lg-6">
        <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
          <h4 class="fw-bold text-dark mb-3">Send Us a Direct Message</h4>
          <p class="text-muted small mb-4">Fill out the form below and we will respond promptly.</p>

          <form action="<?php echo url('contact.php'); ?>" method="POST">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="action" value="send_message">

            <div class="mb-3">
              <label class="form-label small fw-bold text-dark">Your Name</label>
              <input type="text" name="name" class="form-control" placeholder="e.g. Favas Rahman" required>
            </div>

            <div class="mb-3">
              <label class="form-label small fw-bold text-dark">Email Address</label>
              <input type="email" name="email" class="form-control" placeholder="name@example.com" required>
            </div>

            <div class="mb-3">
              <label class="form-label small fw-bold text-dark">Subject</label>
              <input type="text" name="subject" class="form-control" placeholder="How can we help?" required>
            </div>

            <div class="mb-4">
              <label class="form-label small fw-bold text-dark">Message</label>
              <textarea name="message" class="form-control" rows="4" placeholder="Write your question or request..." required></textarea>
            </div>

            <button type="submit" class="btn btn-primary-custom w-100 py-2">
              <i class="fa-solid fa-paper-plane me-1"></i> Send Message
            </button>
          </form>
        </div>
      </div>

      <!-- FAQ Accordion -->
      <div class="col-lg-6">
        <h4 class="fw-bold text-dark mb-4">Community Guidelines & FAQ</h4>

        <div class="accordion" id="faqAccordion">
          <div class="accordion-item border-0 mb-3 shadow-sm rounded-3 overflow-hidden">
            <h2 class="accordion-header">
              <button class="accordion-button fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                How does the security deposit work?
              </button>
            </h2>
            <div id="faq1" class="accordion-collapse collapse show" data-bs-parent="#faqAccordion">
              <div class="accordion-body text-muted small">
                Every owner sets a security deposit representing product safety. When a booking request is made, the deposit is recorded with the rental charges. Once the item is returned on time in the agreed condition, the full security deposit is released back to the renter.
              </div>
            </div>
          </div>

          <div class="accordion-item border-0 mb-3 shadow-sm rounded-3 overflow-hidden">
            <h2 class="accordion-header">
              <button class="accordion-button collapsed fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                Can I both rent and list items on the same account?
              </button>
            </h2>
            <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
              <div class="accordion-body text-muted small">
                Yes! Every ReUseHub user account is dual-purpose. You can rent a DSLR camera today and list your mountain bike or camping tent tomorrow. You have access to both the Renter Dashboard and Owner Dashboard seamlessly.
              </div>
            </div>
          </div>

          <div class="accordion-item border-0 mb-3 shadow-sm rounded-3 overflow-hidden">
            <h2 class="accordion-header">
              <button class="accordion-button collapsed fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
                How are new product listings verified?
              </button>
            </h2>
            <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
              <div class="accordion-body text-muted small">
                To guarantee marketplace quality and prevent fraud or damaged items, every newly submitted listing is initially submitted for administrator review. Our moderation team verifies product descriptions, condition, and image authenticity before making it live.
              </div>
            </div>
          </div>

          <div class="accordion-item border-0 mb-3 shadow-sm rounded-3 overflow-hidden">
            <h2 class="accordion-header">
              <button class="accordion-button collapsed fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#faq4">
                What happens if an item is returned damaged?
              </button>
            </h2>
            <div id="faq4" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
              <div class="accordion-body text-muted small">
                In the rare case of damage beyond standard wear-and-tear, the owner can file a dispute with our platform mediation desk. Repair or replacement costs are deducted from the refundable security deposit based on item valuation.
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
