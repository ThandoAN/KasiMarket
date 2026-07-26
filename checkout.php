<?php
session_start();
require_once 'config.php';

requireLogin();

$db = getDB();
$userId = $_SESSION['user_id'];
$error = '';

$stmt = $db->prepare("SELECT c.quantity AS selected_qty, p.* FROM cart c JOIN products p ON c.product_id = p.id WHERE c.user_id = :uid");
$stmt->execute([':uid' => $userId]);
$cartItems = $stmt->fetchAll();

if (empty($cartItems)) {
    redirect('cart.php');
}

$itemsTotal = 0;
$deliveryTotal = 0;

foreach ($cartItems as $item) {
    $itemsTotal += ((float)$item['price'] * (int)$item['selected_qty']);
    $deliveryTotal += (float)$item['delivery_fee'];
}

$grandTotal = $itemsTotal + $deliveryTotal;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $address = trim($_POST['address'] ?? '');
    $phone   = trim($_POST['phone']   ?? '');

    if (empty($address) || empty($phone)) {
        $error = 'All billing information blocks are strictly mandatory.';
    } else {
        try {
            $db->beginTransaction();

            $orderStmt = $db->prepare("
                INSERT INTO orders (user_id, total_amount, delivery_address, contact_phone, status)
                VALUES (:uid, :total, :address, :phone, 'paid')
            ");
            $orderStmt->execute([
                ':uid'     => $userId,
                ':total'   => $grandTotal,
                ':address' => $address,
                ':phone'   => $phone
            ]);
            $orderId = $db->lastInsertId();

            foreach ($cartItems as $item) {
                $deduct = $db->prepare("UPDATE products SET quantity = quantity - :qty WHERE id = :pid");
                $deduct->execute([':qty' => $item['selected_qty'], ':pid' => $item['id']]);

                $itemStmt = $db->prepare("
                    INSERT INTO order_items (order_id, product_id, price, quantity)
                    VALUES (:oid, :pid, :price, :qty)
                ");
                $itemStmt->execute([
                    ':oid'   => $orderId,
                    ':pid'   => $item['id'],
                    ':price' => $item['price'],
                    ':qty'   => $item['selected_qty']
                ]);
            }

            $clear = $db->prepare("DELETE FROM cart WHERE user_id = :uid");
            $clear->execute([':uid' => $userId]);

            $db->commit();
            redirect('buyer_dashboard.php?msg=success');
        } catch (Exception $e) {
            $db->rollBack();
            $error = "Payment tracking exception encountered: " . $e->getMessage();
        }
    }
}

$pageTitle = 'Secure Checkout';
include 'partials/header.php';
?>

<div class="container my-5" style="max-width: 600px;">
    <h2 class="mb-4 text-center km-section-title">Order Checkout</h2>

    <?php if ($error): ?><div class="km-alert km-alert-danger"><?= e($error) ?></div><?php endif; ?>

    <div class="km-table-wrap p-4">
        <form method="POST" action="checkout.php">
            <div class="mb-3">
                <label class="km-form-label">Full Residential Delivery Destination *</label>
                <textarea name="address" class="km-form-control" rows="3" placeholder="Enter your full home or work address details..." required></textarea>
            </div>

            <div class="mb-4">
                <label class="km-form-label">Contact Mobile Number *</label>
                <input type="tel" name="phone" class="km-form-control" placeholder="071 234 5678" required>
            </div>

            <div class="d-flex justify-content-between mb-2 text-white border-top border-secondary pt-3 fs-6">
                <span>Items Subtotal:</span>
                <span><?= formatPrice($itemsTotal) ?></span>
            </div>

            <div class="d-flex justify-content-between mb-3 text-white fs-6">
                <span>Delivery Total Cost:</span>
                <span><?= formatPrice($deliveryTotal) ?></span>
            </div>

            <div class="d-flex justify-content-between mb-4 fs-5 text-white border-top border-secondary pt-2">
                <span>Grand Total:</span>
                <strong class="text-km-green"><?= formatPrice($grandTotal) ?></strong>
            </div>

            <button type="submit" class="btn btn-km-primary w-100 py-2">Authorize and Confirm Payment</button>
        </form>
    </div>
</div>

<?php include 'partials/footer.php'; ?>