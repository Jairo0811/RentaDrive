# Resultados de pruebas y auditorías

Evidencia obtenida en GitHub Actions sobre la rama de la PR #66 (SQL Server 2022 real, datos ficticios); la CI de `main` repite estas ejecuciones en cada fusión.

| Verificación | Resultado |
|---|---|
| PHPUnit (job `test`) | 350 pasan, 1334 aserciones |
| Playwright (job `e2e`, Chromium: escritorio, móvil, CSP, pagos móvil, recuperación) | 63 pasan |
| `composer validate` | válido |
| `composer audit` | sin avisos de seguridad |
| `npm audit` | 0 vulnerabilidades |
| Escaneo de secretos nuevos (`scan-new-commits`) | verde |
| Pint | sin diferencias en los archivos tocados |
| `staging-verify` (Docker, respaldo/restauración, humo) | verde en PR de despliegue |

## Rendimiento (≈252 filas por entidad, 4 sucursales, base de pruebas)
Consultas con 2 filas → 252 filas, y tiempo de respuesta:

| Pantalla | Consultas | Tiempo |
|---|:-:|--:|
| dashboard | 26 → 26 | 48 ms |
| clientes | 2 → 2 | 14 ms |
| vehículos | 7 → 7 | 24 ms |
| reservas | 4 → 4 | 13 ms |
| alquileres | 7 → 7 | 18 ms |
| facturas | 5 → 5 | 15 ms |
| pagos | 5 → 5 | 15 ms |
| reportes | 11 → 11 | 108 ms |
| inspecciones | 1 → 1 | 6 ms |
| auditoría | 2 → 3 | 21 ms |

Conclusión: sin N+1 detectado. Límite: no se midió concurrencia ni volumen de producción; las cifras son del runner de CI, no de hardware real.

## No ejecutado (por tanto no se afirma)
Zoom real, dispositivos físicos, lectores de pantalla, Safari/Firefox, staging real, carga concurrente.
