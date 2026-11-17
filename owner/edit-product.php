<?php
/**
 * ReUseHub - Edit Existing Product Listing
 */

$pageTitle = 'Edit Product Listing';
$activePage = 'owner_products';
require_once __DIR__ . '/../includes/header.php';

requireOwner();
$user = currentUser();
$pdo = getDB();

$productId = (int)(isset($_GET['id']) ? $_GET['id'] : (isset($_POST['product_id']) ? $_POST['product_id'] : 0));
if ($productId <= 0) {
    header("Location: " . url('owner/my-products.php'));
    exit();
}

// Fetch existing product
$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ? AND owner_id = ?");
$stmt->execute(array($productId, $user['id']));
$product = $stmt->fetch();

if (!$product) {
    setFlash('danger', 'Listing not found or access denied.');
    header("Location: " . url('owner/my-products.php'));
    exit();
}

$categories = $pdo->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll();
$images = getProductImages($productId);
$errors = array();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_product') {
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
    $isAvailable = isset($_POST['is_available']) ? 1 : 0;

    if (empty($title)) $errors[] = 'Title is required.';
    if ($categoryId <= 0) $errors[] = 'Category is required.';
    if (empty($description)) $errors[] = 'Description is required.';
    if ($dailyPrice <= 0) $errors[] = 'Daily price must be greater than zero.';

    // Additional photo uploads
    $allowedExtensions = array('jpg', 'jpeg', 'png', 'webp');
    $allowedMimes = array('image/jpeg', 'image/png', 'image/webp');

    if (isset($_FILES['product_images']) && !empty($_FILES['product_images']['name'][0])) {
        $files = $_FILES['product_images'];
        $uploadDir = __DIR__ . '/../uploads/products/';

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
                        $pdo->prepare("INSERT INTO product_images (product_id, image_path, is_primary) VALUES (?, ?, 0)")
                            ->execute(array($productId, 'uploads/products/' . $newFileName));
                    }
                }
            }
        }
    }

    if (empty($errors)) {
        $upd = $pdo->prepare("UPDATE products SET 
            category_id = ?, title = ?, description = ?, daily_price = ?, security_deposit = ?, original_price = ?,
            condition_grade = ?, location = ?, city = ?, pincode = ?, rental_instructions = ?, is_available = ?, updated_at = NOW()
            WHERE id = ? AND owner_id = ?");
        $upd->execute(array(
            $categoryId, $title, $description, $dailyPrice, $securityDeposit, $originalPrice,
            $conditionGrade, $location, $city, $pincode, $rentalInstructions, $isAvailable,
            $productId, $user['id']
        ));

        setFlash('success', "Listing '{$title}' updated successfully.");
        header("Location: " . url('owner/my-products.php'));
        exit();
    }
}
?>

