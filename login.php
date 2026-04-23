<?php
require_once __DIR__ . '/includes/auth.php';
session_start_safe();
if (!empty($_SESSION['user_id'])) {
    header('Location: ./');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (login($_POST['username'] ?? '', $_POST['password'] ?? '')) {
        header('Location: index.php');
        exit;
    }
    $error = 'Invalid username or password.';
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
            --navy: #070a20;
            --navy2: #11152f;
            --navy3: #1a2250;
            --gold: #a96eff;
            --gold2: #cb93ff;
            --text: #f2f0ff;
            --muted: #8f98c9;
            --danger: #ff708f;
            --radius: 10px;
            --font: 'DM Sans', sans-serif;
            --mono: 'DM Mono', monospace;
        }

        body {
            min-height: 100vh;
            background:
                radial-gradient(circle at 16% 18%, rgba(180, 120, 255, .2) 0%, transparent 32%),
                radial-gradient(circle at 86% 14%, rgba(82, 145, 255, .16) 0%, transparent 34%),
                linear-gradient(145deg, #06081b 0%, #0a1031 48%, #05081a 100%);
            font-family: var(--font);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text);
            position: relative;
            overflow: hidden;
        }

        body::before {
            content: "";
            position: fixed;
            inset: 0;
            pointer-events: none;
            background-image:
                linear-gradient(90deg, transparent 0, transparent 22%, rgba(123, 216, 255, .06) 24%, transparent 28%, transparent 100%),
                linear-gradient(0deg, transparent 0, transparent 60%, rgba(185, 126, 255, .06) 62%, transparent 66%, transparent 100%);
            mix-blend-mode: screen;
            opacity: .44;
        }

        .card {
            width: 100%;
            max-width: 400px;
            padding: 2.5rem 2rem;
            background: linear-gradient(160deg, rgba(20, 26, 62, .9) 0%, rgba(10, 15, 39, .96) 100%);
            border: 1px solid rgba(162, 184, 255, .24);
            border-radius: 16px;
            box-shadow: 0 20px 46px rgba(2, 6, 22, .6), 0 0 0 1px rgba(176, 128, 255, .16);
            position: relative;
            z-index: 1;
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
            background: linear-gradient(135deg, #bb78ff 0%, #5aa0ff 100%);
            font-family: var(--mono);
            font-weight: 500;
            font-size: 18px;
            color: #f7f5ff;
            margin-bottom: .75rem;
            box-shadow: 0 0 0 1px rgba(195, 137, 255, .36), 0 0 28px rgba(126, 156, 255, .32);
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
            border: 1px solid rgba(162, 184, 255, .3);
            border-radius: var(--radius);
            color: var(--text);
            font-family: var(--font);
            font-size: .95rem;
            outline: none;
            transition: border-color .2s, box-shadow .2s;
        }

        .field input:focus {
            border-color: var(--gold);
            box-shadow: 0 0 0 3px rgba(169, 110, 255, .2);
        }

        .error {
            background: rgba(255, 112, 143, .14);
            border: 1px solid rgba(255, 112, 143, .3);
            color: #ff9eb2;
            border-radius: var(--radius);
            padding: .6rem .85rem;
            font-size: .85rem;
            margin-bottom: 1.1rem;
        }

        button[type=submit] {
            width: 100%;
            padding: .75rem;
            background: linear-gradient(135deg, var(--gold) 0%, #5aa0ff 100%);
            color: #f7f5ff;
            border: none;
            border-radius: var(--radius);
            font-family: var(--font);
            font-size: .95rem;
            font-weight: 600;
            cursor: pointer;
            letter-spacing: .03em;
            transition: background .2s, transform .1s;
            margin-top: .4rem;
            box-shadow: 0 8px 22px rgba(92, 127, 255, .28);
        }

        button[type=submit]:hover {
            background: linear-gradient(135deg, var(--gold2) 0%, #79b6ff 100%);
        }

        button[type=submit]:active {
            transform: scale(.98);
        }

        button[type=submit]:focus-visible,
        .field input:focus-visible {
            outline: 2px solid rgba(128, 196, 255, .75);
            outline-offset: 2px;
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

        <form method="POST" action="login.php" autocomplete="on">
            <div class="field">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required autofocus
                    value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
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