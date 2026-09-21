import { expect, test, type Page } from './auth';

async function recordPurchase(page: Page, name: string, price: string, unit: string, normalizedCost: string) {
    await page.goto('/compras/nueva');
    await page.getByLabel('Ingrediente', { exact: true }).fill(name);
    await page.getByLabel('Presentación', { exact: true }).fill('Compra de ' + name);
    await page.getByLabel('Cantidad comprada', { exact: true }).fill('1');
    await page.getByLabel('Unidad', { exact: true }).selectOption(unit);
    await page.getByLabel('Total pagado (MXN)', { exact: true }).fill(price);
    await page.getByLabel('Fecha de compra').fill('2026-09-18');
    await page.getByRole('button', { name: 'Guardar compra', exact: true }).click();
    await expect(page.getByText('Compra registrada.', { exact: true })).toBeVisible();
    await expect(page.getByRole('region', { name: 'Costo vigente', exact: true })).toContainText(normalizedCost + ' MXN');
}

for (const width of [320, 390]) {
    test('configure product and save scenario/manual prices at ' + width + 'px', async ({ page }) => {
        await page.setViewportSize({ width, height: 844 });
        const suffix = width + '-' + Date.now();
        const flour = 'Harina producto ' + suffix;
        const milk = 'Leche producto ' + suffix;
        await recordPurchase(page, flour, '42.00', 'kg', '$0.042');
        await recordPurchase(page, milk, '20.00', 'l', '$0.02');

        await page.goto('/recetas');
        await page.getByRole('link', { name: 'Nueva receta', exact: true }).click();
        const recipeName = 'Roles producto ' + suffix;
        await page.getByLabel('Nombre de la receta', { exact: true }).fill(recipeName);
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

        await page.goto('/productos');
        await page.getByRole('link', { name: new RegExp('^' + recipeName + '.*Configurar producto de esta receta') }).click();
        await expect(page.getByRole('heading', { name: 'Configurar producto' })).toBeVisible();
        await page.getByRole('button', { name: '+ Agregar costo', exact: true }).click();
        await page.locator('[id="components.0.concept"]').fill('Empaque');
        await page.locator('[id="components.0.amount"]').fill('1.50');
        await page.locator('[id="components.0.allocation"]').selectOption('unit');
        await page.getByRole('button', { name: '+ Agregar costo', exact: true }).click();
        await page.locator('[id="components.1.concept"]').fill('Entrega');
        await page.locator('[id="components.1.amount"]').fill('60.00');
        await page.locator('[id="components.1.allocation"]').selectOption('order');
        await page.getByLabel('Cantidad de referencia del pedido', { exact: true }).fill('10');
        await page.getByRole('button', { name: 'Guardar configuración', exact: true }).click();

        await expect(page.getByText('Producto configurado.', { exact: true })).toBeVisible();
        await expect(page.getByRole('heading', { name: 'Costos y precio' })).toBeVisible();
        await expect(page.getByRole('region', { name: 'Costo por pieza', exact: true })).toContainText('$9.04 MXN');
        await page.getByRole('button', { name: '×2.5', exact: true }).click();
        await expect(page.getByRole('article', { name: 'Resultado del escenario', exact: true })).toContainText('$22.59 MXN');
        await expect(page.getByRole('article', { name: 'Resultado del escenario', exact: true })).toContainText('60.0%');
        await page.getByRole('button', { name: 'Usar este precio', exact: true }).click();
        await expect(page.getByRole('heading', { name: '¿Confirmar nuevo precio?' })).toBeVisible();
        await expect(page.getByText('Precio sugerido: $22.59', { exact: true })).toBeVisible();
        await page.getByRole('button', { name: 'Confirmar precio', exact: true }).click();
        await expect(page.getByText('Precio guardado.', { exact: true })).toBeVisible();
        await expect(page.getByRole('region', { name: 'Resumen de precio', exact: true })).toContainText('$22.59 MXN');

        for (const [input, expected] of [['5', '$5.00'], ['5.7', '$5.70'], ['5.70', '$5.70']] as const) {
            await page.getByLabel('Precio manual (MXN)', { exact: true }).fill(input);
            await page.getByRole('button', { name: 'Usar precio manual', exact: true }).click();
            await expect(page.getByText('Precio manual: ' + expected, { exact: true })).toBeVisible();
            await page.getByRole('button', { name: 'Confirmar precio', exact: true }).click();
            await expect(page.getByText('Precio guardado.', { exact: true })).toBeVisible();
            await expect(page.getByRole('region', { name: 'Resumen de precio', exact: true })).toContainText(expected + ' MXN');
            if (input === '5') await expect(page.getByRole('region', { name: 'Resumen de precio', exact: true })).toContainText('-$4.04 MXN');
        }
        await expect(page.getByText('Costo dividido entre la cantidad de referencia')).toBeHidden();
        await page.getByText('Ver desglose de costos', { exact: true }).click();
        await expect(page.getByText('Costo dividido entre la cantidad de referencia (10 piezas)')).toBeVisible();
        expect(await page.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth)).toBe(true);
    });
}
