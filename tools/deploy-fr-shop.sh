#!/bin/bash
# Bootstrap a French e-com shop at /www/wwwroot/<domain>.
# Usage: bash tools/deploy-fr-shop.sh <domain>
# Idempotent — safe to re-run.
set -e

DOMAIN="${1:?usage: $0 <domain>}"
SITE_DIR="/www/wwwroot/$DOMAIN"
NAME=$(echo "$DOMAIN" | sed 's/\..*//' | sed 's/.*/\u&/')

echo "=== Bootstrapping $DOMAIN at $SITE_DIR (site_name=$NAME) ==="

if [ ! -d "$SITE_DIR" ]; then
    echo "ERROR: $SITE_DIR does not exist. Create the site in aaPanel first."
    exit 1
fi

BACKUP="/root/backup/$DOMAIN-$(date +%Y%m%d-%H%M%S)"
mkdir -p "$BACKUP"
cp -a "$SITE_DIR/config.php" "$SITE_DIR/data/products.json" "$BACKUP/" 2>/dev/null || true
echo "[1/5] Backup at $BACKUP"

git config --global --add safe.directory "$SITE_DIR"
cd "$SITE_DIR"
git init 2>/dev/null || true
git remote add origin git@github.com:tobymorley83/ecom.git 2>/dev/null \
    || git remote set-url origin git@github.com:tobymorley83/ecom.git
git fetch origin main
git checkout -f -B main origin/main
echo "[2/5] Repo synced to origin/main"

cp config.fr.example.php config.php
sed -i "s|__SITE_URL__|https://$DOMAIN|; s|__SUPPORT_EMAIL__|support@$DOMAIN|; s|__SITE_NAME__|$NAME|" config.php
chmod 600 config.php
echo "[3/5] config.php stamped (site_name=$NAME)"

if [ ! -f data/products.json ]; then
    cp data/products.example.json data/products.json
    echo "[4/5] data/products.json seeded from example"
else
    echo "[4/5] data/products.json already exists, kept"
fi

chown -R www:www "$SITE_DIR" 2>/dev/null || true
echo "[5/5] Ownership set to www:www"

echo ""
echo "=== Done for $DOMAIN ==="
echo "Next:"
echo "  - aaPanel: add nginx denies for /\\.git, /config/, /setup.sh"
echo "  - GitHub: add webhook https://$DOMAIN/deploy.php with the shared secret"
