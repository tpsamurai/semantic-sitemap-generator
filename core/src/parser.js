/**
 * HTML Parser and Content Extraction
 */
const cheerio = require('cheerio');
const natural = require('natural');

class Parser {
  constructor(config) {
    this.config = config;
    this.tokenizer = new natural.WordTokenizer();
    this.stopWords = new Set([
      'the','a','an','and','or','but','in','on','at','to','for','of','with',
      'by','from','as','is','was','are','were','been','be','have','has','had',
      'do','does','did','will','would','could','should','may','might','must',
      'can','this','that','these','those','i','you','he','she','it','we','they',
      'what','which','who','when','where','why','how','not','no','its','their'
    ]);
  }

  parse(html, url) {
    const $ = cheerio.load(html);
    const cfg = this.config;

    const data = { url, title: this.extractTitle($) };

    if (cfg.get('extraction.includeMetaDescription')) data.metaDescription = this.extractMetaDescription($);
    if (cfg.get('extraction.includeKeywords'))       data.keywords = this.extractKeywords($);
    if (cfg.get('extraction.includeAbstract'))       data.abstract = this.extractAbstract($, cfg.get('extraction.abstractLength', 200));
    if (cfg.get('extraction.includeHeadings'))       data.headings = this.extractHeadings($);
    if (cfg.get('extraction.includeStructuredData')) data.structuredData = this.extractStructuredData($);
    data.lastModified = null;

    return data;
  }

  extractTitle($) {
    return $('title').first().text().trim()
      || $('h1').first().text().trim()
      || $('meta[property="og:title"]').attr('content')
      || 'Untitled';
  }

  extractMetaDescription($) {
    return $('meta[name="description"]').attr('content')
      || $('meta[property="og:description"]').attr('content')
      || $('meta[name="twitter:description"]').attr('content')
      || null;
  }

  extractKeywords($) {
    const kws = new Set();
    const meta = $('meta[name="keywords"]').attr('content');
    if (meta) meta.split(',').forEach(k => { const t = k.trim().toLowerCase(); if (t) kws.add(t); });

    // Frequency-based extraction from body text
    $('script,style,nav,header,footer').remove();
    const text = $('body').text().toLowerCase();
    const tokens = this.tokenizer.tokenize(text);
    const freq = {};
    tokens
      .filter(t => t.length > 3 && !this.stopWords.has(t) && !/^\d+$/.test(t))
      .forEach(t => { freq[t] = (freq[t] || 0) + 1; });

    Object.entries(freq)
      .sort((a, b) => b[1] - a[1])
      .slice(0, 8)
      .forEach(([w]) => kws.add(w));

    return Array.from(kws).slice(0, 10);
  }

  extractAbstract($, wordCount) {
    $('script,style,nav,header,footer,aside,.sidebar,.menu').remove();

    let content = '';
    for (const sel of ['main','article','[role="main"]','.content','.main-content','#content','.post-content','.entry-content']) {
      const el = $(sel).first();
      if (el.length) { content = el.text(); break; }
    }
    if (!content) content = $('body').text();

    content = content.replace(/\s+/g, ' ').trim();
    const words = content.split(' ');
    return words.length > wordCount
      ? words.slice(0, wordCount).join(' ') + '...'
      : content || null;
  }

  extractHeadings($) {
    const h = { h1:[], h2:[], h3:[], h4:[], h5:[], h6:[] };
    for (let i = 1; i <= 6; i++) {
      $(`h${i}`).each((_, el) => {
        const t = $(el).text().trim();
        if (t) h[`h${i}`].push(t);
      });
    }
    return h;
  }

  extractStructuredData($) {
    const data = [];

    $('script[type="application/ld+json"]').each((_, el) => {
      try { data.push({ type: 'json-ld', data: JSON.parse($(el).html()) }); } catch (e) {}
    });

    const og = {};
    $('meta[property^="og:"]').each((_, el) => {
      const p = $(el).attr('property'), c = $(el).attr('content');
      if (p && c) og[p] = c;
    });
    if (Object.keys(og).length) data.push({ type: 'opengraph', data: og });

    return data;
  }
}

module.exports = Parser;