<div class="py-4" style="background-color: var(--bg-main);">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-lg-8">
        <div class="d-flex justify-content-between align-items-center mb-4">
          <div>
            <h3 class="fw-bold text-dark mb-1">Edit Listing</h3>
            <p class="text-muted small mb-0">Update pricing, description, photos and item availability.</p>
          </div>
          <a href="<?php echo  url('owner/my-products.php'); ?>" class="btn btn-outline-secondary btn-sm">
            <i class="fa-solid fa-arrow-left me-1"></i> Back to Products
          </a>
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

        <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5 bg-white">
          <form action="<?php echo  url('owner/edit-product.php?id=' . $productId); ?>" method="POST" enctype="multipart/form-data">
            <?php echo  csrf_field(); ?>
            <input type="hidden" name="action" value="update_product">
            <input type="hidden" name="product_id" value="<?php echo  $productId; ?>">

            <div class="mb-3">
              <label class="form-label small fw-bold text-dark">Listing Title</label>
              <input type="text" name="title" class="form-control" value="<?php echo  e($product['title']); ?>" required>
            </div>

            <div class="row g-3 mb-3">
              <div class="col-md-6">
                <label class="form-label small fw-bold text-dark">Category</label>
                <select name="category_id" class="form-select" required>
                  <?php foreach ($categories as $cat): ?>
                    <option value="<?php echo  $cat['id']; ?>" <?php echo  ($product['category_id'] == $cat['id']) ? 'selected' : ''; ?>>
                      <?php echo  e($cat['name']); ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>

              <div class="col-md-6">
                <label class="form-label small fw-bold text-dark">Condition Grade</label>
                <select name="condition_grade" class="form-select" required>
                  <option value="Brand New" <?php echo  $product['condition_grade'] === 'Brand New' ? 'selected' : ''; ?>>Brand New</option>
                  <option value="Like New" <?php echo  $product['condition_grade'] === 'Like New' ? 'selected' : ''; ?>>Like New</option>
                  <option value="Gently Used" <?php echo  $product['condition_grade'] === 'Gently Used' ? 'selected' : ''; ?>>Gently Used</option>
                  <option value="Fair" <?php echo  $product['condition_grade'] === 'Fair' ? 'selected' : ''; ?>>Fair</option>
                </select>
              </div>
            </div>

            <div class="mb-3">
              <label class="form-label small fw-bold text-dark">Description</label>
              <textarea name="description" class="form-control" rows="4" required><?php echo  e($product['description']); ?></textarea>
            </div>

            <div class="row g-3 mb-3">
              <div class="col-md-4">
                <label class="form-label small fw-bold text-dark">Daily Price (₹)</label>
                <input type="number" name="daily_price" class="form-control" value="<?php echo  e($product['daily_price']); ?>" required>
              </div>
              <div class="col-md-4">
                <label class="form-label small fw-bold text-dark">Security Deposit (₹)</label>
                <input type="number" name="security_deposit" class="form-control" value="<?php echo  e($product['security_deposit']); ?>">
              </div>
              <div class="col-md-4">
                <label class="form-label small fw-bold text-dark">Original Price (₹)</label>
                <input type="number" name="original_price" class="form-control" value="<?php echo  e($product['original_price']); ?>">
              </div>
            </div>

            <div class="row g-3 mb-3">
              <div class="col-md-8">
                <label class="form-label small fw-bold text-dark">Locality / Address</label>
                <input type="text" name="location" class="form-control" value="<?php echo  e($product['location']); ?>" required>
              </div>
              <div class="col-md-4">
                <label class="form-label small fw-bold text-dark">City</label>
                <select name="city" class="form-select">
                  <option value="Kochi" <?php echo  $product['city'] === 'Kochi' ? 'selected' : ''; ?>>Kochi</option>
                  <option value="Trivandrum" <?php echo  $product['city'] === 'Trivandrum' ? 'selected' : ''; ?>>Trivandrum</option>
                  <option value="Kozhikode" <?php echo  $product['city'] === 'Kozhikode' ? 'selected' : ''; ?>>Kozhikode</option>
                  <option value="Thrissur" <?php echo  $product['city'] === 'Thrissur' ? 'selected' : ''; ?>>Thrissur</option>
                </select>
              </div>
            </div>

            <div class="mb-3">
              <label class="form-label small fw-bold text-dark">Rental & Handling Instructions</label>
              <textarea name="rental_instructions" class="form-control" rows="3"><?php echo  e($product['rental_instructions']); ?></textarea>
            </div>

            <div class="form-check form-switch mb-4">
              <input class="form-check-input" type="checkbox" name="is_available" id="availSwitch" value="1" <?php echo  $product['is_available'] ? 'checked' : ''; ?>>
              <label class="form-check-label fw-bold text-dark small" for="availSwitch">Item is Currently Available for Rent</label>
            </div>

            <!-- Existing Photos -->
            <div class="mb-3">
              <label class="form-label small fw-bold text-dark">Current Photos</label>
              <div class="d-flex gap-2 flex-wrap mb-2">
                <?php foreach ($images as $img): ?>
                  <?php
                    $src = $img['image_path'];
                    if (!str_starts_with($src, 'http://') && !str_starts_with($src, 'https://')) {
                      $src = url($src);
                    }
                  ?>
                  <img src="<?php echo  e($src); ?>" class="rounded-3 border" width="80" height="60" style="object-fit: cover;">
                <?php endforeach; ?>
              </div>
              <label class="form-label small fw-bold text-dark">Add More Photos</label>
              <input type="file" name="product_images[]" class="form-control" multiple accept="image/*">
            </div>

            <button type="submit" class="btn btn-primary-custom w-100 py-3">
              <i class="fa-solid fa-floppy-disk me-1"></i> Save Changes
            </button>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

