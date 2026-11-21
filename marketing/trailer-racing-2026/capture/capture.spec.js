import { test, expect } from '@playwright/test';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { credentials, pick } from '../../../e2e/helpers.js';

const here = path.dirname(fileURLToPath(import.meta.url));
const photos = (file) => path.join(here, `../assets/demo-vehicles/${file}`);
const out = (name) => path.join(here, '../captures', name);

test.describe.configure({ mode: 'serial' });
const debug = [];

const isoDay = (n) => new Date(Date.now() + n * 864e5).toISOString().slice(0, 10);
// Rango que incluye el alquiler ficticio (que empieza mañana) para que Reportes muestre datos.
const rangeUrl = `/reports?from=${isoDay(0).slice(0, 8)}01&to=${isoDay(30)}`;

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

    // Marcas y modelos FICTICIOS: las fotos son vehículos genéricos generados (no son fotos de un modelo comercial),
    // por eso no se muestran nombres de fabricantes reales en las fichas.
    await page.goto('/fleet/catalogs');
    const rename = async (from, to, extra) => {
        await page.evaluate(([f, t]) => {
            const form = [...document.querySelectorAll('form')].find((x) => x.querySelector('input[name=_method][value=PUT]') && x.querySelector('input[name=name]')?.value === f);
            if (!form) throw new Error(`No se encontró «${f}» en catálogos`);
            form.querySelector('input[name=name]').value = t;
            form.requestSubmit();
        }, [from, to]);
        await page.waitForLoadState('networkidle');
        await page.goto('/fleet/catalogs');
    };
    await rename('Hyundai', 'Aero');
    await rename('Toyota', 'Orion');
    await rename('Accent', 'Pulse');
    await rename('RAV4', 'Terra');
    await rename('Corolla', 'Vía');

    // Foto distinta y coherente (carrocería y color de la ficha) para cada vehículo, localizada por placa.
    const byPlate = [
        ['A345678', 'sedan-compacto-gris.jpg'], // Económico · Gris
        ['G234567', 'suv-azul.jpg'], // SUV · Azul
        ['A123456', 'sedan-blanco.jpg'], // Sedán · Blanco
    ];
    for (const [plate, file] of byPlate) {
        await page.goto('/vehicles?view=table');
        const href = await page.locator('table tr', { hasText: plate }).locator('a[href*="/vehicles/"]').first().getAttribute('href');
        const id = href.match(/\/vehicles\/(\d+)/)[1];
        await page.goto(`/vehicles/${id}/edit`);
        await page.locator('#photo').setInputFiles(photos(file));
        await page.getByRole('button', { name: /guardar/i }).click();
        await expect(page).toHaveURL(/\/vehicles\/\d+$/);
    }

    // Clientes ficticios adicionales (documentos y correos de ejemplo).
    const customers = [
        ['Carlos', 'Méndez', 'DEMO000002', 'carlos.mendez@example.test', '809-555-0102', 'Santiago'],
        ['Ana', 'Peña', 'DEMO000003', 'ana.pena@example.test', '809-555-0103', 'Punta Cana'],
    ];
    for (const [first, last, doc, email, phone, city] of customers) {
        await page.goto('/customers/create');
        await page.locator('#document_type').selectOption('passport');
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
        await expect(page).toHaveURL(/\/customers\/\d+$/);
    }

    await page.goto('/customers');
    debug.push('clientes: ' + (await page.locator('main').innerText()).replace(/\s+/g, ' ').slice(0, 500));

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
        debug.push(`${customer}: cliente=${await page.locator('input[name=customer_id]').inputValue()} texto=${await page.locator('#customer_id').inputValue()}`);
        const value = await page.locator('#vehicle_category_id option', { hasText: category }).first().getAttribute('value');
        await page.locator('#vehicle_category_id').selectOption(value);
        await pick(page, 'vehicle_id', plate);
        if (!(await page.locator('#daily_rate').inputValue())) await page.locator('#daily_rate').fill('2600');
        const formValues = JSON.stringify(await page.evaluate(() => Object.fromEntries([...new FormData(document.querySelector('form[action*="/reservations"]')).entries()].filter(([k]) => k !== '_token'))));
        const posted = page.waitForResponse((r) => r.request().method() === 'POST' && r.url().includes('/reservations'), { timeout: 10_000 }).catch(() => null);
        await page.getByRole('button', { name: 'Crear reserva' }).click();
        const response = await posted;
        await page.waitForLoadState('networkidle');
        if (!/\/reservations\/\d+$/.test(page.url())) {
            const errors = (await page.evaluate(() => document.body.innerText)).replace(/\s+/g, ' ').slice(0, 1400);
            throw new Error(`Reserva ${customer}/${plate} no creada: DEBUG=${debug.join(' // ')} form=${formValues} POST=${response ? response.status() + ' -> ' + (response.headers().location ?? '') : 'sin petición'} | ${errors.slice(-300)} | url=${page.url()}`);
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


// Lleva los gráficos del dashboard a la parte superior de la vista (el scroll puede estar en un contenedor interno).
async function scrollToCharts(page) {
    await page.getByText('Rendimiento financiero').first().evaluate((el) => {
        el.scrollIntoView({ block: 'start' });
        let node = el.parentElement;
        while (node && !(node.scrollHeight > node.clientHeight + 50 && /(auto|scroll)/.test(getComputedStyle(node).overflowY))) node = node.parentElement;
        (node ?? document.scrollingElement).scrollBy(0, -110);
    });
    await page.waitForTimeout(1500);
}

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
    await shoot(page, 'h-reportes.png', rangeUrl, { wait: 2500 });
    await shoot(page, 'h-dashboard-graficos.png', '/dashboard', { wait: 2500, before: (p) => scrollToCharts(p) });
    await context.close();
});

