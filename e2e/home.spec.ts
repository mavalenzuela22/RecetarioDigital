import { expect, test } from './auth';

test('Today is the operational home on a phone-sized viewport', async ({ page }) => {
    await page.goto('/');

    await expect(page).toHaveTitle(/Hoy.*EmprendimientoOS/);
    await expect(page.getByRole('heading', { name: 'Hoy en tu cocina', exact: true })).toBeVisible();
    await expect(page.getByText('Necesitas preparar', { exact: true }).or(page.getByText('No tienes pedidos para hoy.', { exact: true }))).toBeVisible();
    await expect(page.getByRole('link', { name: 'Ver producción', exact: true })).toBeVisible();
    await expect(page.getByRole('link', { name: 'Registrar compra', exact: true })).toBeVisible();
    expect(await page.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth)).toBe(true);
});
