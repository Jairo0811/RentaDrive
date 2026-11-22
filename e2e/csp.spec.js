import { test, expect } from '@playwright/test';

// La CSP se aplica en modo "enforce": cualquier script/estilo/fuente bloqueado rompería la interfaz.
// Esta prueba falla si el navegador registra una sola violación en las pantallas principales.
const SLUG = process.env.E2E_COMPANY_SLUG ?? 'rentadrive-legacy';
const panelPages = ['/dashboard', '/customers/create', '/vehicles', '/reservations/create', '/reports', '/profile'];
const publicPages = ['/login', '/forgot-password', `/book/${SLUG}`];

async function observeViolations(page) {
    await page.addInitScript(() => {
        window.__cspViolations = [];
        document.addEventListener('securitypolicyviolation', (event) => {
            window.__cspViolations.push(`${event.effectiveDirective} -> ${event.blockedURI || 'inline'} @ ${event.sourceFile || location.pathname}`);
        });
    });
    const consoleViolations = [];
    page.on('console', (message) => {
        // Con la build CSP de Alpine, una expresión no permitida solo avisa por consola: se trata como fallo.
        if (/content security policy|alpine expression error|csp parser error|alpine warning/i.test(message.text())) {
            consoleViolations.push(message.text());
        }
    });
    page.on('pageerror', (error) => consoleViolations.push(`pageerror: ${error.message}`));
    return consoleViolations;
}

async function assertClean(page, consoleViolations, label) {
    const violations = await page.evaluate(() => window.__cspViolations ?? []);
    expect([...violations, ...consoleViolations], `violaciones CSP en ${label}`).toEqual([]);
}

test.describe('CSP en el panel (sesión de administrador)', () => {
    for (const path of panelPages) {
        test(`${path} sin violaciones y con Alpine activo`, async ({ page }) => {
            const consoleViolations = await observeViolations(page);
            const response = await page.goto(path);
            expect(response?.status()).toBeLessThan(400);

            const header = response?.headers()['content-security-policy'] ?? '';
            expect(header).toMatch(/script-src [^;]*'nonce-[A-Za-z0-9+/=]+'/);
            expect(header).not.toMatch(/script-src [^;]*'unsafe-inline'/);
            expect(header).not.toContain('unsafe-eval');
            expect(header).toContain("object-src 'none'");
            expect(header).toContain("frame-ancestors 'none'");

            await page.waitForLoadState('networkidle');
            expect(await page.evaluate(() => typeof window.Alpine?.version)).toBe('string');
            await assertClean(page, consoleViolations, path);
        });
    }

    test('menú de accesibilidad y selector inteligente funcionan bajo CSP', async ({ page }) => {
        const consoleViolations = await observeViolations(page);
        await page.goto('/reservations/create');
        await page.waitForLoadState('networkidle');
        await page.getByRole('button', { name: /accesibilidad/i }).first().click();
        await expect(page.getByRole('dialog').first()).toBeVisible();

        // Opciones del menú (x-for con plantillas migradas) y selector de búsqueda.
        await page.getByRole('button', { name: '125%' }).first().click();
        await expect(page.getByRole('button', { name: '125%' }).first()).toHaveAttribute('aria-pressed', 'true');
        await page.keyboard.press('Escape');

        const lookup = page.getByRole('combobox').first();
        await lookup.click();
        await expect(lookup).toHaveAttribute('aria-expanded', 'true');
        await page.keyboard.press('Escape');
        await expect(lookup).toHaveAttribute('aria-expanded', 'false');
        await assertClean(page, consoleViolations, 'interacción Alpine');
    });

    test('panel de gráficos del dashboard se dibuja', async ({ page }) => {
        const consoleViolations = await observeViolations(page);
        await page.goto('/dashboard');
        await page.waitForLoadState('networkidle');
        const canvases = await page.locator('canvas').count();
        expect(canvases).toBeGreaterThan(0);
        await assertClean(page, consoleViolations, 'dashboard');
    });
});

test.describe('CSP en páginas públicas', () => {
    test.use({ storageState: { cookies: [], origins: [] } });

    for (const path of publicPages) {
        test(`${path} sin violaciones`, async ({ page }) => {
            const consoleViolations = await observeViolations(page);
            const response = await page.goto(path);
            expect(response?.status()).toBeLessThan(400);
            expect(response?.headers()['content-security-policy'] ?? '').toContain("default-src 'self'");
            await page.waitForLoadState('networkidle');
            await assertClean(page, consoleViolations, path);
        });
    }

    test('una inyección de script en línea es bloqueada', async ({ page }) => {
        await page.goto('/login');
        const blocked = await page.evaluate(
            () =>
                new Promise((resolve) => {
                    document.addEventListener('securitypolicyviolation', () => resolve(true), { once: true });
                    const script = document.createElement('script');
                    script.textContent = 'window.__injected = true';
                    document.head.appendChild(script);
                    setTimeout(() => resolve(false), 1500);
                }),
        );
        expect(blocked).toBe(true);
        expect(await page.evaluate(() => window.__injected)).toBeUndefined();
    });
});
