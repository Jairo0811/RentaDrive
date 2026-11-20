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

- `App\Domain\Fiscal\Contracts\EcfProvider`: puerto de proveedor, diseñado para sustituir el adaptador.
- `DisabledEcfProvider`: implementación fail-closed, vinculada en `AppServiceProvider`; rechaza cualquier transmisión.
- `EcfDraftType`: mapea `consumer` a **E32** y `credit` a **E31**, como tipos **candidatos** para preparación.
- `FiscalDraftService`: añade `ecf_candidate_type`, `ecf_preparation_version` y `ecf_issued=false` al snapshot existente; no asigna e-NCF ni cambia estados.
- `EcfReadinessService`: presenta disponibilidad de integración, estado de emisor y **emisión deshabilitada**.
- Vista `/fiscal` informa al administrador por empresa; el detalle de factura explica el tipo candidato.
- Suite de pruebas: adaptador bloqueado, tipos soportados, estado de emisión y snapshot.

**Límite funcional**: el snapshot no es un XML válido según DGII, no contiene firma digital ni sirve como comprobante tributario. Los PDF actuales continúan siendo documentos internos sin e-NCF.

## Evaluación preliminar de proveedores autorizados (13 de noviembre de 2026)

Todos los candidatos siguientes aparecen en el listado oficial DGII. La evaluación se hizo con información pública, **sin contrato ni pruebas de sandbox**. Los precios publicados para usar una plataforma web no significan que incluyan acceso API.

| Proveedor | Registro oficial DGII | Conectividad publicada | Precio de API para SaaS | Próxima acción |
| --- | --- | --- | --- | --- |
| Digifact Servicios SAS, RNC 132752155 | Sí | API y Web Service en el plan Business | No publicado | Solicitar documentación, credenciales sandbox, precio por tenant/e-CF y condiciones white-label |
| Voxel Caribe SRL, RNC 130873011 | Sí | Web Services, AS2, FTP/SFTP y bavel; cada RNC requiere conexión independiente | No publicado | Confirmar costos de multi-RNC, contrato, eventos y soporte de firma |
| Factura Dominicana GTI SRL, RNC 132794001 | Sí | Plataforma y planes prepago; API no comprobada públicamente | No confirmado | Solicitar API, sandbox y cotización comercial separada de planes web |

**Selección para evaluación técnica:** Digifact, por publicar claramente API y Web Service para sistemas administrativos. **No es una selección contractual ni una implementación conectada**. Voxel queda como alternativa de integración empresarial y Factura Dominicana como referencia de precios y opción alternativa si confirma API.

Para comparar costos, Factura Dominicana anuncia prepago de 24 documentos por RD$2,050 y plan de 250 mensuales por RD$1,380 (ITBIS incluido), **sin evidencia de acceso API incluido**. El costo que importa a RentaDrive es API multiempresa, onboarding por RNC, volumen por empresa, firmas, límites y soporte.

Fuentes verificables:
- DGII: https://dgii.gov.do/cicloContribuyente/facturacion/comprobantesFiscalesElectronicosE-CF/Paginas/Proveedores-servicios-FE-autorizados.aspx
- Digifact Business: https://www.digifact.com.do/FacturaElectronica/FacturaElectronica
- Voxel RD: https://www.voxelgroup.net/es/normativa/facturacion-electronica-republica-dominicana/
- Factura Dominicana: https://facturadominicana.do/planes

### Documentación API de Digifact encontrada

Se verificó el portal de desarrolladores para República Dominicana:
https://documentacion.digifact.com/do/api

- Entorno **sandbox/test**: `https://testnucdo.digifact.com/api`; producción: `https://nucdo.digifact.com/api` (solo referencia, no conectado).
- Autenticación POST `/login/get_token` con JSON `Username: DO.<RNC>.<USUARIO>` y `Password`, respuesta `Token`. El token **nunca debe registrarse en logs**.
- Consulta informativa GET `/SHAREDINFO`, `DATA1=SHARED_GETDTEINFO`, `DATA2=AUTHNUMBER|<e-NCF>`, `TAXID=<RNC>`, `USERNAME=<usuario>`. La respuesta incluye RNC emisor, secuencia, estado y seguimiento.
- Certificación API `POST /v2/transform/nuc_json` (o `/nuc` para XML), con esquema NUC y requisitos de firma. **NO llamar desde RentaDrive** hasta tener onboarding, autorización, validación de payload, idempotencia y credenciales con consentimiento.

