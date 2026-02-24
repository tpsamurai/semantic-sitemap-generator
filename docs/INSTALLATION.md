# Installation Guide

## Node.js

**Requirements:** Node.js 16+, npm

```bash
git clone https://github.com/yourusername/semantic-sitemap-generator.git
cd semantic-sitemap-generator/core
npm install
cp .env.example .env
# Edit .env with your settings
npm start
```

## WordPress

**Requirements:** WordPress 5.8+, PHP 7.4+

**Method 1 — Upload:**
1. Zip the `wordpress-plugin` folder
2. Plugins > Add New > Upload Plugin
3. Activate

**Method 2 — FTP:**
Upload `wordpress-plugin/` to `/wp-content/plugins/semantic-sitemap/` then activate

**Method 3 — WP-CLI:**
```bash
wp plugin install /path/to/semantic-sitemap.zip --activate
```

## Troubleshooting

**404 on sitemap URLs (WordPress):**
Settings > Permalinks > Save Changes (flushes rewrite rules)

**Node.js module errors:**
```bash
rm -rf node_modules && npm install
```

**WordPress cron not running:**
```bash
wp cron event run semantic_sitemap_cron
```
