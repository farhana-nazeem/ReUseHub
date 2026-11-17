<?php
/**
 * ReUseHub - Homepage
 * Platform: ReUseHub – Rent Instead of Buy
 */

$pageTitle = 'Why Buy When You Can Rent?';
$activePage = 'home';
require_once __DIR__ . '/includes/header.php';

$pdo = getDB();

// 1. Fetch Featured Categories with actual product counts
$catStmt = $pdo->query("SELECT c.*, COUNT(p.id) as product_count 
                        FROM categories c 
                        LEFT JOIN products p ON c.id = p.category_id AND p.status = 'approved' AND p.is_available = 1
                        WHERE c.is_featured = 1 
                        GROUP BY c.id 
                        ORDER BY c.id ASC");
$featuredCategories = $catStmt->fetchAll();

// 2. Fetch Featured Products (real database records)
$prodStmt = $pdo->query("SELECT p.*, c.name as category_name, c.slug as category_slug, u.name as owner_name,
                         (SELECT image_path FROM product_images WHERE product_id = p.id ORDER BY is_primary DESC, id ASC LIMIT 1) as primary_image,
                         (SELECT AVG(rating) FROM reviews WHERE product_id = p.id) as avg_rating,
                         (SELECT COUNT(*) FROM reviews WHERE product_id = p.id) as review_count
                         FROM products p
                         JOIN categories c ON p.category_id = c.id
                         JOIN users u ON p.owner_id = u.id
                         WHERE p.status = 'approved' AND p.is_available = 1
                         ORDER BY p.featured DESC, p.views_count DESC, p.created_at DESC
                         LIMIT 8");
$featuredProducts = $prodStmt->fetchAll();

// 3. Fetch Platform Statistics for Live Metrics
$statsUsers = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$statsProducts = $pdo->query("SELECT COUNT(*) FROM products WHERE status = 'approved'")->fetchColumn();
$statsBookings = $pdo->query("SELECT COUNT(*) FROM bookings WHERE status IN ('approved', 'active', 'completed')")->fetchColumn();

// 4. Fetch Customer Reviews
$revStmt = $pdo->query("SELECT r.*, u.name as user_name, u.city as user_city, p.title as product_title 
                        FROM reviews r
                        JOIN users u ON r.user_id = u.id
                        JOIN products p ON r.product_id = p.id
                        ORDER BY r.rating DESC, r.created_at DESC
                        LIMIT 3");
$testimonials = $revStmt->fetchAll();
?>

<!-- ==============================================
     HERO SECTION
============================================== -->
<section class="hero-section">
  <div class="container position-relative">
    <div class="row align-items-center justify-content-between g-5">
      <div class="col-lg-7">
        <div class="hero-badge">
          <i class="fa-solid fa-leaf text-warning"></i>
          <span>Kerala's First Peer-to-Peer Rental Community</span>
        </div>
        <h1 class="hero-title">
          Why Buy When You Can <span class="highlight">Rent?</span>
        </h1>
        <p class="hero-subtitle">
          Access the things you need, when you need them. Rent smarter, save money, and give unused products a new purpose.
        </p>

        <!-- Large Floating Search Bar -->
        <div class="hero-search-card mb-4">
          <form action="<?php echo  url('explore.php'); ?>" method="GET" class="row g-2 align-items-center">
            <!-- Search Keyword -->
            <div class="col-md-5 col-12">
              <div class="search-field">
                <i class="fa-solid fa-magnifying-glass search-icon"></i>
                <input type="text" name="q" class="form-control" placeholder="What do you want to rent?" aria-label="Search items">
              </div>
            </div>

            <!-- Location Input -->
            <div class="col-md-3 col-6">
              <div class="search-field">
                <i class="fa-solid fa-location-dot search-icon"></i>
                <select name="city" class="form-select" aria-label="Filter by City">
                  <option value="">All Kerala</option>
                  <option value="Kochi">Kochi</option>
                  <option value="Trivandrum">Trivandrum</option>
                  <option value="Kozhikode">Kozhikode</option>
                  <option value="Thrissur">Thrissur</option>
                  <option value="Kottayam">Kottayam</option>
                </select>
              </div>
            </div>

            <!-- Category Dropdown -->
            <div class="col-md-4 col-6">
              <button type="submit" class="btn btn-primary-custom w-100 py-2">
                <i class="fa-solid fa-arrow-right"></i> Explore Now
              </button>
            </div>
          </form>
        </div>

        <!-- Live Metrics / Stats -->
        <div class="hero-stats">
          <div class="hero-stat-item">
            <h4><?php echo  number_format($statsProducts); ?>+</h4>
            <p>Verified Items</p>
          </div>
          <div class="hero-stat-item">
            <h4><?php echo  number_format($statsUsers); ?>+</h4>
            <p>Happy Members</p>
          </div>
          <div class="hero-stat-item">
            <h4>₹2.4L+</h4>
            <p>User Savings</p>
          </div>
        </div>
      </div>

      <!-- Hero Visual / Floating Preview Card -->
      <div class="col-lg-5 d-none d-lg-block">
        <div class="position-relative">
          <div class="card border-0 rounded-4 shadow-lg overflow-hidden" style="transform: rotate(1deg);">
            <img src="https://images.unsplash.com/photo-1516035069371-29a1b244cc32?auto=format&fit=crop&w=800&q=80" alt="Camera Rental" class="card-img-top" style="height: 340px; object-fit: cover;">
            <div class="card-body p-4 bg-white">
              <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="badge bg-success-subtle text-success fw-bold">Popular Rental</span>
                <span class="text-warning fw-bold"><i class="fa-solid fa-star"></i> 4.9 (28 reviews)</span>
              </div>
              <h5 class="fw-bold mb-1 text-dark">Sony Alpha A7 III Full-Frame</h5>
              <p class="text-muted small mb-3"><i class="fa-solid fa-location-dot me-1 text-success"></i> Kakkanad, Kochi</p>
              <div class="d-flex justify-content-between align-items-center pt-3 border-top">
                <div>
                  <span class="fs-4 fw-bold text-primary">₹ 1,400</span>
                  <span class="text-muted small">/ day</span>
                </div>
                <a href="<?php echo  url('product-details.php?id=1'); ?>" class="btn btn-primary-custom btn-sm">Rent Now</a>
              </div>
            </div>
          </div>

          <!-- Floating Badge -->
          <div class="position-absolute bottom-0 start-0 translate-middle-x bg-white p-3 rounded-4 shadow-lg border d-flex align-items-center gap-3 ms-4 mb-n2" style="max-width: 250px;">
            <div class="rounded-circle bg-warning-subtle text-warning d-flex align-items-center justify-content-center" style="width: 44px; height: 44px; font-size: 1.2rem;">
              <i class="fa-solid fa-piggy-bank"></i>
            </div>
            <div>
              <div class="fw-bold text-dark small">Saved ₹1.6 Lakhs!</div>
              <div class="text-muted" style="font-size: 0.75rem;">Short-term photography rental</div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ==============================================
     POPULAR CATEGORIES
============================================== -->
<section class="py-5 bg-white">
  <div class="container py-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-end mb-4">
      <div>
        <span class="text-secondary fw-bold text-uppercase small tracking-wide">Browse by Interest</span>
        <h2 class="fw-bold mt-1">Popular Rental Categories</h2>
      </div>
      <a href="<?php echo  url('explore.php'); ?>" class="btn btn-outline-custom mt-3 mt-md-0">
        View All Categories <i class="fa-solid fa-arrow-right ms-1"></i>
      </a>
    </div>

    <div class="row g-4">
      <?php foreach ($featuredCategories as $cat): ?>
        <div class="col-xl-3 col-lg-3 col-md-4 col-6">
          <a href="<?php echo  url('explore.php?category=' . urlencode($cat['slug'])); ?>" class="text-decoration-none">
            <div class="category-card">
              <div class="category-icon-box">
                <i class="fa-solid <?php echo  e($cat['icon']); ?>"></i>
              </div>
              <h6><?php echo  e($cat['name']); ?></h6>
              <small><?php echo  (int)$cat['product_count']; ?> <?php echo  $cat['product_count'] == 1 ? 'item available' : 'items available'; ?></small>
            </div>
          </a>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ==============================================
     FEATURED RENTALS
============================================== -->
<section class="py-5" style="background-color: var(--bg-main);">
  <div class="container py-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-end mb-4">
      <div>
        <span class="text-secondary fw-bold text-uppercase small tracking-wide">Handpicked Collection</span>
        <h2 class="fw-bold mt-1">Featured Rental Listings</h2>
        <p class="text-muted mb-0">High-demand gear, electronics, outdoor equipment and party wear available for rent right now.</p>
      </div>
      <a href="<?php echo  url('explore.php'); ?>" class="btn btn-outline-custom mt-3 mt-md-0">
        Browse All Products <i class="fa-solid fa-arrow-right ms-1"></i>
      </a>
    </div>

    <div class="row g-4">
      <?php foreach ($featuredProducts as $item): ?>
        <?php
          $imgUrl = $item['primary_image'] ?: 'assets/images/placeholder.jpg';
          if (!str_starts_with($imgUrl, 'http://') && !str_starts_with($imgUrl, 'https://')) {
            $imgUrl = url($imgUrl);
          }
          $itemRating = $item['avg_rating'] ? round((float)$item['avg_rating'], 1) : 5.0;
          $userWishlisted = $user ? isWishlisted($user['id'], $item['id']) : false;
        ?>
        <div class="col-xl-3 col-lg-4 col-md-6">
          <div class="product-card">
            <!-- Image & Badges -->
            <div class="product-image-wrapper">
              <img src="<?php echo  e($imgUrl); ?>" alt="<?php echo  e($item['title']); ?>" loading="lazy">
              <span class="product-badge-condition"><?php echo  e($item['condition_grade']); ?></span>
              <button type="button" class="wishlist-btn <?php echo  $userWishlisted ? 'active' : ''; ?>" data-product-id="<?php echo  $item['id']; ?>" title="Save to Wishlist">
                <i class="<?php echo  $userWishlisted ? 'fa-solid' : 'fa-regular'; ?> fa-heart"></i>
              </button>
            </div>

            <!-- Content -->
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
                  <span class="product-price-label">per day &bull; Deposit <?php echo  formatPrice($item['security_deposit']); ?></span>
                </div>
                <a href="<?php echo  url('product-details.php?id=' . $item['id']); ?>" class="btn btn-primary-custom btn-sm">
                  View Details
                </a>
              </div>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ==============================================
     HOW IT WORKS SECTION
============================================== -->
<section id="how-it-works" class="py-5 bg-white border-top border-bottom">
  <div class="container py-4">
    <div class="text-center max-w-700 mx-auto mb-5">
      <span class="text-secondary fw-bold text-uppercase small tracking-wide">Simple & Trustworthy</span>
      <h2 class="fw-bold mt-1">How ReUseHub Works</h2>
      <p class="text-muted">Renting quality items from your local neighborhood takes just three simple steps.</p>
    </div>

    <div class="row g-4">
      <div class="col-md-4">
        <div class="step-card">
          <div class="step-number">1</div>
          <h4 class="fw-bold mb-3">Explore & Select</h4>
          <p class="text-muted">
            Find the exact camera, camping tent, luxury suit, or power tool you need. Filter by location in Kerala, dates, and budget.
          </p>
          <div class="text-success fw-semibold small mt-3">
            <i class="fa-solid fa-check me-1"></i> Verified product listings & photos
          </div>
        </div>
      </div>

      <div class="col-md-4">
        <div class="step-card">
          <div class="step-number">2</div>
          <h4 class="fw-bold mb-3">Book & Confirm</h4>
          <p class="text-muted">
            Choose your rental dates and send a booking request. The owner confirms, and your refundable security deposit is held safely.
          </p>
          <div class="text-success fw-semibold small mt-3">
            <i class="fa-solid fa-shield me-1"></i> Transparent refundable deposit
          </div>
        </div>
      </div>

      <div class="col-md-4">
        <div class="step-card">
          <div class="step-number">3</div>
          <h4 class="fw-bold mb-3">Enjoy & Return</h4>
          <p class="text-muted">
            Pick up or receive the item, use it for your shoot, trip, or event, and return it in original condition to get your full deposit back.
          </p>
          <div class="text-success fw-semibold small mt-3">
            <i class="fa-solid fa-arrows-rotate me-1"></i> Rate each other & build trust
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ==============================================
     INNOVATIVE FEATURE: RENTAL SAVINGS CALCULATOR
============================================== -->
<section id="savings-calculator" class="py-5" style="background-color: var(--bg-main);">
  <div class="container py-4">
    <div class="row align-items-center g-5">
      <div class="col-lg-5">
        <span class="text-secondary fw-bold text-uppercase small tracking-wide">Smart Financial Choice</span>
        <h2 class="fw-bold mt-1 mb-3">Rental Savings Calculator</h2>
        <p class="text-muted mb-4">
          See for yourself why smart consumers and professionals choose to rent instead of buying depreciating assets. Calculate your immediate savings right now.
        </p>

        <div class="d-flex align-items-center gap-3 mb-3">
          <div class="rounded-circle bg-success-subtle text-success d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
            <i class="fa-solid fa-coins"></i>
          </div>
          <div>
            <div class="fw-bold text-dark">Zero Upfront Capital Lock-in</div>
            <div class="text-muted small">Save tens of thousands on items you only use 3-4 times a year.</div>
          </div>
        </div>

        <div class="d-flex align-items-center gap-3 mb-4">
          <div class="rounded-circle bg-success-subtle text-success d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
            <i class="fa-solid fa-earth-americas"></i>
          </div>
          <div>
            <div class="fw-bold text-dark">Sustainable Consumption</div>
            <div class="text-muted small">Reduce manufacturing waste and carbon footprint through shared usage.</div>
          </div>
        </div>
      </div>

      <div class="col-lg-7">
        <div class="savings-calc-box">
          <div class="row g-4 align-items-center">
            <!-- Inputs -->
            <div class="col-md-7">
              <h5 class="fw-bold mb-3 text-dark"><i class="fa-solid fa-sliders text-success me-2"></i> Customize Parameters</h5>
              
              <div class="mb-3">
                <label class="form-label fw-semibold small text-muted">Original Purchase Price (₹)</label>
                <div class="input-group">
                  <span class="input-group-text bg-light border-end-0">₹</span>
                  <input type="number" id="calc-purchase-price" class="form-control border-start-0" value="65000" min="1000" step="500">
                </div>
              </div>

              <div class="mb-3">
                <label class="form-label fw-semibold small text-muted">Daily Rental Rate (₹)</label>
                <div class="input-group">
                  <span class="input-group-text bg-light border-end-0">₹</span>
                  <input type="number" id="calc-daily-price" class="form-control border-start-0" value="1200" min="50" step="50">
                </div>
              </div>

              <div class="mb-2">
                <div class="d-flex justify-content-between">
                  <label class="form-label fw-semibold small text-muted">Rental Duration</label>
                  <span class="fw-bold text-primary small"><span id="calc-rental-days-display">3</span> Days</span>
                </div>
                <input type="range" class="form-range" id="calc-days-range" min="1" max="15" value="3" oninput="document.getElementById('calc-rental-days-display').innerText = this.value;">
                <input type="hidden" id="calc-rental-days" value="3">
              </div>
            </div>

            <!-- Savings Result -->
            <div class="col-md-5">
              <div class="savings-result-card">
                <div class="text-white-50 text-uppercase small fw-bold mb-1">Rental Cost</div>
                <div class="fs-4 fw-bold text-white mb-3" id="calc-rental-total">₹ 3,600</div>

                <hr class="border-secondary my-2">

                <div class="text-warning text-uppercase small fw-bold mb-1">Your Pocket Savings</div>
                <div class="savings-highlight-number" id="calc-total-savings">₹ 61,400</div>
                <div class="badge bg-warning text-dark mt-2 mx-auto fw-bold px-3 py-2" id="calc-savings-percent">
                  94% Saved
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ==============================================
     WHY CHOOSE REUSEHUB
============================================== -->
<section class="py-5 bg-white">
  <div class="container py-4">
    <div class="text-center max-w-700 mx-auto mb-5">
      <span class="text-secondary fw-bold text-uppercase small tracking-wide">Community First</span>
      <h2 class="fw-bold mt-1">Why People Love ReUseHub</h2>
      <p class="text-muted">Designed specifically to make neighborhood sharing effortless, transparent and rewarding.</p>
    </div>

    <div class="row g-4">
      <div class="col-lg-4 col-md-6">
        <div class="d-flex gap-3 p-3">
          <div class="rounded-3 bg-primary-light text-primary p-3 flex-shrink-0" style="width: 54px; height: 54px; display:flex; align-items:center; justify-content:center; font-size:1.4rem;">
            <i class="fa-solid fa-wallet"></i>
          </div>
          <div>
            <h5 class="fw-bold mb-2">Save Substantial Money</h5>
            <p class="text-muted small">Don't tie up thousands of rupees in gadgets, tools or outfits you need for a weekend shoot or party.</p>
          </div>
        </div>
      </div>

      <div class="col-lg-4 col-md-6">
        <div class="d-flex gap-3 p-3">
          <div class="rounded-3 bg-primary-light text-primary p-3 flex-shrink-0" style="width: 54px; height: 54px; display:flex; align-items:center; justify-content:center; font-size:1.4rem;">
            <i class="fa-solid fa-hand-holding-dollar"></i>
          </div>
          <div>
            <h5 class="fw-bold mb-2">Earn from Unused Items</h5>
            <p class="text-muted small">Turn your idle cameras, gaming consoles, bikes, and apparel into steady monthly passive income.</p>
          </div>
        </div>
      </div>

      <div class="col-lg-4 col-md-6">
        <div class="d-flex gap-3 p-3">
          <div class="rounded-3 bg-primary-light text-primary p-3 flex-shrink-0" style="width: 54px; height: 54px; display:flex; align-items:center; justify-content:center; font-size:1.4rem;">
            <i class="fa-solid fa-map-location-dot"></i>
          </div>
          <div>
            <h5 class="fw-bold mb-2">Discover Nearby Rentals</h5>
            <p class="text-muted small">Connect directly with trusted neighbors across Kochi, Trivandrum, Kozhikode, and other Kerala hubs.</p>
          </div>
        </div>
      </div>

      <div class="col-lg-4 col-md-6">
        <div class="d-flex gap-3 p-3">
          <div class="rounded-3 bg-primary-light text-primary p-3 flex-shrink-0" style="width: 54px; height: 54px; display:flex; align-items:center; justify-content:center; font-size:1.4rem;">
            <i class="fa-solid fa-shield-halved"></i>
          </div>
          <div>
            <h5 class="fw-bold mb-2">Security Deposit Protection</h5>
            <p class="text-muted small">Every rental has a designated security deposit that is held securely until the product is returned safely.</p>
          </div>
        </div>
      </div>

      <div class="col-lg-4 col-md-6">
        <div class="d-flex gap-3 p-3">
          <div class="rounded-3 bg-primary-light text-primary p-3 flex-shrink-0" style="width: 54px; height: 54px; display:flex; align-items:center; justify-content:center; font-size:1.4rem;">
            <i class="fa-solid fa-star"></i>
          </div>
          <div>
            <h5 class="fw-bold mb-2">Verified Reviews & Ratings</h5>
            <p class="text-muted small">Read honest ratings and feedback from fellow community members before requesting any rental.</p>
          </div>
        </div>
      </div>

      <div class="col-lg-4 col-md-6">
        <div class="d-flex gap-3 p-3">
          <div class="rounded-3 bg-primary-light text-primary p-3 flex-shrink-0" style="width: 54px; height: 54px; display:flex; align-items:center; justify-content:center; font-size:1.4rem;">
            <i class="fa-solid fa-seedling"></i>
          </div>
          <div>
            <h5 class="fw-bold mb-2">Support Green Consumption</h5>
            <p class="text-muted small">Reduce overproduction, cut down e-waste, and actively participate in circular sustainability.</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ==============================================
     LIST YOUR ITEM CTA BANNER
============================================== -->
<section class="py-5" style="background-color: var(--bg-main);">
  <div class="container py-3">
    <div class="cta-banner">
      <div class="row align-items-center g-4">
        <div class="col-lg-8">
          <span class="badge bg-warning text-dark fw-bold mb-3 px-3 py-2">Earn Extra Income</span>
          <h2 class="display-6 fw-bold text-white mb-3">Your Unused Items Can Earn Money.</h2>
          <p class="text-white-50 fs-5 mb-0" style="max-width: 600px;">
            Have a DSLR, PlayStation, tent, or designer attire gathering dust in your closet? List it on ReUseHub in under 2 minutes and start earning passive income today.
          </p>
        </div>
        <div class="col-lg-4 text-lg-end">
          <a href="<?php echo  url('list-item.php'); ?>" class="btn btn-accent-custom btn-lg px-4 py-3 shadow">
            <i class="fa-solid fa-plus-circle me-1"></i> List Your Item Now
          </a>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ==============================================
     TESTIMONIALS SECTION
============================================== -->
<section class="py-5 bg-white border-top">
  <div class="container py-4">
    <div class="text-center max-w-700 mx-auto mb-5">
      <span class="text-secondary fw-bold text-uppercase small tracking-wide">User Experiences</span>
      <h2 class="fw-bold mt-1">What Our Community Says</h2>
      <p class="text-muted">Real stories from renters and owners across Kerala sharing on ReUseHub.</p>
    </div>

    <div class="row g-4">
      <?php if (!empty($testimonials)): ?>
        <?php foreach ($testimonials as $t): ?>
          <div class="col-md-4">
            <div class="card h-100 p-4 border rounded-4 shadow-sm">
              <div class="d-flex text-warning mb-3">
                <?php for ($s = 1; $s <= 5; $s++): ?>
                  <i class="fa-solid fa-star <?php echo  $s <= $t['rating'] ? '' : 'text-muted'; ?>"></i>
                <?php endfor; ?>
              </div>
              <p class="text-muted mb-4 fst-italic">"<?php echo  e($t['comment']); ?>"</p>
              <div class="d-flex align-items-center gap-3 mt-auto pt-3 border-top">
                <img src="<?php echo  asset('images/default-avatar.png'); ?>" class="rounded-circle" width="42" height="42" alt="Avatar">
                <div>
                  <h6 class="fw-bold mb-0 text-dark"><?php echo  e($t['user_name']); ?></h6>
                  <small class="text-muted"><?php echo  e($t['user_city']); ?> &bull; Rented <?php echo  e($t['product_title']); ?></small>
                </div>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <div class="col-12 text-center text-muted">No testimonials available yet.</div>
      <?php endif; ?>
    </div>
  </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

