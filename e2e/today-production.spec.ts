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

function shiftDate(date: string, days: number) {
    const shifted = new Date(`${date}T12:00:00`);
    shifted.setDate(shifted.getDate() + days);
    return shifted.toISOString().slice(0, 10);
}

async function createOrder(page: Page, recipeName: string, customer: string, deliveryDate: string) {
    await page.goto('/pedidos/nuevo');
    await page.getByLabel('Cliente', { exact: true }).fill(customer);
    await page.getByLabel('Producto para agregar', { exact: true }).selectOption({ label: recipeName + ' · $25.00 MXN' });
    await page.getByRole('button', { name: 'Agregar producto', exact: true }).click();
    await page.getByLabel('Cantidad ' + recipeName, { exact: true }).fill('1');
    await page.getByLabel('Fecha de entrega', { exact: true }).fill(deliveryDate);
    await page.getByLabel('Hora', { exact: true }).fill('16:30');
    await page.getByLabel('Anticipo recibido', { exact: true }).fill('0');
    await page.getByRole('button', { name: 'Guardar pedido', exact: true }).click();
    await expect(page.getByText('Pedido registrado.', { exact: true })).toBeVisible();
}

async function createMixedOrder(page: Page, firstRecipe: string, secondRecipe: string, customer: string, deliveryDate: string) {
    await page.goto('/pedidos/nuevo');
    await page.getByLabel('Cliente', { exact: true }).fill(customer);
    await page.getByLabel('Producto para agregar', { exact: true }).selectOption({ label: firstRecipe + ' · $25.00 MXN' });
    await page.getByRole('button', { name: 'Agregar producto', exact: true }).click();
    await page.getByLabel('Producto para agregar', { exact: true }).selectOption({ label: secondRecipe + ' · $25.00 MXN' });
    await page.getByRole('button', { name: 'Agregar producto', exact: true }).click();
    await page.getByLabel('Cantidad ' + firstRecipe, { exact: true }).fill('2');
    await page.getByLabel('Cantidad ' + secondRecipe, { exact: true }).fill('3');
    await page.getByLabel('Fecha de entrega').fill(deliveryDate);
    await page.getByLabel('Hora', { exact: true }).fill('16:30');
    await page.getByLabel('Anticipo recibido', { exact: true }).fill('0');
    await page.getByRole('button', { name: 'Guardar pedido', exact: true }).click();
    await expect(page.getByText('Pedido registrado.', { exact: true })).toBeVisible();
}

