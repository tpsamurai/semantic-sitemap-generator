# Semantic Sitemap Generator

## Metadata
Name: Semantic Sitemap Generator
Version: 1.0
Released by: https://www.ethical.ink
Contact: D. S. Haq 
Email: mailto:tools@ethical.ink
Developed by Samurai Labs for Ethical.ink.
https://tpsamurai.com/labs.

# Description 
A trustworthy, machine-generated semantic sitemap that combines business identity with actual site content. Solves the LLMS.txt manipulation problem by automating content discovery.

## The Problem
LLMS.txt files are manually maintained and susceptible to manipulation. This tool generates a verified, comprehensive semantic map automatically.

## Quick Start

### Node.js
```bash
cd core && npm install
cp .env.example .env      # Set BASE_URL=https://yoursite.com
node src/index.js
```

### WordPress Plugin
1. Upload `wordpress-plugin/` to `/wp-content/plugins/semantic-sitemap`
2. Activate → Settings > Semantic Sitemap → Generate

### Output URLs
- `https://yoursite.com/semantic-sitemap.json`
- `https://yoursite.com/semantic-sitemap.xml`
- `https://yoursite.com/semantic-sitemap.txt`

## Output Structure
```json
{
  "generatedAt": "2026-02-17T10:30:00Z",
  "trustLevel": "machine-generated",
  "businessIdentity": {
    "name": "Your Company",
    "description": "What you do",
    "mission": "Your mission",
    "products": [],
    "services": []
  },
  "siteStructure": {
    "pages": [{
      "url": "https://example.com/about",
      "title": "About Us",
      "metaDescription": "...",
      "keywords": ["about", "company"],
      "abstract": "First 200 words...",
      "headings": { "h1": [], "h2": [], "h3": [] },
      "lastModified": "2026-02-15T08:00:00Z"
    }]
  },
  "statistics": { "totalPages": 25, "crawlDuration": "12.5s" }
}
```

## Project Structure
```
semantic-sitemap-generator/
├── core/                    # Node.js standalone
│   ├── src/
│   │   ├── index.js        # Entry point & CLI
│   │   ├── crawler.js      # Web crawler
│   │   ├── parser.js       # HTML parser
│   │   ├── generator.js    # Output generator
│   │   └── config.js       # Config management
│   ├── config/business.json
│   ├── .env.example
│   └── package.json
├── wordpress-plugin/        # WP plugin
│   ├── semantic-sitemap.php
│   ├── includes/
│   └── assets/
├── examples/
├── docs/
├── QUICKSTART.md
└── LICENSE
```

## Requirements
- **Node.js**: 16.x+ | **WordPress**: 5.8+ / PHP 7.4+

## License
MIT
