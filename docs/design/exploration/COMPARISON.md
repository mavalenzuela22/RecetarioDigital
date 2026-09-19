# Phase 2 · Tres direcciones para elegir

> Registro histórico de Phase 2. Actualización: el operador seleccionó A. Ver [refinamiento actual](../refinement/A/REVIEW-R1.md); los estados de selección pendientes que siguen documentan el cierre original.

**Estado: exploración; ninguna dirección seleccionada ni aceptada.** No es un brandbook ni autorización para implementar. Las tres láminas usan el mismo caso económico, la misma fecha y los mismos destinos de navegación. No se declara ganadora.

## Lectura recomendada

1. Abrir las láminas [A](A/board.png), [B](B/board.png) y [C](C/board.png) al tamaño completo.
2. Comparar cómo localizar qué preparar, a quién entregar, quién debe y cuánto.
3. Comparar el registro de la misma compra y el carácter culinario.
4. Elegir A/B/C, combinar aspectos concretos o rechazar y pedir otra iteración.

| Aspecto | A · Cocina cálida artesanal | B · Emprendedora fuerte | C · Mercadito premium de barrio |
|---|---|---|---|
| Idea central | Agenda de cocina / ficha de receta | Lista priorizada de trabajo | Comandas / etiquetas de pedido |
| Punto de entrada | Producción agrupada por producto, luego entregas | Cantidad pendiente y acción inmediata | Pedidos con nombre y hora, producción agregada |
| Ritmo | Lectura pausada, separadores suaves | Lectura rápida, números grandes, reglas fuertes | Bloques por pedido con referencias de etiqueta |
| Compra | Formulario continuo en una página | Captura compacta de cantidades e importes | Resumen de compra editable por bloques; detalle en hoja inferior |
| Material | Papel crema limpio, radios 16px | Marfil limpio, radios 4px, secciones abiertas | Papel blanco cálido, radios 8px, reglas y etiquetas |
| Tipografía candidata | Fraunces + DM Sans | Archivo + DM Sans | Literata + Manrope |
| Culinaria | Canela, cacao y luz de cocina | Hierbas, lima y bandeja de producción | Verde de mercado, guayaba y etiqueta mostaza |
| Ventaja | Cercanía y bajo estrés visual | Escaneo y decisión rápidos | Relación pedido–persona muy visible |
| Costo | Puede requerir más desplazamiento | Puede sentirse severa si pierde imagen/calidez | Puede crecer mucho con numerosos pedidos |
| Riesgo a probar | No confundir calma con baja prioridad | No comprimir texto ni volverla panel corporativo | No confundir etiqueta decorativa con botón |

## Escenario común, ficticio y reproducible

Viernes **18 de septiembre de 2026**, moneda MXN. Los nombres Ana y Luis son datos de muestra, no clientes reales.

| Pedido | Producto | Piezas | Entrega | Precio/pieza | Venta | Anticipo | Saldo | Costo estimado atribuible | Ganancia estimada |
|---|---|---:|---|---:|---:|---:|---:|---:|---:|
| Ana | Roles de canela | 12 | 12:30 | $40 | $480 | $240 | $240 | $240 | $240 |
| Luis | Empanadas | 12 | 17:00 | $40 | $480 | $80 | $400 | $240 | $240 |
| Total | | 24 | 2 entregas | | $960 | $320 | $640 | $480 | $480 |

Los 24 productos están pendientes de preparar. El rótulo «Listo para entregar» en la columna de componentes es una **muestra de estado**, no el estado de estos pedidos. Las horas de las pantallas indican hora comprometida de entrega, no tiempo estimado automático de cocción. La ganancia es estimada con el costo de referencia atribuible del ejemplo; no es dinero en caja ni beneficio contable certificado.

Compra compartida: Harina de trigo → presentación Bolsa → cantidad comprada 1 kg → total pagado $42.00 MXN → fecha 18 sep 2026 → tienda/nota opcionales. Resultado calculado: 1,000g y $0.0420/g. «Cantidad comprada» siempre representa contenido total comprado; presentación es descripción, no un segundo multiplicador. Caso futuro a probar: 2 bolsas de 1kg se capturan como presentación «2 bolsas de 1kg», cantidad total 2kg. No hay stock, existencia ni reabastecimiento.

