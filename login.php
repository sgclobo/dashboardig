<?php
require_once __DIR__ . '/includes/auth.php';
session_start_safe();
if (!empty($_SESSION['user_id'])) { header('Location: /'); exit; }

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (login($_POST['email'] ?? '', $_POST['password'] ?? '')) {
        header('Location: /index.php');
        exit;
    }
    $error = 'Invalid email or password.';
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login — AIFAESA Dashboard</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link
        href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600&family=DM+Mono:wght@400;500&display=swap"
        rel="stylesheet">
    <style>
    *,
    *::before,
    *::after {
        box-sizing: border-box;
        margin: 0;
        padding: 0;
    }

    :root {
        --navy: #0d1b2a;
        --navy2: #162436;
        --navy3: #1e3452;
        --gold: #c9a84c;
        --gold2: #e8c96a;
        --text: #e8e4d8;
        --muted: #7a8fa6;
        --danger: #e05555;
        --radius: 10px;
        --font: 'DM Sans', sans-serif;
        --mono: 'DM Mono', monospace;
    }

    body {
        min-height: 100vh;
        background: var(--navy);
        font-family: var(--font);
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--text);
        background-image:
            radial-gradient(ellipse 60% 50% at 20% 80%, rgba(201, 168, 76, .08) 0%, transparent 60%),
            radial-gradient(ellipse 40% 60% at 80% 20%, rgba(21, 52, 82, .6) 0%, transparent 70%);
    }

    .card {
        width: 100%;
        max-width: 400px;
        padding: 2.5rem 2rem;
        background: var(--navy2);
        border: 1px solid rgba(201, 168, 76, .18);
        border-radius: 16px;
    }

    .logo {
        text-align: center;
        margin-bottom: 2rem;
    }

    .logo-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 52px;
        height: 52px;
        border-radius: 14px;
        background: linear-gradient(135deg, var(--gold) 0%, #8a6420 100%);
        font-family: var(--mono);
        font-weight: 500;
        font-size: 18px;
        color: var(--navy);
        margin-bottom: .75rem;
    }

    .logo h1 {
        font-size: 1.05rem;
        font-weight: 600;
        letter-spacing: .04em;
        color: var(--text);
    }

    .logo p {
        font-size: .78rem;
        color: var(--muted);
        margin-top: 2px;
        letter-spacing: .06em;
        text-transform: uppercase;
    }

    .field {
        margin-bottom: 1.1rem;
    }

    .field label {
        display: block;
        font-size: .8rem;
        font-weight: 500;
        color: var(--muted);
        letter-spacing: .05em;
        text-transform: uppercase;
        margin-bottom: .4rem;
    }

    .field input {
        width: 100%;
        padding: .65rem .85rem;
        background: var(--navy);
        border: 1px solid rgba(201, 168, 76, .2);
        border-radius: var(--radius);
        color: var(--text);
        font-family: var(--font);
        font-size: .95rem;
        outline: none;
        transition: border-color .2s;
    }

    .field input:focus {
        border-color: var(--gold);
    }

    .error {
        background: rgba(224, 85, 85, .12);
        border: 1px solid rgba(224, 85, 85, .3);
        color: #f08080;
        border-radius: var(--radius);
        padding: .6rem .85rem;
        font-size: .85rem;
        margin-bottom: 1.1rem;
    }

    button[type=submit] {
        width: 100%;
        padding: .75rem;
        background: var(--gold);
        color: var(--navy);
        border: none;
        border-radius: var(--radius);
        font-family: var(--font);
        font-size: .95rem;
        font-weight: 600;
        cursor: pointer;
        letter-spacing: .03em;
        transition: background .2s, transform .1s;
        margin-top: .4rem;
    }

    button[type=submit]:hover {
        background: var(--gold2);
    }

    button[type=submit]:active {
        transform: scale(.98);
    }

    .footer {
        text-align: center;
        margin-top: 1.5rem;
        font-size: .75rem;
        color: var(--muted);
    }
    </style>
</head>

<body>
    <div class="card">
        <div class="logo">
            <div class="logo-badge">AI</div>
            <h1>AIFAESA</h1>
            <p>Monitoring Dashboard</p>
        </div>

        <?php if ($error): ?>
        <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="/login.php" autocomplete="on">
            <div class="field">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required autofocus
                    value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
            </div>
            <div class="field">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit">Sign in →</button>
        </form>

        <div class="footer">Autoridade Inspeção Fiskalizasaun AIFAESA · <?= date('Y') ?></div>
    </div>
</body>

</html>