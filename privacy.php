<?php
session_start();
require_once 'config.php';

$pageTitle = 'Privacy Policy';
include 'partials/header.php';
?>

<div class="container my-5" style="max-width: 800px;">
    <h1 class="km-section-title mb-4">Privacy Policy</h1>

    <div class="km-upload-card p-4 text-white" style="background: var(--km-card);">
        <p>Welcome to KasiMarket. Your privacy is important to us. This policy explains how we handle your personal information.</p>

        <h5 class="text-km-green mt-4">1. Information We Collect</h5>
        <p>When you register, we collect your name, email address, and phone number. If you buy an item, we collect your delivery address to process your order.</p>

        <h5 class="text-km-green mt-4">2. How We Use Your Information</h5>
        <p>We use your information to create your account, let you list items, and process orders. We only share your delivery details with the specific seller you are buying from.</p>

        <h5 class="text-km-green mt-4">3. Data Security</h5>
        <p>Your password is encrypted. We take reasonable steps to keep your data safe, but remember that no system is 100 percent secure.</p>

        <h5 class="text-km-green mt-4">4. Your Rights</h5>
        <p>You have the right to ask us to delete your account and personal data at any time. Just contact an admin.</p>
    </div>
</div>

<?php include 'partials/footer.php'; ?>