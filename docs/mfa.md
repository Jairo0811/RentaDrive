# Autenticación multifactor (MFA)

RentaDrive ofrece verificación en dos pasos **TOTP** (RFC 6238: SHA-1, 6 dígitos, 30 s), compatible con Google Authenticator,
Microsoft Authenticator, Authy, 1Password y similares. Está pensada para **SuperAdmin de plataforma** y **Administradores de empresa**,
pero cualquier usuario puede activarla desde *Perfil → Verificación en dos pasos*.

## Qué incluye

- **Activación**: se genera un secreto de 160 bits, se muestra como clave manual (y URI `otpauth://`) y solo queda activo tras
  confirmar un código válido. Se muestra un código QR (SVG generado en el servidor con `bacon/bacon-qr-code`; el secreto no sale a ningún servicio externo) y, como alternativa, la clave escrita.
- **Login**: con credenciales correctas y MFA activo **no se crea sesión** hasta superar el desafío (`/two-factor-challenge`, válido 5 min).
- **Códigos de recuperación**: 8 códigos de un solo uso. Se muestran una vez (en la respuesta, no en la sesión) y se guardan como HMAC-SHA256.
- **Regeneración**: exige un código TOTP válido; invalida todos los anteriores.
- **Fuerza bruta**: 5 intentos por minuto por usuario+IP y 10 por 15 min por cuenta; el bloqueo cubre también el código correcto.
- **Repetición**: cada código TOTP solo sirve una vez (se guarda el último intervalo aceptado, de forma atómica).
- **Almacenamiento**: secreto y códigos cifrados con `APP_KEY` (cast `encrypted`); excluidos de la serialización y de la auditoría.
- **Confirmación de identidad**: iniciar, regenerar o desactivar MFA exige haber confirmado la contraseña en los últimos 10 minutos.
- **Eventos auditados** (sin secretos): `mfa_enabled`, `mfa_disabled`, `mfa_failed`, `mfa_locked`, `mfa_recovery_used`, `mfa_codes_regenerated`
  (filtro «Seguridad (MFA)» en la auditoría) y log estructurado `security.mfa`.
- La recuperación de contraseña **no cambia**: restablecer la clave no omite el segundo factor.

## Obligatoriedad por rol

`MFA_ENFORCE=true` obliga a SuperAdmin y Administradores sin MFA a activarlo antes de usar el panel (solo quedan accesibles el perfil,
la confirmación de contraseña y el cierre de sesión). Está **desactivado por defecto** para no bloquear a quien despliega por primera vez:
actívalo cuando los administradores ya tengan MFA. `rentadrive:production-check` lo muestra como recomendación (no bloquea).

## Pérdida del dispositivo y de los códigos

Un administrador sin acceso no puede recuperarlo desde la web (a propósito). El operador, con acceso al servidor, ejecuta:

    php artisan rentadrive:mfa-reset usuario@empresa.com

Verifica antes la identidad de la persona por un canal independiente. La acción queda auditada (`mfa_disabled`).

## Limitaciones conocidas

- Sin WebAuthn/passkeys. Sin SMS (por diseño: es un canal más débil).
- El reinicio de MFA de otro usuario es una operación de consola; no existe aún un flujo web con doble aprobación.
- La eficacia depende de que el reloj del servidor esté sincronizado (NTP); se tolera ±30 s.
