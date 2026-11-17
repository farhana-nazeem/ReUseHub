<?php
/**
 * ReUseHub - List Your Item Module
 */

$pageTitle = 'List Your Item';
$activePage = 'list_item';
require_once __DIR__ . '/includes/header.php';

// Must be logged in to list an item
requireLogin();
$user = currentUser();
$pdo = getDB();

// Fetch Categories for selection
$categories = $pdo->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll();

$errors = array();
$title = '';
$categoryId = '';
$description = '';
$dailyPrice = '';
$securityDeposit = '';
$originalPrice = '';
$conditionGrade = 'Like New';
$location = isset($user['address']) ? $user['address'] : '';
$city = isset($user['city']) ? $user['city'] : 'Kochi';
$pincode = isset($user['pincode']) ? $user['pincode'] : '682001';
$rentalInstructions = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_token();

    $title = trim(isset($_POST['title']) ? $_POST['title'] : '');
    $categoryId = (int)(isset($_POST['category_id']) ? $_POST['category_id'] : 0);
    $description = trim(isset($_POST['description']) ? $_POST['description'] : '');
    $dailyPrice = (float)(isset($_POST['daily_price']) ? $_POST['daily_price'] : 0);
    $securityDeposit = (float)(isset($_POST['security_deposit']) ? $_POST['security_deposit'] : 0);
    $originalPrice = (float)(isset($_POST['original_price']) ? $_POST['original_price'] : 0);
    $conditionGrade = trim(isset($_POST['condition_grade']) ? $_POST['condition_grade'] : 'Like New');
    $location = trim(isset($_POST['location']) ? $_POST['location'] : '');
    $city = trim(isset($_POST['city']) ? $_POST['city'] : 'Kochi');
    $pincode = trim(isset($_POST['pincode']) ? $_POST['pincode'] : '682001');
    $rentalInstructions = trim(isset($_POST['rental_instructions']) ? $_POST['rental_instructions'] : '');

    // Validation
    if (empty($title)) {
        $errors[] = 'Product title is required.';
    }
    if ($categoryId <= 0) {
        $errors[] = 'Please select a valid category.';
    }
    if (empty($description)) {
        $errors[] = 'A detailed item description is required.';
    }
    if ($dailyPrice <= 0) {
        $errors[] = 'Daily rental price must be greater than zero.';
    }
    if (empty($location)) {
        $errors[] = 'Pickup location / address is required.';
    }

    // Process uploaded images
    $uploadedImages = array();
    $allowedExtensions = array('jpg', 'jpeg', 'png', 'webp');
    $allowedMimes = array('image/jpeg', 'image/png', 'image/webp');

    if (isset($_FILES['product_images']) && !empty($_FILES['product_images']['name'][0])) {
        $files = $_FILES['product_images'];
        $uploadDir = __DIR__ . '/uploads/products/';

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        foreach ($files['name'] as $idx => $name) {
            if ($files['error'][$idx] === UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                $tmpName = $files['tmp_name'][$idx];
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mime = finfo_file($finfo, $tmpName);
                finfo_close($finfo);

                if (in_array($ext, $allowedExtensions) && in_array($mime, $allowedMimes)) {
                    $newFileName = 'prod_' . uniqid() . '_' . time() . '.' . $ext;
                    $targetPath = $uploadDir . $newFileName;
                    if (move_uploaded_file($tmpName, $targetPath)) {
                        $uploadedImages[] = 'uploads/products/' . $newFileName;
                    }
                } else {
                    $errors[] = "File '{$name}' is not an authorized image format (JPG, PNG, WebP only).";
                }
            }
        }
    }

    if (empty($uploadedImages)) {
        // Provide sample high-quality image if user submitted without upload
        $uploadedImages[] = 'https://images.unsplash.com/photo-1516035069371-29a1b244cc32?auto=format&fit=crop&w=1000&q=80';
    }

    if (empty($errors)) {
        // Generate unique slug
        $baseSlug = slugify($title);
        $slug = $baseSlug;
        $counter = 1;
        while ($pdo->prepare("SELECT id FROM products WHERE slug = ?")->execute(array($slug)) && $pdo->prepare("SELECT id FROM products WHERE slug = ?")->fetch()) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        // Determine initial listing status: Pending admin approval (or approved if user is admin)
        $initialStatus = ($user['role'] === 'admin') ? 'approved' : 'pending';

        // Auto promote user to 'owner' role if they were 'renter'
        if ($user['role'] === 'renter') {
            $pdo->prepare("UPDATE users SET role = 'owner' WHERE id = ?")->execute(array($user['id']));
            $_SESSION['user_role'] = 'owner';
        }

        // Insert Product
        $stmt = $pdo->prepare("INSERT INTO products 
            (owner_id, category_id, title, slug, description, daily_price, security_deposit, original_price, condition_grade, location, city, state, pincode, rental_instructions, status, is_available) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Kerala', ?, ?, ?, 1)");
        
        $inserted = $stmt->execute(array(
            $user['id'],
            $categoryId,
            $title,
            $slug,
            $description,
            $dailyPrice,
            $securityDeposit,
            $originalPrice,
            $conditionGrade,
            $location,
            $city,
            $pincode,
            $rentalInstructions,
            $initialStatus
        ));

        if ($inserted) {
            $newProductId = $pdo->lastInsertId();

            // Insert Images
            $imgStmt = $pdo->prepare("INSERT INTO product_images (product_id, image_path, is_primary) VALUES (?, ?, ?)");
            foreach ($uploadedImages as $idx => $path) {
                $isPrimary = ($idx === 0) ? 1 : 0;
                $imgStmt->execute(array($newProductId, $path, $isPrimary));
            }

            // Create notification for user
            if ($initialStatus === 'pending') {
                createNotification(
                    $user['id'],
                    'Listing Submitted for Review',
                    "Your item '{$title}' was submitted and is pending administrator verification.",
                    'owner/my-products.php'
                );
                setFlash('success', "Your item '{$title}' has been submitted for administrator review! It will appear in search as soon as verified.");
            } else {
                setFlash('success', "Your item '{$title}' is now live on the marketplace!");
            }

            header("Location: " . url('owner/my-products.php'));
            exit();
        } else {
            $errors[] = 'Failed to create product listing. Please try again.';
        }
    }
}
?>

