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

test.describe('Today and production', () => {
    test.describe.configure({ mode: 'serial' });

    for (const width of [320, 390]) {
        test('runs the preparation and ready flow at ' + width + 'px', async ({ page }) => {
            await page.setViewportSize({ width, height: 844 });
            const suffix = width + '-' + Date.now();
            const ingredient = 'Harina hoy ' + suffix;
            const recipe = 'Galleta hoy ' + suffix;
            const customer = 'Cliente hoy ' + suffix;
            await recordPurchase(page, ingredient);
            await createRecipe(page, recipe, ingredient);
            await createProduct(page, recipe);

            await page.goto('/pedidos/nuevo');
            const businessDate = await page.getByLabel('Fecha de entrega', { exact: true }).inputValue();
            await page.getByLabel('Cliente', { exact: true }).fill(customer);
            await page.getByLabel('Producto para agregar', { exact: true }).selectOption({ label: recipe + ' · $25.00 MXN' });
            await page.getByRole('button', { name: 'Agregar producto', exact: true }).click();
            await page.getByLabel('Cantidad ' + recipe, { exact: true }).fill('2');
            await page.getByLabel('Fecha de entrega').fill(businessDate);
            await page.getByLabel('Hora', { exact: true }).fill('16:30');
            await page.getByLabel('Anticipo recibido', { exact: true }).fill('0');
            await page.getByRole('button', { name: 'Guardar pedido', exact: true }).click();
            await expect(page.getByText('Pedido registrado.', { exact: true })).toBeVisible();

            await page.goto('/');
            await expect(page.getByRole('heading', { name: 'Hoy en tu cocina', exact: true })).toBeVisible();
            await expect(page.getByText(recipe, { exact: true })).toBeVisible();
            await expect(page.getByText(customer, { exact: true }).first()).toBeVisible();
            await expect(page.getByText('Por cobrar hoy', { exact: true })).toBeVisible();
            await page.getByRole('link', { name: 'Ver producción', exact: true }).first().click();

            await expect(page.getByRole('heading', { name: 'Producción', exact: true })).toBeVisible();
            await expect(page.getByText(recipe, { exact: true })).toBeVisible();
            await expect(page.getByText(customer, { exact: true })).toBeVisible();
            await page.getByRole('button', { name: 'Iniciar preparación', exact: true }).click();
            await expect(page.getByText('Pedidos en preparación.', { exact: true })).toBeVisible();
            const orderRow = page.locator('li').filter({ hasText: customer });
            await expect(orderRow.getByText('En preparación', { exact: true })).toBeVisible();
            await orderRow.getByRole('button', { name: 'Marcar listo', exact: true }).click();
            await expect(page.getByText('Pedido listo para entregar.', { exact: true })).toBeVisible();
            await expect(orderRow.getByText('Listo para entregar', { exact: true })).toBeVisible();

            await page.goto('/');
            await expect(page.getByText('0 piezas', { exact: true })).toBeVisible();
            const customerCollectionRow = page.locator('section[aria-labelledby="collections-title"]')
                .getByRole('link')
                .filter({ hasText: customer });
            await expect(customerCollectionRow).toBeVisible();
            await expect(customerCollectionRow.getByText('$50.00 MXN', { exact: true })).toBeVisible();
            expect(await page.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth)).toBe(true);
        });
    }
});