Muestra tipográfica de precio: costo por pieza $20.00; precio $40.00; ganancia $20.00; margen 50%; multiplicador x2. No se equipara x2 con 200% de margen. El escenario es ilustrativo y no define una recomendación de precio.

## Contrato común de navegación propuesto

| Destino inferior | Contenido previsto en v1 |
|---|---|
| Hoy | Trabajo de la fecha, preparación, entregas, cobros y estimaciones |
| Pedidos | Lista de pedidos; detalle con estado de entrega separado de pago |
| Recetario | Recetas y productos, rendimiento, costo y precio |
| Más | Ingredientes, compras e historial; accesos a funciones existentes en alcance |

«Ver producción» abre la agregación de pedidos activos de la fecha. «Registrar compra» entra al formulario de ingrediente; no genera una orden de compra. «Te debe» abre el cobro del pedido elegido. No se cambian estados de pago o entrega al tocar un resumen. La navegación inferior se oculta en el formulario para priorizar guardar y volver; salir con cambios debe conservar borrador o advertir descarte. Esto es un contrato de interacción propuesto, no funcionalidad construida.

## Medidas candidatas comunes

Lienzo objetivo móvil 390 × 844 CSS px; ancho fluido 320–430; padding lateral 20px, 16px en 320. Área de texto no se fija en altura. Cuerpo 16/24; ayuda 14/20; etiquetas de navegación 12/16; títulos 28/34; cantidades principales 36/40. Números tabulares; importes no se truncan. Botones principales de al menos 52px; otros objetivos ≥48 × 48px; separación mínima 8px. Navegación 72px más `safe-area-inset-bottom`. Acción de guardado sobre área segura; con teclado, campos y errores permanecen visibles mediante scroll. A 320, apilar cantidad y total si compiten; no reducir tamaño de letra para encajar. En escritorio, preservar ancho de lectura del flujo y abrir detalle al lado, sin convertirlo en tabla densa.

Son restricciones para refinar; **no mediciones certificadas de los píxeles generados**. Ver `candidates.json` para valores exactos de cada propuesta y [contrastes calculados](../audit/contrast.json). No se entrega un sistema final de tokens ni componentes de producción antes de elegir.

## Criterios de interacción y estados para la siguiente revisión

- Volver: preservar campos válidos. Descartar requiere acción explícita.
- Error de importe: «Escribe un total mayor que $0.»; mantener ingrediente, unidad y fecha.
- Campo numérico: `inputmode=decimal`; aceptar entrada local y validar en servidor. Moneda MXN informativa, no selector arbitrario.
- Envío: «Guardando compra…» y bloqueo de doble envío; nunca borrar formulario antes de confirmar éxito.
- Red: «No se guardó la compra. Revisa tu conexión e intenta de nuevo.»; reintentar con datos conservados, sin promesa de sincronización offline.
- Confirmación: «Compra registrada. Costo por gramo: $0.0420.»; no decir «inventario actualizado».
- Selector de unidad con dimensión compatible; no convertir litros a gramos sin dato de densidad autorizado.
- Éxito, error y selección con texto/icono además de color. Foco visible; orden lógico y nombre accesible.

## Cómo decidir sin elegir solo por color

Tres tareas para revisión del operador, aún **no realizadas como prueba de usabilidad**:

1. Sin explicación verbal, indicar dónde abrir la producción de 24 piezas.
2. Identificar quién debe más y a qué hora se entrega su pedido.
3. Ubicar cómo registrar 1kg por $42 sin calcular por cuenta propia.

Observar dudas y equivocaciones antes que preferencias estéticas. Cambiar de dirección si la persona necesita que se le explique qué tocar. Verificar la escala real en teléfono después de la elección; las láminas son evidencia de diseño, no una simulación táctil ni garantía de accesibilidad.

## Gate explícito

La siguiente acción pertenece al operador: **seleccionar A/B/C, combinar aspectos específicos o rechazar e iterar**. Seleccionar permite Phase 3 (refinamiento), no aceptación automática. Phase 4–6, brandbook final, exportaciones PWA/splash, biblioteca completa y handoff esperan aceptación visual explícita posterior.
