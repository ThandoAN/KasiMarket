<?php
session_start();
require_once 'config.php';

requireLogin();

$db = getDB();
$userId = $_SESSION['user_id'];
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'add') {
        $productId = (int)($_POST['product_id'] ?? 0);
        $qty = (int)($_POST['quantity'] ?? 1);

        $pStmt = $db->prepare("SELECT quantity FROM products WHERE id = :id AND status = 'active'");
        $pStmt->execute([':id' => $productId]);
        $stockAvailable = (int)$pStmt->fetchColumn();

        if ($stockAvailable > 0) {
            $stmt = $db->prepare("
                INSERT INTO cart (user_id, product_id, quantity)
                VALUES (:uid, :pid, :qty)
                ON DUPLICATE KEY UPDATE quantity = LEAST(quantity + :qty_update, :stock)
            ");
            $stmt->execute([
                ':uid' => $userId,
                ':pid' => $productId,
                ':qty' => $qty,
                ':qty_update' => $qty,
                ':stock' => $stockAvailable
            ]);
            $message = "Item added to cart successfully!";
        }
    }

    if (isset($_POST['action']) && $_POST['action'] === 'update') {
        $cartId = (int)($_POST['cart_id'] ?? 0);
        $newQty = (int)($_POST['quantity'] ?? 1);

        if ($newQty < 1) {
            $stmt = $db->prepare("DELETE FROM cart WHERE id = :id AND user_id = :uid");
            $stmt->execute([':id' => $cartId, ':uid' => $userId]);
        } else {
            $stmt = $db->prepare("SELECT p.quantity FROM cart c JOIN products p ON c.product_id = p.id WHERE c.id = :cid");
            $stmt->execute([':cid' => $cartId]);
            $maxStock = (int)$stmt->fetchColumn();

            $finalQty = min($newQty, $maxStock);

            $stmt = $db->prepare("UPDATE cart SET quantity = :qty WHERE id = :id AND user_id = :uid");
            $stmt->execute([':qty' => $finalQty, ':id' => $cartId, ':uid' => $userId]);
        }
        $message = "Cart quantities updated.";
    }

    if (isset($_POST['action']) && $_POST['action'] === 'delete') {
        $cartId = (int)($_POST['cart_id'] ?? 0);
        $stmt = $db->prepare("DELETE FROM cart WHERE id = :id AND user_id = :uid");
        $stmt->execute([':id' => $cartId, ':uid' => $userId]);
        $message = "Item removed from cart.";
    }
}

$stmt = $db->prepare("
    SELECT c.id AS cart_id, c.quantity AS selected_qty, p.* FROM cart c
    JOIN products p ON c.product_id = p.id
    WHERE c.user_id = :uid
");
$stmt->execute([':uid' => $userId]);
$cartItems = $stmt->fetchAll();

$pageTitle = 'My Shopping Cart';
include 'partials/header.php';
?>

<div class="container my-5">
    <h2 class="mb-4 km-section-title">Your Cart</h2>

    <?php if ($message): ?><div class="km-alert km-alert-success"><?= e($message) ?></div><?php endif; ?>

    <?php if (empty($cartItems)): ?>
        <div class="km-table-wrap p-5 text-center text-km-muted">
            <i class="bi bi-cart-x fs-1 d-block mb-3"></i>
            <h4>Your cart is empty</h4>
            <a href="index.php" class="btn btn-km-primary mt-3">Browse Items</a>
        </div>
    <?php else: ?>
        <div class="row g-4">
            <div class="col-12 col-lg-8">
                <div class="km-table-wrap" style="overflow-x:auto;">
                    <table class="km-table">
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th>Price</th>
                                <th>Delivery Cost</th>
                                <th>Quantity</th>
                                <th>Subtotal</th>
                                <th>Remove</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $itemsTotal = 0;
                            $deliveryTotal = 0;

                            foreach ($cartItems as $item):
                                $subtotal = (float)$item['price'] * (int)$item['selected_qty'];
                                $itemsTotal += $subtotal;
                                $deliveryTotal += (float)$item['delivery_fee'];
                            ?>
                                <tr>
                                    <td><span class="text-white fw-bold"><?= e($item['title']) ?></span></td>
                                    <td class="text-km-green fw-bold"><?= formatPrice((float)$item['price']) ?></td>
                                    <td class="text-white"><?= (float)$item['delivery_fee'] > 0 ? formatPrice((float)$item['delivery_fee']) : 'Free' ?></td>
                                    <td>
                                        <form method="POST" action="cart.php" style="max-width:90px;">
                                            <input type="hidden" name="action" value="update">
                                            <input type="hidden" name="cart_id" value="<?= $item['cart_id'] ?>">
                                            <input type="number" name="quantity" class="km-form-control text-center py-1" value="<?= $item['selected_qty'] ?>" min="1" max="<?= $item['quantity'] ?>" onchange="this.form.submit()">
                                        </form>
                                    </td>
                                    <td class="text-white fw-bold"><?= formatPrice($subtotal) ?></td>
                                    <td>
                                        <form method="POST" action="cart.php">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="cart_id" value="<?= $item['cart_id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash3"></i></button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="col-12 col-lg-4">
                <div class="km-table-wrap p-4">
                    <h5 class="text-white mb-3">Summary</h5>

                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-km-muted">Items Total:</span>
                        <span class="text-white"><?= formatPrice($itemsTotal) ?></span>
                    </div>

                    <div class="d-flex justify-content-between mb-3 border-bottom border-secondary pb-2">
                        <span class="text-km-muted">Delivery Total:</span>
                        <span class="text-white"><?= formatPrice($deliveryTotal) ?></span>
                    </div>

                    <div class="d-flex justify-content-between mb-4 fs-5">
                        <span class="text-km-muted">Grand Total:</span>
                        <strong class="text-km-green"><?= formatPrice($itemsTotal + $deliveryTotal) ?></strong>
                    </div>

                    <a href="checkout.php" class="btn btn-km-primary w-100 py-2">Proceed to Checkout</a>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include 'partials/footer.php'; ?>