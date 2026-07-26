<?php
session_start();
require_once 'config.php';

$db = getDB();
$prodId = (int)($_GET['id'] ?? 0);

if ($prodId === 0) {
    redirect('index.php');
}

$stmt = $db->prepare("
    SELECT p.*, u.name AS seller_name 
    FROM products p 
    JOIN users u ON p.seller_id = u.id 
    WHERE p.id = :id AND p.status = 'active'
");
$stmt->execute([':id' => $prodId]);
$product = $stmt->fetch();

if (!$product) {
    redirect('index.php');
}

$pageTitle = $product['title'];
include 'partials/header.php';
?>

<style>
    .km-modal {
        display: none;
        position: fixed;
        z-index: 9999;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.9);
        align-items: center;
        justify-content: center;
    }

    .km-modal img {
        max-width: 90%;
        max-height: 80%;
        border-radius: 8px;
    }

    .km-close {
        position: absolute;
        top: 20px;
        right: 30px;
        color: #fff;
        font-size: 40px;
        cursor: pointer;
    }
</style>

<div id="imageModal" class="km-modal" onclick="closeModal()">
    <span class="km-close" onclick="closeModal()">&times;</span>
    <img id="fullSizeImg" src="" alt="Full size image">
</div>

<div class="container my-5">
    <div class="row g-5">
        <div class="col-12 col-md-6">
            <div class="km-card-img-wrap" style="height: 400px; border-radius: 12px; overflow: hidden; cursor: pointer;" onclick="openModal('<?= UPLOAD_URL . e($product['image_url']) ?>')">
                <?php if (!empty($product['image_url']) && file_exists(UPLOAD_DIR . $product['image_url'])): ?>
                    <img src="<?= UPLOAD_URL . e($product['image_url']) ?>"
                        alt="<?= e($product['title']) ?>"
                        style="width:100%; height:100%; object-fit:cover;">
                <?php else: ?>
                    <div class="h-100 d-flex align-items-center justify-content-center bg-dark text-muted">
                        <i class="bi bi-box" style="font-size: 5rem;"></i>
                    </div>
                <?php endif; ?>
            </div>
            <p class="text-km-muted mt-2 small text-center">Click image to enlarge</p>
        </div>

        <div class="col-12 col-md-6 text-white">
            <span class="badge bg-secondary mb-2"><?= e($product['category']) ?></span>
            <h1 class="mb-3"><?= e($product['title']) ?></h1>
            <h3 class="text-km-green mb-4"><?= formatPrice((float)$product['price']) ?></h3>

            <div class="mb-4">
                <h6 class="text-km-muted">Availability</h6>
                <p>
                    <?php if ((int)$product['quantity'] > 0): ?>
                        <span class="text-success"><i class="bi bi-check-circle-fill me-1"></i><?= (int)$product['quantity'] ?> items in stock</span>
                    <?php else: ?>
                        <span class="text-danger">Out of stock</span>
                    <?php endif; ?>
                </p>
            </div>

            <div class="mb-4">
                <h6 class="text-km-muted">Delivery</h6>
                <p>
                    <i class="bi bi-truck me-2"></i>
                    <?= (float)$product['delivery_fee'] > 0 ? 'Delivery Fee: ' . formatPrice((float)$product['delivery_fee']) : 'Free delivery' ?>
                </p>
            </div>

            <div class="mb-4">
                <h6 class="text-km-muted">Description</h6>
                <p style="line-height: 1.8; color: #ccc;">
                    <?= nl2br(e($product['description'] ?: 'No description provided by the seller.')) ?>
                </p>
            </div>

            <?php if ((int)$product['quantity'] > 0): ?>
                <form method="POST" action="cart.php">
                    <input type="hidden" name="action" value="add">
                    <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                    <div class="d-flex align-items-center gap-3">
                        <input type="number" name="quantity" class="km-form-control" value="1" min="1" max="<?= (int)$product['quantity'] ?>" style="width: 80px;">
                        <button type="submit" class="btn btn-km-primary px-4 py-2">
                            <i class="bi bi-cart-plus me-1"></i>Add to Cart
                        </button>
                    </div>
                </form>
            <?php else: ?>
                <button class="btn btn-secondary" disabled>Out of Stock</button>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    function openModal(imgSrc) {
        document.getElementById('fullSizeImg').src = imgSrc;
        document.getElementById('imageModal').style.display = 'flex';
    }

    function closeModal() {
        document.getElementById('imageModal').style.display = 'none';
    }
</script>

<?php include 'partials/footer.php'; ?>