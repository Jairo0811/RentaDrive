import { expect } from '@playwright/test';
import zlib from 'node:zlib';

/** Credenciales solo del entorno E2E de CI; nunca se guardan en el repositorio. */
export const credentials = () => {
    const email = process.env.E2E_ADMIN_EMAIL;
    const password = process.env.E2E_ADMIN_PASSWORD;

    if (!email || !password) {
        throw new Error('Define E2E_ADMIN_EMAIL y E2E_ADMIN_PASSWORD (base de datos E2E descartable).');
    }

    return { email, password };
};

/** Elige una opción del selector con búsqueda (combobox del servidor). */
export async function pick(page, id, text) {
    const input = page.locator(`#${id}`);
    await input.click();
    // Espera la búsqueda filtrada (la lista inicial sin filtro puede seguir visible hasta que llega).
    const filtered = page.waitForResponse((r) => r.url().includes('/lookups/') && r.url().includes('q='), { timeout: 10_000 });
    await input.fill(text);
    await filtered;
    await page.waitForTimeout(200); // render de Alpine tras la respuesta
    const option = page.locator(`#${id}-list [role=option]`).first();
    await expect(option).toBeVisible();
    await option.click();
    await expect(page.locator(`input[type=hidden][name="${await input.evaluate((el) => el.closest('[x-data]').querySelector('input[type=hidden]').name)}"]`)).not.toHaveValue('');
}

/** PNG válido (cuadrado, color sólido) generado en memoria, sin archivos binarios en el repo. */
export function solidPng(size = 64) {
    const crcTable = Array.from({ length: 256 }, (_, n) => {
        let c = n;
        for (let k = 0; k < 8; k++) c = c & 1 ? 0xedb88320 ^ (c >>> 1) : c >>> 1;
        return c >>> 0;
    });
    const crc = (buf) => {
        let c = 0xffffffff;
        for (const byte of buf) c = crcTable[(c ^ byte) & 0xff] ^ (c >>> 8);
        return (c ^ 0xffffffff) >>> 0;
    };
    const chunk = (type, data) => {
        const length = Buffer.alloc(4);
        length.writeUInt32BE(data.length);
        const body = Buffer.concat([Buffer.from(type), data]);
        const sum = Buffer.alloc(4);
        sum.writeUInt32BE(crc(body));
        return Buffer.concat([length, body, sum]);
    };
    const header = Buffer.alloc(13);
    header.writeUInt32BE(size, 0);
    header.writeUInt32BE(size, 4);
    header[8] = 8;
    header[9] = 2;
    const row = Buffer.concat([Buffer.from([0]), Buffer.alloc(size * 3, 0x60)]);
    const raw = Buffer.concat(Array.from({ length: size }, () => row));

    return Buffer.concat([
        Buffer.from([0x89, 0x50, 0x4e, 0x47, 0x0d, 0x0a, 0x1a, 0x0a]),
        chunk('IHDR', header),
        chunk('IDAT', zlib.deflateSync(raw)),
        chunk('IEND', Buffer.alloc(0)),
    ]);
}
