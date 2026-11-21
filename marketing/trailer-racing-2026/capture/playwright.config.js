import { defineConfig } from '@playwright/test';

// Capturas del tráiler: solo contra una base descartable con datos ficticios (CI manual).
export default defineConfig({
    testDir: '.',
    testMatch: /capture\.spec\.js/,
    timeout: 180_000,
    workers: 1,
    retries: 0,
    reporter: [['list']],
    outputDir: '../captures/.pw-output',
    use: {
        baseURL: process.env.E2E_BASE_URL ?? 'http://127.0.0.1:8000',
        locale: 'es-DO',
        timezoneId: 'America/Santo_Domingo',
        colorScheme: 'dark',
    },
});
