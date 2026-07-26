<?php
define('DB_HOST', 'sql105.infinityfree.com'); 
define('DB_NAME', 'if0_42127129_kasimarket');
define('DB_USER', 'if0_42127129');
define('DB_PASS', 'A3xhr5kDXWphJ27');
define('DB_CHARSET', 'utf8mb4');

define('APP_NAME',  'Kasi-Market');
define('APP_URL',   'https://kasimarket.xo.je/');
define('UPLOAD_DIR', __DIR__ . '/assets/uploads/');
define('UPLOAD_URL',  APP_URL . '/assets/uploads/');

function getDB(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $dsn = sprintf(
            'mysql:host=%s;dbname=%s;charset=%s',
            DB_HOST,
            DB_NAME,
            DB_CHARSET
        );

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4",
        ];

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            die('<div style="font-family:sans-serif;padding:2rem;color:#c00;">
                    <strong>Database Connection Failed.</strong> Please ensure MySQL is running in Laragon.
                    <br><small>' . htmlspecialchars($e->getMessage()) . '</small>
                 </div>');
        }
    }

    return $pdo;
}

function e(string $str): string
{
    return htmlspecialchars($str, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function redirect(string $url): void
{
    header('Location: ' . $url);
    exit;
}

function requireLogin(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (empty($_SESSION['user_id'])) {
        redirect(APP_URL . '/login.php?msg=login_required');
    }
}

function requireAdmin(): void
{
    requireLogin();
    if (($_SESSION['user_role'] ?? '') !== 'admin') {
        redirect(APP_URL . '/index.php?msg=access_denied');
    }
}

function formatPrice(float $price): string
{
    return 'R ' . number_format($price, 2);
}

function timeAgo(string $datetime): string
{
    $now  = new DateTime();
    $past = new DateTime($datetime);
    $diff = $now->diff($past);

    if ($diff->y > 0) return $diff->y . ' year'  . ($diff->y > 1 ? 's' : '') . ' ago';
    if ($diff->m > 0) return $diff->m . ' month' . ($diff->m > 1 ? 's' : '') . ' ago';
    if ($diff->d > 0) return $diff->d . ' day'   . ($diff->d > 1 ? 's' : '') . ' ago';
    if ($diff->h > 0) return $diff->h . ' hour'  . ($diff->h > 1 ? 's' : '') . ' ago';
    if ($diff->i > 0) return $diff->i . ' min'   . ($diff->i > 1 ? 's' : '') . ' ago';
    return 'Just now';
}
