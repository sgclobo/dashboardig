#!/usr/bin/env bash
# start.sh — Run the dashboard locally on macOS / Linux
# Usage: ./start.sh
# Make executable first: chmod +x start.sh

set -e

PORT=8000
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

echo ""
echo "  ╔══════════════════════════════════════╗"
echo "  ║   AIFAESA Dashboard — Dev Server     ║"
echo "  ║   http://localhost:${PORT}              ║"
echo "  ║   Press Ctrl+C to stop              ║"
echo "  ╚══════════════════════════════════════╝"
echo ""

# Check PHP
if ! command -v php &>/dev/null; then
    echo "  ERROR: PHP not found."
    echo ""
    echo "  macOS:  brew install php"
    echo "  Ubuntu: sudo apt install php php-sqlite3 php-curl"
    echo "  Fedora: sudo dnf install php php-pdo"
    exit 1
fi

PHP_VER=$(php -r "echo PHP_VERSION;")
echo "  PHP ${PHP_VER}"

# Check php-sqlite3 extension
if ! php -r "new PDO('sqlite::memory:');" &>/dev/null; then
    echo ""
    echo "  ERROR: php-sqlite3 extension not loaded."
    echo "  Ubuntu: sudo apt install php-sqlite3"
    echo "  macOS:  brew install php  (includes sqlite3)"
    exit 1
fi

echo "  SQLite extension: OK"
echo ""

# Open browser (macOS and Linux)
(sleep 1.5 && {
    if command -v xdg-open &>/dev/null; then
        xdg-open "http://localhost:${PORT}" &>/dev/null
    elif command -v open &>/dev/null; then
        open "http://localhost:${PORT}"
    fi
}) &

# Start PHP built-in server
cd "$SCRIPT_DIR"
php -S "localhost:${PORT}" router.php