test('capturas 9:16 (540x960 a 2x = 1080x1920)', async ({ browser }) => {
    const context = await browser.newContext({ viewport: MOBILE, deviceScaleFactor: 2, isMobile: true, hasTouch: true });
    const page = await context.newPage();
    await login(page);

    await shoot(page, 'v-dashboard.png', '/dashboard', { wait: 2500 });
    await shoot(page, 'v-flota-tarjetas.png', '/vehicles?view=cards');
    await shoot(page, 'v-flota-tabla.png', '/vehicles?view=table');
    const detailV = await page.locator('a[href*="/vehicles/"]').evaluateAll((links) => links.map((a) => a.getAttribute('href')).find((h) => /\/vehicles\/\d+$/.test(h)));
    if (detailV) await shoot(page, 'v-vehiculo-detalle.png', detailV);
    await shoot(page, 'v-reservas.png', '/reservations');
    await shoot(page, 'v-reserva-formulario.png', '/reservations/create');
    await shoot(page, 'v-facturas.png', '/invoices');
    await shoot(page, 'v-reportes.png', rangeUrl, { wait: 2500 });
    await shoot(page, 'v-dashboard-graficos.png', '/dashboard', { wait: 2500, before: (p) => scrollToCharts(p) });
    await context.close();
});

// Pasos REALES del formulario de reserva (selector inteligente de cliente y de vehículo, tarifa autocompletada).
async function reservationSequence(page, prefix, focus) {
    const day = (n) => new Date(Date.now() + n * 864e5).toISOString().slice(0, 10);
    await page.goto('/reservations/create');
    await settle(page, 800);
    await page.locator('#start_at').fill(`${day(20)}T09:00`);
    await page.locator('#end_at').fill(`${day(23)}T09:00`);
    const shot = async (name, id) => {
        if (focus && id) {
            await page.evaluate((target) => {
                const el = document.getElementById(target);
                el.scrollIntoView({ block: 'start' });
                window.scrollBy(0, -170);
            }, id);
        }
        await page.waitForTimeout(450);
        await page.screenshot({ path: out(`${prefix}-form-${name}.png`), fullPage: false });
    };
    await shot('0-inicio', null);
    const customer = page.locator('#customer_id');
    await customer.click();
    await customer.fill('Rod');
    await page.locator('#customer_id-list [role=option]').first().waitFor();
    await shot('1-cliente-buscando', 'customer_id');
    await page.locator('#customer_id-list [role=option]').first().click();
    await shot('2-cliente', 'customer_id');
    const category = await page.locator('#vehicle_category_id option', { hasText: 'Sedán' }).first().getAttribute('value');
    await page.locator('#vehicle_category_id').selectOption(category);
    const vehicle = page.locator('#vehicle_id');
    await vehicle.click();
    await vehicle.fill('A12');
    await page.locator('#vehicle_id-list [role=option]').first().waitFor();
    await shot('3-vehiculo-buscando', 'vehicle_id');
    await page.locator('#vehicle_id-list [role=option]').first().click();
    await shot('4-vehiculo', 'daily_rate');
}

test('secuencia de reserva 16:9', async ({ browser }) => {
    const context = await browser.newContext({ viewport: DESKTOP, deviceScaleFactor: 1 });
    const page = await context.newPage();
    await login(page);
    await reservationSequence(page, 'h', false);
    await context.close();
});

test('secuencia de reserva 9:16', async ({ browser }) => {
    const context = await browser.newContext({ viewport: MOBILE, deviceScaleFactor: 2, isMobile: true, hasTouch: true });
    const page = await context.newPage();
    await login(page);
    await reservationSequence(page, 'v', true);
    await context.close();
});
