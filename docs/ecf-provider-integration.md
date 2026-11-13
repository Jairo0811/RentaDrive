# RentaDrive — e-CF: arquitectura de integración mediante proveedor

**Estado (13-11-2026): fundamento técnico preparado, EMISIÓN REAL DESHABILITADA.**

## Decisión y alcance

Se elige integrar un **Proveedor de Servicios de Facturación Electrónica autorizado por la DGII** mediante un contrato de software intercambiable, sin integrar ni pagar a un proveedor específico todavía. Esta estrategia reduce el alcance de certificación técnica propia y conserva libertad comercial para seleccionar proveedor por precio, API, soporte, SLA y cobertura.

La autorización **del contribuyente emisor** y la autorización **del proveedor** son conceptos distintos. La existencia de este código NO certifica a RentaDrive ni habilita a sus empresas para emitir e-CF. La DGII define las secuencias e-NCF de 13 caracteres y los tipos E31, E32, E33, E34, etc. No generar secuencias localmente.

Fuentes primarias:
- https://dgii.gov.do/cicloContribuyente/facturacion/comprobantesFiscalesElectronicosE-CF/Paginas/default.aspx
- https://dgii.gov.do/cicloContribuyente/facturacion/comprobantesFiscales/Paginas/comprobantesFiscalesElectronicos.aspx
- https://dgii.gov.do/cicloContribuyente/facturacion/comprobantesFiscalesElectronicosE-CF/Paginas/Proveedores-servicios-FE-autorizados.aspx
- https://dgii.gov.do/cicloContribuyente/facturacion/comprobantesFiscalesElectronicosE-CF/Documentacin%20sobre%20eCF/Formatos%20XML/Formato%20Comprobante%20Fiscal%20Electr%C3%B3nico%20%28e-CF%29%20V1.0.pdf

## Implementación inicial — no transmite ni firma

- \`App\Domain\Fiscal\Contracts\EcfProvider\`: puerto de proveedor, diseñado para sustituir el adaptador.
- \`DisabledEcfProvider\`: implementación fail-closed, vinculada en \`AppServiceProvider\`; rechaza cualquier transmisión.
- \`EcfDraftType\`: mapea \`consumer\` a **E32** y \`credit\` a **E31**, como tipos **candidatos** para preparación.
- \`FiscalDraftService\`: añade \`ecf_candidate_type\`, \`ecf_preparation_version\` y \`ecf_issued=false\` al snapshot existente; no asigna e-NCF ni cambia estados.
- \`EcfReadinessService\`: presenta disponibilidad de integración, estado de emisor y **emisión deshabilitada**.
- Vista \`/fiscal\` informa al administrador por empresa; el detalle de factura explica el tipo candidato.
- Suite de pruebas: adaptador bloqueado, tipos soportados, estado de emisión y snapshot.

**Límite funcional**: el snapshot no es un XML válido según DGII, no contiene firma digital ni sirve como comprobante tributario. Los PDF actuales continúan siendo documentos internos sin e-NCF.

## Próxima implementación (requiere elegir proveedor y habilitación)

1. Comparar 2–3 **proveedores actualmente autorizados** en listado DGII: API, sandbox, precios por emisión, volumen, modalidad de empresa, E31/E32/E33/E34, soporte, contingencias, seguridad, manejo de XML/QR, política de retención y exportación de datos.
2. Incorporar onboarding de cada tenant con su RNC verificado, habilitación de emisor, autorización de secuencias, certificados y credenciales en gestor de secretos; nunca en \`.env.example\`, base de datos en claro ni repositorio.
3. Modelar documentos e-CF inmutables (ID tenant, factura, tipo, e-NCF asignado por flujo autorizado, estado, hash XML, referencia de proveedor, eventos), unicidad por emisor/tipo/secuencia y política de retención.
4. Implementar mapeo completo y validación contra **versión oficial vigente** de XML/XSD: impuestos, ITBIS, exenciones, descuentos, tipo de cliente, referencias, datos obligatorios y particularidades de alquiler.
5. Adaptador real con HTTPS/TLS, credenciales segregadas por tenant, timeouts, idempotencia, reintentos controlados, seguimiento de estado DGII/proveedor y webhook autenticado. **Nunca marcar una factura como e-CF aceptado solo por HTTP 200 o por una solicitud enviada.**
6. Firma y manejo de XML con certificado conforme al flujo del proveedor y requerimientos aplicables. Control de acceso, eventos auditables, recuperación, reversión y contingencias.
7. Agregar notas de crédito **E34** y débito **E33** cuando existan modelos contables de documentos rectificativos; no reutilizar la función actual de descuento como equivalente fiscal.
8. Pruebas negativas: tenant A/B, intentos duplicados, documentos alterados después del snapshot, firma inválida, reloj y secuencia, indisponibilidad del proveedor, rechazo DGII, recepción múltiple de webhook y desfase de montos.

## Requisitos de salida comercial

**No marcar esta fase como completada** hasta tener contrato/proveedor probado, autorización de emisor por empresa, firma, XML/validación, e-NCF autorizado, estados verificables de DGII, auditoría, recuperación, ambientes separados y aceptación con el proveedor. Mantener el puerto deshabilitado mientras tanto.
