import { test, expect } from '@playwright/test';
import AxeBuilder from '@axe-core/playwright';

const representativeRoutes = [
  '/',
  '/solutions/workflows-conformite',
  '/finance-islamique',
  '/insights/referentiel-moteur-regles-conformite-charia',
  '/contact',
  '/demander-un-projet',
  '/admin/login',
];

const publicRoutes = [
  '/',
  '/solutions',
  '/solutions/plateformes-investissement',
  '/solutions/portails-investisseurs',
  '/solutions/workflows-conformite',
  '/solutions/data-reporting',
  '/solutions/integrations-financieres',
  '/expertise',
  '/finance-islamique',
  '/approche',
  '/a-propos',
  '/insights',
  '/contact',
  '/demander-un-projet',
];

function observeRuntimeErrors(page) {
  const errors = [];
  page.on('console', (message) => {
    if (message.type() === 'error') errors.push(message.text());
  });
  page.on('pageerror', (error) => errors.push(error.message));
  return errors;
}

test('canonical public routes render without runtime or asset errors', async ({ page }) => {
  const runtimeErrors = observeRuntimeErrors(page);
  for (const route of publicRoutes) {
    const response = await page.goto(route, { waitUntil: 'load' });
    expect(response?.status(), route).toBe(200);
    await expect(page.locator('h1'), route).toHaveCount(1);
    const brokenImages = await page.locator('img').evaluateAll((images) => images
      .filter((image) => !image.getAttribute('src') || (image.complete && image.naturalWidth === 0))
      .map((image) => image.getAttribute('src')));
    expect(brokenImages, route).toEqual([]);
  }
  expect(runtimeErrors).toEqual([]);
});

for (const width of [320, 375, 390, 430, 768, 1024, 1280, 1440, 1920]) {
  test(`homepage remains usable at ${width}px`, async ({ page }) => {
    await page.setViewportSize({ width, height: 900 });
    await page.goto('/', { waitUntil: 'load' });

    const audit = await page.evaluate(() => {
      const duplicateIds = [...document.querySelectorAll('[id]')]
        .map((element) => element.id)
        .filter((id, index, ids) => ids.indexOf(id) !== index);
      const smallTargets = [...document.querySelectorAll(
        'a, button, summary, input:not([type="checkbox"]):not([type="radio"]), select, textarea'
      )].filter((element) => {
        const rect = element.getBoundingClientRect();
        const style = getComputedStyle(element);
        return !element.closest('.form-trap')
          && style.display !== 'none'
          && style.visibility !== 'hidden'
          && rect.width > 0
          && rect.height > 0
          && (rect.width < 44 || rect.height < 44);
      }).map((element) => element.textContent?.trim() || element.getAttribute('name'));

      return {
        duplicateIds: [...new Set(duplicateIds)],
        overflow: document.documentElement.scrollWidth > document.documentElement.clientWidth + 1,
        smallTargets,
        brokenImages: [...document.images]
          .filter((image) => image.complete && image.naturalWidth === 0)
          .map((image) => image.currentSrc || image.src),
      };
    });

    expect(audit).toEqual({ duplicateIds: [], overflow: false, smallTargets: [], brokenImages: [] });
  });
}

test('mobile menu is keyboard-operable and closes with Escape', async ({ page }) => {
  await page.setViewportSize({ width: 390, height: 900 });
  await page.goto('/', { waitUntil: 'load' });
  const menu = page.locator('details.mobile-nav');
  const summary = menu.locator('summary');

  await summary.focus();
  await page.keyboard.press('Enter');
  await expect(menu).toHaveAttribute('open', '');
  await expect(menu.getByRole('link', { name: 'Solutions' })).toBeVisible();
  await page.keyboard.press('Escape');
  await expect(menu).not.toHaveAttribute('open', '');
  await expect(summary).toBeFocused();
});

test('essential content and native navigation work without JavaScript', async ({ browser }) => {
  const context = await browser.newContext({
    javaScriptEnabled: false,
    viewport: { width: 390, height: 900 },
    locale: 'fr-FR',
  });
  const page = await context.newPage();
  await page.goto('/', { waitUntil: 'load' });
  await expect(page.getByRole('heading', {
    level: 1,
    name: 'Votre logique financière. Construite dans le produit.',
  })).toBeVisible();
  await expect(page.getByText('Des solutions distinctes. Une architecture cohérente.')).toBeVisible();
  const menu = page.locator('details.mobile-nav');
  await menu.locator('summary').click();
  await expect(menu).toHaveAttribute('open', '');
  await context.close();
});

test('reduced motion keeps all content visible', async ({ page }) => {
  await page.emulateMedia({ reducedMotion: 'reduce' });
  await page.goto('/', { waitUntil: 'load' });
  const hiddenReveal = await page.locator('[data-reveal]').evaluateAll((elements) => elements.filter((element) => {
    const style = getComputedStyle(element);
    return style.opacity === '0' || style.visibility === 'hidden';
  }).length);
  expect(hiddenReveal).toBe(0);
});

for (const route of representativeRoutes) {
  test(`Axe has no critical or serious findings on ${route}`, async ({ page }) => {
    await page.emulateMedia({ reducedMotion: 'reduce' });
    await page.goto(route, { waitUntil: 'load' });
    const results = await new AxeBuilder({ page })
      .withTags(['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa', 'wcag22aa'])
      .analyze();
    const blocking = results.violations.filter((violation) =>
      violation.impact === 'critical' || violation.impact === 'serious'
    );
    expect(blocking, JSON.stringify(blocking, null, 2)).toEqual([]);
  });
}

for (const width of [390, 768, 1440]) {
  test(`homepage reference capture at ${width}px`, async ({ page }) => {
    await page.setViewportSize({ width, height: 900 });
    await page.goto('/', { waitUntil: 'load' });
    await page.evaluate(() => document.fonts.ready);
    await expect(page).toHaveScreenshot(`home-${width}.png`, {
      animations: 'disabled',
      caret: 'hide',
      fullPage: false,
    });
  });
}