test.describe('Today and production', () => {
    test.describe.configure({ mode: 'serial' });

    for (const width of [320, 390]) {
        test('runs the preparation and ready flow at ' + width + 'px', async ({ page }) => {
            await page.setViewportSize({ width, height: 844 });
            const suffix = width + '-' + Date.now();
            const firstIngredient = 'Harina hoy ' + suffix;
            const secondIngredient = 'Canela hoy ' + suffix;
            const firstRecipe = 'Galleta hoy ' + suffix;
            const secondRecipe = 'Pan hoy ' + suffix;
            const customer = 'Cliente hoy ' + suffix;
            await recordPurchase(page, firstIngredient);
            await recordPurchase(page, secondIngredient);
            await createRecipe(page, firstRecipe, firstIngredient);
            await createRecipe(page, secondRecipe, secondIngredient);
            await createProduct(page, firstRecipe);
            await createProduct(page, secondRecipe);

            await page.goto('/pedidos/nuevo');
            const businessDate = await page.getByLabel('Fecha de entrega', { exact: true }).inputValue();
            await createMixedOrder(page, firstRecipe, secondRecipe, customer, businessDate);

            await page.goto('/');
            await expect(page.getByRole('heading', { name: 'Hoy en tu cocina', exact: true })).toBeVisible();
            await expect(page.getByText(firstRecipe, { exact: true })).toBeVisible();
            await expect(page.getByText(customer, { exact: true }).first()).toBeVisible();
            await expect(page.getByText('Por cobrar hoy', { exact: true })).toBeVisible();
            await page.getByRole('link', { name: 'Ver producción', exact: true }).first().click();

            await expect(page.getByRole('heading', { name: 'Producción', exact: true })).toBeVisible();
            await expect(page.getByText(firstRecipe, { exact: true })).toBeVisible();
            await expect(page.getByText(secondRecipe, { exact: true })).toBeVisible();
            const orderRows = page.locator('li').filter({ hasText: customer });
            await expect(orderRows).toHaveCount(2);
            await page.getByRole('button', { name: 'Iniciar preparación', exact: true }).click();
            await expect(page.getByText('Pedidos en preparación.', { exact: true })).toBeVisible();
            await expect(orderRows).toHaveCount(2);
            await expect(orderRows.nth(0).getByText('En preparación', { exact: true })).toBeVisible();
            await expect(page.getByRole('button', { name: 'Marcar todo el pedido como listo', exact: true })).toHaveCount(2);
            await page.getByRole('button', { name: 'Marcar todo el pedido como listo', exact: true }).first().click();
            await expect(page.getByText('Pedido completo listo para entregar.', { exact: true })).toBeVisible();
            await expect(orderRows.nth(0).getByText('Listo para entregar', { exact: true })).toBeVisible();
            await expect(orderRows.nth(1).getByText('Listo para entregar', { exact: true })).toBeVisible();
            await expect(page.getByRole('button', { name: 'Marcar todo el pedido como listo', exact: true })).toHaveCount(0);

            await page.goto('/');
            await expect(page.getByText('0 piezas', { exact: true })).toBeVisible();
            const customerCollectionRow = page.locator('section[aria-labelledby="collections-title"]')
                .getByRole('link')
                .filter({ hasText: customer });
            await expect(customerCollectionRow).toBeVisible();
            await expect(customerCollectionRow.getByText('$125.00 MXN', { exact: true })).toBeVisible();

            const customerDeliveryRow = page.locator('section[aria-labelledby="deliveries-title"]')
                .getByRole('link')
                .filter({ hasText: customer });
            await customerDeliveryRow.click();
            await page.getByRole('button', { name: 'Marcar como entregado', exact: true }).click();
            await page.getByRole('button', { name: 'Sí, ya entregué', exact: true }).click();
            await expect(page.getByText('Entrega registrada.', { exact: true })).toBeVisible();
            await page.getByRole('button', { name: 'Registrar cobro', exact: true }).click();
            await page.getByLabel('Importe recibido', { exact: true }).fill('125.00');
            await page.getByRole('dialog').getByRole('button', { name: 'Registrar cobro', exact: true }).click();
            await expect(page.getByText('Cobro registrado.', { exact: true })).toBeVisible();

            await page.goto('/');
            await expect(page.getByRole('heading', { name: 'Todo lo de hoy está completo.', exact: true })).toBeVisible();
            await expect(page.getByText('No tienes pedidos para hoy.', { exact: true })).not.toBeVisible();
            const dayMoney = page.locator('section[aria-label="Resumen de dinero de hoy"]');
            if (width === 320) {
                await expect(dayMoney).toContainText('$125.00 MXN');
                await expect(dayMoney).toContainText('$21.00 MXN');
                await expect(dayMoney).toContainText('$104.00 MXN');
            } else {
                await expect(dayMoney).toContainText('$250.00 MXN');
                await expect(dayMoney).toContainText('$42.00 MXN');
                await expect(dayMoney).toContainText('$208.00 MXN');
            }
            await expect(dayMoney.locator('dl > div').filter({ hasText: 'Saldo pendiente' }).locator('dd')).toHaveText('$0.00 MXN');
            expect(await page.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth)).toBe(true);
        });
    }

    test('starts preparation for the displayed range after changing dates', async ({ page }) => {
        await page.setViewportSize({ width: 390, height: 844 });
        const suffix = 'range-' + Date.now();
        const ingredient = 'Harina rango ' + suffix;
        const recipe = 'Galleta rango ' + suffix;
        const customerA = 'Cliente A ' + suffix;
        const customerB = 'Cliente B ' + suffix;
        await recordPurchase(page, ingredient);
        await createRecipe(page, recipe, ingredient);
        await createProduct(page, recipe);

        await page.goto('/pedidos/nuevo');
        const dayA = await page.getByLabel('Fecha de entrega', { exact: true }).inputValue();
        const dayB = shiftDate(dayA, 1);
        await createOrder(page, recipe, customerA, dayA);
        await createOrder(page, recipe, customerB, dayB);

        await page.goto(`/produccion?from=${dayA}&to=${dayA}`);
        await expect(page.locator('li').filter({ hasText: customerA })).toBeVisible();
        await expect(page.locator('li').filter({ hasText: customerB })).not.toBeVisible();

        await page.getByLabel('Desde', { exact: true }).fill(dayB);
        await page.getByLabel('Hasta', { exact: true }).fill(dayB);
        await page.getByRole('button', { name: 'Actualizar fechas', exact: true }).click();
        await expect(page.locator('li').filter({ hasText: customerB })).toBeVisible();
        await expect(page.locator('li').filter({ hasText: customerA })).not.toBeVisible();

        await page.getByRole('button', { name: 'Iniciar preparación', exact: true }).click();
        await expect(page.getByText('Pedidos en preparación.', { exact: true })).toBeVisible();
        await expect(page.locator('li').filter({ hasText: customerB }).getByText('En preparación', { exact: true })).toBeVisible();

        await page.goto(`/produccion?from=${dayA}&to=${dayA}`);
        await expect(page.locator('li').filter({ hasText: customerA }).getByText('Confirmado', { exact: true })).toBeVisible();
    });

    for (const width of [320, 390]) {
        test('shows the last accepted range after a reversed submission at ' + width + 'px', async ({ page }) => {
            await page.setViewportSize({ width, height: 844 });
            const suffix = 'invalid-range-' + width + '-' + Date.now();
            const ingredient = 'Harina rango inválido ' + suffix;
            const recipe = 'Galleta rango inválido ' + suffix;
            const customer = 'Cliente rango inválido ' + suffix;
            await recordPurchase(page, ingredient);
            await createRecipe(page, recipe, ingredient);
            await createProduct(page, recipe);

            await page.goto('/pedidos/nuevo');
            const acceptedDate = await page.getByLabel('Fecha de entrega', { exact: true }).inputValue();
            const rejectedStart = shiftDate(acceptedDate, 1);
            await createOrder(page, recipe, customer, acceptedDate);
            await page.goto(`/produccion?from=${acceptedDate}&to=${acceptedDate}`);
            await expect(page.locator('li').filter({ hasText: customer })).toBeVisible();

            await page.getByLabel('Desde', { exact: true }).fill(rejectedStart);
            await page.getByLabel('Hasta', { exact: true }).fill(acceptedDate);
            await page.getByRole('button', { name: 'Actualizar fechas', exact: true }).click();

            const alert = page.locator('#production-range-error');
            await expect(alert).toBeVisible();
            await expect(alert).toContainText('La fecha final debe ser igual o posterior a la inicial.');
            await expect(alert).toContainText('último rango válido:');
            await expect(alert).toContainText(/\d{1,2} [a-z]{3} \d{4}/);
            await expect(page.getByLabel('Desde', { exact: true })).toHaveValue(rejectedStart);
            await expect(page.getByLabel('Hasta', { exact: true })).toHaveValue(acceptedDate);
            await expect(page.locator('li').filter({ hasText: customer })).toBeVisible();
            expect(await page.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth)).toBe(true);
        });
    }
});
