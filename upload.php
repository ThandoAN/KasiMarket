<?php
session_start();
require_once 'config.php';

requireLogin();

$errors  = [];
$success = '';
$old     = [];

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
    $old['title']        = trim($_POST['title']        ?? '');
    $old['description']  = trim($_POST['description']  ?? '');
    $old['price']        = trim($_POST['price']        ?? '');
    $old['delivery_fee'] = trim($_POST['delivery_fee'] ?? '0.00');
    $old['quantity']     = trim($_POST['quantity']     ?? '1');
    $old['category']     = trim($_POST['category']     ?? '');
    $old['condition']    = trim($_POST['condition']    ?? '');

    if (strlen($old['title']) < 3) {
        $errors[] = 'Title must be at least 3 characters.';
    }
    if (strlen($old['title']) > 200) {
        $errors[] = 'Title is too long (max 200 characters).';
    }
    if (!is_numeric($old['price']) || (float)$old['price'] <= 0) {
        $errors[] = 'Please enter a valid price (greater than R0).';
    }
    if (!is_numeric($old['delivery_fee']) || (float)$old['delivery_fee'] < 0) {
        $errors[] = 'Please enter a valid delivery fee (min R0).';
    }
    if (!is_numeric($old['quantity']) || (int)$old['quantity'] < 1) {
        $errors[] = 'Please enter a valid quantity (minimum is 1).';
    }
    if (!in_array($old['category'], $categories, true)) {
        $errors[] = 'Please select a valid category.';
    }
    if (!in_array($old['condition'], $conditions, true)) {
        $errors[] = 'Please select a valid item condition.';
    }

    $imageFilename = null;
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
                $imageFilename = uniqid('prod_', true) . '.' . strtolower($ext);
                $destPath      = UPLOAD_DIR . $imageFilename;

                if (!move_uploaded_file($file['tmp_name'], $destPath)) {
                    $errors[] = 'Could not save the image. Check folder permissions.';
                    $imageFilename = null;
                }
            }
        }
    }

    if (empty($errors)) {
        $db   = getDB();
        $stmt = $db->prepare("
            INSERT INTO products
                (seller_id, title, description, price, delivery_fee, quantity, category, image_url,
                 condition_type, status)
            VALUES
                (:seller_id, :title, :description, :price, :delivery_fee, :quantity, :category, :image_url,
                 :condition, 'pending')
        ");
        $stmt->execute([
            ':seller_id'    => $_SESSION['user_id'],
            ':title'        => $old['title'],
            ':description'  => $old['description'] ?: null,
            ':price'        => number_format((float)$old['price'], 2, '.', ''),
            ':delivery_fee' => number_format((float)$old['delivery_fee'], 2, '.', ''),
            ':quantity'     => (int)$old['quantity'],
            ':category'     => $old['category'],
            ':image_url'    => $imageFilename,
            ':condition'    => $old['condition'],
        ]);

        $success = 'Your item has been submitted and is pending admin approval!';
        $old = [];
    }
}

$pageTitle = 'List an Item for Sale';
include 'partials/header.php';
?>

<div class="km-upload-page">
    <div class="container" style="max-width: 780px;">
        <div class="mb-4">
            <a href="index.php" class="btn btn-km-ghost btn-sm mb-3">
                <i class="bi bi-arrow-left me-1"></i>Back to Listings
            </a>
            <h1 style="font-size:1.8rem;">List Your Item</h1>
            <p class="text-km-muted">Fill in the details below and start selling today.</p>
        </div>

        <?php if ($success): ?>
            <div class="km-alert km-alert-success mb-4" data-auto-dismiss>
                <i class="bi bi-check-circle-fill flex-shrink-0"></i>
                <div>
                    <strong><?= e($success) ?></strong>
                    <div><a href="seller_dashboard.php" class="text-km-green">View your listings</a></div>
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
            <span><strong>All new listings require admin approval.</strong> Your item will appear on the storefront once verified.</span>
        </div>

        <div class="km-upload-card">
            <div class="km-upload-header">
                <h5 class="mb-1 text-white">
                    <i class="bi bi-plus-circle-fill me-2 text-km-green"></i>New Listing
                </h5>
                <small class="text-km-muted">Fields marked with * are required.</small>
            </div>
            <div class="km-upload-body">
                <form method="POST" action="upload.php"
                    enctype="multipart/form-data" novalidate>
                    <div class="row g-4">
                        <div class="col-12 col-md-5">
                            <label class="km-form-label">Product Photo</label>
                            <div class="km-photo-drop" id="photoDropZone">
                                <img id="photoPreview"
                                    class="km-photo-preview"
                                    src="" alt="Preview"
                                    style="display:none;">
                                <div id="dropText">
                                    <i class="bi bi-image-fill"></i>
                                    <p>
                                        <span>Click to upload</span> or drag a photo here<br>
                                        <small class="text-km-muted">JPG, PNG, WebP max 4 MB</small>
                                    </p>
                                </div>
                            </div>
                            <input type="file" id="productImage" name="image"
                                class="km-photo-hidden"
                                accept="image/jpeg,image/png,image/webp,image/gif">

                            <div class="km-tip-box mt-3">
                                <strong><i class="bi bi-lightbulb me-1"></i>Selling Tips</strong>
                                <ul class="mb-0 mt-2 ps-3" style="line-height:1.8;">
                                    <li>Good photos sell faster</li>
                                    <li>Be honest about the condition</li>
                                    <li>Set a fair, competitive price</li>
                                </ul>
                            </div>
                        </div>

                        <div class="col-12 col-md-7">
                            <div class="mb-3">
                                <label class="km-form-label" for="title">Item Title *</label>
                                <input type="text" id="title" name="title"
                                    class="km-form-control"
                                    placeholder="e.g. Air Jordan 1 Retro High OG Size 10"
                                    value="<?= e($old['title'] ?? '') ?>"
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
                                            value="<?= e($old['price'] ?? '') ?>"
                                            required>
                                    </div>
                                </div>
                                <div class="col-6 col-md-4">
                                    <label class="km-form-label" for="quantity">Stock *</label>
                                    <input type="number" id="quantity" name="quantity"
                                        class="km-form-control"
                                        placeholder="1"
                                        min="1"
                                        value="<?= e($old['quantity'] ?? '1') ?>"
                                        required>
                                </div>
                                <div class="col-6 col-md-4">
                                    <label class="km-form-label" for="condition">Condition *</label>
                                    <select id="condition" name="condition" class="km-select form-select" required>
                                        <option value="">Select...</option>
                                        <?php foreach ($conditions as $cond): ?>
                                            <option value="<?= e($cond) ?>"
                                                <?= (($old['condition'] ?? '') === $cond) ? 'selected' : '' ?>>
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
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?= e($cat) ?>"
                                            <?= (($old['category'] ?? '') === $cat) ? 'selected' : '' ?>>
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
                                    rows="3"><?= e($old['description'] ?? '') ?></textarea>
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
                                        value="<?= e($old['delivery_fee'] ?? '0.00') ?>"
                                        required>
                                </div>
                                <small class="text-km-muted mt-1 d-block">
                                    Specify the cost to dispatch and ship this item to the buyer.
                                </small>
                            </div>

                            <button type="submit" class="btn btn-km-primary w-100 py-2">
                                <i class="bi bi-check2-circle me-1"></i>
                                Submit for Approval
                            </button>

                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include 'partials/footer.php'; ?>