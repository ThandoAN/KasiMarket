<?php
session_start();
require_once 'config.php';

requireAdmin();

$db      = getDB();
$message = '';
$msgType = 'success';

if (isset($_GET['action']) && $_GET['action'] === 'verify' && isset($_GET['uid'])) {
    $uid  = (int) $_GET['uid'];
    $stmt = $db->prepare("UPDATE users SET status = 'verified' WHERE id = :id AND role = 'user'");
    $stmt->execute([':id' => $uid]);
    $message = 'User verified successfully.';
}

if (isset($_GET['action']) && $_GET['action'] === 'suspend' && isset($_GET['uid'])) {
    $uid  = (int) $_GET['uid'];
    $stmt = $db->prepare("UPDATE users SET status = 'suspended' WHERE id = :id AND role = 'user'");
    $stmt->execute([':id' => $uid]);
    $message = 'User has been suspended.';
    $msgType = 'warning';
}

if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['uid'])) {
    $uid = (int) $_GET['uid'];

    if ($uid === (int) $_SESSION['user_id']) {
        $message = 'You cannot delete your own admin account.';
        $msgType = 'danger';
    } else {
        $prodStmt = $db->prepare("SELECT image_url FROM products WHERE seller_id = :id");
        $prodStmt->execute([':id' => $uid]);
        while ($row = $prodStmt->fetch()) {
            if ($row['image_url']) {
                $imgPath = UPLOAD_DIR . $row['image_url'];
                if (file_exists($imgPath)) unlink($imgPath);
            }
        }

        $stmt = $db->prepare("DELETE FROM users WHERE id = :id AND role != 'admin'");
        $stmt->execute([':id' => $uid]);
        $message = 'User and their listings have been deleted.';
        $msgType = 'danger';
    }
}

if (isset($_GET['action']) && $_GET['action'] === 'approve_product' && isset($_GET['pid'])) {
    $pid  = (int) $_GET['pid'];
    $stmt = $db->prepare("UPDATE products SET status = 'active' WHERE id = :id");
    $stmt->execute([':id' => $pid]);
    $message = 'Product approved and is now live on the marketplace.';
    $msgType = 'success';
}

if (isset($_GET['action']) && $_GET['action'] === 'remove_product' && isset($_GET['pid'])) {
    $pid  = (int) $_GET['pid'];

    $stmt = $db->prepare("SELECT image_url FROM products WHERE id = :id");
    $stmt->execute([':id' => $pid]);
    $prod = $stmt->fetch();
    if ($prod && $prod['image_url']) {
        $imgPath = UPLOAD_DIR . $prod['image_url'];
        if (file_exists($imgPath)) unlink($imgPath);
    }

    $stmt = $db->prepare("DELETE FROM products WHERE id = :id");
    $stmt->execute([':id' => $pid]);
    $message = 'Product listing removed.';
    $msgType = 'warning';
}

$stats = [
    'total_users'      => $db->query("SELECT COUNT(*) FROM users WHERE role='user'")->fetchColumn(),
    'verified_users'   => $db->query("SELECT COUNT(*) FROM users WHERE role='user' AND status='verified'")->fetchColumn(),
    'pending_users'    => $db->query("SELECT COUNT(*) FROM users WHERE role='user' AND status='pending'")->fetchColumn(),
    'total_products'   => $db->query("SELECT COUNT(*) FROM products WHERE status='active'")->fetchColumn(),
    'pending_products' => $db->query("SELECT COUNT(*) FROM products WHERE status='pending'")->fetchColumn(),
];

$filterStatus = trim($_GET['filter_status'] ?? '');
$filterSearch = trim($_GET['filter_search'] ?? '');
$tab          = trim($_GET['tab'] ?? 'users');

$userWhere = ["u.role = 'user'"];
$userParams = [];

if ($filterStatus !== '') {
    $userWhere[] = "u.status = :status";
    $userParams[':status'] = $filterStatus;
}
if ($filterSearch !== '') {
    $userWhere[] = "(u.name LIKE :search OR u.email LIKE :search2)";
    $userParams[':search']  = '%' . $filterSearch . '%';
    $userParams[':search2'] = '%' . $filterSearch . '%';
}

