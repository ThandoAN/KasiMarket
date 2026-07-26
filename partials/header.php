<?php
$pageTitle = $pageTitle ?? APP_NAME;

if (session_status() === PHP_SESSION_NONE) session_start();

$loggedIn  = !empty($_SESSION['user_id']);
$isAdmin   = ($loggedIn && ($_SESSION['user_role'] ?? '') === 'admin');
$userName  = e($_SESSION['user_name'] ?? '');

$cartCount = 0;
if ($loggedIn) {
    $db = getDB();
    $stmt = $db->prepare("SELECT SUM(quantity) FROM cart WHERE user_id = :uid");
    $stmt->execute([':uid' => $_SESSION['user_id']]);
    $cartCount = (int)$stmt->fetchColumn();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Kasi-Market   Buy & sell in your township, safely.">
    <title><?= e($pageTitle) ?> | <?= APP_NAME ?></title>
    <link rel="apple-touch-icon" sizes="180x180" href="<?= APP_URL ?>/assets/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="<?= APP_URL ?>/assets/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="<?= APP_URL ?>/assets/favicon-16x16.png">
    <link rel="manifest" href="<?= APP_URL ?>/assets/site.webmanifest">


    <meta property="og:image" content="<?= APP_URL ?>/assets/Preview.jpg">

    <meta property="og:title" content="Kasi-Market | Buy & Sell in Your Township">
    <meta property="og:description" content="South Africa's #1 Township Marketplace. Buy and sell clothes, electronics, books, and more safely.">
    <meta property="og:url" content="<?= APP_URL ?>">
    <meta property="og:type" content="website">

    <link rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">

    <link rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@400;500;600&display=swap"
        rel="stylesheet">

    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css">
</head>

<body>

    <div class="km-topbar d-none d-md-block">
        <div class="container-fluid px-4 d-flex justify-content-between align-items-center">
            <small><i class="bi bi-geo-alt-fill me-1"></i>Connecting township hustlers across South Africa </small>
            <small>Support: <a href="mailto:hello@kasimarket.co.za">hello@kasimarket.co.za</a></small>
        </div>
    </div>

    <nav class="navbar navbar-expand-lg km-navbar sticky-top">
        <div class="container-fluid px-3 px-lg-4">
            <a class="navbar-brand km-brand" href="<?= APP_URL ?>/index.php">
                <span class="km-brand-icon"><i class="bi bi-bag-heart-fill"></i></span>
                Kasi<span class="km-brand-accent">Market</span>
            </a>

            <button class="navbar-toggler" type="button"
                data-bs-toggle="collapse" data-bs-target="#mainNav">
                <i class="bi bi-list fs-4 text-white"></i>
            </button>

            <div class="collapse navbar-collapse" id="mainNav">
                <ul class="navbar-nav mx-auto gap-1">
                    <li class="nav-item">
                        <a class="nav-link km-nav-link" href="<?= APP_URL ?>/index.php">
                            <i class="bi bi-house me-1"></i>Home
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link km-nav-link" href="<?= APP_URL ?>/index.php?cat=Electronics">
                            <i class="bi bi-phone me-1"></i>Electronics
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link km-nav-link" href="<?= APP_URL ?>/index.php?cat=Clothing+%26+Shoes">
                            <i class="bi bi-bag me-1"></i>Clothing
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link km-nav-link" href="<?= APP_URL ?>/index.php?cat=Books+%26+Stationery">
                            <i class="bi bi-book me-1"></i>Books
                        </a>
                    </li>
                </ul>

                <div class="d-flex align-items-center gap-2 mt-3 mt-lg-0">
                    <?php if ($loggedIn): ?>
                        <a href="<?= APP_URL ?>/cart.php" class="btn btn-km-outline btn-sm position-relative">
                            <i class="bi bi-cart me-1"></i>My Cart
                            <?php if ($cartCount > 0): ?>
                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                    <?= $cartCount ?>
                                </span>
                            <?php endif; ?>
                        </a>

                        <a href="<?= APP_URL ?>/buyer_dashboard.php" class="btn btn-km-outline btn-sm">
                            <i class="bi bi-box me-1"></i>Orders
                        </a>

                        <a href="<?= APP_URL ?>/seller_dashboard.php" class="btn btn-km-outline btn-sm">
                            <i class="bi bi-shop me-1"></i>My Listings
                        </a>

                        <a href="<?= APP_URL ?>/upload.php" class="btn btn-km-primary btn-sm">
                            <i class="bi bi-plus-circle me-1"></i>Sell Now
                        </a>

                        <?php if ($isAdmin): ?>
                            <a href="<?= APP_URL ?>/admin_dashboard.php" class="btn btn-km-outline btn-sm">
                                <i class="bi bi-shield-check me-1"></i>Admin
                            </a>
                        <?php endif; ?>

                        <div class="dropdown">
                            <button class="btn btn-km-ghost btn-sm dropdown-toggle" data-bs-toggle="dropdown">
                                <i class="bi bi-person-circle me-1"></i><?= $userName ?>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end km-dropdown">
                                <li><a class="dropdown-item" href="<?= APP_URL ?>/logout.php">
                                        <i class="bi bi-box-arrow-right me-2"></i>Logout
                                    </a></li>
                            </ul>
                        </div>
                    <?php else: ?>
                        <a href="<?= APP_URL ?>/login.php" class="btn btn-km-outline btn-sm">Login</a>
                        <a href="<?= APP_URL ?>/register.php" class="btn btn-km-primary btn-sm">Join Free</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>