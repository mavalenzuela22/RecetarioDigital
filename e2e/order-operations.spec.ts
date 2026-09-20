import { expect, test, type Page } from './auth';

async function recordPurchase(page: Page, name: string) {
    await page.goto('/compras/nueva');
    await page.getByLabel('Ingrediente', { exact: true }).fill(name);
    await page.getByLabel('Presentación', { exact: true }).fill('Compra de ' + name);
    await page.getByLabel('Cantidad comprada', { exact: true }).fill('1');
    await page.getByLabel('Unidad', { exact: true }).selectOption('kg');
    await page.getByLabel('Total pagado (MXN)', { exact: true }).fill('42.00');
    await page.getByLabel('Fecha de compra').fill('2026-09-18');
    await page.getByRole('button', { name: 'Guardar compra', exact: true }).click();
    await expect(page.getByText('Compra registrada.', { exact: true })).toBeVisible();
}

async function createRecipe(page: Page, name: string, ingredient: string) {
    await page.goto('/recetas');
    await page.getByRole('link', { name: 'Nueva receta', exact: true }).click();
    await page.getByLabel('Nombre de la receta', { exact: true }).fill(name);
    await page.getByRole('button', { name: '+ Agregar ingrediente', exact: true }).click();
    await page.locator('[id="ingredients.0.ingredient_id"]').selectOption({ label: ingredient });
    await page.locator('[id="ingredients.0.quantity"]').fill('1');
    await page.locator('[id="ingredients.0.unit"]').selectOption('kg');
    await page.getByLabel('Rendimiento esperado', { exact: true }).fill('10');
    await page.getByRole('button', { name: 'Guardar receta', exact: true }).click();
    await expect(page.getByText('Receta guardada como nueva versión.', { exact: true })).toBeVisible();
}

async function createProduct(page: Page, recipeName: string) {
    await page.goto('/productos');
    await page.getByRole('link', { name: new RegExp('^' + recipeName + '.*Configurar producto de esta receta') }).click();
    await page.getByRole('button', { name: 'Guardar configuración', exact: true }).click();
    await expect(page.getByText('Producto configurado.', { exact: true })).toBeVisible();
    await page.getByLabel('Precio manual (MXN)', { exact: true }).fill('25.00');
    await page.getByRole('button', { name: 'Usar precio manual', exact: true }).click();
    await page.getByRole('button', { name: 'Confirmar precio', exact: true }).click();
    await expect(page.getByText('Precio guardado.', { exact: true })).toBeVisible();
}

async function captureOrder(page: Page, customer: string, recipe: string, advance: string) {
    await page.goto('/pedidos');
    await page.getByRole('link', { name: 'Tomar pedido', exact: true }).click();
    await page.getByLabel('Cliente', { exact: true }).fill(customer);
    await page.getByLabel('Producto para agregar', { exact: true }).selectOption({ label: recipe + ' · $25.00 MXN' });
    await page.getByRole('button', { name: 'Agregar producto', exact: true }).click();
    await page.getByLabel('Cantidad ' + recipe, { exact: true }).fill('2');
    await page.getByLabel('Fecha de entrega').fill('2026-09-24');
    await page.getByLabel('Hora', { exact: true }).fill('16:30');
    await page.getByLabel('Anticipo recibido', { exact: true }).fill(advance);
    await page.getByRole('button', { name: 'Guardar pedido', exact: true }).click();
    await expect(page.getByText('Pedido registrado.', { exact: true })).toBeVisible();
}

for (const width of [320, 390]) {
    test('records collection, delivery and cancellation at ' + width + 'px', async ({ page }) => {
        await page.setViewportSize({ width, height: 844 });
        const suffix = width + '-' + Date.now();
        const ingredient = 'Harina operaciones ' + suffix;
        const recipe = 'Galleta operaciones ' + suffix;
        await recordPurchase(page, ingredient);
        await createRecipe(page, recipe, ingredient);
        await createProduct(page, recipe);

        await captureOrder(page, 'Cliente cobro ' + suffix, recipe, '10.00');
        await expect(page).toHaveTitle(/^Cobro y entrega(?: · EmprendimientoOS)?$/);
        await expect(page.getByText('Pago parcial', { exact: true })).toBeVisible();
        await page.getByRole('button', { name: 'Registrar cobro', exact: true }).click();
        await page.getByLabel('Importe recibido', { exact: true }).fill('15.00');
        await page.getByLabel('Fecha del cobro', { exact: true }).fill('2026-09-23');
        await page.getByRole('button', { name: 'Registrar cobro', exact: true }).last().click();
        await expect(page.getByText('Cobro registrado.', { exact: true })).toBeVisible();
        await expect(page.getByRole('region', { name: 'Resumen del pedido', exact: true })).toContainText('$25.00 MXN');
        await expect(page.getByText('Pago parcial', { exact: true })).toBeVisible();
        await expect(page.getByText('Anticipo', { exact: true })).toBeVisible();
        await expect(page.getByText('Cobro', { exact: true })).toBeVisible();

        await page.getByRole('button', { name: 'Marcar como entregado', exact: true }).click();
        await expect(page.getByRole('dialog')).toContainText('¿Entregaste este pedido a Cliente cobro ' + suffix + '?');
        await page.getByRole('button', { name: 'Sí, ya entregué', exact: true }).click();
        await expect(page.getByText('Entrega registrada.', { exact: true })).toBeVisible();
        await expect(page.getByText('Entregado', { exact: true })).toBeVisible();
        await expect(page.getByText('Pago parcial', { exact: true })).toBeVisible();

        await captureOrder(page, 'Cliente cancelado ' + suffix, recipe, '10.00');
        await page.getByRole('button', { name: 'Cancelar pedido', exact: true }).click();
        await expect(page.getByRole('dialog')).toContainText('Cancelar el pedido no registra una devolución.');
        await page.getByRole('button', { name: 'Sí, cancelar pedido', exact: true }).click();
        await expect(page.getByText('Pedido cancelado.', { exact: true })).toBeVisible();
        await expect(page.getByText('Cancelado', { exact: true })).toBeVisible();
        await expect(page.getByText('Pago parcial', { exact: true })).toBeVisible();
        await expect(page.getByRole('region', { name: 'Resumen del pedido', exact: true })).toContainText('$10.00 MXN');
        await expect(page.getByRole('region', { name: 'Resumen del pedido', exact: true })).toContainText('$40.00 MXN');
        await expect(page.getByText('Cancelar el pedido no registra una devolución.')).not.toBeVisible();
        expect(await page.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth)).toBe(true);
    });
}
