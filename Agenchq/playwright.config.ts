import { defineConfig } from '@playwright/test';

const baseURL = process.env.UI_BASE_URL || 'https://blueinc.agenchq.com';

export default defineConfig({
  testDir: './tests/ui',
  timeout: 60_000,
  expect: {
    timeout: 15_000,
  },
  fullyParallel: false,
  retries: process.env.CI ? 1 : 0,
  reporter: [['list'], ['html', { open: 'never' }]],
  use: {
    baseURL,
    trace: 'retain-on-failure',
    screenshot: 'only-on-failure',
    video: 'retain-on-failure',
  },
  projects: [
    {
      name: 'chromium',
      use: { viewport: { width: 1440, height: 900 } },
    },
  ],
});

