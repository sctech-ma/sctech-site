import { defineConfig } from '@playwright/test';

export default defineConfig({
  testDir: './tests/Browser',
  outputDir: './storage/cache/playwright',
  fullyParallel: false,
  workers: 1,
  retries: 0,
  reporter: [['line']],
  use: {
    baseURL: process.env.PLAYWRIGHT_BASE_URL || 'http://127.0.0.1:8090',
    channel: process.env.PLAYWRIGHT_CHANNEL || 'chrome',
    viewport: { width: 1440, height: 900 },
    colorScheme: 'light',
    locale: 'fr-FR',
    screenshot: 'only-on-failure',
    trace: 'retain-on-failure',
    video: 'off',
  },
});
