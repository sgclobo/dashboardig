<?php
// includes/auth.php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/db.php';

function session_start_safe(): void
{
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

function require_login(): void
{
    session_start_safe();
    if (empty($_SESSION['user_id'])) {
        header('Location: login.php');
        exit;
    }
}

function current_user(): ?array
{
    session_start_safe();
    if (empty($_SESSION['user_id'])) return null;
    static $user = null;
    if ($user === null) {
        $st = intranet_db()->prepare('SELECT user_id as id, username, naran as name, email, role FROM users WHERE user_id = ?');
        $st->execute([$_SESSION['user_id']]);
        $user = $st->fetch() ?: null;
    }
    return $user;
}

function login(string $username, string $pass): bool
{
    session_start_safe();
    try {
        // Authenticate against intranet users table (case-insensitive username match)
        $st = intranet_db()->prepare('SELECT user_id, password FROM users WHERE LOWER(username) = LOWER(?)');
        $st->execute([trim($username)]);
        $row = $st->fetch();
        if ($row && password_verify($pass, $row['password'])) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $row['user_id'];
            return true;
        }
    } catch (Exception $e) {
        error_log('Login error: ' . $e->getMessage());
    }
    return false;
}

function logout(): void
{
    session_start_safe();
    session_unset();

    // Expire the session cookie immediately
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $p['path'],
            $p['domain'],
            $p['secure'],
            $p['httponly']
        );
    }

    session_destroy();
    header('Location: login.php');
    exit;
}
