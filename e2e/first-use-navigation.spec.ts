import { expect, test, type Page } from './auth';

async function goBackAndExpect(page: Page, heading: string, url: RegExp) {
    await page.getByRole('link', { name: '← Volver', exact: true }).click();
    await expect(page).toHaveURL(url);
    await expect(page.getByRole('heading', { name: heading, exact: true })).toBeVisible();
}

for (const width of [320, 390]) {
    test(`completes first-use catalog setup from visible navigation at ${width}px`, async ({ page }) => {
        await page.setViewportSize({ width, height: 844 });
        const suffix = `${width}-${Date.now()}`;
        const ingredient = `Harina primer uso ${suffix}`;
        const recipe = `Pan primer uso ${suffix}`;
        const customer = `Cliente primer uso ${suffix}`;

        await expect(page).toHaveURL(/\/$/);
        await expect(page.getByRole('link', { name: 'Registrar compra', exact: true })).toBeVisible();
        await expect(page.getByRole('link', { name: 'Registrar una compra', exact: true })).toBeVisible();
        await expect(page.getByRole('link', { name: 'Recetario', exact: true })).toBeVisible();
        await expect(page.getByRole('link', { name: 'Productos', exact: true })).toBeVisible();

        await page.getByRole('link', { name: 'Registrar una compra', exact: true }).click();
        await page.getByLabel('Ingrediente', { exact: true }).fill(ingredient);
        await page.getByLabel('Presentación', { exact: true }).fill('Bolsa de 1 kg');
        await page.getByLabel('Cantidad comprada', { exact: true }).fill('1');
        await page.getByLabel('Unidad', { exact: true }).selectOption('kg');
        await page.getByLabel('Total pagado (MXN)', { exact: true }).fill('42.00');
        await page.getByLabel('Fecha de compra').fill('2026-09-18');
        await page.getByRole('button', { name: 'Guardar compra', exact: true }).click();
        await expect(page.getByText('Compra registrada.', { exact: true })).toBeVisible();

        await goBackAndExpect(page, 'Ingredientes', /\/ingredientes$/);
        await goBackAndExpect(page, 'Hoy en tu cocina', /\/$/);
        await page.getByRole('link', { name: 'Recetario', exact: true }).click();
        await expect(page).toHaveURL(/\/recetas$/);
        await expect(page.getByRole('heading', { name: 'Recetario', exact: true })).toBeVisible();
        await page.getByRole('link', { name: 'Nueva receta', exact: true }).click();
        await page.getByLabel('Nombre de la receta', { exact: true }).fill(recipe);
        await page.getByRole('button', { name: '+ Agregar ingrediente', exact: true }).click();
        await page.locator('[id="ingredients.0.ingredient_id"]').selectOption({ label: ingredient });
        await page.locator('[id="ingredients.0.quantity"]').fill('500');
        await page.locator('[id="ingredients.0.unit"]').selectOption('g');
        await page.getByLabel('Rendimiento esperado', { exact: true }).fill('10');
        await page.getByRole('button', { name: 'Guardar receta', exact: true }).click();
        await expect(page.getByText('Receta guardada como nueva versión.', { exact: true })).toBeVisible();
        await expect(page.getByRole('heading', { name: recipe, exact: true })).toBeVisible();

        await goBackAndExpect(page, 'Recetario', /\/recetas$/);
        await goBackAndExpect(page, 'Hoy en tu cocina', /\/$/);
        await page.getByRole('link', { name: 'Productos', exact: true }).click();
        await expect(page).toHaveURL(/\/productos$/);
        await expect(page.getByRole('heading', { name: 'Productos', exact: true })).toBeVisible();
        await page.getByRole('link', { name: new RegExp(`^${recipe}.*Configurar producto de esta receta`) }).click();
        await page.getByRole('button', { name: 'Guardar configuración', exact: true }).click();
        await expect(page.getByText('Producto configurado.', { exact: true })).toBeVisible();
        await page.getByLabel('Precio manual (MXN)', { exact: true }).fill('25.00');
        await page.getByRole('button', { name: 'Usar precio manual', exact: true }).click();
        await expect(page.getByRole('heading', { name: '¿Confirmar nuevo precio?', exact: true })).toBeVisible();
        await page.getByRole('button', { name: 'Confirmar precio', exact: true }).click();
        await expect(page.getByText('Precio guardado.', { exact: true })).toBeVisible();
        await expect(page.getByRole('region', { name: 'Resumen de precio', exact: true })).toContainText('$25.00 MXN');

        await goBackAndExpect(page, 'Productos', /\/productos$/);
        await goBackAndExpect(page, 'Recetario', /\/recetas$/);
        await goBackAndExpect(page, 'Hoy en tu cocina', /\/$/);
        await page.getByRole('link', { name: 'Tomar pedido', exact: true }).click();
        await expect(page.getByRole('heading', { name: 'Tomar pedido', exact: true })).toBeVisible();
        await page.getByLabel('Cliente', { exact: true }).fill(customer);
        await page.getByLabel('Producto para agregar', { exact: true }).selectOption({ label: `${recipe} · $25.00 MXN` });
        await page.getByRole('button', { name: 'Agregar producto', exact: true }).click();
        await page.getByLabel('Fecha de entrega').fill('2026-09-21');
        await page.getByLabel('Hora', { exact: true }).fill('16:30');
        await page.getByRole('button', { name: 'Guardar pedido', exact: true }).click();
        await expect(page.getByText('Pedido registrado.', { exact: true })).toBeVisible();
        await expect(page.getByText(customer, { exact: true })).toBeVisible();
        await expect(page.getByRole('heading', { name: 'Productos', exact: true })).toBeVisible();
        await expect(page.getByText('$25.00 MXN', { exact: true }).first()).toBeVisible();
        expect(await page.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth)).toBe(true);
    });
}
