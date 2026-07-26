<?php
session_start();
require_once 'config.php';
requireLogin();

$db = getDB();
$userId = $_SESSION['user_id'];
$msg = '';

if (isset($_GET['msg']) && $_GET['msg'] === 'success') {
    $msg = 'Order generated successfully! Stock verification locked down.';
}

$stmt = $db->prepare("SELECT * FROM orders WHERE user_id = :uid ORDER BY created_at DESC");
$stmt->execute([':uid' => $userId]);
$orders = $stmt->fetchAll();

$pageTitle = 'Buyer Dashboard';
include 'partials/header.php';
?>

<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h2 class="km-section-title mb-1">Buyer Operations Hub</h2>
            <p class="text-km-muted mb-0">Monitor shipment paths and processed transactions here.</p>
        </div>
        <a href="index.php" class="btn btn-km-outline btn-sm">Return Marketplace</a>
    </div>

    <?php if ($msg): ?><div class="km-alert km-alert-success"><?= e($msg) ?></div><?php endif; ?>

    <?php if (empty($orders)): ?>
        <div class="km-table-wrap p-5 text-center text-km-muted">
            <i class="bi bi-box-seam fs-2 d-block mb-2"></i>
            <p>You haven't checked out any items yet.</p>
        </div>
    <?php else: ?>
        <div class="km-table-wrap" style="overflow-x:auto;">
            <table class="km-table">
                <thead>
                    <tr>
                        <th>Order #</th>
                        <th>Date Registered</th>
                        <th>Shipment Target Address</th>
                        <th>Total Amount Paid</th>
                        <th>Order Fulfillment State</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): ?>
                        <tr>
                            <td class="text-white fw-bold">#<?= $order['id'] ?></td>
                            <td><?= date('d M Y, H:i', strtotime($order['created_at'])) ?></td>
                            <td><span class="d-inline-block text-truncate" style="max-width: 200px;"><?= e($order['delivery_address']) ?></span></td>
                            <td class="text-km-green fw-bold"><?= formatPrice((float)$order['total_amount']) ?></td>
                            <td><span class="km-badge km-badge-verified"><?= ucfirst(e($order['status'])) ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php include 'partials/footer.php'; ?>