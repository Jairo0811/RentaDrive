# Fases 5 y 6 — Expediente digital y automatización

## Evidencias de inspección
Las fotografías nuevas se guardan en disco privado por empresa e inspección. Se sirven con autorización y aislamiento multiempresa. Las fotos antiguas de instalaciones existentes podrían continuar en el disco público: deberán migrarse y retirarse del almacenamiento público antes de una salida comercial. Definir retención, cifrado y backups.

## Acuse interno del operador
La aceptación interna del contrato conserva nombre declarado, operador autenticado, fecha, IP, agente de usuario y hash del resumen del alquiler. No es firma digital certificada ni aceptación verificable del cliente. Requiere integración con proveedor de firma y consentimiento verificable.

## Automatizaciones
Ejecutar el comando `php artisan rentadrive:automations` de forma idempotente. Notificaciones email a la empresa cuando una reserva comienza mañana, un alquiler está retrasado o un mantenimiento está próximo. Requiere consentimiento de cada empresa en Configuración (correo) y está deshabilitado por defecto: `RENTA_AUTOMATION_ENABLED=false`. Habilitar solo tras configurar el transporte de correo, una dirección autorizada y consentimiento operativo.

El scheduler se ejecuta con `php artisan schedule:run` cada minuto en producción y requiere observabilidad. WhatsApp comercial y otros canales siguen pendientes de proveedor y opt-in. No se envían documentos de clientes por email.
