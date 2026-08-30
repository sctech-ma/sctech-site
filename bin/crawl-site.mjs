import { chromium } from '@playwright/test';

const baseUrl = new URL(process.env.CRAWL_BASE_URL || 'http://127.0.0.1:8090/');
const browser = await chromium.launch({
  headless: true,
  channel: process.env.PLAYWRIGHT_CHANNEL || 'chrome',
});
const context = await browser.newContext({ locale: 'fr-FR' });
const page = await context.newPage();
const pending = new Set(['/']);
const visited = new Set();
const assets = new Set();
const failures = [];

const normalizePage = (value) => {
  const url = new URL(value, baseUrl);
  url.hash = '';
  return url.origin === baseUrl.origin ? `${url.pathname}${url.search}` : null;
};

try {
  while (pending.size > 0) {
    if (visited.size > 100) throw new Error('Crawl stopped after 100 pages.');
    const route = pending.values().next().value;
    pending.delete(route);
    if (visited.has(route)) continue;
    visited.add(route);

    const response = await page.goto(new URL(route, baseUrl).href, { waitUntil: 'load' });
    const status = response?.status() || 0;
    if (status >= 400 || status === 0) failures.push(`${route} returned ${status || 'no response'}`);

    const discovered = await page.evaluate(() => ({
      links: [...document.querySelectorAll('a[href]')].map((element) => element.href),
      assets: [
        ...[...document.querySelectorAll('img[src],script[src]')].map((element) => element.src),
        ...[...document.querySelectorAll('link[rel~="stylesheet"][href],link[rel~="icon"][href],link[rel="manifest"][href]')]
          .map((element) => element.href),
      ],
    }));

    for (const href of discovered.links) {
      const normalized = normalizePage(href);
      if (normalized !== null && !visited.has(normalized)) pending.add(normalized);
    }
    for (const src of discovered.assets) {
      const url = new URL(src, baseUrl);
      if (url.origin === baseUrl.origin) assets.add(url.href);
    }
  }

  for (const asset of assets) {
    const response = await context.request.get(asset);
    if (!response.ok()) failures.push(`${new URL(asset).pathname} returned ${response.status()}`);
  }
} finally {
  await browser.close();
}

console.log(`Crawl: ${visited.size} pages, ${assets.size} assets.`);
if (failures.length > 0) {
  failures.forEach((failure) => console.error(`- ${failure}`));
  process.exit(1);
}
