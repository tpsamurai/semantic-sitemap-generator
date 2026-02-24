/**
 * Configuration Management
 */
const fs = require('fs');
const path = require('path');
require('dotenv').config();

class Config {
  constructor(customConfigPath = null) {
    this.config = this.loadConfig(customConfigPath);
  }

  loadConfig(customConfigPath) {
    const defaults = {
      baseUrl: process.env.BASE_URL || 'https://example.com',
      outputDir: process.env.OUTPUT_DIR || './output',
      outputFormat: process.env.OUTPUT_FORMAT || 'json',
      crawler: {
        maxDepth: parseInt(process.env.MAX_DEPTH) || 3,
        maxConcurrentRequests: parseInt(process.env.MAX_CONCURRENT_REQUESTS) || 5,
        requestTimeout: parseInt(process.env.REQUEST_TIMEOUT) || 10000,
        respectRobotsTxt: process.env.RESPECT_ROBOTS_TXT !== 'false',
        userAgent: process.env.USER_AGENT || 'SemanticSitemapBot/1.0',
        rateLimitDelay: parseInt(process.env.RATE_LIMIT_DELAY) || 100,
        maxRetries: parseInt(process.env.MAX_RETRIES) || 3,
      },
      extraction: {
        includeMetaDescription: process.env.INCLUDE_META_DESCRIPTION !== 'false',
        includeKeywords: process.env.INCLUDE_KEYWORDS !== 'false',
        includeHeadings: process.env.INCLUDE_HEADINGS !== 'false',
        includeAbstract: process.env.INCLUDE_ABSTRACT !== 'false',
        abstractLength: parseInt(process.env.ABSTRACT_LENGTH) || 200,
        includeStructuredData: process.env.INCLUDE_STRUCTURED_DATA !== 'false',
      },
      content: {
        includePages: true,
        includePosts: true,
        includeCategories: true,
        includeTags: false,
        includeCustomPostTypes: [],
      },
      businessInfoFile: process.env.BUSINESS_INFO_FILE || './config/business.json',
    };

    let customConfig = {};
    if (customConfigPath && fs.existsSync(customConfigPath)) {
      try {
        customConfig = JSON.parse(fs.readFileSync(customConfigPath, 'utf8'));
      } catch (e) {
        console.warn(`Could not load custom config: ${e.message}`);
      }
    }
    return this.mergeDeep(defaults, customConfig);
  }

  mergeDeep(target, source) {
    const output = Object.assign({}, target);
    if (this.isObject(target) && this.isObject(source)) {
      Object.keys(source).forEach(key => {
        if (this.isObject(source[key])) {
          output[key] = !(key in target)
            ? source[key]
            : this.mergeDeep(target[key], source[key]);
        } else {
          Object.assign(output, { [key]: source[key] });
        }
      });
    }
    return output;
  }

  isObject(item) {
    return item && typeof item === 'object' && !Array.isArray(item);
  }

  get(key, defaultValue = null) {
    const keys = key.split('.');
    let value = this.config;
    for (const k of keys) {
      if (value && typeof value === 'object' && k in value) {
        value = value[k];
      } else {
        return defaultValue;
      }
    }
    return value;
  }

  getAll() { return this.config; }

  loadBusinessInfo() {
    const filePath = path.resolve(this.config.businessInfoFile);
    if (!fs.existsSync(filePath)) {
      console.warn(`Business info not found at ${filePath} — using defaults`);
      return { name: 'Your Company', description: '', mission: '', products: [], services: [] };
    }
    try {
      return JSON.parse(fs.readFileSync(filePath, 'utf8'));
    } catch (e) {
      console.error(`Failed to parse business info: ${e.message}`);
      return {};
    }
  }

  validate() {
    const errors = [];
    if (!this.config.baseUrl) errors.push('BASE_URL is required');
    if (!this.config.baseUrl.startsWith('http')) errors.push('BASE_URL must start with http:// or https://');
    return errors;
  }
}

module.exports = Config;
