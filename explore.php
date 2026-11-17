<?php
/**
 * ReUseHub - Explore Marketplace & Search
 */

$pageTitle = 'Explore Rentals';
$activePage = 'explore';
require_once __DIR__ . '/includes/header.php';

$pdo = getDB();

// 1. Fetch Categories for Filter Sidebar
$categories = $pdo->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll();

// 2. Read Query & Filter Parameters
$search = trim(isset($_GET['q']) ? $_GET['q'] : '');
$categorySlug = trim(isset($_GET['category']) ? $_GET['category'] : '');
$city = trim(isset($_GET['city']) ? $_GET['city'] : '');
$minPrice = isset($_GET['min_price']) && is_numeric($_GET['min_price']) ? (float)$_GET['min_price'] : null;
$maxPrice = isset($_GET['max_price']) && is_numeric($_GET['max_price']) ? (float)$_GET['max_price'] : null;
$condition = trim(isset($_GET['condition']) ? $_GET['condition'] : '');
$availability = trim(isset($_GET['availability']) ? $_GET['availability'] : '1'); // Default to available items
$sort = trim(isset($_GET['sort']) ? $_GET['sort'] : 'newest');
$page = max(1, (int)(isset($_GET['page']) ? $_GET['page'] : 1));
$perPage = 9;
$offset = ($page - 1) * $perPage;

// 3. Build Dynamic SQL Query with Prepared Statements
$where = array("p.status = 'approved'");
$params = array();

