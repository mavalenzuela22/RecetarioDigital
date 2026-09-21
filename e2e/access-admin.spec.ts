import { expect, test } from '@playwright/test';

test.describe('accesos mobile', () => {
    test('keeps the invitation entry surface calm at 320 and 390px without calling Google', async ({ page }) => {
        for (const width of [320, 390]) {
            await page.setViewportSize({ width, height: 844 });
            await page.goto('/invitaciones/not-a-real-token');
            await expect(page).toHaveURL(/\/login$/);
            await expect(page.getByText('Esta invitación ya no está disponible. Pide una nueva invitación.', { exact: true })).toBeVisible();
            await expect(page.getByRole('link', { name: 'Continuar con Google', exact: true })).toBeVisible();
            expect(await page.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth)).toBe(true);
        }
    });

    test('shows the small access administration surface for a configured admin', async ({ page }) => {
        test.skip(!process.env.E2E_ADMIN_EMAIL || !process.env.E2E_ADMIN_PASSWORD, 'Requires an explicit admin fixture; it never invokes Google.');
        await page.setViewportSize({ width: 390, height: 844 });
        await page.goto('/login');
        await page.getByLabel('Correo', { exact: true }).fill(process.env.E2E_ADMIN_EMAIL!);
        await page.getByLabel('Contraseña de recuperación', { exact: true }).fill(process.env.E2E_ADMIN_PASSWORD!);
        await page.getByRole('button', { name: 'Entrar con contraseña', exact: true }).click();
        await page.getByRole('link', { name: 'Accesos', exact: true }).click();
        await expect(page.getByRole('heading', { name: 'Accesos', exact: true })).toBeVisible();
        await expect(page.getByRole('heading', { name: 'Nueva invitación', exact: true })).toBeVisible();
        await expect(page.getByRole('heading', { name: 'Personas con acceso', exact: true })).toBeVisible();
        await expect(page.getByRole('heading', { name: 'Invitaciones', exact: true })).toBeVisible();
        await expect(page.getByRole('heading', { name: 'Contraseña de recuperación', exact: true })).toBeVisible();
        expect(await page.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth)).toBe(true);
    });
});
