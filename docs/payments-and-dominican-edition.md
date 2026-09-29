# RentaDrive — Payments + Dominican Edition

Las Fases 3 y 4 añaden a RentaDrive un ledger de pagos auditable y una capa fiscal dominicana desacoplada de proveedores externos.

## Fase 3 — Payments

### Depósitos de reserva

Cada tenant configura:

- pasarela `manual` o `hosted`;
- depósito porcentual o fijo;
- valor del depósito.

La reserva persiste `deposit_required` y `deposit_paid`. El portal público genera una URL temporal firmada para completar el depósito antes del alquiler.

### Payment intents

Los intentos de pago conservan:

- tenant y sucursal;
- reserva o factura;
- propósito;
- gateway;
- monto y moneda;
- clave de idempotencia;
- referencia y URL de checkout;
- estado y metadatos.

### Adaptador de pasarela

RentaDrive expone un contrato interno `PaymentGateway`.

El adaptador hosted espera un servicio normalizado con:

```text
POST /checkout-sessions
POST /refunds
```

Las credenciales se mantienen únicamente en variables de entorno.

### Webhook

Endpoint:

```text
POST /api/webhooks/payments/hosted
```

Cabecera obligatoria:

```text
X-RentaDrive-Signature: HMAC-SHA256(raw_body, PAYMENT_GATEWAY_WEBHOOK_SECRET)
```

Captura:

```json
{
  "event_id": "evt-001",
  "type": "payment.captured",
  "data": {
    "payment_intent_id": "uuid",
    "transaction_id": "txn-001",
    "amount": 1500
  }
}
```

También se admite `payment.failed`.

El par proveedor/evento es único. Un evento repetido no crea un segundo pago.

### Reembolsos y conciliación

Los pagos no se eliminan para representar un reembolso. Se conserva el pago original y se registran movimientos en `payment_refunds`.

La conciliación reconstruye:

- `Invoice.paid_amount`;
- balance y estado de factura;
- `Reservation.deposit_paid`;

usando el monto neto de cada pago.

---

## Fase 4 — Dominican Edition

### Perfil fiscal

Cada tenant puede configurar:

- habilitación fiscal;
- modalidad NCF o e-CF;
- RNC emisor;
- razón social;
- domicilio fiscal;
- condición de emisor electrónico autorizado;
- secuencias de comprobantes.

No se guardan certificados, API keys ni secretos fiscales en la base de datos de configuración.

### ITBIS

RentaDrive mantiene la tasa general de ITBIS en 18% como regla protegida del motor fiscal.

El total fiscal se determina antes de emitir:

```text
base gravada = subtotal - descuento
ITBIS = base gravada × 18%
total = base gravada + ITBIS + monto exento
```

Después de emitir un comprobante, no se permite modificar importes fiscales mediante la edición ordinaria de la factura.

### Secuencias

Formato de configuración:

```text
TIPO|PROXIMO|FINAL|VENCIMIENTO
```

Ejemplos:

```text
B01|1|99999999|2027-12-31
B02|1|99999999|2027-12-31
E31|1|9999999999|2027-12-31
E32|1|9999999999|2027-12-31
```

Las secuencias se bloquean transaccionalmente al asignar un comprobante para impedir duplicados por concurrencia.

### Tipos soportados

- `B01`: comprobante de crédito fiscal.
- `B02`: comprobante de consumo.
- `E31`: factura de crédito fiscal electrónica.
- `E32`: factura de consumo electrónica.

Los comprobantes de crédito fiscal requieren un cliente con RNC válido de 9 dígitos.

### Integración e-CF

La emisión electrónica usa un contrato desacoplado. El adaptador hosted espera:

```text
POST /documents
```

La respuesta normalizada admite estados:

- `accepted`;
- `pending`;
- `rejected`.

RentaDrive persiste el payload enviado, la respuesta del proveedor y la referencia de seguimiento.

La plataforma no simula una autorización DGII: el tenant debe estar autorizado para emitir e-CF y el despliegue debe usar un proveedor certificado o un conector propio que cumpla las especificaciones vigentes.

## Variables de entorno

```dotenv
PAYMENT_GATEWAY_BASE_URL=
PAYMENT_GATEWAY_TOKEN=
PAYMENT_GATEWAY_WEBHOOK_SECRET=
PAYMENT_GATEWAY_TIMEOUT=15

FISCAL_GATEWAY_BASE_URL=
FISCAL_GATEWAY_TOKEN=
FISCAL_GATEWAY_TIMEOUT=20
```

## Controles de cierre

La suite de integración cubre:

- pagos parciales;
- reembolsos;
- conciliación;
- webhook idempotente;
- depósito de reserva;
- NCF B02;
- e-CF E31 mediante proveedor simulado;
- SQL Server 2022;
- Laravel Pint;
- build Vite;
- npm audit.
