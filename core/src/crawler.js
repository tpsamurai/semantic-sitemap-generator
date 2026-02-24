/**
 * Web Crawler Module
 */
const axios = require('axios');
const { URL } = require('url');
const robotsParser = require('robots-parser');
const PQueue = require('p-queue').default;
const cheerio = require('cheerio');

class Crawler {
  constructor(config) {
    this.config = config;
    this.baseUrl = new URL(config.get('baseUrl'));
    this.visited = new Set();
    this.queue = new PQueue({
      concurrency: config.get('crawler.maxConcurrentRequests', 5),
    });
    this.robots = null;
    this.pages = [];
  }

  async initialize() {
    console.log('Initializing crawler...');
    if (this.config.get('crawler.respectRobotsTxt')) {
      await this.loadRobotsTxt();
    }
  }

  async loadRobotsTxt() {
    try {
      const url = new URL('/robots.txt', this.baseUrl).href;
      const res = await axios.get(url, { timeout: 5000 });
      this.robots = robotsParser(url, res.data);
      console.log('robots.txt loaded');
    } catch (e) {
      console.log('No robots.txt found');
    }
  }

  isAllowed(url) {
    if (!this.robots) return true;
    return this.robots.isAllowed(url, this.config.get('crawler.userAgent'));
  }

  shouldCrawl(url) {
    try {
      const u = new URL(url);
      if (u.hostname !== this.baseUrl.hostname) return false;
      if (this.visited.has(url)) return false;
      const skip = ['.pdf','.jpg','.jpeg','.png','.gif','.css','.js','.ico','.svg','.woff','.woff2','.mp4','.zip'];
      if (skip.some(ext => u.pathname.toLowerCase().endsWith(ext))) return false;
      if (!this.isAllowed(url)) return false;
      return true;
    } catch (e) {
      return false;
    }
  }

  async fetchPage(url) {
    try {
      const res = await axios.get(url, {
        timeout: this.config.get('crawler.requestTimeout', 10000),
        headers: { 'User-Agent': this.config.get('crawler.userAgent') },
        maxRedirects: 5,
      });
      return { url: res.request.res?.responseUrl || url, html: res.data, headers: res.headers, status: res.status };
    } catch (e) {
      console.error(`Failed: ${url} — ${e.message}`);
      return null;
    }
  }

  extractLinks(html, currentUrl) {
    const $ = cheerio.load(html);
    const links = new Set();
    $('a[href]').each((i, el) => {
      try {
        const href = $(el).attr('href');
        const abs = new URL(href, currentUrl).href;
        const clean = abs.split('#')[0].split('?')[0];
        if (this.shouldCrawl(clean)) links.add(clean);
      } catch (e) {}
    });
    return Array.from(links);
  }

  async crawl(url, depth = 0) {
    if (depth > this.config.get('crawler.maxDepth', 3)) return;
    if (!this.shouldCrawl(url)) return;
    this.visited.add(url);
    console.log(`[${depth}] ${url}`);

    const page = await this.fetchPage(url);
    if (!page) return;

    this.pages.push({ url: page.url, html: page.html, headers: page.headers, depth });

    if (depth < this.config.get('crawler.maxDepth', 3)) {
      const links = this.extractLinks(page.html, page.url);
      for (const link of links) {
        this.queue.add(() => this.crawl(link, depth + 1));
      }
    }
  }

  async start() {
    await this.initialize();
    const t = Date.now();
    console.log(`Crawling: ${this.baseUrl.href}`);
    await this.crawl(this.baseUrl.href, 0);
    await this.queue.onIdle();
    console.log(`Done: ${this.pages.length} pages in ${((Date.now()-t)/1000).toFixed(1)}s`);
    return this.pages;
  }

  async crawlUrls(urls) {
    await this.initialize();
    for (const url of urls) {
      if (this.shouldCrawl(url)) {
        this.queue.add(async () => {
          const page = await this.fetchPage(url);
          if (page) this.pages.push({ url: page.url, html: page.html, headers: page.headers, depth: 0 });
        });
      }
    }
    await this.queue.onIdle();
    return this.pages;
  }

  getPages() { return this.pages; }
}

module.exports = Crawler;
