/**
 * Semantic Sitemap Generator
 */
const fs = require('fs');
const path = require('path');
const xml2js = require('xml2js');

class Generator {
  constructor(config) {
    this.config = config;
    this.sitemap = null;
  }

  generate(businessInfo, pagesData) {
    console.log('Generating semantic sitemap...');
    this.sitemap = {
      generatedAt: new Date().toISOString(),
      generator: 'Semantic Sitemap Generator v1.0.0',
      trustLevel: 'machine-generated',
      businessIdentity: {
        name: businessInfo.name || 'Unknown',
        description: businessInfo.description || '',
        mission: businessInfo.mission || '',
        products: businessInfo.products || [],
        services: businessInfo.services || [],
        industry: businessInfo.industry || '',
        contact: businessInfo.contact || {},
      },
      siteStructure: {
        pages: pagesData.map(p => ({
          url: p.url,
          title: p.title,
          metaDescription: p.metaDescription || null,
          keywords: p.keywords || [],
          abstract: p.abstract || null,
          headings: p.headings || {},
          structuredData: p.structuredData || [],
          lastModified: p.lastModified || null,
          depth: p.depth || 0,
        })),
        categories: [],
        tags: [],
      },
      statistics: {
        totalPages: pagesData.length,
        totalCategories: 0,
        totalTags: 0,
      },
    };
    console.log(`Sitemap generated: ${pagesData.length} pages`);
    return this.sitemap;
  }

  async save(outputPath = null, format = null) {
    if (!this.sitemap) throw new Error('Call generate() first');
    format = format || this.config.get('outputFormat', 'json');
    outputPath = outputPath || path.join(this.config.get('outputDir', './output'), `semantic-sitemap.${format}`);

    const dir = path.dirname(outputPath);
    if (!fs.existsSync(dir)) fs.mkdirSync(dir, { recursive: true });

    let content;
    if (format === 'json')      content = JSON.stringify(this.sitemap, null, 2);
    else if (format === 'xml')  content = await this.toXML();
    else if (format === 'txt')  content = this.toText();
    else throw new Error(`Unknown format: ${format}`);

    fs.writeFileSync(outputPath, content, 'utf8');
    console.log(`Saved: ${outputPath}`);
    return outputPath;
  }

  async toXML() {
    const builder = new xml2js.Builder({ rootName: 'semanticSitemap', xmldec: { version: '1.0', encoding: 'UTF-8' } });
    return builder.buildObject({
      $: { generatedAt: this.sitemap.generatedAt, trustLevel: this.sitemap.trustLevel },
      businessIdentity: this.sitemap.businessIdentity,
      pages: {
        page: this.sitemap.siteStructure.pages.map(p => ({
          $: { url: p.url },
          title: p.title,
          metaDescription: p.metaDescription,
          keywords: p.keywords.join(', '),
          abstract: p.abstract,
          lastModified: p.lastModified,
        })),
      },
      statistics: this.sitemap.statistics,
    });
  }

  toText() {
    const s = this.sitemap;
    let t = `# SEMANTIC SITEMAP\n# Generated: ${s.generatedAt}\n# Trust Level: ${s.trustLevel}\n\n`;
    t += `## BUSINESS IDENTITY\n\n`;
    t += `Name: ${s.businessIdentity.name}\n`;
    t += `Description: ${s.businessIdentity.description}\n`;
    t += `Mission: ${s.businessIdentity.mission}\n`;
    if (s.businessIdentity.products.length) t += `Products: ${s.businessIdentity.products.join(', ')}\n`;
    if (s.businessIdentity.services.length) t += `Services: ${s.businessIdentity.services.join(', ')}\n`;
    t += `\n## SITE STRUCTURE\n\nTotal Pages: ${s.statistics.totalPages}\n\n`;
    s.siteStructure.pages.forEach((p, i) => {
      t += `### Page ${i+1}\nURL: ${p.url}\nTitle: ${p.title}\n`;
      if (p.metaDescription) t += `Description: ${p.metaDescription}\n`;
      if (p.keywords.length) t += `Keywords: ${p.keywords.join(', ')}\n`;
      if (p.abstract) t += `Abstract: ${p.abstract.substring(0,150)}...\n`;
      if (p.headings?.h1?.length) t += `H1: ${p.headings.h1.join(', ')}\n`;
      if (p.headings?.h2?.length) t += `H2: ${p.headings.h2.join(', ')}\n`;
      t += '\n';
    });
    return t;
  }

  getSitemap() { return this.sitemap; }
}

module.exports = Generator;
