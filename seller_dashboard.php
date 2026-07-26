<?php
session_start();
require_once 'config.php';

requireLogin();

$db = getDB();
$userId = $_SESSION['user_id'];
$message = '';
$msgType = 'success';

if (isset($_GET['action']) && isset($_GET['id'])) {
    $action = $_GET['action'];
    $prodId = (int)$_GET['id'];

    $checkStmt = $db->prepare("SELECT id, image_url FROM products WHERE id = :pid AND seller_id = :uid");
    $checkStmt->execute([':pid' => $prodId, ':uid' => $userId]);
    $product = $checkStmt->fetch();

    if ($product) {
        if ($action === 'delete') {
            if (!empty($product['image_url'])) {
                $imgPath = UPLOAD_DIR . $product['image_url'];
                if (file_exists($imgPath)) {
                    unlink($imgPath);
                }
            }
            $stmt = $db->prepare("DELETE FROM products WHERE id = :pid");
            $stmt->execute([':pid' => $prodId]);
            $message = "Listing deleted successfully.";
        } elseif ($action === 'mark_sold') {
            $stmt = $db->prepare("UPDATE products SET status = 'sold' WHERE id = :pid");
            $stmt->execute([':pid' => $prodId]);
            $message = "Listing marked as Sold. It is now hidden from the marketplace.";
        } elseif ($action === 'mark_active') {
            $stmt = $db->prepare("UPDATE products SET status = 'active' WHERE id = :pid");
            $stmt->execute([':pid' => $prodId]);
            $message = "Listing reactivated. It is now visible to buyers again.";
        }
    } else {
        $message = "Action failed: Permission denied or item not found.";
        $msgType = 'danger';
    }
}

$stmt = $db->prepare("SELECT * FROM products WHERE seller_id = :uid ORDER BY created_at DESC");
$stmt->execute([':uid' => $userId]);
$listings = $stmt->fetchAll();

$pageTitle = 'Manage My Listings';
include 'partials/header.php';
?>

<div class="container my-5" style="max-width: 1000px;">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
        <h2 class="km-section-title mb-0">Manage My Listings</h2>
        <a href="upload.php" class="btn btn-km-primary">
            <i class="bi bi-plus-circle me-2"></i>Create New Listing
        </a>
    </div>

    <?php if ($message): ?>
        <div class="km-alert km-alert-<?= e($msgType) ?> mb-4" data-auto-dismiss>
            <i class="bi bi-info-circle-fill flex-shrink-0"></i>
            <div><?= e($message) ?></div>
        </div>
    <?php endif; ?>

    <?php if (empty($listings)): ?>
        <div class="km-table-wrap p-5 text-center text-km-muted">
            <i class="bi bi-shop fs-1 d-block mb-3"></i>
            <h4>You have not listed any items yet.</h4>
            <p>Ready to start making money? List your first item today.</p>
            <a href="upload.php" class="btn btn-km-primary mt-3">Sell an Item</a>
        </div>
    <?php else: ?>
        <div class="km-table-wrap" style="overflow-x:auto;">
            <table class="km-table">
                <thead>
                    <tr>
                        <th>Item Details</th>
                        <th>Price</th>
                        <th>Stock</th>
                        <th>Status</th>
                        <th>Date Listed</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($listings as $item): ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-3">
                                    <?php if (!empty($item['image_url']) && file_exists(UPLOAD_DIR . $item['image_url'])): ?>
                                        <img src="<?= UPLOAD_URL . e($item['image_url']) ?>"
                                            alt="<?= e($item['title']) ?>"
                                            style="width:50px; height:50px; object-fit:cover; border-radius:6px; background:var(--km-bg-dark);">
                                    <?php else: ?>
                                        <div style="width:50px; height:50px; background:var(--km-bg-dark); border-radius:6px; display:flex; align-items:center; justify-content:center;">
                                            <i class="bi bi-image text-km-muted"></i>
                                        </div>
                                    <?php endif; ?>

                                    <div>
                                        <div class="fw-bold text-white" style="max-width: 250px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                            <?= e($item['title']) ?>
                                        </div>
                                        <small class="text-km-muted"><?= e($item['category']) ?></small>
                                    </div>
                                </div>
                            </td>

                            <td class="text-km-green fw-bold">
                                <?= formatPrice((float)$item['price']) ?>
                            </td>

                            <td class="fw-bold text-white">
                                <?= (int)$item['quantity'] ?>
                            </td>

                            <td>
                                <?php
                                $badgeClass = match ($item['status']) {
                                    'active'  => 'km-badge-verified',
                                    'sold'    => 'km-badge-suspended',
                                    'removed' => 'km-badge-suspended',
                                    'pending' => 'km-badge-pending',
                                    default   => 'km-badge-pending'
                                };
                                ?>
                                <span class="km-badge <?= $badgeClass ?>">
                                    <?= ucfirst(e($item['status'])) ?>
                                </span>
                            </td>

                            <td class="text-km-muted" style="font-size:13px;">
                                <?= date('d M Y', strtotime($item['created_at'])) ?>
                            </td>

                            <td>
                                <div class="d-flex gap-2">
                                    <a href="edit_listing.php?id=<?= $item['id'] ?>"
                                        class="btn btn-sm btn-km-primary"
                                        title="Edit Listing">
                                        <i class="bi bi-pencil-square"></i> Edit
                                    </a>

                                    <?php if ($item['status'] === 'active'): ?>
                                        <a href="seller_dashboard.php?action=mark_sold&id=<?= $item['id'] ?>"
                                            class="btn btn-sm btn-km-outline"
                                            title="Mark as Sold"
                                            data-confirm="Mark this item as sold? It will be hidden from buyers.">
                                            <i class="bi bi-tag-fill text-warning"></i> Sold
                                        </a>
                                    <?php elseif ($item['status'] === 'sold'): ?>
                                        <a href="seller_dashboard.php?action=mark_active&id=<?= $item['id'] ?>"
                                            class="btn btn-sm btn-km-outline"
                                            title="Reactivate Listing"
                                            data-confirm="Relist this item? It will become visible to buyers again.">
                                            <i class="bi bi-arrow-counterclockwise text-km-green"></i> Relist
                                        </a>
                                    <?php endif; ?>

                                    <a href="seller_dashboard.php?action=delete&id=<?= $item['id'] ?>"
                                        class="btn btn-sm"
                                        style="background:rgba(255,61,61,0.15);color:var(--km-danger);border:1px solid rgba(255,61,61,0.3);"
                                        title="Permanently Delete"
                                        data-confirm="Permanently delete this listing? This cannot be undone.">
                                        <i class="bi bi-trash3"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php include 'partials/footer.php'; ?>