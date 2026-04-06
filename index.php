<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/fetcher.php';

require_login();

$user    = current_user();
$sources = all_sources();

// Log access
db()->prepare('INSERT INTO access_log (user_id, section, ip) VALUES (?,?,?)')
    ->execute([$user['id'], 'dashboard', $_SERVER['REMOTE_ADDR'] ?? '']);

// Initials for avatar
$initials = implode('', array_map(fn($w) => strtoupper($w[0]),
    array_slice(explode(' ', $user['name']), 0, 2)));

// Icon map (inline SVG paths — no external icon library needed)
$icons = [
    'clipboard-check' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>',
    'exclamation-circle' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>',
    'users'           => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197"/>',
    'truck'           => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10m10 0H3m10 0a2 2 0 01-2 2h-1m5-10h3l2 4v4h-5V6z"/>',
    'server'          => '<path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2"/>',
    'globe'           => '<path stroke-linecap="round" stroke-linejoin="round" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064"/><circle cx="12" cy="12" r="9"/>',
    'circle'          => '<circle cx="12" cy="12" r="9"/>',
];

function icon(string $name, array $icons): string {
    $path = $icons[$name] ?? $icons['circle'];
    return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">' . $path . '</svg>';
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars(SITE_NAME) ?></title>
    <link rel="stylesheet" href="assets/css/dashboard.css">
</head>

<body>

    <!-- Overlay (mobile) -->
    <div class="sidebar-overlay" id="sidebar-overlay"></div>

    <div class="layout">

        <!-- ══════════════ SIDEBAR ══════════════ -->
        <aside class="sidebar" id="sidebar">

            <!-- Logo -->
            <div class="logo-block">
                <div class="logo-badge">AI</div>
                <div>
                    <div class="sidebar-title">AIFAESA</div>
                    <div class="sidebar-subtitle">Dashboard</div>
                </div>
            </div>

            <!-- Navigation -->
            <nav class="nav" id="sidebar-nav">

                <div class="section-label">Monitoring</div>

                <?php foreach ($sources as $src): ?>
                <div class="nav-item" data-section="<?= htmlspecialchars($src['slug']) ?>"
                    data-label="<?= htmlspecialchars($src['label']) ?>" title="<?= htmlspecialchars($src['label']) ?>">
                    <span class="nav-icon"><?= icon($src['icon'], $icons) ?></span>
                    <span class="nav-label"><?= htmlspecialchars($src['label']) ?></span>
                </div>
                <?php endforeach; ?>

                <div class="section-label" style="margin-top:.5rem">System</div>

                <div class="nav-item" data-section="overview" data-label="Overview" title="Overview">
                    <span class="nav-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <rect x="3" y="3" width="7" height="7" rx="1" />
                            <rect x="14" y="3" width="7" height="7" rx="1" />
                            <rect x="3" y="14" width="7" height="7" rx="1" />
                            <rect x="14" y="14" width="7" height="7" rx="1" />
                        </svg>
                    </span>
                    <span class="nav-label">Overview</span>
                </div>

                <?php if ($user['role'] === 'admin'): ?>
                <div class="nav-item" data-section="settings" data-label="Settings" title="Settings">
                    <span class="nav-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572C2.561 15.274 2.561 12.776 4.317 12.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                            <circle cx="12" cy="12" r="3" />
                        </svg>
                    </span>
                    <span class="nav-label">Settings</span>
                </div>
                <?php endif; ?>

            </nav>

            <!-- User footer -->
            <div class="sidebar-footer">
                <div class="avatar"><?= htmlspecialchars($initials) ?></div>
                <div class="user-info">
                    <div class="user-name"><?= htmlspecialchars($user['name']) ?></div>
                    <div class="user-role"><?= htmlspecialchars($user['role']) ?></div>
                </div>
            </div>

        </aside>
        <!-- /SIDEBAR -->

        <!-- ══════════════ MAIN ══════════════ -->
        <div class="main">

            <!-- Topbar -->
            <div class="topbar">
                <button class="topbar-toggle" id="sidebar-toggle" aria-label="Toggle sidebar">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round">
                        <line x1="3" y1="6" x2="21" y2="6" />
                        <line x1="3" y1="12" x2="21" y2="12" />
                        <line x1="3" y1="18" x2="21" y2="18" />
                    </svg>
                </button>

                <span class="topbar-title" id="topbar-title">Dashboard</span>

                <div class="topbar-meta" id="topbar-clock"><?= date('D, d M Y · H:i') ?></div>

                <button class="btn-refresh" id="btn-refresh">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="1 4 1 10 7 10" />
                        <path d="M3.51 15a9 9 0 1 0 .49-4.3" />
                    </svg>
                    Refresh
                </button>

                <a class="btn-refresh" id="btn-logout" data-logout href="logout.php" style="margin-left:.25rem">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round"
                            d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h6a2 2 0 012 2v1" />
                    </svg>
                    Sign out
                </a>
            </div>
            <!-- /Topbar -->

            <!-- Content -->
            <div class="content">

                <!-- ─ OVERVIEW PANEL ─ -->
                <div class="section-panel" id="section-overview">
                    <div class="page-heading">
                        <div>
                            <h2>Overview</h2>
                            <p>Status of all connected data sources</p>
                        </div>
                    </div>
                    <div class="stats-grid">
                        <?php foreach ($sources as $src): ?>
                        <div class="stat-card" style="cursor:pointer"
                            onclick="document.querySelector('.nav-item[data-section=\'<?= $src['slug'] ?>\']')?.click()">
                            <div class="stat-label"><?= htmlspecialchars($src['label']) ?></div>
                            <div class="stat-value" style="font-size:1rem;color:var(--muted)">
                                <?= $src['active'] ? '● Connected' : '○ Inactive' ?>
                            </div>
                            <div class="stat-sub"><?= parse_url($src['api_url'], PHP_URL_HOST) ?></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- ─ DYNAMIC SECTION PANELS (one per data source) ─ -->
                <?php foreach ($sources as $src): ?>
                <div class="section-panel" id="section-<?= htmlspecialchars($src['slug']) ?>"
                    data-source="<?= htmlspecialchars($src['slug']) ?>">
                    <div class="page-heading">
                        <div>
                            <h2><?= htmlspecialchars($src['label']) ?></h2>
                            <p><?= htmlspecialchars(parse_url($src['api_url'], PHP_URL_HOST)) ?></p>
                        </div>
                        <span class="badge-status cache">Cached</span>
                    </div>

                    <!-- Stats cards — populated via JS -->
                    <div class="stats-grid">
                        <div class="stat-card" style="opacity:.35">
                            <div class="stat-label">Loading…</div>
                            <div class="stat-value">—</div>
                        </div>
                    </div>

                    <!-- Placeholder for future charts/tables per section -->
                    <div id="<?= htmlspecialchars($src['slug']) ?>-extras"></div>
                </div>
                <?php endforeach; ?>

                <!-- ─ SETTINGS PANEL (admin only) ─ -->
                <?php if ($user['role'] === 'admin'): ?>
                <div class="section-panel" id="section-settings">
                    <div class="page-heading">
                        <div>
                            <h2>Settings</h2>
                            <p>Manage data sources and dashboard users</p>
                        </div>
                    </div>
                    <div class="info-card">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572C2.561 15.274 2.561 12.776 4.317 12.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                            <circle cx="12" cy="12" r="3" />
                        </svg>
                        <p>Settings UI coming soon.<br>Edit data sources directly in the <code>data_sources</code> table
                            for now.</p>
                    </div>
                </div>
                <?php endif; ?>

            </div>
            <!-- /Content -->

        </div>
        <!-- /MAIN -->

    </div>
    <!-- /layout -->

    <script src="assets/js/dashboard.js?v=<?= (int) @filemtime(__DIR__ . '/assets/js/dashboard.js') ?>"></script>
    <script>
    // Live clock update
    setInterval(() => {
        const el = document.getElementById('topbar-clock');
        if (el) {
            const now = new Date();
            const days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
            const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
            const pad = n => String(n).padStart(2, '0');
            el.textContent =
                `${days[now.getDay()]}, ${pad(now.getDate())} ${months[now.getMonth()]} ${now.getFullYear()} · ${pad(now.getHours())}:${pad(now.getMinutes())}`;
        }
    }, 30000);
    </script>
</body>

</html>