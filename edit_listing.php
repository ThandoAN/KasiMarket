<?php
session_start();
require_once 'config.php';

requireLogin();

$db = getDB();
$userId = $_SESSION['user_id'];
$errors  = [];
$success = '';

$prodId = (int)($_GET['id'] ?? 0);
if ($prodId === 0) {
    redirect('seller_dashboard.php');
}

$stmt = $db->prepare("SELECT * FROM products WHERE id = :pid AND seller_id = :uid");
$stmt->execute([':pid' => $prodId, ':uid' => $userId]);
$product = $stmt->fetch();

if (!$product) {
    redirect('seller_dashboard.php');
}

$categories = [
    'Clothing & Shoes',
    'Electronics',
    'Books & Stationery',
    'Food & Groceries',
    'Furniture & Home',
    'Beauty & Health',
    'Sports & Outdoors',
    'Toys & Kids',
    'Other',
];
$conditions = ['New', 'Like New', 'Good', 'Fair'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title        = trim($_POST['title']        ?? '');
    $description  = trim($_POST['description']  ?? '');
    $price        = trim($_POST['price']        ?? '');
    $delivery_fee = trim($_POST['delivery_fee'] ?? '0.00');
    $quantity     = trim($_POST['quantity']     ?? '1');
    $category     = trim($_POST['category']     ?? '');
    $condition    = trim($_POST['condition']    ?? '');

    if (strlen($title) < 3) {
        $errors[] = 'Title must be at least 3 characters.';
    }
    if (strlen($title) > 200) {
        $errors[] = 'Title is too long (max 200 characters).';
    }
    if (!is_numeric($price) || (float)$price <= 0) {
        $errors[] = 'Please enter a valid price (greater than R0).';
    }
    if (!is_numeric($delivery_fee) || (float)$delivery_fee < 0) {
        $errors[] = 'Please enter a valid delivery fee (min R0).';
    }
    if (!is_numeric($quantity) || (int)$quantity < 1) {
        $errors[] = 'Please enter a valid quantity (minimum is 1).';
    }
    if (!in_array($category, $categories, true)) {
        $errors[] = 'Please select a valid category.';
    }
    if (!in_array($condition, $conditions, true)) {
        $errors[] = 'Please select a valid item condition.';
    }

    $imageFilename = $product['image_url'];

    if (!empty($_FILES['image']['name'])) {
        $file      = $_FILES['image'];
        $allowed   = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
        $maxSize   = 4 * 1024 * 1024;

        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'Image upload failed. Please try again.';
        } elseif (!in_array($file['type'], $allowed, true)) {
            $errors[] = 'Only JPG, PNG, WebP or GIF images are allowed.';
        } elseif ($file['size'] > $maxSize) {
            $errors[] = 'Image is too large. Maximum size is 4 MB.';
        } else {
            $imgInfo = @getimagesize($file['tmp_name']);
            if ($imgInfo === false || !in_array($imgInfo['mime'], $allowed, true)) {
                $errors[] = 'Invalid image file.';
            } else {
                $ext           = pathinfo($file['name'], PATHINFO_EXTENSION);
                $newImage      = uniqid('prod_', true) . '.' . strtolower($ext);
                $destPath      = UPLOAD_DIR . $newImage;

                if (move_uploaded_file($file['tmp_name'], $destPath)) {
                    if (!empty($imageFilename) && file_exists(UPLOAD_DIR . $imageFilename)) {
                        unlink(UPLOAD_DIR . $imageFilename);
                    }
                    $imageFilename = $newImage;
                } else {
                    $errors[] = 'Could not save the new image. Check folder permissions.';
                }
            }
        }
    }

    if (empty($errors)) {
        $upStmt = $db->prepare("
            UPDATE products 
            SET title = :title, description = :description, price = :price, 
                delivery_fee = :delivery_fee, quantity = :quantity, category = :category, 
                condition_type = :condition, image_url = :image_url, status = 'pending'
            WHERE id = :pid AND seller_id = :uid
        ");

        $upStmt->execute([
            ':title'        => $title,
            ':description'  => $description ?: null,
            ':price'        => number_format((float)$price, 2, '.', ''),
            ':delivery_fee' => number_format((float)$delivery_fee, 2, '.', ''),
            ':quantity'     => (int)$quantity,
            ':category'     => $category,
            ':condition'    => $condition,
            ':image_url'    => $imageFilename,
            ':pid'          => $prodId,
            ':uid'          => $userId
        ]);

        $success = 'Your item has been updated and is now pending admin approval!';

        $stmt->execute([':pid' => $prodId, ':uid' => $userId]);
        $product = $stmt->fetch();
    }
}

$pageTitle = 'Edit Listing';
include 'partials/header.php';
?>

