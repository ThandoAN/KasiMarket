<?php
session_start();
require_once 'config.php';

$db = getDB();

$search    = trim($_GET['q']   ?? '');
$category  = trim($_GET['cat'] ?? '');

$pageTitle = 'Buy & Sell in Your Township';

$whereClauses = ["p.status = 'active'"];
$params       = [];

if ($search !== '') {
    $whereClauses[] = "(p.title LIKE :search OR p.description LIKE :search2)";
    $params[':search']  = '%' . $search . '%';
    $params[':search2'] = '%' . $search . '%';
}

if ($category !== '') {
    $whereClauses[] = "p.category = :category";
    $params[':category'] = $category;
}

$where = 'WHERE ' . implode(' AND ', $whereClauses);

$sql = "
    SELECT p.*, u.name AS seller_name, u.status AS seller_status
    FROM   products p
    JOIN   users    u ON u.id = p.seller_id
    {$where}
    ORDER  BY p.created_at DESC
    LIMIT  48
";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

$totalProducts = $db->query("SELECT COUNT(*) FROM products WHERE status='active'")->fetchColumn();
$totalSellers  = $db->query("SELECT COUNT(*) FROM users   WHERE role='user' AND status='verified'")->fetchColumn();

$categories = [
    'Clothing & Shoes'    => 'bi-bag',
    'Electronics'         => 'bi-phone',
    'Books & Stationery'  => 'bi-book',
    'Food & Groceries'    => 'bi-basket',
    'Furniture & Home'    => 'bi-house',
    'Beauty & Health'     => 'bi-heart',
    'Sports & Outdoors'   => 'bi-trophy',
    'Toys & Kids'         => 'bi-balloon',
    'Other'               => 'bi-grid',
];

$catIcons = [
    'Clothing & Shoes'   => 'bi-bag-heart',
    'Electronics'        => 'bi-phone-fill',
    'Books & Stationery' => 'bi-book-fill',
    'Food & Groceries'   => 'bi-basket-fill',
    'Furniture & Home'   => 'bi-house-fill',
    'Beauty & Health'    => 'bi-heart-fill',
    'Sports & Outdoors'  => 'bi-trophy-fill',
    'Toys & Kids'        => 'bi-balloon-fill',
    'Other'              => 'bi-grid-fill',
];

include 'partials/header.php';
?>

<section class="km-hero text-center">
    <div class="container">
        <div class="km-hero-tag">
            <span> </span>South Africa's #1 Township Marketplace
        </div>
        <h1>
            Buy. Sell. <span class="km-highlight">Hustle.</span><br>
            Right in Your Kasi.
        </h1>
        <p class="km-hero-sub mx-auto">
            Find great deals on clothes, electronics, books and more
            from verified sellers in your area.
        </p>

        <div class="d-flex justify-content-center flex-wrap gap-3 mt-4">
            <div class="km-stat-pill">
                <span class="stat-num"><?= number_format((int)$totalProducts) ?>+</span>
                <span>Active Listings</span>
            </div>
            <div class="km-stat-pill">
                <span class="stat-num"><?= number_format((int)$totalSellers) ?>+</span>
                <span>Verified Sellers</span>
            </div>
            <div class="km-stat-pill">
                <span class="stat-num">9</span>
                <span>Categories</span>
            </div>
        </div>
    </div>
</section>

<div class="container" style="max-width: 860px;">
    <div class="km-search-bar">
        <form method="GET" action="index.php" class="row g-2 align-items-end">
            <div class="col-12 col-md-6">
                <label class="km-form-label mb-1">
                    <i class="bi bi-search me-1"></i>Search Items
                </label>
                <input type="text" name="q"
                    class="km-search-input form-control"
                    placeholder="e.g. Air Jordan, Samsung, Matric books..."
                    value="<?= e($search) ?>">
            </div>

            <div class="col-12 col-md-4">
                <label class="km-form-label mb-1">
                    <i class="bi bi-tag me-1"></i>Category
                </label>
                <select name="cat" class="km-select form-select">
                    <option value="">All Categories</option>
                    <?php foreach (array_keys($categories) as $cat): ?>
                        <option value="<?= e($cat) ?>"
                            <?= ($category === $cat) ? 'selected' : '' ?>>
                            <?= e($cat) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-12 col-md-2 d-grid">
                <button type="submit" class="btn btn-km-primary">
                    <i class="bi bi-search me-1"></i>Search
                </button>
            </div>

            <?php if ($search || $category): ?>
                <div class="col-12">
                    <a href="index.php" class="btn btn-km-ghost btn-sm">
                        <i class="bi bi-x me-1"></i>Clear Filters
                    </a>
                    <small class="text-km-muted ms-2">
                        Found <strong class="text-white"><?= count($products) ?></strong> result<?= count($products) !== 1 ? 's' : '' ?>
                        <?= $category ? ' in <em>' . e($category) . '</em>' : '' ?>
                    </small>
                </div>
            <?php endif; ?>
        </form>
    </div>
</div>

<div class="container mt-5">
    <div class="km-category-scroll d-flex gap-2">
        <a href="index.php"
            class="km-cat-pill <?= (!$category) ? 'active' : '' ?>"
            data-cat="">
            <i class="bi bi-grid-3x3-gap"></i> All
        </a>
        <?php foreach ($categories as $cat => $icon): ?>
            <a href="index.php?cat=<?= urlencode($cat) ?>"
                class="km-cat-pill"
                data-cat="<?= e($cat) ?>">
                <i class="<?= $icon ?>"></i>
                <?= e(explode(' & ', $cat)[0]) ?>
            </a>
        <?php endforeach; ?>
    </div>
