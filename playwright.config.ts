import { defineConfig, devices } from '@playwright/test';

export default defineConfig({
    testDir: './e2e',
    fullyParallel: true,
    forbidOnly: !!process.env.CI,
    retries: process.env.CI ? 2 : 0,
    reporter: 'html',
    use: {
        baseURL: process.env.PLAYWRIGHT_BASE_URL ?? 'http://127.0.0.1:18080',
        trace: 'on-first-retry',
    },
    webServer: process.env.PLAYWRIGHT_BASE_URL
        ? undefined
        : {
              // /tmp is container-local, never the mounted developer database. Ignore cached DB config.
              command: `docker compose run --rm --name eo-tsk003-e2e -p 18080:18080 app sh -ec 'export APP_ENV=testing APP_KEY=base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA= DB_CONNECTION=sqlite DB_DATABASE=/tmp/eo-e2e.sqlite SESSION_DRIVER=file CACHE_STORE=array APP_CONFIG_CACHE=/tmp/eo-e2e-config.php DATABASE_URL= E2E_PASSWORD=e2e-password-123; touch /tmp/eo-e2e.sqlite && php -d variables_order=EGPCS artisan migrate:fresh --database=sqlite --force && php -d variables_order=EGPCS artisan app:user-provision e2e@example.test --name="E2E Test" --password-env=E2E_PASSWORD && cd public && exec php -d variables_order=EGPCS -S 0.0.0.0:18080 ../vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php'`,
              url: 'http://127.0.0.1:18080/up',
              reuseExistingServer: false,
              timeout: 120_000,
          },
    projects: [
        {
            name: 'mobile',
            use: { ...devices['iPhone 13'] },
        },
    ],
});
