# Quick Start

## Node.js (5 minutes)
```bash
cd core
npm install
cp .env.example .env
# Edit .env — set BASE_URL=https://yoursite.com
# Edit config/business.json — add your business info
npm start
# Output: ./output/semantic-sitemap.json
```

## WordPress (2 minutes)
1. Upload `wordpress-plugin/` → `/wp-content/plugins/semantic-sitemap`
2. Activate plugin
3. Settings > Semantic Sitemap > fill in business info > Generate

## Cron (automated)
```bash
# Add to crontab — runs daily at 2 AM
0 2 * * * cd /path/to/core && node src/index.js
```

## Output URLs (WordPress)
- https://yoursite.com/semantic-sitemap.json
- https://yoursite.com/semantic-sitemap.xml  
- https://yoursite.com/semantic-sitemap.txt
