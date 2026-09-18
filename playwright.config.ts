import { defineConfig, devices } from '@playwright/test';

export default defineConfig({
    testDir: './e2e',
    fullyParallel: true,
    forbidOnly: !!process.env.CI,
    retries: process.env.CI ? 2 : 0,
    reporter: 'html',
    use: {
        baseURL: process.env.PLAYWRIGHT_BASE_URL ?? 'http://127.0.0.1:8000',
        trace: 'on-first-retry',
    },
    webServer: process.env.PLAYWRIGHT_BASE_URL
        ? undefined
        : {
              command: 'docker compose run --rm --service-ports app php artisan serve --host=0.0.0.0 --port=8000',
              url: 'http://127.0.0.1:8000/up',
              reuseExistingServer: !process.env.CI,
              timeout: 120_000,
          },
    projects: [
        {
            name: 'mobile',
            use: { ...devices['iPhone 13'] },
        },
    ],
});
