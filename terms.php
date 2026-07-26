<?php
session_start();
require_once 'config.php';

$pageTitle = 'Terms of Use';
include 'partials/header.php';
?>

<div class="container my-5" style="max-width: 800px;">
    <h1 class="km-section-title mb-4">Terms of Use</h1>

    <div class="km-upload-card p-4 text-white" style="background: var(--km-card);">
        <p>By using KasiMarket, you agree to these rules. Please read them carefully.</p>

        <h5 class="text-km-green mt-4">1. Account Rules</h5>
        <p>You must provide accurate information when registering. You are responsible for keeping your password safe.</p>

        <h5 class="text-km-green mt-4">2. Buying and Selling</h5>
        <p>KasiMarket is a platform to connect buyers and sellers. We do not own the items listed. All sales are between the buyer and the seller.</p>

        <h5 class="text-km-green mt-4">3. Prohibited Items</h5>
        <p>You may not sell illegal items, stolen goods, weapons, or anything that violates South African law. Admins will delete these listings and suspend your account.</p>

        <h5 class="text-km-green mt-4">4. Liability</h5>
        <p>KasiMarket is not responsible for any money lost, damaged items, or disputes between users. Always practice safe trading.</p>
    </div>
</div>

<?php include 'partials/footer.php'; ?>