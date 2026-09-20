import { expect, test } from './auth';

for (const width of [320, 390]) {
    test('compares configured product economics on mobile at ' + width + 'px', async ({ page }) => {
        await page.setViewportSize({ width, height: 844 });
        const suffix = width + '-' + Date.now();
        const ingredient = 'Harina historial ' + suffix;
        const recipeName = 'Galleta historial ' + suffix;

        await page.goto('/compras/nueva');
        await page.getByLabel('Ingrediente', { exact: true }).fill(ingredient);
        await page.getByLabel('Presentación', { exact: true }).fill('Compra de ' + ingredient);
        await page.getByLabel('Cantidad comprada', { exact: true }).fill('1');
        await page.getByLabel('Unidad', { exact: true }).selectOption('kg');
        await page.getByLabel('Total pagado (MXN)', { exact: true }).fill('42.00');
        await page.getByLabel('Fecha de compra').fill('2026-09-18');
        await page.getByRole('button', { name: 'Guardar compra', exact: true }).click();
        await expect(page.getByText('Compra registrada.', { exact: true })).toBeVisible();

        await page.goto('/recetas');
        await page.getByRole('link', { name: 'Nueva receta', exact: true }).click();
        await page.getByLabel('Nombre de la receta', { exact: true }).fill(recipeName);
        await page.getByRole('button', { name: '+ Agregar ingrediente', exact: true }).click();
        await page.locator('[id="ingredients.0.ingredient_id"]').selectOption({ label: ingredient });
        await page.locator('[id="ingredients.0.quantity"]').fill('1');
        await page.locator('[id="ingredients.0.unit"]').selectOption('kg');
        await page.getByLabel('Rendimiento esperado', { exact: true }).fill('10');
        await page.getByRole('button', { name: 'Guardar receta', exact: true }).click();
        await expect(page.getByText('Receta guardada como nueva versión.', { exact: true })).toBeVisible();

        await page.goto('/productos');
        await page.getByRole('link', { name: new RegExp('^' + recipeName + '.*Configurar producto de esta receta') }).click();
        await page.getByRole('button', { name: 'Guardar configuración', exact: true }).click();
        await expect(page.getByText('Producto configurado.', { exact: true })).toBeVisible();
        await page.getByLabel('Precio manual (MXN)', { exact: true }).fill('20.00');
        await page.getByRole('button', { name: 'Usar precio manual', exact: true }).click();
        await page.getByRole('button', { name: 'Confirmar precio', exact: true }).click();
        await expect(page.getByText('Precio guardado.', { exact: true })).toBeVisible();

        await page.getByRole('link', { name: 'Antes y ahora', exact: true }).click();
        await expect(page.getByRole('heading', { name: 'Antes y ahora', exact: true })).toBeVisible();
        await expect(page.getByText('Compara la economía configurada del producto. No recalcula pedidos históricos.', { exact: true })).toBeVisible();

        const start = page.getByLabel('Fecha inicial', { exact: true });
        const end = page.getByLabel('Fecha final', { exact: true });
        const currentBusinessDate = await end.inputValue();
        await start.fill(currentBusinessDate);
        await end.fill(currentBusinessDate);
        await page.getByRole('button', { name: 'Comparar fechas', exact: true }).click();
        await expect(page.getByRole('article', { name: 'Antes', exact: true })).toContainText('$4.200000 MXN');
        await expect(page.getByRole('article', { name: 'Ahora', exact: true })).toContainText('$4.200000 MXN');
        await expect(page.getByText('$20.00 MXN').first()).toBeVisible();
        await expect(page.getByText('$15.800000 MXN').first()).toBeVisible();
        await expect(page.getByText('79.0%', { exact: true }).first()).toBeVisible();
        await page.getByText('Ver de dónde salen estos datos', { exact: true }).click();
        await expect(page.getByText('Versión 1 · rendimiento 10 piezas').first()).toBeVisible();
        await expect(page.getByText('Harina historial', { exact: false }).first()).toBeVisible();

        await start.fill('2026-09-19');
        await end.fill('2026-09-18');
        await page.getByRole('button', { name: 'Comparar fechas', exact: true }).click();
        await expect(page.getByRole('alert')).toHaveText('La fecha final debe ser igual o posterior a la inicial.');
        await expect(start).toHaveValue('2026-09-19');
        await expect(end).toHaveValue('2026-09-18');

        await page.getByRole('link', { name: 'Explorar otro precio', exact: true }).click();
        await expect(page.getByRole('heading', { name: 'Costos y precio', exact: true })).toBeVisible();
        expect(await page.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth)).toBe(true);
    });
}
