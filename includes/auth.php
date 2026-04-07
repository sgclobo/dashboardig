<?php
// includes/auth.php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/db.php';

function session_start_safe(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_name(SESSION_NAME);
        $isLocal = in_array($_SERVER['SERVER_NAME'] ?? '', ['localhost', '127.0.0.1', '::1'], true);
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'secure'   => !$isLocal,
            'httponly' => true,
            'samesite' => 'Strict',
        ]);
        session_start();
    }
}

function require_login(): void {
    session_start_safe();
    if (empty($_SESSION['user_id'])) {
        header('Location: /login.php');
        exit;
    }
}

function current_user(): ?array {
    session_start_safe();
    if (empty($_SESSION['user_id'])) return null;
    static $user = null;
    if ($user === null) {
        $st = db()->prepare('SELECT id, name, email, role FROM dashboard_users WHERE id = ?');
        $st->execute([$_SESSION['user_id']]);
        $user = $st->fetch() ?: null;
    }
    return $user;
}

function login(string $email, string $pass): bool {
    session_start_safe();
    $st = db()->prepare('SELECT * FROM dashboard_users WHERE email = ?');
    $st->execute([strtolower(trim($email))]);
    $row = $st->fetch();
    if ($row && password_verify($pass, $row['password'])) {
        session_regenerate_id(true);
        $_SESSION['user_id'] = $row['id'];
        db()->prepare("UPDATE dashboard_users SET last_login = datetime('now') WHERE id = ?")
             ->execute([$row['id']]);
        return true;
    }
    return false;
}

function logout(): void {
    session_start_safe();
    session_unset();
    session_destroy();
    header('Location: /login.php');
    exit;
}