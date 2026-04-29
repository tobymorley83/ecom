#!/bin/bash
# First-time server setup for an ecom site deployment.
# Run on the aaPanel VPS after `git clone`: bash setup.sh
set -e

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
echo "=== ecom site setup ==="
echo "Project dir: $SCRIPT_DIR"

cd "$SCRIPT_DIR"

echo "[1/2] Checking git..."
command -v git &>/dev/null || { echo "ERROR: git not found. apt install git"; exit 1; }
echo "  $(git --version)"

echo "[2/2] Setting permissions..."
chmod 600 config.php 2>/dev/null || true
chmod 600 brevo/config.local.php 2>/dev/null || true
chown -R www:www "$SCRIPT_DIR" 2>/dev/null || true
echo "  Done."

echo ""
echo "=== Next steps ==="
echo "  1. Edit config.php for this site (language, currency, country, products,"
echo "     traffic.fb.checkout_url, tracking pixels, spin_wheel, etc.)"
echo "  2. Make sure data/products.json exists for this site's catalog."
echo "  3. aaPanel nginx: deny /config/, /.git, /setup.sh"
echo "  4. GitHub webhook for this domain → https://<domain>/deploy.php"
echo "     (use the shared secret from config/deploy.php; no per-site override needed)"
