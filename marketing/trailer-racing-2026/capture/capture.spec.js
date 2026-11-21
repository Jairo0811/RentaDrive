import { test, expect } from '@playwright/test';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { credentials, pick } from '../../../e2e/helpers.js';

const here = path.dirname(fileURLToPath(import.meta.url));
const photos = (n) => path.join(here, `../assets/demo-vehicles/vehiculo-demo-${n}.jpg`);
const out = (name) => path.join(here, '../captures', name);

test.describe.configure({ mode: 'serial' });

const DESKTOP = { width: 1920, height: 1080 };
const MOBILE = { width: 540, height: 960 };

async function login(page) {
    const { email, password } = credentials();
    await page.goto('/login');
    await page.locator('#email').fill(email);
    await page.locator('input[name=password]').fill(password);
    await page.getByRole('button', { name: /iniciar sesión|entrar|acceder/i }).click();
    await expect(page).toHaveURL(/dashboard/);
}

async function settle(page, ms = 1500) {
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(ms);
}

test('preparar datos ficticios', async ({ browser }) => {
    const context = await browser.newContext({ viewport: DESKTOP });
    const page = await context.newPage();
    await login(page);

    // Fotos de demostración (arte propio de la marca) en los tres vehículos sembrados.
    await page.goto('/vehicles?view=table');
    const hrefs = await page.locator('table a[href*="/vehicles/"]').evaluateAll((links) => [...new Set(links.map((a) => a.getAttribute('href').match(/\/vehicles\/(\d+)/)?.[1]).filter(Boolean))]);
    for (const [index, id] of hrefs.slice(0, 3).entries()) {
        await page.goto(`/vehicles/${id}/edit`);
        await page.locator('#photo').setInputFiles(photos(index + 1));
        await page.getByRole('button', { name: /guardar/i }).click();
        await expect(page).toHaveURL(/\/vehicles\/\d+$/);
    }

    // Clientes ficticios adicionales (documentos y correos de ejemplo).
    const customers = [
        ['Carlos', 'Méndez', '001-0000002-2', 'carlos.mendez@example.test', '809-555-0102', 'Santiago'],
        ['Ana', 'Peña', '001-0000003-3', 'ana.pena@example.test', '809-555-0103', 'Punta Cana'],
    ];
    for (const [first, last, doc, email, phone, city] of customers) {
        await page.goto('/customers/create');
        await page.locator('#document_number').fill(doc);
        await page.locator('#first_name').fill(first);
        await page.locator('#last_name').fill(last);
        await page.locator('#phone').fill(phone);
        await page.locator('#email').fill(email);
        await page.locator('#city').fill(city);
        await page.locator('#license_number').fill(`LIC-DEMO-${doc.slice(-3)}`);
        const expiry = new Date(Date.now() + 3 * 365 * 864e5).toISOString().slice(0, 10);
        await page.locator('#license_expiry').fill(expiry);
        await page.getByRole('button', { name: /registrar cliente|guardar/i }).click();
        await expect(page).toHaveURL(/\/customers/);
    }

    // Tres reservas; la primera se convierte en alquiler con inspección y pago.
    const reservations = [
        ['Rodríguez', 'Sedán', 'A123456'],
        ['Méndez', 'SUV', 'G234567'],
        ['Peña', 'Económico', 'A345678'],
    ];
    let firstUrl = '';
    for (const [index, [customer, category, plate]] of reservations.entries()) {
        await page.goto('/reservations/create');
        const day = (n) => new Date(Date.now() + n * 864e5).toISOString().slice(0, 10);
        await page.locator('#start_at').fill(`${day(1 + index)}T09:00`);
        await page.locator('#end_at').fill(`${day(3 + index)}T09:00`);
        await pick(page, 'customer_id', customer);
        const value = await page.locator('#vehicle_category_id option', { hasText: category }).first().getAttribute('value');
        await page.locator('#vehicle_category_id').selectOption(value);
        await pick(page, 'vehicle_id', plate);
        if (!(await page.locator('#daily_rate').inputValue())) await page.locator('#daily_rate').fill('2600');
        await page.getByRole('button', { name: 'Crear reserva' }).click();
        await page.waitForLoadState('networkidle');
        if (!/\/reservations\/\d+$/.test(page.url())) {
            const errors = (await page.evaluate(() => document.body.innerText)).replace(/\s+/g, ' ').slice(0, 1400);
            throw new Error(`Reserva ${customer}/${plate} no creada: ${errors}`);
        }
        firstUrl ||= page.url();
    }

    await page.goto(firstUrl);
    await page.getByRole('link', { name: 'Convertir en alquiler' }).click();
    await page.getByRole('button', { name: 'Abrir alquiler y generar factura' }).click();
    await expect(page).toHaveURL(/\/rentals\/\d+$/);

    await page.goto('/inspections/create');
    await pick(page, 'rental_id', 'A123456');
    await page.locator('#mileage').fill('18250');
    await page.locator('#damages').fill('Sin daños visibles.');
    await page.getByRole('button', { name: 'Guardar inspección' }).click();
    await expect(page).toHaveURL(/\/inspections\/\d+$/);

    await page.goto('/invoices');
    await page.locator('table a[href*="/invoices/"]').first().click();
    await page.locator('#amount').fill('2600');
    await page.locator('#reference').fill('DEMO-0001');
    await page.getByRole('button', { name: 'Registrar pago' }).click();
    await context.close();
});

