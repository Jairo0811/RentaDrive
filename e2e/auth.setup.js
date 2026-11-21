import { test as setup, expect } from '@playwright/test';
import { credentials } from './helpers.js';

setup('iniciar sesión como administrador del entorno E2E', async ({ page }) => {
    const { email, password } = credentials();

    await page.goto('/login');
    await page.getByLabel(/correo/i).fill(email);
    await page.locator('input[name=password]').fill(password);
    await page.getByRole('button', { name: /iniciar sesión|entrar|acceder/i }).click();
    await expect(page).toHaveURL(/dashboard/);

    await page.context().storageState({ path: 'test-results/.auth/admin.json' });
});
