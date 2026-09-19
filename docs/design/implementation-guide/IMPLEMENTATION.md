# Handoff para React + TypeScript + Tailwind / Laravel-Inertia

## Orden de lectura y límites

1. Autoridades externas a este paquete: PRODUCT-DEFINITION-v1, ARCHITECTURE-v1 y UX-PRINCIPLES-v1.
2. Brandbook visual + tokens.json; components/SPECIFICATION.md + registry.json.
3. screens/flows.json + fixtures.json y capturas de los ocho recorridos.
4. Este mapeo, asset manifest, catálogo de microcopy y checklist.

Este paquete es autoridad de diseño aceptada, no código productivo aprobado. El prototipo HTML/CSS/JS sirve referencias inspeccionables dentro de docs/design. No migrar su estado en memoria, cálculos ni mensajes «de ejemplo» a producción. No agregar frameworks. Laravel/Inertia/React/TypeScript/Tailwind permanecen como arquitectura. Mantener lógica monetaria, conversiones y snapshots en dominio/servicios, no en componentes ni controladores.

## Traducción exacta

Copiar tokens.css generado al destino autorizado de implementación y cargar las fuentes locales desde Laravel/Vite. Mantener fuentes con OFL. El archivo CSS de referencia usa variables --eo-*; el ejemplo Tailwind4 de este directorio las publica como utilidades. No conservar los viejos colores canvas/coral/sage del shell como alias ambiguos. Las dos familias reales se muestran en brandbook; no sustituirlas por las formas rasterizadas del mock.

| Referencia | Componente React sugerido | Props mínimas |
|---|---|---|
| button | Button | variant,loading,disabled,type,children,onClick |
| field | Field + TextInput | id,label,hint,error,required,inputMode,value,onChange |
| currency | MoneyInput | decimalString,currency,onChange,error |
| quantity | QuantityInput / Stepper | quantityString,unit,dimension,min,max,onChange |
| row | OrderRow / IngredientRow | semantic label,secondary,trailing,href OR actions |
| money | MoneyValue | minorUnits OR decimalString,currency,precision,status |
| status | OrderStatus / PaymentStatus | exact enum; no shared combined status |
| summary | CostSummary | cost,price,profit,margin,completeness,referenceDate |
| sheet/dialog | Sheet / ConfirmDialog | open,onClose,title,initialFocus,returnFocus,dirty |
| form footer | StickyActions | primary,secondary,safeArea |
| bottom navigation | BottomNav | currentDestination; exactly4 entries |

Primitivas headless accesibles permitidas por arquitectura, sin inventar una librería obligatoria. `dialog` nativo del prototipo es referencia de foco y fondo inerte; el implementador debe verificar soporte y el patrón con teclado/lector. Componentes de ejemplo de props en [contracts.ts](contracts.ts) son contratos de tipos, no funcionalidad lista para pegar.

## Datos y dinero

Entradas de dinero viajan como string decimal o centavos enteros en JSON según contrato de dominio. API a UI expone valores ya calculados por servidor y metadatos de completitud. Moneda explícita aun cuando v1 use solo MXN. `Intl.NumberFormat('es-MX',...)` para mostrar valores convertidos con cuidado; nunca convertir números grandes o decimal autoritativo a float antes de persistir. Cantidades y costo normalizado pueden requerir escala mayor. Redondeo monetario de presentación a2dp, porcentaje a1dp, costo normalizado4–6dp; ninguna cifra redondeada de UI vuelve como dato autoritativo de cálculo.

Producto: costo de receta/tanda dividido entre rendimiento, más extras unitarios y asignación del pedido de referencia. Mostrar denominador y tipo de asignación. Evitar costo de entrega contado a la vez por unidad y por pedido. Distinguir margen (ganancia/precio) de multiplicador (precio/costo). Si falta costo o rendimiento válido, retornar `incomplete`, no0.

Historia: compras append-only; versión/snapshot de receta; precio effective-dated; pedidos con precio y base de costo por línea. Fecha local del negocio para filtros y as-of. No editar pasados con datos nuevos. UI de producto es estimación; pedidos completados usan costos atribuibles históricos. Campos de proveedor/nota no autorizan crear módulos adicionales.

## Formularios y transacciones

Cada guardado es una operación de dominio atómica. Conservar borrador durante errores y navegación controlada. Cancelar edición no borra registros. Descartar cambios requiere confirmación si se perderá borrador; no mostrar confirmación al volver de un formulario intacto. Formularios largos usan scroll; no wizard innecesario. Error enfoca primer campo, no recarga la app. Labels/hints/errors se conectan con id/aria-describedby, aria-invalid solo cuando corresponda.

Envíos de compra,pedido,pago y cambio de precio deben bloquear duplicados visualmente y soportar idempotencia/validación del servidor; un botón deshabilitado por sí solo no garantiza integridad. Cobro mayor que saldo falla preservando entrada; no inventar crédito/saldo a favor. Entregar confirma solo fulfillment; pagar cambia solo payment. Cancelación con anticipo advierte que no implica devolución. No usar transacción optimista para dinero si puede mostrarse éxito antes de confirmación.

## Assets y PWA

Rutas actuales e intención de destino en [asset-manifest.json](asset-manifest.json). Son propuestas de ubicación para el siguiente contrato, no autorización de escritura fuera de docs/design. Importar/icon components deben conservar fuente y licencia Lucide. Imágenes del usuario por abstracción Laravel storage; no base64 en tablas relacionales. Límites propuestos foto5MiB,JPEG/PNG/WebP; validar MIME y procesamiento del lado servidor, no confiar solo en accept del navegador.

Manifest de ejemplo con192/512/maskable; no sobrescribe public/manifest.webmanifest. PNG maskable tiene fondo opaco y glifos dentro de zona segura central. Wordmark SVG incluye fuente; para entrega productiva se puede convertir a contornos con herramienta vectorial conservando exactamente composición y tamaño. No reemplazar imagen de referencia por anuncio comercial de un producto no real.

## Verificación que debe heredar implementación

Pest: dinero exacto, normalización1kg→1000g, rendimiento, margen frente a multiplicador, asignaciones sin duplicado, saldos, snapshots, histórico as-of y idempotencia. Frontend: conservar campos válidos tras422, navegar con borrador, foco modal, unidades/teclado y estados incompletos. Playwright: compra→receta→precio→pedido→producción/cobro, en viewport móvil. Real phone: teclado, área segura, zoom200%, lector, manos ocupadas, instalación PWA y uso una semana. Estas pruebas no se ejecutaron sobre producción en una tarea de diseño.

## Reproducir referencias

Desde raíz: `python3 docs/design/audit/serve.py`; abrir localhost4327/brandbook/index.html. No necesita Node en runtime ni build de aplicación. Generadores opcionales: tokens/build.py (Pillow), components/build_catalog.py, screens/build_contracts.py, brandbook/build.py y splashes/export.py. Solo escriben docs/design. No correrlos sobre un paquete modificado sin revisar: son fuentes reproducibles del artefacto final, no automatización de producto.
