# RentaDrive — Digital Rental + Automation

Este documento describe las Fases 5 y 6 del roadmap comercial.

## Fase 5 — Digital Rental

### Firma digital del contrato

Cada alquiler puede registrar una firma del arrendatario.

Se conserva:

- nombre y documento del firmante;
- imagen PNG de la firma en storage privado;
- fecha/hora de aceptación;
- hash SHA-256 del contrato;
- hash de IP y user-agent;
- usuario interno que capturó la firma.

La firma no se almacena como archivo público.

### Hash contractual

El hash incluye una representación estable de:

- tenant;
- alquiler;
- cliente;
- vehículo;
- período;
- tarifa, depósito, cargos, impuestos y total;
- factura relacionada.

El hash guardado representa el contrato aceptado al momento de la firma.

### Check-in y check-out móvil

Rutas internas:

```text
GET /rentals/{rental}/check-in
GET /rentals/{rental}/check-out
```

El formulario está optimizado para teléfono/tablet e incluye:

- kilometraje;
- combustible;
- carrocería;
- interior;
- neumáticos;
- checklist de accesorios;
- daños estructurados;
- fotografías;
- geolocalización opcional.

En modo móvil se exige al menos una fotografía.

### Evidencia sellada

Después de crear una inspección, RentaDrive calcula un SHA-256 de la evidencia y registra `sealed_at`.

Una inspección sellada no se puede eliminar.

El cierre de un alquiler desde la interfaz exige:

1. contrato firmado;
2. check-in de entrega sellado;
3. check-out de devolución sellado.

El kilometraje final, combustible y hora real de devolución se toman directamente del check-out sellado.

---

## Fase 6 — Automation

### Canales

RentaDrive usa un contrato interno de canal y adaptadores independientes:

- Email mediante el mailer configurado en Laravel;
- WhatsApp Cloud API mediante plantilla configurada.

Los recordatorios no dependen directamente de un proveedor específico.

### Scanner operativo

`ScanOperationalAlertsJob` se ejecuta cada 15 minutos.

Revisa por tenant:

- reservas próximas a iniciar;
- alquileres próximos a devolución;
- alquileres vencidos;
- mantenimientos programados;
- mantenimiento por kilometraje;
- licencias de conducir próximas a vencer;
- seguros de vehículos próximos a vencer;
- matrícula/documento del vehículo próximo a vencer.

### Idempotencia

Cada aviso se registra en `automation_deliveries`.

La combinación:

```text
company_id + event_key + channel
```

es única.

Esto evita que el mismo barrido de scheduler envíe repetidamente una notificación ya creada.

### Colas

Los avisos se envían por la cola:

```text
notifications
```

El driver oficial por defecto continúa siendo `database`.

La conexión database usa `after_commit=true` para impedir que un job sea visible antes de confirmarse la transacción que lo originó.

### Configuración por tenant

Cada empresa define:

- email automático;
- WhatsApp automático;
- horas antes de una reserva;
- horas antes de una devolución;
- días antes de mantenimiento;
- días antes de vencimiento documental.

### Variables WhatsApp

```dotenv
WHATSAPP_AUTOMATION_TEMPLATE=
WHATSAPP_AUTOMATION_TEMPLATE_LANGUAGE=es
```

La plantilla de automatización debe aceptar tres parámetros de texto:

1. título;
2. mensaje;
3. nombre de la empresa.

### Operación en producción

Worker:

```bash
php artisan queue:work --queue=notifications,default --tries=3 --backoff=60 --timeout=90
```

Scheduler del sistema operativo:

```cron
* * * * * cd /ruta/rentadrive && php artisan schedule:run >> /dev/null 2>&1
```

Diagnóstico manual:

```bash
php artisan rentadrive:automation-scan
php artisan schedule:list
php artisan queue:failed
```

El worker y el scheduler deben ejecutarse como procesos supervisados en producción.

## Controles de cierre

La suite automatizada cubre:

- firma digital;
- firma almacenada en storage privado;
- check-in móvil con fotografía;
- check-out móvil con fotografía;
- evidencia sellada;
- imposibilidad de borrar evidencia sellada;
- cierre del alquiler usando datos del check-out;
- scanner de recordatorios;
- deduplicación de eventos;
- entrega de email por job;
- SQL Server 2022;
- Laravel Pint;
- Vite;
- npm audit.
