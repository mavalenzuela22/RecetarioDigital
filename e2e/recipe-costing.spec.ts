import { expect, test, type Page } from './auth';

test.setTimeout(60_000);

async function recordPurchase(page: Page, name: string, price: string, unit: string) {
    await page.goto('/compras/nueva');
    await page.getByLabel('Ingrediente', { exact: true }).fill(name);
    await page.getByLabel('Presentación', { exact: true }).fill(`Compra de ${name}`);
    await page.getByLabel('Cantidad comprada', { exact: true }).fill('1');
    await page.getByLabel('Unidad', { exact: true }).selectOption(unit);
    await page.getByLabel('Total pagado (MXN)', { exact: true }).fill(price);
    await page.getByLabel('Fecha de compra').fill('2026-09-18');
    await page.getByRole('button', { name: 'Guardar compra', exact: true }).click();
    await expect(page.getByText('Compra registrada.', { exact: true })).toBeVisible();
}

for (const width of [320, 390]) {
    test(`create, cost and version a recipe at ${width}px`, async ({ page }) => {
        await page.setViewportSize({ width, height: 844 });
        const suffix = `${width}-${Date.now()}`;
        const flour = `Harina receta ${suffix}`;
        const milk = `Leche receta ${suffix}`;
        await recordPurchase(page, flour, '42.00', 'kg');
        await recordPurchase(page, milk, '20.00', 'l');

        await page.goto('/recetas');
        await page.getByRole('link', { name: 'Nueva receta', exact: true }).click();
        await expect(page.getByText('El costo del borrador todavía no está calculado.', { exact: true })).toBeVisible();
        await page.getByLabel('Nombre de la receta', { exact: true }).fill(`Roles ${suffix}`);
        await page.getByRole('button', { name: '+ Agregar ingrediente', exact: true }).click();
        await page.locator('[id="ingredients.0.ingredient_id"]').selectOption({ label: flour });
        await page.locator('[id="ingredients.0.quantity"]').fill('320');
        await page.locator('[id="ingredients.0.unit"]').selectOption('g');
        await page.getByRole('button', { name: '+ Agregar ingrediente', exact: true }).click();
        await page.locator('[id="ingredients.1.ingredient_id"]').selectOption({ label: milk });
        await page.locator('[id="ingredients.1.quantity"]').fill('250');
        await page.locator('[id="ingredients.1.unit"]').selectOption('ml');
        await page.getByLabel('Rendimiento esperado', { exact: true }).fill('12');
        await page.getByRole('button', { name: 'Guardar receta', exact: true }).click();

        await expect(page.getByText('Receta guardada como nueva versión.', { exact: true })).toBeVisible();
        await expect(page.getByRole('heading', { name: `Roles ${suffix}` })).toBeVisible();
        await expect(page.getByRole('region', { name: 'Costo vigente', exact: true })).toContainText('$18.44 MXN');
        await expect(page.getByText('Costo por pieza:')).toContainText('$1.54 MXN');

        await page.getByRole('link', { name: 'Editar receta', exact: true }).click();
        await expect(page.getByRole('heading', { name: 'Receta · versión 1' })).toBeVisible();
        const editUrl = page.url();
        await expect(page.getByRole('region', { name: 'Costo de la versión guardada 1', exact: true })).toContainText('$18.44 MXN');
        await page.getByLabel('Rendimiento esperado', { exact: true }).fill('10');
        await expect(page.getByText('no representa tus cambios sin guardar', { exact: false })).toBeVisible();
        await expect(page.getByText('El costo del borrador se calculará y confirmará al guardar.', { exact: false })).toBeVisible();

        const pageA = await page.context().newPage();
        await pageA.setViewportSize({ width, height: 844 });
        await pageA.goto(editUrl);
        const pageB = await page.context().newPage();
        await pageB.setViewportSize({ width, height: 844 });
        await pageB.goto(editUrl);
        await expect(pageA.getByRole('heading', { name: 'Receta · versión 1' })).toBeVisible();
        await expect(pageB.getByRole('heading', { name: 'Receta · versión 1' })).toBeVisible();

        await pageA.getByLabel('Rendimiento esperado', { exact: true }).fill('9');
        await pageA.getByRole('button', { name: 'Guardar receta', exact: true }).click();
        await expect(pageA.getByText('Versión 2 · esta versión es inmutable.')).toBeVisible();

        await pageB.getByLabel('Rendimiento esperado', { exact: true }).fill('8');
        await pageB.getByRole('button', { name: 'Guardar receta', exact: true }).click();
        await expect(pageB).not.toHaveURL(/Something is broken|409/);
        await expect(pageB.getByRole('region', { name: 'Conflicto de versión', exact: true })).toBeVisible();
        await expect(pageB.getByRole('region', { name: 'Conflicto de versión', exact: true })).toContainText('versión 2');
        await expect(pageB.getByText('Tus cambios todavía están aquí', { exact: false })).toBeVisible();
        await expect(pageB.getByLabel('Rendimiento esperado', { exact: true })).toHaveValue('8');
        await pageB.getByRole('button', { name: 'Conservar mis cambios y usar la versión 2 como base', exact: true }).click();
        await expect(pageB.getByLabel('Rendimiento esperado', { exact: true })).toHaveValue('8');
        await expect(pageB.getByRole('region', { name: 'Conflicto de versión', exact: true })).not.toBeVisible();
        await expect(pageB.getByText('La receta cambió mientras la editabas.', { exact: false })).not.toBeVisible();
        await pageB.getByRole('button', { name: 'Guardar receta', exact: true }).click();
        await expect(pageB.getByText('Receta guardada como nueva versión.', { exact: true })).toBeVisible();
        await expect(pageB.getByText('Versión 3 · esta versión es inmutable.')).toBeVisible();
        await expect(pageB.getByText('por tanda de 8 piezas', { exact: true })).toBeVisible();
        expect(await pageA.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth)).toBe(true);
        expect(await pageB.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth)).toBe(true);
        await pageA.close();
        await pageB.close();
        expect(await page.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth)).toBe(true);
    });
}
