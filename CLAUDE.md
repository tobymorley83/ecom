# E-com Multi-Site Platform

## Project Overview
Shared codebase for custom PHP e-commerce sites on aaPanel VPS.
Each site is a separate clone of this repo with its own config, domain,
language, currency, and product catalog.

## Architecture
- Stack: Custom PHP 8.x on Nginx (aaPanel), MySQL per site
- Server: 185.81.128.20 (aaPanel VPS)
- Current sites:
  - ofertasydescuento.com — /www/wwwroot/ofertasydescuento.com — ES, EUR
  - [future sites added here as they go live]

## Deployment
- Local dev: WSL2 Ubuntu, project at `~/claude/ecom`
- Flow: git push → GitHub webhook → deploy.php → git fetch + reset --hard origin/main
- Each site has its own GitHub webhook but shares this repo.
- Deploy log per site: /tmp/deploy_<site_key>.log

## Per-Site Files (NEVER committed — unique per site)
- config/config.php       — DB creds, webhook secret, site_key, API keys
- config/site.json        — domain, language, currency, country, contact
- config/products.json    — catalog for this site
- .user.ini               — aaPanel-managed

## Adding a New Site
1. aaPanel → create new Site for the domain
2. ssh in: git clone git@github.com:tobymorley83/ecom.git /www/wwwroot/<domain>
3. cd /www/wwwroot/<domain> && bash setup.sh
4. Edit config/config.php, config/site.json, config/products.json
5. aaPanel → nginx config → add denies (see below)
6. GitHub → Settings → Webhooks → add https://<domain>/deploy.php
   (same secret as config/config.php webhook_secret)

## Nginx Security (paste inside server{} block)
    location ~ /\.git       { deny all; return 404; }
    location ~ ^/config/    { deny all; return 404; }
    location ~ ^/setup\.sh$ { deny all; return 404; }

## Testing Deploy
    # On server:
    tail -f /tmp/deploy_<site_key>.log
    # In WSL:
    echo "# test $(date)" >> README.md && git commit -am "test deploy" && git push

## Adding New Per-Site Config Keys
1. Add the key to config/site.example.json with a sane default
2. Commit and push
3. On each live site's server, add the key to its config/site.json
4. PHP code should fall back to defaults if key missing

## Migration Path to Per-Site Branches (future)
If we ever need staggered rollouts:
- Create branch site/<site_key> off main
- Change that site's webhook to only fire on pushes to its branch
- Merge main → site/<site_key> when ready to roll out
