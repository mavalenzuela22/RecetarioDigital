import { expect, test as base, type Page } from '@playwright/test';

export const E2E_EMAIL = 'e2e@example.test';
export const E2E_PASSWORD = 'e2e-password-123';

export async function login(page: Page, destination = '/') {
    await page.goto(destination);
    if (new URL(page.url()).pathname !== '/login') return;

    await page.getByLabel('Correo', { exact: true }).fill(E2E_EMAIL);
    await page.getByLabel('Contraseña de recuperación', { exact: true }).fill(E2E_PASSWORD);
    await page.getByRole('button', { name: 'Entrar con contraseña', exact: true }).click();
    await expect(page).not.toHaveURL(/\/login$/);
}

export const test = base.extend({
    page: async ({ page }, use) => {
        await login(page);
        await use(page);
    },
});

export { expect };