async function shoot(page, name, url, opts = {}) {
    await page.goto(url);
    await settle(page, opts.wait);
    if (opts.before) await opts.before(page);
    await page.screenshot({ path: out(name), fullPage: false });
}

test('capturas 16:9 (1920x1080)', async ({ browser }) => {
    const context = await browser.newContext({ viewport: DESKTOP, deviceScaleFactor: 1 });
    const page = await context.newPage();
    await login(page);

    await shoot(page, 'h-dashboard.png', '/dashboard', { wait: 2500 });
    await shoot(page, 'h-flota-tabla.png', '/vehicles?view=table');
    await shoot(page, 'h-flota-tarjetas.png', '/vehicles?view=cards');
    const detail = await page.locator('a[href*="/vehicles/"]').evaluateAll((links) => links.map((a) => a.getAttribute('href')).find((h) => /\/vehicles\/\d+$/.test(h)));
    if (detail) await shoot(page, 'h-vehiculo-detalle.png', detail);
    await shoot(page, 'h-clientes.png', '/customers');
    await shoot(page, 'h-reservas.png', '/reservations');
    await shoot(page, 'h-reserva-formulario.png', '/reservations/create', {
        before: async (p) => {
            await pick(p, 'customer_id', 'Peña');
            await p.locator('#vehicle_id').click();
            await p.locator('#vehicle_id').fill('A');
            await p.locator('#vehicle_id-list [role=option]').first().waitFor();
            await p.waitForTimeout(400);
        },
    });
    await shoot(page, 'h-alquileres.png', '/rentals');
    await shoot(page, 'h-inspecciones.png', '/inspections');
    await shoot(page, 'h-facturas.png', '/invoices');
    await shoot(page, 'h-pagos.png', '/payments');
    await shoot(page, 'h-reportes.png', '/reports', { wait: 2500 });
    await context.close();
});

test('capturas 9:16 (540x960 a 2x = 1080x1920)', async ({ browser }) => {
    const context = await browser.newContext({ viewport: MOBILE, deviceScaleFactor: 2, isMobile: true, hasTouch: true });
    const page = await context.newPage();
    await login(page);

    await shoot(page, 'v-dashboard.png', '/dashboard', { wait: 2500 });
    await shoot(page, 'v-flota-tarjetas.png', '/vehicles?view=cards');
    await shoot(page, 'v-reservas.png', '/reservations');
    await shoot(page, 'v-reserva-formulario.png', '/reservations/create');
    await shoot(page, 'v-facturas.png', '/invoices');
    await shoot(page, 'v-reportes.png', '/reports', { wait: 2500 });
    await context.close();
});
