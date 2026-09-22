import { expect, test, type Page } from './auth';

async function recordPurchase(page: Page, name: string, price: string) {
    await page.goto('/compras/nueva');
    await page.getByLabel('Ingrediente', { exact: true }).fill(name);
    await page.getByLabel('Presentación', { exact: true }).fill('Compra de ' + name);
    await page.getByLabel('Cantidad comprada', { exact: true }).fill('1');
    await page.getByLabel('Unidad', { exact: true }).selectOption('kg');
    await page.getByLabel('Total pagado (MXN)', { exact: true }).fill(price);
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

async function createProduct(page: Page, recipeName: string, price: string) {
    await page.goto('/productos');
    await page.getByRole('link', { name: new RegExp('^' + recipeName + '.*Configurar producto de esta receta') }).click();
    await page.getByRole('button', { name: 'Guardar configuración', exact: true }).click();
    await expect(page.getByText('Producto configurado.', { exact: true })).toBeVisible();
    await page.getByLabel('Precio manual (MXN)', { exact: true }).fill(price);
    await page.getByRole('button', { name: 'Usar precio manual', exact: true }).click();
    await page.getByRole('button', { name: 'Confirmar precio', exact: true }).click();
    await expect(page.getByText('Precio guardado.', { exact: true })).toBeVisible();
}

async function deactivateProduct(page: Page, recipeName: string) {
    await page.goto('/productos');
    await page.getByRole('link', { name: new RegExp('^' + recipeName) }).click();
    await page.getByRole('link', { name: 'Editar', exact: true }).click();
    await page.getByRole('checkbox', { name: 'Producto activo' }).uncheck();
    await page.getByRole('button', { name: 'Guardar configuración', exact: true }).click();
    await expect(page.getByText('Configuración guardada como nueva versión.', { exact: true })).toBeVisible();
    await expect(page.getByText('Inactivo', { exact: true })).toBeVisible();
}

for (const width of [320, 390]) {
    test('protects a dirty order draft from visible Back at ' + width + 'px', async ({ page }) => {
        await page.setViewportSize({ width, height: 844 });
        await page.goto('/pedidos/nuevo');
        await page.getByLabel('Cliente', { exact: true }).fill('Borrador protegido');

        await page.getByRole('button', { name: '← Volver', exact: true }).click();
        await expect(page.getByRole('dialog')).toContainText('¿Descartar este pedido?');
        await page.getByRole('button', { name: 'Seguir editando', exact: true }).click();
        await expect(page.getByLabel('Cliente', { exact: true })).toHaveValue('Borrador protegido');
        await expect(page.getByRole('dialog')).not.toBeVisible();

        await page.getByRole('button', { name: '← Volver', exact: true }).click();
        await page.getByRole('button', { name: 'Descartar cambios', exact: true }).click();
        await expect(page).toHaveURL(/\/pedidos$/);
        await expect(page.getByRole('link', { name: 'Tomar pedido', exact: true })).toBeVisible();
        expect(await page.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth)).toBe(true);
    });
}

for (const width of [320, 390]) {
    test('captures a multi-product order at ' + width + 'px', async ({ page }) => {
        await page.setViewportSize({ width, height: 844 });
        const suffix = width + '-' + Date.now();
        const firstIngredient = 'Harina pedido ' + suffix;
        const secondIngredient = 'Cocoa pedido ' + suffix;
        const firstRecipe = 'Galleta chocolate ' + suffix;
        const secondRecipe = 'Galleta vainilla ' + suffix;
        await recordPurchase(page, firstIngredient, '42.00');
        await recordPurchase(page, secondIngredient, '30.00');
        await createRecipe(page, firstRecipe, firstIngredient);
        await createRecipe(page, secondRecipe, secondIngredient);
        await createProduct(page, firstRecipe, '25.00');
        await createProduct(page, secondRecipe, '40.00');

        await page.goto('/pedidos');
        await page.getByRole('link', { name: 'Tomar pedido', exact: true }).click();
        await page.getByLabel('Cliente', { exact: true }).fill('Cliente pedido ' + suffix);
        await page.getByLabel('Producto para agregar', { exact: true }).selectOption({ label: firstRecipe + ' · $25.00 MXN' });
        await page.getByRole('button', { name: 'Agregar producto', exact: true }).click();
        await page.getByLabel('Producto para agregar', { exact: true }).selectOption({ label: secondRecipe + ' · $40.00 MXN' });
        await page.getByRole('button', { name: 'Agregar producto', exact: true }).click();
        await expect(page.getByLabel('Precio acordado ' + firstRecipe, { exact: true })).toHaveValue('25');
        await expect(page.getByLabel('Precio acordado ' + secondRecipe, { exact: true })).toHaveValue('40');
        await page.getByLabel('Cantidad ' + firstRecipe, { exact: true }).fill('2');
        await page.getByLabel('Cantidad ' + secondRecipe, { exact: true }).fill('1');
        await page.getByLabel('Precio acordado ' + firstRecipe, { exact: true }).fill('30.00');
        await page.getByLabel('Fecha de entrega').fill('2026-09-21');
        await page.getByLabel('Hora', { exact: true }).fill('16:30');
        await page.getByLabel('Anticipo recibido', { exact: true }).fill('20.00');
        await expect(page.getByRole('region', { name: 'Resumen del pedido', exact: true })).toContainText('$100.00 MXN');
        await expect(page.getByRole('region', { name: 'Resumen del pedido', exact: true })).toContainText('$20.00 MXN');
        await expect(page.getByRole('region', { name: 'Resumen del pedido', exact: true })).toContainText('$80.00 MXN');
        await page.getByRole('button', { name: 'Guardar pedido', exact: true }).click();
        await expect(page.getByText('Pedido registrado.', { exact: true })).toBeVisible();
        await expect(page.getByText('Cliente pedido ' + suffix, { exact: true })).toBeVisible();
        await expect(page.getByRole('heading', { name: 'Productos', exact: true })).toBeVisible();
        await expect(page.getByText('Precio acordado').first()).toBeVisible();
        await expect(page.getByText('$30.00 MXN').first()).toBeVisible();
        await expect(page.getByText('$40.00 MXN').first()).toBeVisible();
        await expect(page.getByRole('region', { name: 'Resumen del pedido', exact: true })).toContainText('$100.00 MXN');
        await expect(page.getByText('Pago parcial', { exact: true })).toBeVisible();
        await expect(page.getByText('$80.00 MXN').first()).toBeVisible();
        expect(await page.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth)).toBe(true);
    });
}

