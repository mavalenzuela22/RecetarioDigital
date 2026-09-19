import { expect, test } from '@playwright/test';

test('home shell is usable on a phone-sized viewport', async ({ page }) => {
    await page.goto('/');

    await expect(page).toHaveTitle(/Inicio.*EmprendimientoOS/);
    await expect(page.getByRole('heading', { name: /Más claridad para hacer crecer/ })).toBeVisible();
    await expect(page.getByRole('link', { name: /Conoce el punto de partida/ })).toBeVisible();
    await expect(page.locator('body')).toHaveCSS('overflow-x', 'visible');
});
