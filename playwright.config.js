import { defineConfig, devices } from '@playwright/test';

const baseURL = process.env.E2E_BASE_URL ?? 'http://127.0.0.1:8000';

export default defineConfig({
    testDir: './e2e',
    timeout: 45_000,
    expect: { timeout: 10_000 },
    fullyParallel: false,
    workers: 1,
    retries: 0,
    reporter: [['list'], ['html', { open: 'never', outputFolder: 'playwright-report' }]],
    outputDir: 'test-results',
    use: {
        baseURL,
        locale: 'es-DO',
        timezoneId: 'America/Santo_Domingo',
        trace: 'retain-on-failure',
        screenshot: 'only-on-failure',
    },
    projects: [
        { name: 'setup', testMatch: /auth\.setup\.js/ },
        {
            // Pantallas, acceso y selectores en móvil (no necesita facturas previas).
            name: 'mobile',
            testMatch: /movil\.spec\.js/,
            grep: /^(?!.*pago con facturas)/,
            dependencies: ['setup'],
            use: { ...devices['Pixel 5'], storageState: 'test-results/.auth/admin.json' },
        },
        {
            // Verifica que la CSP en modo enforce no bloquee scripts, estilos ni fuentes legítimos.
            name: 'csp',
            testMatch: /csp\.spec\.js/,
            dependencies: ['setup', 'mobile'],
            use: { ...devices['Desktop Chrome'], storageState: 'test-results/.auth/admin.json' },
        },
        {
            name: 'desktop',
            testMatch: /flujos\.spec\.js/,
            dependencies: ['setup', 'mobile', 'csp'],
            use: { ...devices['Desktop Chrome'], storageState: 'test-results/.auth/admin.json' },
        },
        {
            // Necesita facturas abiertas, que crea el flujo de escritorio.
            name: 'mobile-pagos',
            testMatch: /movil\.spec\.js/,
            grep: /pago con facturas/,
            dependencies: ['desktop'],
            use: { ...devices['Pixel 5'], storageState: 'test-results/.auth/admin.json' },
        },
        {
            // Cambia la contraseña del administrador E2E: siempre al final.
            name: 'recovery',
            testMatch: /recuperacion\.spec\.js/,
            dependencies: ['mobile-pagos'],
            use: { ...devices['Desktop Chrome'], storageState: 'test-results/.auth/admin.json' },
        },
    ],
});
