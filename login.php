<?php
session_start();
require_once 'config.php';

if (!empty($_SESSION['user_id'])) {
    redirect(APP_URL . '/index.php');
}

$errors = [];
$oldEmail = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $oldEmail = $email;

    if (empty($email) || empty($password)) {
        $errors[] = 'Please fill in both your email and password.';
    } else {
        $db = getDB();
        $stmt = $db->prepare("SELECT id, name, password, role, status FROM users WHERE email = :email LIMIT 1");
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            if ($user['status'] === 'suspended') {
                $errors[] = 'Your account has been suspended. Please contact support.';
            } else {
                $_SESSION['user_id']   = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_role'] = $user['role'];

                if ($user['role'] === 'admin') {
                    redirect(APP_URL . '/admin_dashboard.php');
                } else {
                    redirect(APP_URL . '/index.php');
                }
            }
        } else {
            $errors[] = 'Incorrect email or password.';
        }
    }
}

$pageTitle = 'Login';
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
        <p class="km-form-sub">Welcome back, hustler.</p>

        <?php if (!empty($errors)): ?>
            <div class="km-alert km-alert-danger" data-auto-dismiss>
                <i class="bi bi-exclamation-circle-fill flex-shrink-0 mt-1"></i>
                <div>
                    <?php foreach ($errors as $err): ?>
                        <div><?= e($err) ?></div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <form method="POST" action="login.php">
            <div class="mb-3">
                <label class="km-form-label" for="email">Email Address</label>
                <input type="email" id="email" name="email"
                    class="km-form-control"
                    placeholder="thabo@example.co.za"
                    value="<?= e($oldEmail) ?>"
                    required autocomplete="email">
            </div>

            <div class="mb-4">
                <div class="d-flex justify-content-between">
                    <label class="km-form-label" for="password">Password</label>
                    <a href="#" class="text-km-green text-decoration-none" style="font-size: 13px;">Forgot Password?</a>
                </div>

                <div class="position-relative">
                    <input type="password" id="password" name="password"
                        class="km-form-control"
                        style="padding-right: 40px;"
                        placeholder="Enter your password"
                        required autocomplete="current-password">
                    <span class="position-absolute top-50 end-0 translate-middle-y me-3 km-password-toggle" data-target="password">
                        <i class="bi bi-eye"></i>
                    </span>
                </div>
            </div>

            <button type="submit" class="btn btn-km-primary w-100 py-2">
                <i class="bi bi-box-arrow-in-right me-1"></i>Login
            </button>
        </form>

        <div class="km-divider mt-4">or</div>

        <div class="text-center" style="font-size:14px; color:var(--km-muted);">
            Don't have an account yet?
            <a href="register.php" class="text-km-green fw-bold">Register for free</a>
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