test('keeps a deactivated selected product visible and recoverable after catalog revalidation', async ({ page }) => {
    await page.setViewportSize({ width: 390, height: 844 });
    const suffix = 'stale-' + Date.now();
    const firstIngredient = 'Harina stale ' + suffix;
    const secondIngredient = 'Cocoa stale ' + suffix;
    const firstRecipe = 'Galleta activa ' + suffix;
    const secondRecipe = 'Galleta desactivada ' + suffix;
    await recordPurchase(page, firstIngredient, '42.00');
    await recordPurchase(page, secondIngredient, '30.00');
    await createRecipe(page, firstRecipe, firstIngredient);
    await createRecipe(page, secondRecipe, secondIngredient);
    await createProduct(page, firstRecipe, '25.00');
    await createProduct(page, secondRecipe, '40.00');

    await page.goto('/pedidos/nuevo');
    await page.getByLabel('Cliente', { exact: true }).fill('Cliente stale ' + suffix);
    await page.getByLabel('Producto para agregar', { exact: true }).selectOption({ label: firstRecipe + ' · $25.00 MXN' });
    await page.getByRole('button', { name: 'Agregar producto', exact: true }).click();
    await page.getByLabel('Producto para agregar', { exact: true }).selectOption({ label: secondRecipe + ' · $40.00 MXN' });
    await page.getByRole('button', { name: 'Agregar producto', exact: true }).click();
    await page.getByLabel('Cantidad ' + firstRecipe, { exact: true }).fill('2');
    await page.getByLabel('Fecha de entrega').fill('2026-09-21');
    await page.getByLabel('Hora', { exact: true }).fill('16:30');
    await expect(page.getByRole('region', { name: 'Resumen del pedido', exact: true })).toContainText('$90.00 MXN');

    const tabB = await page.context().newPage();
    try {
        await deactivateProduct(tabB, secondRecipe);
        await page.getByRole('button', { name: 'Actualizar catálogo', exact: true }).click();

        await expect(page.getByRole('heading', { name: firstRecipe, exact: true })).toBeVisible();
        await expect(page.getByRole('heading', { name: secondRecipe, exact: true })).toBeVisible();
        await expect(page.getByText('Este producto ya no está disponible para nuevos pedidos. Quita esta línea para continuar.', { exact: true })).toBeVisible();
        await expect(page.getByRole('region', { name: 'Resumen del pedido', exact: true })).toContainText('$90.00 MXN');
        await expect(page.getByRole('button', { name: 'Guardar pedido', exact: true })).toBeDisabled();
        await expect(page.getByRole('article').filter({ has: page.getByRole('heading', { name: secondRecipe, exact: true }) }).getByRole('button', { name: 'Quitar', exact: true })).toBeVisible();

        await page.getByRole('article').filter({ has: page.getByRole('heading', { name: secondRecipe, exact: true }) }).getByRole('button', { name: 'Quitar', exact: true }).click();
        await expect(page.getByRole('heading', { name: secondRecipe, exact: true })).not.toBeVisible();
        await expect(page.getByRole('region', { name: 'Resumen del pedido', exact: true })).toContainText('$50.00 MXN');
        await expect(page.getByRole('button', { name: 'Guardar pedido', exact: true })).toBeEnabled();
        await page.getByRole('button', { name: 'Guardar pedido', exact: true }).click();
        await expect(page.getByText('Pedido registrado.', { exact: true })).toBeVisible();
        await expect(page.getByText(firstRecipe, { exact: true }).first()).toBeVisible();
        await expect(page.getByText(secondRecipe, { exact: true })).not.toBeVisible();
    } finally {
        await tabB.close();
    }
});
