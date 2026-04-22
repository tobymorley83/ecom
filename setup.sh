#!/bin/bash
# First-time server setup for an ecom site deployment.
# Run on the aaPanel VPS after `git clone`: bash setup.sh
set -e

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
echo "=== ecom site setup ==="
echo "Project dir: $SCRIPT_DIR"

echo "[1/3] Checking git..."
command -v git &>/dev/null || { echo "ERROR: git not found. apt install git"; exit 1; }
echo "  $(git --version)"

echo "[2/3] Bootstrapping per-site config..."
cd "$SCRIPT_DIR"

for pair in \
  "config/config.example.php:config/config.php" \
  "config/site.example.json:config/site.json" \
  "config/products.example.json:config/products.json"
do
    src="${pair%%:*}"
    dst="${pair##*:}"
    if [ ! -f "$dst" ] && [ -f "$src" ]; then
        cp "$src" "$dst"
        echo "  Created $dst — EDIT with real values for this site."
    else
        echo "  $dst exists or template missing — skipping."
    fi
done

echo "[3/3] Setting permissions..."
chmod 600 config/config.php 2>/dev/null || true
chown -R www:www "$SCRIPT_DIR" 2>/dev/null || true
echo "  Done."

echo ""
echo "=== Next steps ==="
echo "  1. Edit config/config.php (webhook secret: openssl rand -hex 32, DB creds)"
echo "  2. Edit config/site.json (domain, language, currency)"
echo "  3. Edit config/products.json (this site's catalog)"
echo "  4. aaPanel nginx: deny /config/, /.git, /setup.sh"
echo "  5. GitHub webhook: https://<domain>/deploy.php, same secret"