if (!empty($search)) {
    $where[] = "(p.title LIKE ? OR p.description LIKE ? OR p.location LIKE ?)";
    $like = "%{$search}%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

if (!empty($categorySlug)) {
    $where[] = "c.slug = ?";
    $params[] = $categorySlug;
}

if (!empty($city)) {
    $where[] = "p.city = ?";
    $params[] = $city;
}

if ($minPrice !== null && $minPrice > 0) {
    $where[] = "p.daily_price >= ?";
    $params[] = $minPrice;
}

if ($maxPrice !== null && $maxPrice > 0) {
    $where[] = "p.daily_price <= ?";
    $params[] = $maxPrice;
}

if (!empty($condition)) {
    $where[] = "p.condition_grade = ?";
    $params[] = $condition;
}

if ($availability === '1') {
    $where[] = "p.is_available = 1";
}

$whereSql = implode(" AND ", $where);

// Sorting
$orderBy = '';
    if ($sort === 'price_asc') {
        $orderBy = 'p.daily_price ASC';
    } elseif ($sort === 'price_desc') {
        $orderBy = 'p.daily_price DESC';
    } elseif ($sort === 'popular') {
        $orderBy = 'p.views_count DESC, p.created_at DESC';
    } else {
        $orderBy = 'p.created_at DESC';
    }

// Count Total matching items for pagination
$countSql = "SELECT COUNT(*) FROM products p 
             JOIN categories c ON p.category_id = c.id 
             WHERE {$whereSql}";
$countStmt = $pdo->prepare($countSql);
$countStmt->execute($params);
$totalProducts = (int)$countStmt->fetchColumn();
$totalPages = ceil($totalProducts / $perPage);

// Fetch Paginated Results
$sql = "SELECT p.*, c.name as category_name, c.slug as category_slug, u.name as owner_name,
        (SELECT image_path FROM product_images WHERE product_id = p.id ORDER BY is_primary DESC, id ASC LIMIT 1) as primary_image,
        (SELECT AVG(rating) FROM reviews WHERE product_id = p.id) as avg_rating,
        (SELECT COUNT(*) FROM reviews WHERE product_id = p.id) as review_count
        FROM products p
        JOIN categories c ON p.category_id = c.id
        JOIN users u ON p.owner_id = u.id
        WHERE {$whereSql}
        ORDER BY {$orderBy}
        LIMIT {$perPage} OFFSET {$offset}";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

// Unique Cities in DB for filtering
$cityList = $pdo->query("SELECT DISTINCT city FROM products WHERE status = 'approved' AND city IS NOT NULL ORDER BY city ASC")->fetchAll(PDO::FETCH_COLUMN);
?>

<div class="py-4" style="background-color: var(--bg-main);">
  <div class="container">
    <!-- Breadcrumb & Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
      <div>
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb mb-1 small">
            <li class="breadcrumb-item"><a href="<?php echo  url(); ?>" class="text-secondary text-decoration-none">Home</a></li>
            <li class="breadcrumb-item active" aria-current="page">Explore Marketplace</li>
          </ol>
        </nav>
        <h2 class="fw-bold mb-0">Browse Rental Listings</h2>
        <p class="text-muted small mb-0">Showing <?php echo  count($products); ?> of <?php echo  $totalProducts; ?> available items across Kerala</p>
      </div>

      <!-- Quick Sorting Dropdown -->
      <div class="d-flex align-items-center gap-2">
        <label class="form-label small text-muted mb-0 text-nowrap">Sort By:</label>
        <select class="form-select form-select-sm shadow-sm" style="width: 180px;" onchange="applySort(this.value)">
          <option value="newest" <?php echo  $sort === 'newest' ? 'selected' : ''; ?>>Newest Added</option>
          <option value="price_asc" <?php echo  $sort === 'price_asc' ? 'selected' : ''; ?>>Price: Low to High</option>
          <option value="price_desc" <?php echo  $sort === 'price_desc' ? 'selected' : ''; ?>>Price: High to Low</option>
          <option value="popular" <?php echo  $sort === 'popular' ? 'selected' : ''; ?>>Most Popular</option>
        </select>
      </div>
    </div>

    <div class="row g-4">
      <!-- ================= FILTER SIDEBAR ================= -->
      <div class="col-lg-3">
        <div class="card border-0 shadow-sm rounded-4 p-4 bg-white sticky-top" style="top: 85px;">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-filter text-success me-2"></i> Filters</h5>
            <a href="<?php echo  url('explore.php'); ?>" class="text-muted small text-decoration-none">Reset All</a>
          </div>

          <form action="<?php echo  url('explore.php'); ?>" method="GET" id="filterForm">
            <!-- Search Keyword -->
            <div class="mb-3">
              <label class="form-label small fw-bold text-dark">Keyword</label>
              <div class="input-group input-group-sm">
                <span class="input-group-text bg-light text-muted"><i class="fa-solid fa-magnifying-glass"></i></span>
                <input type="text" name="q" class="form-control" placeholder="Search title or tags..." value="<?php echo  e($search); ?>">
              </div>
            </div>

            <!-- Category -->
            <div class="mb-3">
              <label class="form-label small fw-bold text-dark">Category</label>
              <select name="category" class="form-select form-select-sm">
                <option value="">All Categories</option>
                <?php foreach ($categories as $cat): ?>
                  <option value="<?php echo  e($cat['slug']); ?>" <?php echo  $categorySlug === $cat['slug'] ? 'selected' : ''; ?>>
                    <?php echo  e($cat['name']); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <!-- Location / City -->
            <div class="mb-3">
              <label class="form-label small fw-bold text-dark">City / District</label>
              <select name="city" class="form-select form-select-sm">
                <option value="">All Kerala</option>
                <?php foreach ($cityList as $c): ?>
                  <option value="<?php echo  e($c); ?>" <?php echo  $city === $c ? 'selected' : ''; ?>><?php echo  e($c); ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <!-- Price Range -->
            <div class="mb-3">
              <label class="form-label small fw-bold text-dark">Daily Price Range (₹)</label>
              <div class="row g-2">
                <div class="col-6">
                  <input type="number" name="min_price" class="form-control form-control-sm" placeholder="Min" value="<?php echo  e($minPrice); ?>">
                </div>
                <div class="col-6">
                  <input type="number" name="max_price" class="form-control form-control-sm" placeholder="Max" value="<?php echo  e($maxPrice); ?>">
                </div>
              </div>
            </div>

            <!-- Condition -->
            <div class="mb-3">
              <label class="form-label small fw-bold text-dark">Item Condition</label>
              <select name="condition" class="form-select form-select-sm">
                <option value="">Any Condition</option>
                <option value="Brand New" <?php echo  $condition === 'Brand New' ? 'selected' : ''; ?>>Brand New</option>
                <option value="Like New" <?php echo  $condition === 'Like New' ? 'selected' : ''; ?>>Like New</option>
                <option value="Gently Used" <?php echo  $condition === 'Gently Used' ? 'selected' : ''; ?>>Gently Used</option>
                <option value="Fair" <?php echo  $condition === 'Fair' ? 'selected' : ''; ?>>Fair</option>
              </select>
            </div>

            <!-- Availability toggle -->
            <div class="form-check form-switch mb-4">
              <input class="form-check-input" type="checkbox" name="availability" value="1" id="availSwitch" <?php echo  $availability === '1' ? 'checked' : ''; ?>>
              <label class="form-check-label small text-muted" for="availSwitch">Show Available Only</label>
            </div>

            <input type="hidden" name="sort" value="<?php echo  e($sort); ?>">

            <button type="submit" class="btn btn-primary-custom w-100 btn-sm py-2">
              <i class="fa-solid fa-arrows-rotate me-1"></i> Apply Filters
            </button>
          </form>
        </div>
      </div>

      <!-- ================= PRODUCT GRID ================= -->
      <div class="col-lg-9">
        <?php if (empty($products)): ?>
          <div class="card border-0 shadow-sm rounded-4 p-5 text-center bg-white my-4">
            <div class="rounded-circle bg-light d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 80px; height: 80px; font-size: 2rem; color: var(--primary);">
              <i class="fa-solid fa-magnifying-glass"></i>
            </div>
            <h4 class="fw-bold text-dark">No Products Found</h4>
            <p class="text-muted small mb-4">We couldn't find any rentals matching your search criteria. Try loosening your filters or explore other categories.</p>
            <div>
              <a href="<?php echo  url('explore.php'); ?>" class="btn btn-primary-custom btn-sm px-4 py-2">
                Clear All Filters
              </a>
            </div>
          </div>
        <?php else: ?>
          <div class="row g-4">
            <?php foreach ($products as $item): ?>
              <?php
                $imgUrl = $item['primary_image'] ?: 'assets/images/placeholder.jpg';
                if (!str_starts_with($imgUrl, 'http://') && !str_starts_with($imgUrl, 'https://')) {
                  $imgUrl = url($imgUrl);
                }
                $itemRating = $item['avg_rating'] ? round((float)$item['avg_rating'], 1) : 5.0;
                $userWishlisted = $user ? isWishlisted($user['id'], $item['id']) : false;
              ?>
              <div class="col-xl-4 col-md-6">
                <div class="product-card">
                  <!-- Image Wrapper -->
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
                        <span class="product-price-label">per day &bull; Dep. <?php echo  formatPrice($item['security_deposit']); ?></span>
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

          <!-- Pagination -->
          <?php if ($totalPages > 1): ?>
            <nav class="mt-5" aria-label="Explore pages">
              <ul class="pagination justify-content-center">
                <?php if ($page > 1): ?>
                  <li class="page-item">
                    <a class="page-link" href="<?php echo  url('explore.php?' . http_build_query(array_merge($_GET, array('page' => $page - 1)))); ?>">
                      <i class="fa-solid fa-chevron-left"></i>
                    </a>
                  </li>
                <?php endif; ?>

                <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                  <li class="page-item <?php echo  $p === $page ? 'active' : ''; ?>">
                    <a class="page-link" href="<?php echo  url('explore.php?' . http_build_query(array_merge($_GET, array('page' => $p)))); ?>">
                      <?php echo  $p; ?>
                    </a>
                  </li>
                <?php endfor; ?>

                <?php if ($page < $totalPages): ?>
                  <li class="page-item">
                    <a class="page-link" href="<?php echo  url('explore.php?' . http_build_query(array_merge($_GET, array('page' => $page + 1)))); ?>">
                      <i class="fa-solid fa-chevron-right"></i>
                    </a>
                  </li>
                <?php endif; ?>
              </ul>
            </nav>
          <?php endif; ?>

        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<script>
function applySort(val) {
  const urlParams = new URLSearchParams(window.location.search);
  urlParams.set('sort', val);
  urlParams.set('page', '1');
  window.location.search = urlParams.toString();
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