Se añadió `DigifactSandboxApi` como **cliente exclusivamente de consulta y autenticación en el entorno test**. No implementa el contrato de emisión `EcfProvider`, no cambia el adaptador bloqueado, no se puede invocar desde rutas web y **no usa endpoints de producción**. Los tests usan HTTP simulado y comprueban coincidencia de RNC y e-NCF; nunca contactan la API real.

**Nota de seguridad:** no introducir credenciales de prueba/proveedor en GitHub ni enviarlas en chats. Su almacenamiento por tenant requiere gestor de secretos y protocolo de autorización, aún pendiente.


### Preguntas comerciales/técnicas obligatorias

1. ¿Existe API REST o SOAP con documentación, ejemplo de payload y sandbox separado de producción?
2. ¿Se admite multiempresa SaaS: varios RNC, credenciales segregadas y onboarding por empresa?
3. ¿El proveedor firma, reserva e-NCF, valida XSD, transmite, recibe y conserva los acuses DGII?
4. ¿Cómo funcionan idempotencia, reintentos, timeouts, rechazados, cancelaciones y contingencias?
5. ¿Costos de incorporación, certificado, facturas, licencias por RNC, soporte, mínimo de consumo y SLA?
6. ¿Cobertura y ejemplos de E31/E32 y notas E33/E34, retenciones e ITBIS?
7. ¿Ofrecen webhook firmado, polling de estado y exportación de XML/acuse/representación impresa?
8. ¿Retención, residencia de datos, cifrado, acceso de soporte, privacidad y salida del proveedor?

### Estado de preparación interna

La vista `/fiscal` ahora muestra cinco hitos de preparación por empresa. Solo marca **datos fiscales presentes** cuando existe razón social y RNC con formato válido. Todos los hitos de habilitación DGII, integración, firma y acuses permanecen **pendientes** mientras no exista evidencia y código de emisión certificado.

El contador del panel **no** representa porcentaje de cumplimiento jurídico ni certificación; la transmisión real continúa bloqueada.


## Próxima implementación (requiere elegir proveedor y habilitación)

1. Comparar 2–3 **proveedores actualmente autorizados** en listado DGII: API, sandbox, precios por emisión, volumen, modalidad de empresa, E31/E32/E33/E34, soporte, contingencias, seguridad, manejo de XML/QR, política de retención y exportación de datos.
2. Incorporar onboarding de cada tenant con su RNC verificado, habilitación de emisor, autorización de secuencias, certificados y credenciales en gestor de secretos; nunca en `.env.example`, base de datos en claro ni repositorio.
3. Modelar documentos e-CF inmutables (ID tenant, factura, tipo, e-NCF asignado por flujo autorizado, estado, hash XML, referencia de proveedor, eventos), unicidad por emisor/tipo/secuencia y política de retención.
4. Implementar mapeo completo y validación contra **versión oficial vigente** de XML/XSD: impuestos, ITBIS, exenciones, descuentos, tipo de cliente, referencias, datos obligatorios y particularidades de alquiler.
5. Adaptador real con HTTPS/TLS, credenciales segregadas por tenant, timeouts, idempotencia, reintentos controlados, seguimiento de estado DGII/proveedor y webhook autenticado. **Nunca marcar una factura como e-CF aceptado solo por HTTP 200 o por una solicitud enviada.**
6. Firma y manejo de XML con certificado conforme al flujo del proveedor y requerimientos aplicables. Control de acceso, eventos auditables, recuperación, reversión y contingencias.
7. Agregar notas de crédito **E34** y débito **E33** cuando existan modelos contables de documentos rectificativos; no reutilizar la función actual de descuento como equivalente fiscal.
8. Pruebas negativas: tenant A/B, intentos duplicados, documentos alterados después del snapshot, firma inválida, reloj y secuencia, indisponibilidad del proveedor, rechazo DGII, recepción múltiple de webhook y desfase de montos.

## Requisitos de salida comercial

**No marcar esta fase como completada** hasta tener contrato/proveedor probado, autorización de emisor por empresa, firma, XML/validación, e-NCF autorizado, estados verificables de DGII, auditoría, recuperación, ambientes separados y aceptación con el proveedor. Mantener el puerto deshabilitado mientras tanto.
