# Fase 2 — Booking Engine y experiencia comercial

La Fase 2 convierte RentaDrive en un portal de reservas multi-tenant y white-label conectado con la operación interna.

## Portal público

Cada empresa dispone de:

- `/r/{company-slug}`
- logo y colores propios;
- dominio personalizado opcional;
- catálogo de vehículos disponibles;
- cotización por fechas;
- extras, seguros y promociones;
- checkout público;
- confirmación y cancelación self-service.

El dominio personalizado se configura sin protocolo, por ejemplo `reservas.miempresa.com`. DNS y TLS se resuelven en la infraestructura de despliegue.

## Tarifas por duración

En Configuración se definen:

- descuento semanal: se aplica desde 7 días;
- descuento mensual: se aplica desde 30 días.

La tarifa final siempre se recalcula en backend antes de guardar la reserva.

## Temporadas

Una regla por línea:

```text
Navidad|2026-12-15|2027-01-10|1.20
Semana Santa|2027-03-20|2027-03-31|1.15
```

Formato:

`Nombre|fecha-inicio|fecha-fin|multiplicador`

El multiplicador se aplica día por día únicamente dentro del rango de temporada.

## Extras y seguros

Una regla por línea:

```text
GPS|GPS|300|per_day|extra
BABY|Silla infantil|250|per_day|extra
FULL|Seguro Full|900|per_day|insurance
AIRPORT|Entrega aeropuerto|1500|flat|extra
```

Formato:

`Código|Nombre|Precio|per_day/flat|extra/insurance`

## Promociones

Una regla por línea:

```text
WELCOME10|percent|10|2026-01-01|2026-12-31
VIP1500|fixed|1500||
```

Formato:

`Código|percent/fixed|valor|inicio|fin`

Las fechas son opcionales. Los códigos se normalizan a mayúsculas.

## Cancelación self-service

La empresa define cuántas horas antes de la recogida cierra la cancelación en línea.

RentaDrive genera un enlace temporal firmado. Solo reservas `pending` o `confirmed`, sin alquiler abierto, pueden cancelarse desde el portal antes del límite configurado.

## Confirmaciones

### Email

La confirmación por correo utiliza el mailer estándar de Laravel y puede activarse o desactivarse por tenant.

### WhatsApp Cloud API

La integración es opcional y se activa por tenant. Requiere:

```dotenv
WHATSAPP_ACCESS_TOKEN=
WHATSAPP_PHONE_NUMBER_ID=
WHATSAPP_GRAPH_VERSION=
WHATSAPP_BOOKING_TEMPLATE=
WHATSAPP_BOOKING_TEMPLATE_LANGUAGE=es
```

`WHATSAPP_GRAPH_VERSION` debe configurarse explícitamente con una versión soportada en el despliegue.

La plantilla de WhatsApp debe estar aprobada y aceptar tres parámetros en el cuerpo, en este orden:

1. código de reserva;
2. nombre de la empresa;
3. total estimado.

Si la configuración de WhatsApp no está completa, la reserva continúa normalmente y el canal se omite.

## Persistencia del precio

La reserva conserva:

- tarifa diaria base;
- total con temporadas;
- extras;
- descuentos;
- promoción aplicada;
- desglose completo de pricing;
- total estimado final.

Esto evita recalcular históricamente una reserva con reglas comerciales que hayan cambiado después.
