<?php
session_start();
require_once 'config.php';

if (!empty($_SESSION['user_id'])) {
    redirect(APP_URL . '/index.php');
}

$errors  = [];
$success = '';
$old     = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old['name']  = trim($_POST['name']  ?? '');
    $old['email'] = trim($_POST['email'] ?? '');
    $old['phone'] = trim($_POST['phone'] ?? '');
    $password     = $_POST['password']   ?? '';
    $confirm      = $_POST['confirm']    ?? '';
    $terms        = isset($_POST['terms']);

    if (strlen($old['name']) < 2) {
        $errors[] = 'Full name must be at least 2 characters.';
    }
    if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }
    if ($old['phone'] !== '' && !preg_match('/^[0-9+\s\-]{7,15}$/', $old['phone'])) {
        $errors[] = 'Phone number looks invalid.';
    }
    if (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters.';
    }
    if ($password !== $confirm) {
        $errors[] = 'Passwords do not match.';
    }
    if (!$terms) {
        $errors[] = 'You must agree to the Terms of Use to register.';
    }

    if (empty($errors)) {
        $db   = getDB();
        $stmt = $db->prepare("SELECT id FROM users WHERE email = :email LIMIT 1");
        $stmt->execute([':email' => $old['email']]);
        if ($stmt->fetch()) {
            $errors[] = 'That email address is already registered. <a href="login.php">Login instead?</a>';
        }
    }

    if (empty($errors)) {
        $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

        $db   = getDB();
        $stmt = $db->prepare("
            INSERT INTO users (name, email, password, phone, role, status)
            VALUES (:name, :email, :password, :phone, 'user', 'pending')
        ");
        $stmt->execute([
            ':name'     => $old['name'],
            ':email'    => $old['email'],
            ':password' => $hash,
            ':phone'    => $old['phone'] ?: null,
        ]);

        $newId = $db->lastInsertId();
        $_SESSION['user_id']   = $newId;
        $_SESSION['user_name'] = $old['name'];
        $_SESSION['user_role'] = 'user';

        redirect(APP_URL . '/index.php?msg=registered');
    }
}

$pageTitle = 'Create Your Account';
include 'partials/header.php';
?>

<style>
    .km-password-toggle {
        cursor: pointer;
        color: var(--km-muted);
        transition: color 0.2s;
    }

    .km-password-toggle:hover {
        color: var(--km-green);
    }
</style>

<div class="km-form-page">
    <div class="km-form-card">
        <div class="km-form-logo">
            <a href="index.php" class="text-white text-decoration-none">
                Kasi<span class="km-brand-accent">Market</span>
            </a>
        </div>
        <p class="km-form-sub">Join the township marketplace. It is free.</p>

        <?php if (!empty($errors)): ?>
            <div class="km-alert km-alert-danger" data-auto-dismiss>
                <i class="bi bi-exclamation-circle-fill flex-shrink-0 mt-1"></i>
                <div>
                    <?php foreach ($errors as $err): ?>
                        <div><?= $err ?></div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <form method="POST" action="register.php" novalidate>

            <div class="mb-3">
                <label class="km-form-label" for="name">Full Name</label>
                <input type="text" id="name" name="name"
                    class="km-form-control"
                    placeholder="e.g. Thabo Nkosi"
                    value="<?= e($old['name'] ?? '') ?>"
                    required autocomplete="name">
            </div>

            <div class="mb-3">
                <label class="km-form-label" for="email">Email Address</label>
                <input type="email" id="email" name="email"
                    class="km-form-control"
                    placeholder="thabo@example.co.za"
                    value="<?= e($old['email'] ?? '') ?>"
                    required autocomplete="email">
            </div>

            <div class="mb-3">
                <label class="km-form-label" for="phone">
                    Phone Number <span class="text-km-muted fw-normal">(optional)</span>
                </label>
                <input type="tel" id="phone" name="phone"
                    class="km-form-control"
                    placeholder="071 234 5678"
                    value="<?= e($old['phone'] ?? '') ?>"
                    autocomplete="tel">
            </div>

            <div class="mb-2">
                <label class="km-form-label" for="password">Password</label>
                <div class="position-relative">
                    <input type="password" id="password" name="password"
                        class="km-form-control"
                        style="padding-right: 40px;"
                        placeholder="Min. 8 characters"
                        required autocomplete="new-password">
                    <span class="position-absolute top-50 end-0 translate-middle-y me-3 km-password-toggle" data-target="password">
                        <i class="bi bi-eye"></i>
                    </span>
                </div>

                <div style="margin-top:8px; height:4px; background:var(--km-border); border-radius:4px; overflow:hidden;">
                    <div id="passStrengthBar"
                        style="height:100%; width:0%; border-radius:4px; transition:width 0.3s ease, background 0.3s ease;"></div>
                </div>
                <small id="passStrengthLabel" style="font-size:12px; font-weight:600;"></small>
            </div>

            <div class="mb-4">
                <label class="km-form-label" for="confirm">Confirm Password</label>
                <div class="position-relative">
                    <input type="password" id="confirm" name="confirm"
                        class="km-form-control"
                        style="padding-right: 40px;"
                        placeholder="Repeat your password"
                        required autocomplete="new-password">
                    <span class="position-absolute top-50 end-0 translate-middle-y me-3 km-password-toggle" data-target="confirm">
                        <i class="bi bi-eye"></i>
                    </span>
                </div>
            </div>

            <div class="mb-4 d-flex align-items-start gap-2" style="font-size:13px; color:var(--km-muted);">
                <input type="checkbox" id="terms" name="terms" required
                    style="margin-top:3px; accent-color:var(--km-green); flex-shrink:0;">
                <label for="terms">
                    I agree to the <a href="terms.php" target="_blank">Terms of Use</a> and confirm I will trade safely.
                </label>
            </div>

            <button type="submit" class="btn btn-km-primary w-100 py-2">
                <i class="bi bi-person-plus me-1"></i>Create My Account
            </button>
        </form>

        <div class="km-divider mt-4">or</div>

        <div class="text-center" style="font-size:14px; color:var(--km-muted);">
            Already have an account?
            <a href="login.php" class="text-km-green fw-bold">Log in</a>
        </div>

        <div class="km-alert km-alert-info mt-4" style="font-size:12px;">
            <i class="bi bi-info-circle-fill flex-shrink-0"></i>
            <span>New accounts are set to <strong>Pending</strong> until verified by an admin.
                You can browse immediately, but selling requires verification.</span>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const toggles = document.querySelectorAll('.km-password-toggle');
        toggles.forEach(toggle => {
            toggle.addEventListener('click', function() {
                const targetId = this.getAttribute('data-target');
                const inputField = document.getElementById(targetId);
                const icon = this.querySelector('i');

                if (inputField.type === 'password') {
                    inputField.type = 'text';
                    icon.classList.remove('bi-eye');
                    icon.classList.add('bi-eye-slash');
                } else {
                    inputField.type = 'password';
                    icon.classList.remove('bi-eye-slash');
                    icon.classList.add('bi-eye');
                }
            });
        });
    });
</script>

<?php include 'partials/footer.php'; ?>