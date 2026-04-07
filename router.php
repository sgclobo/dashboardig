<?php
// router.php — PHP built-in dev server router

$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

// Block sensitive files
$blocked = ['/config.php', '/config.local.php', '/database.sql'];
foreach ($blocked as $b) {
    if ($uri === $b) { http_response_code(403); echo '403 Forbidden'; exit; }
}
if (str_starts_with($uri, '/includes/')) {
    http_response_code(403); echo '403 Forbidden'; exit;
}

// Serve static files with correct MIME types
$ext = strtolower(pathinfo($uri, PATHINFO_EXTENSION));
$mimeTypes = [
    'css'   => 'text/css',
    'js'    => 'application/javascript',
    'png'   => 'image/png',
    'jpg'   => 'image/jpeg',
    'jpeg'  => 'image/jpeg',
    'gif'   => 'image/gif',
    'svg'   => 'image/svg+xml',
    'ico'   => 'image/x-icon',
    'woff'  => 'font/woff',
    'woff2' => 'font/woff2',
    'ttf'   => 'font/ttf',
];

if (isset($mimeTypes[$ext])) {
    $file = __DIR__ . $uri;
    if (file_exists($file)) {
        header('Content-Type: ' . $mimeTypes[$ext]);
        readfile($file);
        exit;
    }
    http_response_code(404);
    exit;
}

// Route / to index.php
if ($uri === '/') {
    require __DIR__ . '/index.php';
    exit;
}

// Route .php files directly
$phpFile = __DIR__ . $uri;
if (file_exists($phpFile) && str_ends_with($phpFile, '.php')) {
    require $phpFile;
    exit;
}

// Try adding .php extension
$phpFile = __DIR__ . $uri . '.php';
if (file_exists($phpFile)) {
    require $phpFile;
    exit;
}

http_response_code(404);
echo '404 Not Found: ' . htmlspecialchars($uri);