</div>

<section class="km-section">
    <div class="container">
        <div class="km-section-header">
            <h2 class="km-section-title">
                <?php if ($category): ?>
                    <?= e($category) ?>
                <?php elseif ($search): ?>
                    Results for "<?= e($search) ?>"
                <?php else: ?>
                    Recent Listings
                <?php endif; ?>
            </h2>
            <?php if (!empty($_SESSION['user_id'])): ?>
                <a href="upload.php" class="btn btn-km-primary btn-sm">
                    <i class="bi bi-plus-circle me-1"></i>List an Item
                </a>
            <?php else: ?>
                <a href="register.php" class="btn btn-km-outline btn-sm">
                    <i class="bi bi-person-plus me-1"></i>Join to Sell
                </a>
            <?php endif; ?>
        </div>

        <?php if (empty($products)): ?>
            <div class="km-empty">
                <i class="bi bi-search"></i>
                <h4 class="mb-2">No listings found</h4>
                <p class="text-km-muted">
                    <?php if ($search || $category): ?>
                        Try adjusting your filters or
                        <a href="index.php">browse all items</a>.
                    <?php else: ?>
                        Be the first to list something!
                        <a href="upload.php">Sell an item</a>
                    <?php endif; ?>
                </p>
            </div>
        <?php else: ?>
            <div class="row g-3 row-cols-2 row-cols-sm-2 row-cols-md-3 row-cols-lg-4">
                <?php foreach ($products as $p): ?>
                    <div class="col">
                        <div class="km-card h-100">
                            <div class="km-card-img-wrap">
                                <?php if (!empty($p['image_url']) && file_exists(UPLOAD_DIR . $p['image_url'])): ?>
                                    <img src="<?= UPLOAD_URL . e($p['image_url']) ?>"
                                        alt="<?= e($p['title']) ?>"
                                        loading="lazy">
                                <?php else: ?>
                                    <div class="km-card-placeholder">
                                        <i class="bi <?= e($catIcons[$p['category']] ?? 'bi-box') ?>"></i>
                                    </div>
                                <?php endif; ?>
                                <span class="km-badge-cat"><?= e($p['category']) ?></span>
                                <span class="km-badge-condition"><?= e($p['condition_type']) ?></span>
                            </div>

                            <div class="km-card-body">
                                <div class="km-card-title" title="<?= e($p['title']) ?>">
                                    <?= e($p['title']) ?>
                                </div>
                                <div class="km-card-price"><?= formatPrice((float)$p['price']) ?></div>
                                <div class="mb-2 small text-km-muted">
                                    Delivery: <span class="text-white"><?= (float)$p['delivery_fee'] > 0 ? formatPrice((float)$p['delivery_fee']) : 'Free' ?></span>
                                </div>

                                <div class="km-card-meta">
                                    <span><i class="bi bi-clock me-1"></i><?= timeAgo($p['created_at']) ?></span>
                                    <?php if ($p['seller_status'] === 'verified'): ?>
                                        <span class="ms-auto text-km-green">
                                            <i class="bi bi-patch-check-fill"></i>
                                        </span>
                                    <?php endif; ?>
                                </div>

                                <div class="mt-3 pt-3" style="border-top: 1px solid var(--km-border); display:flex; gap:10px;">
                                    <a href="product.php?id=<?= $p['id'] ?>" class="btn btn-km-ghost btn-sm w-50">Details</a>
                                    <form method="POST" action="cart.php" class="w-50">
                                        <input type="hidden" name="action" value="add">
                                        <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                                        <input type="hidden" name="quantity" value="1">
                                        <button type="submit" class="btn btn-km-outline btn-sm w-100">
                                            <i class="bi bi-cart-plus me-1"></i>Cart
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<section style="background: var(--km-black); border-top: 1px solid var(--km-border); border-bottom: 1px solid var(--km-border); padding: 40px 0;">
    <div class="container">
        <div class="row g-4 text-center">
            <div class="col-6 col-md-3">
                <i class="bi bi-shield-check fs-2 text-km-green d-block mb-2"></i>
                <div class="fw-bold text-white">Verified Sellers</div>
                <small class="text-km-muted">Admin-approved profiles</small>
            </div>
            <div class="col-6 col-md-3">
                <i class="bi bi-truck fs-2 text-km-green d-block mb-2"></i>
                <div class="fw-bold text-white">Fast Township Delivery</div>
                <small class="text-km-muted">Straight to your doorstep</small>
            </div>
            <div class="col-6 col-md-3">
                <i class="bi bi-people fs-2 text-km-green d-block mb-2"></i>
                <div class="fw-bold text-white">Local Community</div>
                <small class="text-km-muted">Buy from your neighbours</small>
            </div>
            <div class="col-6 col-md-3">
                <i class="bi bi-phone fs-2 text-km-green d-block mb-2"></i>
                <div class="fw-bold text-white">Mobile-Friendly</div>
                <small class="text-km-muted">Works on any device</small>
            </div>
        </div>
    </div>
</section>

<?php include 'partials/footer.php'; ?>