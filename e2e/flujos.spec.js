import { test, expect } from '@playwright/test';
import fs from 'node:fs';
import { credentials, pick, solidPng } from './helpers.js';

// Los flujos comparten datos (reserva -> alquiler -> factura -> pago), por eso van en serie.
test.describe.configure({ mode: 'serial' });

const state = { reservationUrl: '', rentalUrl: '', rentalCode: '' };

test('reservar un vehículo', async ({ page }) => {
    await page.goto('/reservations/create');
    await pick(page, 'customer_id', 'Rodríguez');
    const sedan = await page.locator('#vehicle_category_id option', { hasText: 'Sedán' }).getAttribute('value');
    await page.locator('#vehicle_category_id').selectOption(sedan);
    await pick(page, 'vehicle_id', 'A123456');
    if (!(await page.locator('#daily_rate').inputValue())) {
        await page.locator('#daily_rate').fill('2600');
    }
    await page.getByRole('button', { name: 'Crear reserva' }).click();

    await expect(page).toHaveURL(/\/reservations\/\d+$/);
    await expect(page.getByRole('link', { name: 'Convertir en alquiler' })).toBeVisible();
    state.reservationUrl = page.url();
});

test('convertir la reserva en alquiler y generar factura', async ({ page }) => {
    await page.goto(state.reservationUrl);
    await page.getByRole('link', { name: 'Convertir en alquiler' }).click();
    await expect(page).toHaveURL(/\/rentals\/create\?reservation=/);
    await page.getByRole('button', { name: 'Abrir alquiler y generar factura' }).click();

    await expect(page).toHaveURL(/\/rentals\/\d+$/);
    state.rentalUrl = page.url();
    state.rentalCode = (await page.locator('h1, h2').first().innerText()).trim();
});

test('registrar una inspección de entrega', async ({ page }) => {
    await page.goto('/inspections/create');
    await pick(page, 'rental_id', 'A123456');
    await page.locator('#mileage').fill('18250');
    await page.locator('#damages').fill('Sin daños visibles (prueba E2E).');
    await page.getByRole('button', { name: 'Guardar inspección' }).click();

    await expect(page).toHaveURL(/\/inspections\/\d+$/);
});

test('registrar un pago manual desde la factura', async ({ page }) => {
    await page.goto('/invoices');
    await page.locator('table a[href*="/invoices/"]').first().click();
    await expect(page).toHaveURL(/\/invoices\/\d+$/);

    await page.locator('#amount').fill('500');
    await page.locator('#reference').fill('E2E-REF-1');
    await page.getByRole('button', { name: 'Registrar pago' }).click();

    await expect(page).toHaveURL(/\/invoices\/\d+$/);
    await expect(page.getByText('E2E-REF-1')).toBeVisible();
});

test('aplicar un pago desde el módulo Pagos', async ({ page }) => {
    await page.goto('/payments');
    await page.getByRole('button', { name: /registrar|nuevo pago|cobro/i }).first().click();
    await pick(page, 'invoice_id', 'F');
    await page.locator('#amount').fill('100');
    await page.locator('#reference').fill('E2E-REF-2');
    await page.getByRole('button', { name: 'Aplicar pago' }).click();

    await expect(page.getByText('E2E-REF-2')).toBeVisible();
});

test('descargar la factura interna en PDF', async ({ page }) => {
    await page.goto('/invoices');
    await page.locator('table a[href*="/invoices/"]').first().click();
    const href = await page.getByRole('link', { name: 'Descargar PDF' }).getAttribute('href');
    const response = await page.request.get(href);

    expect(response.status()).toBe(200);
    expect(response.headers()['content-type']).toContain('pdf');
    expect((await response.body()).subarray(0, 4).toString()).toBe('%PDF');
});

test('consultar reportes con un rango de fechas válido', async ({ page }) => {
    const today = new Date().toISOString().slice(0, 10);
    await page.goto(`/reports?from=${today.slice(0, 8)}01&to=${today}`);

    await expect(page.getByRole('heading', { level: 1 }).first()).toBeVisible();
    await expect(page.locator('#from')).toHaveValue(`${today.slice(0, 8)}01`);
    await expect(page.locator('body')).not.toContainText(/server error|exception/i);
});

test('actualizar y quitar la foto de un vehículo', async ({ page }) => {
    await page.goto('/vehicles');
    const href = await page.locator('table a[href*="/vehicles/"]').first().getAttribute('href');
    const id = href.match(/\/vehicles\/(\d+)/)[1];
    await page.goto(`/vehicles/${id}/edit`);
    await expect(page).toHaveURL(/\/vehicles\/\d+\/edit$/);

    await page.locator('#photo').setInputFiles({ name: 'foto-e2e.png', mimeType: 'image/png', buffer: solidPng() });
    await page.getByRole('button', { name: /guardar/i }).click();
    await expect(page).toHaveURL(/\/vehicles\/\d+$/);
    await expect(page.locator('img[src*="vehicles"], img[alt*="Foto"]').first()).toBeVisible();
});
