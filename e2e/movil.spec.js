import { test, expect } from '@playwright/test';
import { credentials, pick } from './helpers.js';

// QA móvil: 360 px y 390 px (teléfonos habituales) y 640/320 px de ancho CSS, que equivalen al reflujo
// de una página de 1280 px con zoom 200 % y 400 % (WCAG 1.4.10). Es emulación de viewport: NO sustituye
// una prueba con zoom real del navegador ni con dispositivos físicos (ver docs/qa-movil.md).
const SLUG = process.env.E2E_COMPANY_SLUG ?? 'rentadrive-legacy';
const phones = [
    { name: '360 px', width: 360, height: 740 },
    { name: '390 px', width: 390, height: 844 },
];
const zooms = [
    { name: 'zoom 200 %', width: 640, height: 800 },
    { name: 'zoom 400 %', width: 320, height: 800 },
];
const publicPages = ['/login', '/forgot-password', `/book/${SLUG}`];

async function sinDesbordeHorizontal(page) {
    const exceso = await page.evaluate(() => document.documentElement.scrollWidth - document.documentElement.clientWidth);
    expect(exceso, 'la página no debe tener scroll horizontal').toBeLessThanOrEqual(1);
}

async function sinError(page) {
    await expect(page.locator('body')).not.toContainText(/server error|whoops|exception|sqlstate/i);
}

for (const vp of [...phones, ...zooms]) {
    test.describe(`páginas públicas a ${vp.name}`, () => {
        test.use({ storageState: { cookies: [], origins: [] }, viewport: { width: vp.width, height: vp.height } });

        for (const path of publicPages) {
            test(`${path} sin desborde y con acción principal visible`, async ({ page }) => {
                const respuesta = await page.goto(path);
                expect(respuesta?.status()).toBeLessThan(400);
                await expect(page.getByRole('heading').first()).toBeVisible();
                await expect(page.locator('main a, main button, form button, form input').first()).toBeVisible();
                await sinDesbordeHorizontal(page);
                await sinError(page);
            });
        }
    });
}

test.describe('acceso y recuperación en móvil', () => {
    test.use({ storageState: { cookies: [], origins: [] } });

    for (const vp of phones) {
        test(`credenciales inválidas muestran error a ${vp.name}`, async ({ page }) => {
            await page.setViewportSize({ width: vp.width, height: vp.height });
            await page.goto('/login');
            await page.getByLabel(/correo/i).fill('nadie@rentadrive.invalid');
            await page.locator('input[name=password]').fill('clave-incorrecta-123');
            await page.getByRole('button', { name: /iniciar sesión|entrar|acceder/i }).click();

            await expect(page).toHaveURL(/login/);
            await expect(page.locator('body')).toContainText(/credenciales|no coinciden|incorrect/i);
            await sinDesbordeHorizontal(page);
        });

        test(`inicio de sesión válido a ${vp.name}`, async ({ page }) => {
            const { email, password } = credentials();
            await page.setViewportSize({ width: vp.width, height: vp.height });
            await page.goto('/login');
            await page.getByLabel(/correo/i).fill(email);
            await page.locator('input[name=password]').fill(password);
            await page.getByRole('button', { name: /iniciar sesión|entrar|acceder/i }).click();
            await expect(page).toHaveURL(/dashboard/);
            await sinDesbordeHorizontal(page);
        });

        test(`solicitar recuperación a ${vp.name}`, async ({ page }) => {
            const { email } = credentials();
            await page.setViewportSize({ width: vp.width, height: vp.height });
            await page.goto('/forgot-password');
            await page.getByLabel(/correo/i).fill(email);
            await page.getByRole('button', { name: /enviar enlace/i }).click();
            await expect(page.locator('body')).toContainText(/enlace|correo/i);
            await sinDesbordeHorizontal(page);
        });
    }
});

for (const vp of [...phones, ...zooms]) {
    test.describe(`pantallas internas a ${vp.name}`, () => {
        test.use({ viewport: { width: vp.width, height: vp.height } });

        for (const path of ['/dashboard', '/reservations', '/reservations/create', '/invoices', '/payments']) {
            test(`${path} sin desborde de página`, async ({ page }) => {
                await page.goto(path);
                await expect(page.getByRole('heading').first()).toBeVisible();
                await sinDesbordeHorizontal(page);
                await sinError(page);
            });
        }
    });
}

test.describe('estados de error de los selectores en móvil', () => {
    test.use({ viewport: { width: 360, height: 740 } });

    test('búsqueda sin resultados no selecciona nada ni rompe el formulario', async ({ page }) => {
        const errores = [];
        page.on('pageerror', (e) => errores.push(e.message));
        await page.goto('/reservations/create');
        const input = page.locator('#customer_id');
        await input.click();
        await input.fill('zzzz-sin-coincidencias-9876');
        await page.waitForTimeout(800);

        await expect(page.locator('#customer_id-list [role=option]')).toHaveCount(0);
        await expect(input.locator('xpath=ancestor::*[@x-data][1]//input[@type="hidden"]').first()).toHaveValue('');
        expect(errores, 'sin errores de JavaScript').toEqual([]);
        await sinDesbordeHorizontal(page);
    });

    test('enviar vacío muestra errores de validación visibles', async ({ page }) => {
        await page.goto('/reservations/create');
        await page.getByRole('button', { name: 'Crear reserva' }).click();

        await expect(page).toHaveURL(/reservations\/create/);
        await expect(page.locator('body')).toContainText(/obligatorio|requerid|necesario|seleccion/i);
        await sinError(page);
        await sinDesbordeHorizontal(page);
    });
});

test.describe('pago con facturas abiertas en móvil (datos sintéticos E2E)', () => {
    test.use({ viewport: { width: 390, height: 844 } });

    test('aplicar un pago desde Pagos', async ({ page }) => {
        await page.goto('/payments');
        await page.getByRole('button', { name: /registrar|nuevo pago|cobro/i }).first().click();
        await pick(page, 'invoice_id', 'F');
        await page.locator('#amount').fill('50');
        await page.locator('#reference').fill('E2E-MOVIL-1');
        await sinDesbordeHorizontal(page);
        await page.getByRole('button', { name: 'Aplicar pago' }).click();

        await expect(page.getByText('E2E-MOVIL-1')).toBeVisible();
        await sinDesbordeHorizontal(page);
    });
});
