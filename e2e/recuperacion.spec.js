import { test, expect } from '@playwright/test';
import fs from 'node:fs';
import { credentials } from './helpers.js';

// Cambia la contraseña del administrador E2E, por eso corre al final (proyecto "recovery").

test('recuperar la contraseña con un enlace válido', async ({ browser }) => {
    const { email } = credentials();
    const newPassword = `${process.env.E2E_ADMIN_PASSWORD}-nueva`;
    const logFile = process.env.E2E_MAIL_LOG ?? 'storage/logs/laravel.log';
    const context = await browser.newContext({ storageState: { cookies: [], origins: [] } }); // sin sesión
    const page = await context.newPage();

    await page.goto('/forgot-password');
    await page.getByLabel(/correo/i).fill(email);
    await page.getByRole('button', { name: /enviar enlace/i }).click();
    await expect(page.locator('body')).toContainText(/enlace|correo/i);

    // MAIL_MAILER=log escribe el correo en el log; no se envía ningún correo real.
    let link = null;
    await expect.poll(() => {
        const raw = fs.existsSync(logFile) ? fs.readFileSync(logFile, 'utf8') : '';
        const decoded = raw.replace(/=\r?\n/g, '').replace(/=3D/g, '=');
        const matches = decoded.match(/https?:\/\/[^\s"<>]*reset-password\/[^\s"<>]+/g);
        link = matches ? matches[matches.length - 1] : null;
        return link;
    }, { timeout: 15_000 }).not.toBeNull();

    const url = new URL(link);
    await page.goto(`${url.pathname}${url.search}`);
    await page.locator('#password').fill(newPassword);
    await page.locator('#password_confirmation').fill(newPassword);
    await page.getByRole('button', { name: 'Restablecer contraseña' }).click();
    await expect(page).toHaveURL(/login/);

    await page.getByLabel(/correo/i).fill(email);
    await page.locator('input[name=password]').fill(newPassword);
    await page.getByRole('button', { name: /iniciar sesión|entrar|acceder/i }).click();
    await expect(page).toHaveURL(/dashboard/);
    await context.close();
});