$userSQL = "
    SELECT u.*, COUNT(p.id) AS listing_count
    FROM   users u
    LEFT   JOIN products p ON p.seller_id = u.id AND p.status = 'active'
    WHERE  " . implode(' AND ', $userWhere) . "
    GROUP  BY u.id
    ORDER  BY u.created_at DESC
";
$userStmt = $db->prepare($userSQL);
$userStmt->execute($userParams);
$users = $userStmt->fetchAll();

$productStmt = $db->query("
    SELECT p.*, u.name AS seller_name
    FROM   products p
    JOIN   users u ON u.id = p.seller_id
    ORDER  BY p.created_at DESC
    LIMIT  50
");
$products = $productStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | <?= APP_NAME ?></title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css">
</head>

<body>

    <div class="km-admin-layout">

        <aside class="km-sidebar">
            <div class="km-sidebar-brand">
                <a href="<?= APP_URL ?>/index.php" class="text-white text-decoration-none">
                    Kasi<span class="km-brand-accent">Market</span>
                </a>
                <div style="font-size:11px; color:var(--km-muted); font-family:var(--font-body); font-weight:400; margin-top:2px;">
                    Admin Panel
                </div>
            </div>

            <nav class="km-sidebar-nav">
                <a href="admin_dashboard.php?tab=users"
                    class="km-sidebar-link <?= ($tab === 'users') ? 'active' : '' ?>">
                    <i class="bi bi-people"></i>Users
                    <?php if ($stats['pending_users'] > 0): ?>
                        <span class="ms-auto km-badge km-badge-pending">
                            <?= $stats['pending_users'] ?>
                        </span>
                    <?php endif; ?>
                </a>
                <a href="admin_dashboard.php?tab=products"
                    class="km-sidebar-link <?= ($tab === 'products') ? 'active' : '' ?>">
                    <i class="bi bi-grid"></i>Products
                    <?php if ($stats['pending_products'] > 0): ?>
                        <span class="ms-auto km-badge km-badge-pending">
                            <?= $stats['pending_products'] ?>
                        </span>
                    <?php endif; ?>
                </a>

                <div style="height:1px; background:var(--km-border); margin:10px 4px;"></div>

                <a href="<?= APP_URL ?>/index.php" class="km-sidebar-link">
                    <i class="bi bi-house"></i>View Site
                </a>
                <a href="<?= APP_URL ?>/logout.php" class="km-sidebar-link">
                    <i class="bi bi-box-arrow-right"></i>Logout
                </a>
            </nav>

            <div style="padding:14px 16px; border-top:1px solid var(--km-border);">
                <div style="font-size:12px; color:var(--km-muted);">Logged in as</div>
                <div style="font-size:14px; font-weight:600; color:#fff;">
                    <?= e($_SESSION['user_name']) ?>
                </div>
            </div>
        </aside>

        <main class="km-admin-main">

            <div class="km-admin-topbar">
                <div>
                    <h5 class="mb-0">
                        <?= ($tab === 'products') ? 'Product Listings' : 'User Management' ?>
                    </h5>
                    <small class="text-km-muted">
                        <?= date('l, d F Y') ?>
                    </small>
                </div>
                <a href="<?= APP_URL ?>/upload.php" class="btn btn-km-primary btn-sm">
                    <i class="bi bi-plus-circle me-1"></i>New Listing
                </a>
            </div>

            <div class="km-admin-content">

                <?php if ($message): ?>
                    <div class="km-alert km-alert-<?= e($msgType) ?> mb-4" data-auto-dismiss>
                        <i class="bi bi-info-circle-fill flex-shrink-0"></i>
                        <span><?= e($message) ?></span>
                    </div>
                <?php endif; ?>

                <div class="row g-3 mb-4">
                    <div class="col-6 col-md-3">
                        <div class="km-stat-card">
                            <div class="km-stat-icon blue"><i class="bi bi-people-fill"></i></div>
                            <div>
                                <div class="km-stat-num"><?= number_format((int)$stats['total_users']) ?></div>
                                <div class="km-stat-label">Total Users</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="km-stat-card">
                            <div class="km-stat-icon green"><i class="bi bi-patch-check-fill"></i></div>
                            <div>
                                <div class="km-stat-num"><?= number_format((int)$stats['verified_users']) ?></div>
                                <div class="km-stat-label">Verified</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="km-stat-card">
                            <div class="km-stat-icon yellow"><i class="bi bi-hourglass-split"></i></div>
                            <div>
                                <div class="km-stat-num"><?= number_format((int)$stats['pending_users']) ?></div>
                                <div class="km-stat-label">Pending Users</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="km-stat-card">
                            <div class="km-stat-icon green"><i class="bi bi-grid-fill"></i></div>
                            <div>
                                <div class="km-stat-num"><?= number_format((int)$stats['total_products']) ?></div>
                                <div class="km-stat-label">Active Listings</div>
                            </div>
                        </div>
                    </div>
                </div>

                <?php if ($tab !== 'products'): ?>

                    <div class="km-table-wrap mb-3" style="padding:14px 18px;">
                        <form method="GET" action="admin_dashboard.php"
                            class="row g-2 align-items-end">
                            <input type="hidden" name="tab" value="users">

                            <div class="col-12 col-md-5">
                                <input type="text" name="filter_search"
                                    class="km-search-input form-control"
                                    placeholder="Search by name or email..."
                                    value="<?= e($filterSearch) ?>">
                            </div>
                            <div class="col-6 col-md-3">
                                <select name="filter_status" class="km-select form-select">
                                    <option value="">All Statuses</option>
                                    <option value="pending" <?= $filterStatus === 'pending'   ? 'selected' : '' ?>>Pending</option>
                                    <option value="verified" <?= $filterStatus === 'verified'  ? 'selected' : '' ?>>Verified</option>
                                    <option value="suspended" <?= $filterStatus === 'suspended' ? 'selected' : '' ?>>Suspended</option>
                                </select>
                            </div>
                            <div class="col-6 col-md-2">
                                <button type="submit" class="btn btn-km-primary w-100">
                                    <i class="bi bi-filter me-1"></i>Filter
                                </button>
                            </div>
                            <?php if ($filterSearch || $filterStatus): ?>
                                <div class="col-12 col-md-2">
                                    <a href="admin_dashboard.php?tab=users" class="btn btn-km-ghost w-100">Clear</a>
                                </div>
                            <?php endif; ?>
                        </form>
                    </div>

                    <div class="km-table-wrap" style="overflow-x:auto;">
                        <table class="km-table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Listings</th>
                                    <th>Status</th>
                                    <th>Joined</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($users)): ?>
                                    <tr>
                                        <td colspan="8" class="text-center text-km-muted py-5">
                                            <i class="bi bi-inbox d-block mb-2 fs-3"></i>
                                            No users found.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($users as $u): ?>
                                        <tr>
                                            <td style="color:var(--km-muted); font-size:12px;">
                                                #<?= $u['id'] ?>
                                            </td>
                                            <td>
                                                <div style="font-weight:600; color:#fff;">
                                                    <?= e($u['name']) ?>
                                                </div>
                                            </td>
                                            <td>
                                                <span style="font-size:13px; color:var(--km-muted);">
                                                    <?= e($u['email']) ?>
                                                </span>
                                            </td>
                                            <td style="font-size:13px; color:var(--km-muted);">
                                                <?= e($u['phone'] ?? ' ') ?>
                                            </td>
                                            <td>
                                                <span class="km-badge km-badge-verified">
                                                    <?= (int)$u['listing_count'] ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php
                                                $statusClass = match ($u['status']) {
                                                    'verified'  => 'km-badge-verified',
                                                    'pending'   => 'km-badge-pending',
                                                    'suspended' => 'km-badge-suspended',
                                                    default     => 'km-badge-pending',
                                                };
                                                ?>
                                                <span class="km-badge <?= $statusClass ?>">
                                                    <?= ucfirst(e($u['status'])) ?>
                                                </span>
                                            </td>
                                            <td style="font-size:12px; color:var(--km-muted); white-space:nowrap;">
                                                <?= date('d M Y', strtotime($u['created_at'])) ?>
                                            </td>
                                            <td>
                                                <div class="d-flex gap-1 flex-wrap">
                                                    <?php if ($u['status'] !== 'verified'): ?>
                                                        <a href="admin_dashboard.php?action=verify&uid=<?= $u['id'] ?>&tab=users"
                                                            class="btn btn-sm btn-km-primary"
                                                            title="Verify User"
                                                            data-confirm="Verify <?= e($u['name']) ?>?">
                                                            <i class="bi bi-patch-check"></i>
                                                        </a>
                                                    <?php endif; ?>

                                                    <?php if ($u['status'] !== 'suspended'): ?>
                                                        <a href="admin_dashboard.php?action=suspend&uid=<?= $u['id'] ?>&tab=users"
                                                            class="btn btn-sm btn-km-accent"
                                                            title="Suspend User"
                                                            data-confirm="Suspend <?= e($u['name']) ?>? They won't be able to sell.">
                                                            <i class="bi bi-slash-circle"></i>
                                                        </a>
                                                    <?php endif; ?>

                                                    <a href="admin_dashboard.php?action=delete&uid=<?= $u['id'] ?>&tab=users"
                                                        class="btn btn-sm"
                                                        style="background:rgba(255,61,61,0.15);color:var(--km-danger);border:1px solid rgba(255,61,61,0.3);"
                                                        title="Delete User & Listings"
                                                        data-confirm="  Permanently delete <?= e($u['name']) ?> and ALL their listings? This cannot be undone.">
                                                        <i class="bi bi-trash3"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <small class="text-km-muted mt-2 d-block">
                        Showing <?= count($users) ?> user<?= count($users) !== 1 ? 's' : '' ?>.
                    </small>

                <?php endif; ?>


                <?php if ($tab === 'products'): ?>

                    <div class="km-table-wrap" style="overflow-x:auto;">
                        <table class="km-table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Title</th>
                                    <th>Seller</th>
                                    <th>Category</th>
                                    <th>Price</th>
                                    <th>Status</th>
                                    <th>Listed</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($products)): ?>
                                    <tr>
                                        <td colspan="8" class="text-center text-km-muted py-5">
                                            <i class="bi bi-inbox d-block mb-2 fs-3"></i>
                                            No products found.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($products as $p): ?>
                                        <tr>
                                            <td style="color:var(--km-muted); font-size:12px;">#<?= $p['id'] ?></td>
                                            <td>
                                                <div style="font-weight:600; color:#fff; max-width:180px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
                                                    <?= e($p['title']) ?>
                                                </div>
                                            </td>
                                            <td style="font-size:13px; color:var(--km-muted);">
                                                <?= e($p['seller_name']) ?>
                                            </td>
                                            <td style="font-size:12px;"><?= e($p['category']) ?></td>
                                            <td>
                                                <span class="text-km-green fw-bold"><?= formatPrice((float)$p['price']) ?></span>
                                            </td>
                                            <td>
                                                <?php
                                                $pStatusClass = match ($p['status']) {
                                                    'active'  => 'km-badge-verified',
                                                    'sold'    => 'km-badge-admin',
                                                    'removed' => 'km-badge-suspended',
                                                    'pending' => 'km-badge-pending',
                                                    default   => 'km-badge-pending',
                                                };
                                                ?>
                                                <span class="km-badge <?= $pStatusClass ?>">
                                                    <?= ucfirst(e($p['status'])) ?>
                                                </span>
                                            </td>
                                            <td style="font-size:12px; color:var(--km-muted); white-space:nowrap;">
                                                <?= date('d M Y', strtotime($p['created_at'])) ?>
                                            </td>
                                            <td>
                                                <div class="d-flex gap-1 flex-wrap">
                                                    <?php if ($p['status'] === 'pending'): ?>
                                                        <a href="admin_dashboard.php?action=approve_product&pid=<?= $p['id'] ?>&tab=products"
                                                            class="btn btn-sm btn-km-primary"
                                                            title="Approve Listing"
                                                            data-confirm="Approve this listing to go live?">
                                                            <i class="bi bi-check-circle"></i>
                                                        </a>
                                                    <?php endif; ?>

                                                    <a href="admin_dashboard.php?action=remove_product&pid=<?= $p['id'] ?>&tab=products"
                                                        class="btn btn-sm"
                                                        style="background:rgba(255,61,61,0.15);color:var(--km-danger);border:1px solid rgba(255,61,61,0.3);"
                                                        title="Remove Listing"
                                                        data-confirm="Remove this listing permanently?">
                                                        <i class="bi bi-trash3"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <small class="text-km-muted mt-2 d-block">
                        Showing most recent <?= count($products) ?> listing<?= count($products) !== 1 ? 's' : '' ?>.
                    </small>

                <?php endif; ?>

            </div>
        </main>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?= APP_URL ?>/assets/js/main.js"></script>
</body>

</html>