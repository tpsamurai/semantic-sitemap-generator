#!/usr/bin/env node
/**
 * Semantic Sitemap Generator — Main Entry Point
 */
const Config = require('./config');
const Crawler = require('./crawler');
const Parser = require('./parser');
const Generator = require('./generator');

class SemanticSitemapGenerator {
  constructor(options = {}) {
    this.config    = new Config(options.configPath);
    this.crawler   = new Crawler(this.config);
    this.parser    = new Parser(this.config);
    this.generator = new Generator(this.config);
  }

  async generate() {
    console.log('='.repeat(60));
    console.log('Semantic Sitemap Generator v1.0.0');
    console.log('='.repeat(60));

    const errors = this.config.validate();
    if (errors.length) throw new Error(`Config errors:\n${errors.join('\n')}`);

    const businessInfo = this.config.loadBusinessInfo();
    console.log(`\nBusiness : ${businessInfo.name}`);
    console.log(`Base URL : ${this.config.get('baseUrl')}\n`);

    // Crawl
    const pages = await this.crawler.start();
    if (!pages.length) throw new Error('No pages found');

    // Parse
    console.log('\nParsing pages...');
    const parsed = pages.map((page, i) => {
      process.stdout.write(`\rParsing ${i+1}/${pages.length}`);
      return { ...this.parser.parse(page.html, page.url), depth: page.depth };
    });
    console.log(' ✓\n');

    // Generate
    const sitemap = this.generator.generate(businessInfo, parsed);
    const outputPath = await this.generator.save();

    console.log('\n' + '='.repeat(60));
    console.log('Complete!');
    console.log(`Pages  : ${sitemap.statistics.totalPages}`);
    console.log(`Output : ${outputPath}`);
    console.log('='.repeat(60));
    return sitemap;
  }

  async save(outputPath, format) {
    return this.generator.save(outputPath, format);
  }

  getSitemap() { return this.generator.getSitemap(); }
}

// CLI
if (require.main === module) {
  const yargs = require('yargs/yargs');
  const { hideBin } = require('yargs/helpers');
  const argv = yargs(hideBin(process.argv))
    .option('config',  { alias: 'c', type: 'string', describe: 'Custom config file path' })
    .option('output',  { alias: 'o', type: 'string', describe: 'Output file path' })
    .option('format',  { alias: 'f', type: 'string', describe: 'Output format: json, xml, txt', choices: ['json','xml','txt'] })
    .help()
    .argv;

  const gen = new SemanticSitemapGenerator({ configPath: argv.config });
  gen.generate()
    .then(async () => {
      if (argv.output || argv.format) await gen.save(argv.output, argv.format);
      process.exit(0);
    })
    .catch(err => { console.error('\n❌', err.message); process.exit(1); });
}

module.exports = SemanticSitemapGenerator;
