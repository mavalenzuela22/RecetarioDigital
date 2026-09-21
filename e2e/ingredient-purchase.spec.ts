import { expect, test } from './auth';

for (const width of [320, 390]) {
    test(`purchase, validation, retry and backdated history at ${width}px`, async ({ page }) => {
        await page.setViewportSize({ width, height: 844 });
        const name = `Harina ${width} ${Date.now()}`;
        await page.goto('/');
        await page.getByRole('link', { name: 'Registrar compra', exact: true }).click();
        await expect(page.getByRole('heading', { name: 'Registrar compra' })).toBeVisible();
        await page.getByLabel('Ingrediente', { exact: true }).fill(name);
        await page.getByLabel('Presentación', { exact: true }).fill('Bolsa de 1 kg');
        await page.getByLabel('Cantidad comprada', { exact: true }).fill('1');
        await page.getByLabel('Unidad', { exact: true }).selectOption('kg');
        await page.getByLabel('Fecha de compra').fill('2026-09-18');
        await page.getByRole('button', { name: /Agregar tienda o nota/ }).click();
        await page.getByLabel('Tienda o proveedor', { exact: true }).fill('Mercado del barrio');
        await page.getByLabel('Nota', { exact: true }).fill('Para el pan del viernes');
        await page.getByLabel('Total pagado (MXN)', { exact: true }).fill('0');
        await page.getByRole('button', { name: 'Guardar compra', exact: true }).click();
        await expect(page.getByText('Escribe un total mayor que $0.')).toBeVisible();
        await expect(page.getByLabel('Total pagado (MXN)', { exact: true })).toBeFocused();
        await expect(page.getByLabel('Ingrediente', { exact: true })).toHaveValue(name);
        await expect(page.getByLabel('Presentación', { exact: true })).toHaveValue('Bolsa de 1 kg');
        await expect(page.getByLabel('Cantidad comprada', { exact: true })).toHaveValue('1');
        await expect(page.getByLabel('Nota', { exact: true })).toHaveValue('Para el pan del viernes');
        await page.getByRole('button', { name: '← Volver' }).click();
        await expect(page.getByRole('dialog')).toBeVisible();
        await expect(page.getByRole('button', { name: 'Seguir editando' })).toBeFocused();
        await page.getByRole('button', { name: 'Seguir editando' }).click();
        await page.getByLabel('Total pagado (MXN)', { exact: true }).fill('42,00');
        // Hold the real request long enough to prove the UI blocks a second tap.
        let release!: () => void;
        const gate = new Promise<void>((resolve) => { release = resolve; });
        await page.route('**/compras', async (route) => { await gate; await route.continue(); });
        const post = page.waitForRequest((request) => request.method() === 'POST' && new URL(request.url()).pathname === '/compras');
        await page.getByRole('button', { name: 'Guardar compra', exact: true }).click();
        const request = await post;
        await expect(page.getByRole('button', { name: 'Guardando compra…' })).toBeDisabled();
        release();
        await expect(page.getByText('Compra registrada.', { exact: true })).toBeVisible();
        await expect(page.getByRole('region', { name: 'Costo vigente', exact: true })).toContainText('$0.042 MXN');
        await expect(page.locator('.purchase-card')).toHaveCount(1);
        await expect(page.locator('.purchase-card')).toContainText('1,000 g');
        await expect(page.locator('.purchase-card')).toContainText('Mercado del barrio');
        await expect(page.locator('.purchase-card')).toContainText('Para el pan del viernes');
        // Replay the original HTTP submission with the same UUID and session/CSRF token.
        const headers = request.headers();
        const replay = await page.request.post('/compras', {
            form: request.postDataJSON(),
            headers: { 'X-XSRF-TOKEN': headers['x-xsrf-token'], Accept: 'text/html' },
        });
        expect(replay.ok()).toBeTruthy();
        await page.reload();
        await expect(page.locator('.purchase-card')).toHaveCount(1);
        await page.getByRole('link', { name: 'Registrar otra compra' }).click();
        await expect(page.getByLabel('Ingrediente', { exact: true })).toHaveValue(name);
        await page.getByLabel('Presentación', { exact: true }).fill('Compra anterior');
        await page.getByLabel('Cantidad comprada', { exact: true }).fill('1000');
        await page.getByLabel('Unidad', { exact: true }).selectOption('l');
        await page.getByLabel('Total pagado (MXN)', { exact: true }).fill('30.00');
        await page.getByLabel('Fecha de compra').fill('2026-08-01');
        await page.getByRole('button', { name: 'Guardar compra', exact: true }).click();
        await expect(page.getByText(/Elige una unidad compatible/)).toBeVisible();
        await page.getByLabel('Unidad', { exact: true }).selectOption('g');
        await page.getByRole('button', { name: 'Guardar compra', exact: true }).click();
        await expect(page.getByText('Compra registrada.', { exact: true })).toBeVisible();
        await expect(page.locator('.purchase-card')).toHaveCount(2);
        await expect(page.locator('.purchase-card').first()).toContainText('18 sep 2026');
        await expect(page.locator('.purchase-card').last()).toContainText('01 ago 2026');
        await expect(page.getByRole('region', { name: 'Costo vigente', exact: true })).toContainText('$0.042 MXN');
        await page.reload();
        await expect(page.locator('.purchase-card')).toHaveCount(2);
        await expect(page.locator('.purchase-card').last()).toContainText('$0.03 MXN');
        await expect(page.locator('body')).toHaveCSS('background-color', 'rgb(255, 248, 237)');
        expect(await page.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth)).toBe(true);
        await page.getByRole('link', { name: '← Volver' }).click();
        await page.getByLabel('Buscar ingrediente').fill(name);
        await expect(page.locator('.ingredient-row')).toHaveCount(1);
        await expect(page.locator('.ingredient-row')).toContainText('$0.042 MXN');
    });
}