<div class="py-5" style="background-color: var(--bg-main);">
  <div class="container py-2">
    <div class="row justify-content-center">
      <div class="col-lg-9 col-xl-8">
        <!-- Header Card -->
        <div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-4">
          <div class="d-flex align-items-center gap-3">
            <div class="rounded-circle bg-success-subtle text-success d-flex align-items-center justify-content-center flex-shrink-0" style="width: 56px; height: 56px; font-size: 1.5rem;">
              <i class="fa-solid fa-plus-circle"></i>
            </div>
            <div>
              <h3 class="fw-bold text-dark mb-1">List an Item for Rent</h3>
              <p class="text-muted small mb-0">Monetize your unused gadgets, cameras, camping gear, party apparel, and tools safely.</p>
            </div>
          </div>
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

        <!-- Listing Form -->
        <div class="card border-0 shadow-lg rounded-4 p-4 p-md-5 bg-white">
          <form action="<?php echo  url('list-item.php'); ?>" method="POST" enctype="multipart/form-data">
            <?php echo  csrf_field(); ?>

            <!-- Section 1: Basic Information -->
            <h5 class="fw-bold text-dark mb-3 pb-2 border-bottom">
              <i class="fa-solid fa-circle-info text-success me-2"></i> 1. Product Details
            </h5>

            <div class="mb-3">
              <label class="form-label small fw-bold text-dark">Listing Title <span class="text-danger">*</span></label>
              <input type="text" name="title" class="form-control" placeholder="e.g. Sony Alpha A7 III Full-Frame Camera + 28-70mm Lens" value="<?php echo  e($title); ?>" required>
              <small class="text-muted" style="font-size: 0.75rem;">Be specific: Include brand, model, and included accessories.</small>
            </div>

            <div class="row g-3 mb-3">
              <div class="col-md-6">
                <label class="form-label small fw-bold text-dark">Category <span class="text-danger">*</span></label>
                <select name="category_id" class="form-select" required>
                  <option value="">Select Category</option>
                  <?php foreach ($categories as $cat): ?>
                    <option value="<?php echo  $cat['id']; ?>" <?php echo  ($categoryId == $cat['id']) ? 'selected' : ''; ?>>
                      <?php echo  e($cat['name']); ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>

              <div class="col-md-6">
                <label class="form-label small fw-bold text-dark">Condition Grade <span class="text-danger">*</span></label>
                <select name="condition_grade" class="form-select" required>
                  <option value="Brand New" <?php echo  $conditionGrade === 'Brand New' ? 'selected' : ''; ?>>Brand New (Unopened or rarely tested)</option>
                  <option value="Like New" <?php echo  $conditionGrade === 'Like New' ? 'selected' : ''; ?>>Like New (Spotless, no noticeable wear)</option>
                  <option value="Gently Used" <?php echo  $conditionGrade === 'Gently Used' ? 'selected' : ''; ?>>Gently Used (Minor cosmetic marks, 100% functional)</option>
                  <option value="Fair" <?php echo  $conditionGrade === 'Fair' ? 'selected' : ''; ?>>Fair (Visible cosmetic signs, completely operational)</option>
                </select>
              </div>
            </div>

            <div class="mb-4">
              <label class="form-label small fw-bold text-dark">Detailed Description <span class="text-danger">*</span></label>
              <textarea name="description" class="form-control" rows="4" placeholder="Describe specifications, what's included in the box, and recommended use..." required><?php echo  e($description); ?></textarea>
            </div>

            <!-- Section 2: Pricing & Value -->
            <h5 class="fw-bold text-dark mb-3 pb-2 border-bottom">
              <i class="fa-solid fa-indian-rupee-sign text-success me-2"></i> 2. Rental Pricing & Security Deposit
            </h5>

            <div class="row g-3 mb-4">
              <div class="col-md-4">
                <label class="form-label small fw-bold text-dark">Daily Rental Rate (₹) <span class="text-danger">*</span></label>
                <div class="input-group">
                  <span class="input-group-text bg-light">₹</span>
                  <input type="number" name="daily_price" class="form-control" placeholder="e.g. 1400" value="<?php echo  e($dailyPrice); ?>" min="10" step="10" required>
                </div>
              </div>

              <div class="col-md-4">
                <label class="form-label small fw-bold text-dark">Security Deposit (₹)</label>
                <div class="input-group">
                  <span class="input-group-text bg-light">₹</span>
                  <input type="number" name="security_deposit" class="form-control" placeholder="e.g. 5000" value="<?php echo  e($securityDeposit); ?>" min="0" step="50">
                </div>
                <small class="text-muted" style="font-size: 0.72rem;">Held during rental & refunded after safe return.</small>
              </div>

              <div class="col-md-4">
                <label class="form-label small fw-bold text-dark">Original Purchase Value (₹)</label>
                <div class="input-group">
                  <span class="input-group-text bg-light">₹</span>
                  <input type="number" name="original_price" class="form-control" placeholder="e.g. 165000" value="<?php echo  e($originalPrice); ?>" min="0" step="100">
                </div>
                <small class="text-muted" style="font-size: 0.72rem;">Used for the Rental Savings Calculator.</small>
              </div>
            </div>

            <!-- Section 3: Photos Upload -->
            <h5 class="fw-bold text-dark mb-3 pb-2 border-bottom">
              <i class="fa-solid fa-camera text-success me-2"></i> 3. Product Photos
            </h5>

            <div class="mb-3">
              <label class="form-label small fw-bold text-dark">Upload High Quality Photos</label>
              <input type="file" name="product_images[]" id="product-images-input" class="form-control" multiple accept="image/*">
              <small class="text-muted" style="font-size: 0.75rem;">Upload clear photos. First image will be used as the primary cover photo.</small>
            </div>

            <!-- Live Multi-image Preview Grid -->
            <div id="image-preview-grid" class="row g-2 mb-4"></div>

            <!-- Section 4: Location & Instructions -->
            <h5 class="fw-bold text-dark mb-3 pb-2 border-bottom">
              <i class="fa-solid fa-location-dot text-success me-2"></i> 4. Location & Pickup Guidelines
            </h5>

            <div class="row g-3 mb-3">
              <div class="col-md-8">
                <label class="form-label small fw-bold text-dark">Pickup Locality / Street <span class="text-danger">*</span></label>
                <input type="text" name="location" class="form-control" placeholder="e.g. Near Infopark, Kakkanad" value="<?php echo  e($location); ?>" required>
              </div>
              <div class="col-md-4">
                <label class="form-label small fw-bold text-dark">City (Kerala)</label>
                <select name="city" class="form-select">
                  <option value="Kochi" <?php echo  $city === 'Kochi' ? 'selected' : ''; ?>>Kochi</option>
                  <option value="Trivandrum" <?php echo  $city === 'Trivandrum' ? 'selected' : ''; ?>>Trivandrum</option>
                  <option value="Kozhikode" <?php echo  $city === 'Kozhikode' ? 'selected' : ''; ?>>Kozhikode</option>
                  <option value="Thrissur" <?php echo  $city === 'Thrissur' ? 'selected' : ''; ?>>Thrissur</option>
                  <option value="Kottayam" <?php echo  $city === 'Kottayam' ? 'selected' : ''; ?>>Kottayam</option>
                  <option value="Kannur" <?php echo  $city === 'Kannur' ? 'selected' : ''; ?>>Kannur</option>
                </select>
              </div>
            </div>

            <div class="mb-4">
              <label class="form-label small fw-bold text-dark">Rental & Handling Instructions</label>
              <textarea name="rental_instructions" class="form-control" rows="3" placeholder="ID proof requirements, handling precautions, battery charging, returning expectations..."><?php echo  e($rentalInstructions); ?></textarea>
            </div>

            <div class="alert alert-light border small text-muted mb-4">
              <i class="fa-solid fa-shield-halved text-success me-1"></i> By publishing this listing, you agree to ReUseHub's Community Standards. All new listings undergo prompt verification.
            </div>

            <button type="submit" class="btn btn-primary-custom w-100 py-3 fs-6">
              <i class="fa-solid fa-cloud-arrow-up me-1"></i> Submit Listing for Verification
            </button>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

