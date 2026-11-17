<?php
/**
 * ReUseHub - Global Footer Component
 */
?>
</main>

<footer class="footer-reusehub">
  <div class="container">
    <div class="row g-4 mb-4">
      <!-- Col 1: Brand Info -->
      <div class="col-lg-4 col-md-6">
        <a class="navbar-brand-logo mb-3 d-inline-flex" href="<?php echo url(); ?>">
          <div class="brand-icon">
            <i class="fa-solid fa-arrows-rotate"></i>
          </div>
          <span class="text-white">ReUse<span class="text-success">Hub</span></span>
        </a>
        <p class="text-secondary-subtle mt-2 pe-lg-4" style="font-size: 0.92rem; line-height: 1.7;">
          Kerala's premier peer-to-peer rental marketplace. Why purchase high-cost items for occasional use when you can rent them safely from your community? Save money, earn passive income, and encourage a greener circular economy.
        </p>
        <div class="d-flex gap-3 mt-4">
          <a href="#" class="btn btn-outline-light btn-sm rounded-circle" style="width:36px;height:36px;display:flex;align-items:center;justify-content:center;"><i class="fa-brands fa-instagram"></i></a>
          <a href="#" class="btn btn-outline-light btn-sm rounded-circle" style="width:36px;height:36px;display:flex;align-items:center;justify-content:center;"><i class="fa-brands fa-linkedin-in"></i></a>
          <a href="#" class="btn btn-outline-light btn-sm rounded-circle" style="width:36px;height:36px;display:flex;align-items:center;justify-content:center;"><i class="fa-brands fa-x-twitter"></i></a>
          <a href="#" class="btn btn-outline-light btn-sm rounded-circle" style="width:36px;height:36px;display:flex;align-items:center;justify-content:center;"><i class="fa-brands fa-facebook-f"></i></a>
        </div>
      </div>

      <!-- Col 2: Quick Links -->
      <div class="col-lg-2 col-md-3 col-6">
        <h5>Quick Links</h5>
        <ul class="footer-links">
          <li><a href="<?php echo url(); ?>">Home</a></li>
          <li><a href="<?php echo url('explore.php'); ?>">Explore Marketplace</a></li>
          <li><a href="<?php echo url('list-item.php'); ?>">List Your Item</a></li>
          <li><a href="<?php echo url('#how-it-works'); ?>">How It Works</a></li>
          <li><a href="<?php echo url('#savings-calculator'); ?>">Savings Calculator</a></li>
          <li><a href="<?php echo url('contact.php'); ?>">Contact Us</a></li>
        </ul>
      </div>

      <!-- Col 3: Categories -->
      <div class="col-lg-3 col-md-3 col-6">
        <h5>Popular Categories</h5>
        <ul class="footer-links">
          <li><a href="<?php echo url('explore.php?category=photography-equipment'); ?>">Photography & Cameras</a></li>
          <li><a href="<?php echo url('explore.php?category=electronics-gadgets'); ?>">Electronics & Consoles</a></li>
          <li><a href="<?php echo url('explore.php?category=camping-travel'); ?>">Camping & Trekking</a></li>
          <li><a href="<?php echo url('explore.php?category=fashion-accessories'); ?>">Wedding & Party Wear</a></li>
          <li><a href="<?php echo url('explore.php?category=tools-appliances'); ?>">Power Tools & DIY</a></li>
          <li><a href="<?php echo url('explore.php?category=event-party-equipment'); ?>">Projectors & Sound</a></li>
        </ul>
      </div>

      <!-- Col 4: Trust & Contact -->
      <div class="col-lg-3 col-md-6">
        <h5>Contact & Support</h5>
        <ul class="footer-links">
          <li class="d-flex align-items-center gap-2 text-secondary-subtle">
            <i class="fa-solid fa-location-dot text-success"></i>
            <span>Panampilly Nagar, Kochi, Kerala – 682036</span>
          </li>
          <li class="d-flex align-items-center gap-2 text-secondary-subtle">
            <i class="fa-solid fa-envelope text-success"></i>
            <span>support@reusehub.in</span>
          </li>
          <li class="d-flex align-items-center gap-2 text-secondary-subtle">
            <i class="fa-solid fa-phone text-success"></i>
            <span>+91 98460 00001 (Mon-Sat, 9am - 7pm)</span>
          </li>
        </ul>
        <div class="mt-3 p-3 rounded-3" style="background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255,255,255,0.1);">
          <div class="d-flex align-items-center gap-2 text-warning mb-1">
            <i class="fa-solid fa-shield-halved"></i>
            <span class="fw-bold small text-white">Trust & Security Deposit</span>
          </div>
          <p class="text-secondary-subtle small mb-0">Every rental is backed by transparent security deposits and verified user profiles.</p>
        </div>
      </div>
    </div>

    <!-- Bottom Bar -->
    <div class="footer-bottom d-flex flex-column flex-md-row justify-content-between align-items-center gap-2">
      <div>
        &copy; <?php echo date('Y'); ?> <?php echo APP_NAME; ?>. All rights reserved. Made with <i class="fa-solid fa-heart text-danger"></i> in Kerala, India.
      </div>
      <div class="d-flex gap-3">
        <a href="<?php echo url('contact.php'); ?>" class="text-secondary-subtle text-decoration-none small">Terms of Service</a>
        <span class="text-secondary-subtle small">&bull;</span>
        <a href="<?php echo url('contact.php'); ?>" class="text-secondary-subtle text-decoration-none small">Privacy Policy</a>
        <span class="text-secondary-subtle small">&bull;</span>
        <a href="<?php echo url('contact.php'); ?>" class="text-secondary-subtle text-decoration-none small">Rental Guidelines</a>
      </div>
    </div>
  </div>
</footer>

<!-- Bootstrap 5.3 JS Bundle with Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>

<!-- Custom Main JS -->
<script src="<?php echo asset('js/main.js'); ?>"></script>

</body>
</html>
