import { existsSync } from 'node:fs';
import { mkdir, writeFile } from 'node:fs/promises';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import * as chromeLauncher from 'chrome-launcher';
import lighthouse from 'lighthouse';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const outputDirectory = path.join(root, 'storage', 'cache', 'lighthouse');
const baseUrl = (process.env.LIGHTHOUSE_BASE_URL || 'http://127.0.0.1:8090').replace(/\/$/, '');
const chromeCandidates = process.platform === 'win32'
  ? [
      'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',
      'C:\\Program Files (x86)\\Microsoft\\Edge\\Application\\msedge.exe',
    ]
  : [];
const chromePath = process.env.CHROME_PATH || chromeCandidates.find((candidate) => existsSync(candidate));

if (!chromePath) {
  throw new Error('Set CHROME_PATH to a local Chrome or Edge executable.');
}

const allPages = {
  home: '/',
  solution: '/solutions/workflows-conformite',
  islamicFinance: '/finance-islamique',
  article: '/insights/referentiel-moteur-regles-conformite-charia',
  contact: '/contact',
};
const requestedPages = new Set((process.env.LIGHTHOUSE_PAGES || '')
  .split(',')
  .map((value) => value.trim())
  .filter(Boolean));
const pages = Object.fromEntries(Object.entries(allPages)
  .filter(([key]) => requestedPages.size === 0 || requestedPages.has(key)));

if (Object.keys(pages).length === 0) {
  throw new Error(`No matching Lighthouse pages. Choose from: ${Object.keys(allPages).join(', ')}.`);
}

await mkdir(outputDirectory, { recursive: true });
const chrome = await chromeLauncher.launch({
  chromePath,
  chromeFlags: ['--headless=new', '--no-sandbox', '--disable-gpu', '--disable-dev-shm-usage'],
  logLevel: 'silent',
});

const summaries = [];
let failed = false;

try {
  for (const [key, route] of Object.entries(pages)) {
    const result = await lighthouse(`${baseUrl}${route}`, {
      port: chrome.port,
      logLevel: 'silent',
      output: 'json',
      onlyCategories: ['performance', 'accessibility', 'best-practices', 'seo'],
    });
    if (!result?.lhr) throw new Error(`Lighthouse returned no result for ${route}.`);

    const { lhr } = result;
    const summary = {
      page: key,
      route,
      performance: Math.round((lhr.categories.performance.score || 0) * 100),
      accessibility: Math.round((lhr.categories.accessibility.score || 0) * 100),
      bestPractices: Math.round((lhr.categories['best-practices'].score || 0) * 100),
      seo: Math.round((lhr.categories.seo.score || 0) * 100),
      lcpMs: Math.round(lhr.audits['largest-contentful-paint'].numericValue || 0),
      cls: Number((lhr.audits['cumulative-layout-shift'].numericValue || 0).toFixed(3)),
      tbtMs: Math.round(lhr.audits['total-blocking-time'].numericValue || 0),
    };
    const passes = summary.performance >= 90
      && summary.accessibility >= 95
      && summary.bestPractices >= 95
      && summary.seo >= 95
      && summary.lcpMs <= 2500
      && summary.cls <= 0.1
      && summary.tbtMs <= 200;
    summary.status = passes ? 'PASS' : 'FAIL';
    failed ||= !passes;
    summaries.push(summary);
    await writeFile(path.join(outputDirectory, `${key}.json`), result.report);
  }
} finally {
  try {
    await chrome.kill();
  } catch (error) {
    // Restricted QA sandboxes can stop Chrome but deny removal of its temporary profile.
    if (error?.code !== 'EPERM') throw error;
  }
}

await writeFile(
  path.join(outputDirectory, 'summary.json'),
  `${JSON.stringify(summaries, null, 2)}\n`,
);
console.table(summaries);
process.exit(failed ? 1 : 0);