<div class="km-upload-page">
    <div class="container" style="max-width: 780px;">
        <div class="mb-4">
            <a href="seller_dashboard.php" class="btn btn-km-ghost btn-sm mb-3">
                <i class="bi bi-arrow-left me-1"></i>Back to Dashboard
            </a>
            <h1 style="font-size:1.8rem;">Edit Your Item</h1>
            <p class="text-km-muted">Update your listing details below.</p>
        </div>

        <?php if ($success): ?>
            <div class="km-alert km-alert-success mb-4" data-auto-dismiss>
                <i class="bi bi-check-circle-fill flex-shrink-0"></i>
                <div>
                    <strong><?= e($success) ?></strong>
                </div>
            </div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <div class="km-alert km-alert-danger mb-4">
                <i class="bi bi-exclamation-circle-fill flex-shrink-0 mt-1"></i>
                <div>
                    <?php foreach ($errors as $err): ?>
                        <div><?= e($err) ?></div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <div class="km-alert km-alert-warning mb-4">
            <i class="bi bi-hourglass-split flex-shrink-0"></i>
            <span><strong>Please note:</strong> Saving changes will require the listing to be approved by an admin again.</span>
        </div>

        <div class="km-upload-card">
            <div class="km-upload-header">
                <h5 class="mb-1 text-white">
                    <i class="bi bi-pencil-square me-2 text-km-green"></i>Edit Listing
                </h5>
                <small class="text-km-muted">Fields marked with * are required.</small>
            </div>
            <div class="km-upload-body">
                <form method="POST" action="edit_listing.php?id=<?= $prodId ?>" enctype="multipart/form-data" novalidate>
                    <div class="row g-4">
                        <div class="col-12 col-md-5">
                            <label class="km-form-label">Product Photo</label>
                            <div class="km-photo-drop" id="photoDropZone">
                                <?php if (!empty($product['image_url']) && file_exists(UPLOAD_DIR . $product['image_url'])): ?>
                                    <img id="photoPreview" class="km-photo-preview" src="<?= UPLOAD_URL . e($product['image_url']) ?>" alt="Preview" style="display:block;">
                                    <div id="dropText" style="display:none;">
                                    <?php else: ?>
                                        <img id="photoPreview" class="km-photo-preview" src="" alt="Preview" style="display:none;">
                                        <div id="dropText">
                                        <?php endif; ?>
                                        <i class="bi bi-image-fill"></i>
                                        <p>
                                            <span>Click to upload</span> to change photo<br>
                                            <small class="text-km-muted">JPG, PNG, WebP max 4 MB</small>
                                        </p>
                                        </div>
                                    </div>
                                    <input type="file" id="productImage" name="image" class="km-photo-hidden" accept="image/jpeg,image/png,image/webp,image/gif">
                            </div>

                            <div class="col-12 col-md-7">
                                <div class="mb-3">
                                    <label class="km-form-label" for="title">Item Title *</label>
                                    <input type="text" id="title" name="title"
                                        class="km-form-control"
                                        placeholder="e.g. Air Jordan 1 Retro High OG Size 10"
                                        value="<?= e($_POST['title'] ?? $product['title']) ?>"
                                        maxlength="200" required>
                                </div>

                                <div class="row g-3 mb-3">
                                    <div class="col-12 col-md-4">
                                        <label class="km-form-label" for="price">Price (ZAR) *</label>
                                        <div style="position:relative;">
                                            <span style="position:absolute;left:13px;top:50%;transform:translateY(-50%);color:var(--km-muted);font-weight:700;">R</span>
                                            <input type="number" id="price" name="price"
                                                class="km-form-control"
                                                style="padding-left:28px !important;"
                                                placeholder="0.00"
                                                min="1" step="0.01"
                                                value="<?= e($_POST['price'] ?? $product['price']) ?>"
                                                required>
                                        </div>
                                    </div>
                                    <div class="col-6 col-md-4">
                                        <label class="km-form-label" for="quantity">Stock *</label>
                                        <input type="number" id="quantity" name="quantity"
                                            class="km-form-control"
                                            placeholder="1"
                                            min="1"
                                            value="<?= e($_POST['quantity'] ?? $product['quantity']) ?>"
                                            required>
                                    </div>
                                    <div class="col-6 col-md-4">
                                        <label class="km-form-label" for="condition">Condition *</label>
                                        <select id="condition" name="condition" class="km-select form-select" required>
                                            <option value="">Select...</option>
                                            <?php
                                            $currentCond = $_POST['condition'] ?? $product['condition_type'];
                                            foreach ($conditions as $cond):
                                            ?>
                                                <option value="<?= e($cond) ?>" <?= ($currentCond === $cond) ? 'selected' : '' ?>>
                                                    <?= e($cond) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="km-form-label" for="category">Category *</label>
                                    <select id="category" name="category" class="km-select form-select" required>
                                        <option value="">Select a category...</option>
                                        <?php
                                        $currentCat = $_POST['category'] ?? $product['category'];
                                        foreach ($categories as $cat):
                                        ?>
                                            <option value="<?= e($cat) ?>" <?= ($currentCat === $cat) ? 'selected' : '' ?>>
                                                <?= e($cat) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label class="km-form-label" for="description">Description</label>
                                    <textarea id="description" name="description"
                                        class="km-form-control"
                                        placeholder="Describe your item - size, brand, any defects, what is included, etc."
                                        rows="3"><?= e($_POST['description'] ?? $product['description']) ?></textarea>
                                </div>

                                <div class="mb-4">
                                    <label class="km-form-label" for="delivery_fee">Delivery Fee / Cost (ZAR) *</label>
                                    <div style="position:relative;">
                                        <span style="position:absolute;left:13px;top:50%;transform:translateY(-50%);color:var(--km-muted);font-weight:700;">R</span>
                                        <input type="number" id="delivery_fee" name="delivery_fee"
                                            class="km-form-control"
                                            style="padding-left:28px !important;"
                                            placeholder="0.00 (Enter 0 if delivery is free)"
                                            min="0" step="0.01"
                                            value="<?= e($_POST['delivery_fee'] ?? $product['delivery_fee']) ?>"
                                            required>
                                    </div>
                                    <small class="text-km-muted mt-1 d-block">
                                        Specify the cost to dispatch and ship this item to the buyer.
                                    </small>
                                </div>

                                <button type="submit" class="btn btn-km-primary w-100 py-2">
                                    <i class="bi bi-save me-1"></i>
                                    Save Changes & Submit
                                </button>

                            </div>
                        </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include 'partials/footer.php'; ?>