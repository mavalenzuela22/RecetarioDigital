import { expect, test } from '@playwright/test';

for (const width of [320, 390]) {
    test('authenticates and protects business data at ' + width + 'px', async ({ page }) => {
        await page.setViewportSize({ width, height: 844 });
        await page.goto('/recetas');
        await expect(page).toHaveURL(/\/login$/);
        await expect(page.getByRole('heading', { name: 'Iniciar sesión', exact: true })).toBeVisible();
        await expect(page.getByLabel('Correo', { exact: true })).toBeVisible();
        await expect(page.getByLabel('Contraseña', { exact: true })).toBeVisible();
        expect(await page.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth)).toBe(true);

        await page.getByLabel('Correo', { exact: true }).fill('wrong@example.test');
        await page.getByLabel('Contraseña', { exact: true }).fill('wrong-password-123');
        await page.getByRole('button', { name: 'Entrar', exact: true }).click();
        await expect(page.getByText('Los datos de acceso no son correctos.', { exact: true })).toBeVisible();

        await page.getByLabel('Correo', { exact: true }).fill('e2e@example.test');
        await page.getByLabel('Contraseña', { exact: true }).fill('e2e-password-123');
        await page.getByRole('button', { name: 'Entrar', exact: true }).click();
        await expect(page).toHaveURL(/\/recetas$/);
        await expect(page.getByRole('heading', { name: 'Recetario', exact: true })).toBeVisible();

        await page.getByRole('button', { name: 'Cerrar sesión', exact: true }).click();
        await expect(page).toHaveURL(/\/login$/);
        await page.goto('/productos');
        await expect(page).toHaveURL(/\/login$/);
    });
}
