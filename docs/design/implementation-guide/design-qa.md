# Revisión visual final · A — Cocina cálida artesanal

**final result: passed**

Resultado aplicable al paquete de diseño y su referencia navegable local. Sin hallazgos P0/P1/P2 abiertos después de las correcciones siguientes. No es validación de una aplicación de producción ni certificación integral de accesibilidad.

## Fuente y comparación

- Fuente visual aceptada: [board-r1.png](../refinement/A/board-r1.png),1448×1086 px. Lámina generada con dos pantallas; no captura de un dispositivo real.
- Render comparado: [Hoy390×900](../screens/evidence/hoy-viewport-390x900.png),390×900 px, viewport CSS390×900, DPR1; mismo caso inicial, día18sep,24 piezas,Ana/Luis,saldo640 y ganancia480. Sin marco del navegador ni dispositivo.
- [Comparación conjunta](comparison.html) y [captura conjunta](../screens/evidence/qa-comparison.png): recorte izquierdo de fuente x42,y76,423×976, escalado por390/423; fondo de lámina fuera de la pantalla excluido. El borde del recorte puede variar1px. No se afirma equivalencia píxel a píxel.
- La misma evidencia incluye detalle conjunto ampliado de importes y acciones. La navegación se evalúa en la vista completa a ancho390. Tipos, etiquetas y números son legibles a esta escala.
- [Hoy390×844](../screens/evidence/hoy-viewport-390.png) conserva el viewport habitual más corto: la acción secundaria requiere desplazar; no se interpreta esa diferencia de altura como cambio de jerarquía. La barra es sticky y todos los controles se alcanzan con scroll.
- Los ocho [renders finales](../brandbook/index.html#pantallas) son capturas reales del HTML, no imágenes generadas. Para páginas largas se documenta el modo capture que pone acciones al final, evitando que una barra sticky tape un campo al componer la captura completa.

## Historial de hallazgos y correcciones

| Prioridad | Hallazgo y efecto | Corrección | Evidencia posterior |
|---|---|---|---|
| P2 | Wordmark del brandbook imponía400px dentro de una columna móvil; a320 el documento llegaba a520px de ancho | Columna con min-width0 e imagen width100%; grilla de capturas limitada al ancho disponible | [Marca a320](../screens/evidence/brandbook-320-fixed.png); scrollWidth305,clientWidth305,viewport320 con scrollbar15 |
| P2 | Control de producto activo heredaba azul del navegador ajeno a la paleta | accent-color primary, conservando control nativo y nombre accesible | [Producto final](../screens/evidence/producto-390.png) |
| P2 | Capturas largas de formularios mantenían barra sticky en medio y tapaban campos, aunque se alcanzaban al desplazar el prototipo | Modo documental capture, sin modificar comportamiento normal; nuevas capturas de los ocho flujos | [Pedido final](../screens/evidence/pedido-390.png), [Receta final](../screens/evidence/receta-390.png), [Compra final](../screens/evidence/compra-390.png) |
| P2 | Detalle de producción para Luis abría Ana y cambio de fecha no representaba ausencia de pedidos | Selección contextual por fila y vacío por fecha | Navegador: Luis/17:00; [vacío](../screens/evidence/produccion-empty-390.png) |
| P2 | Cierre del catálogo y script de diálogo necesitaban corregir área/escape del texto generado | Botón Cerrar de ancho natural, script validado y diálogo abierto/cerrado en navegador | [Hoja](../screens/evidence/components-sheet-390.png), [confirmación destructiva](../screens/evidence/destructive-dialog-390.png) |

La revisión final conserva la estructura aceptada: marca discreta, producción antes de entregas, resumen en dos columnas, terracota en la acción, navegación de cuatro destinos. No quedan correcciones bloqueantes observadas en el alcance revisado.

## Cinco superficies obligatorias

| Superficie | Evaluación final |
|---|---|
| Fuentes y tipografía | Fraunces real para títulos/cifras, DM Sans real para etiquetas/cuerpo. Las fuentes se distribuyen localmente; se revisó su render y jerarquía. Variantes cuantitativas en SPECIFICATION.md. La forma exacta de letras rasterizadas de ImageGen no es fuente tipográfica. Español real envuelve sin recorte en recorridos320/390. |
| Espaciado y composición | Orden, márgenes, radios, resumen y navegación conservan la dirección. Columna max480 y padding20/16. Filas y controles generosos; formularios largos desplazan. Diferencias de densidad frente al moodboard provienen de texto real/controles nativos y están explicitadas; no se comprime texto para fingir que todo cabe. |
| Colores y tokens | Paleta exacta crema/cacao/terracota/hierba; dinero pendiente neutral, éxito etiquetado. Diez pares medidos cumplen objetivos4.5/3; input-border distinto a divisor. No usar los píxeles aproximados de la lámina como muestra exacta. |
| Imágenes y assets | Maestro independiente de roles de canela,1448×1086, mantiene alimento,luz y cerámica de la dirección. Recorte cover explícito. Iconos Lucide oficiales, no pictogramas dibujados para sustituir assets. EO es tratamiento tipográfico; fuentes y licencias incluidas. Foto sintética identificada como referencia. La hoja ornamental del moodboard se excluye deliberadamente conforme al brandbook. |
| Copy y contenido | Navegación, formularios, costos, saldos y errores en español es-MX. Fechas/horas nativas pueden reflejar configuración del navegador; fuera del editor la referencia usa24h. Sin estereotipos ni chistes de cobro. Mensajes «de ejemplo» solo pertenecen al prototipo y no se migran. |

## Interacciones y estados observados

- Compra: total0 activa error, conserva ingrediente/cantidad; al corregir42 muestra guardando y confirmación demostrativa. Equivalencia1kg→1000g y0.0420/g.
- Producto: ×3 muestra60,ganancia40,margen66.7%; precio actual sigue40 hasta confirmar. Abrir/cerrar confirmación de cambio de precio.
- Pedido: cantidad12→13 actualiza total480→520 y saldo240→280; el control no cambia precio acordado.
- Cobro: confirmar240 deja Pagado/saldo0 y mantiene Listo para entregar. Confirmar entrega después cambia solo fulfillment a Entregado. [Resultado](../screens/evidence/cobro-entregado-pagado-390.png).
- Producción: detalle de cada persona correcto, fecha sin pedidos muestra vacío, preparación usa confirmación.
- Catálogo: edición en hoja, aplicar/cerrar, confirmación destructiva, presionado/foco/disabled/loading, vacíos, errores y avisos.24 grupos visuales.
- Histórico: comparación explícita, referencia faltante y rango inválido; no se inventan registros para otra fecha.
- Consola del navegador de revisión: consulta de nivel error sin entradas al cierre de los ocho recorridos. Sintaxis JavaScript de prototipo y catálogo comprobada. No se ejecutó suite productiva.

## Responsive y límites

Ocho recorridos medidos a320 sin desbordamiento horizontal ni controles de altura menor al mínimo inspeccionado: [medición móvil](mobile-320-checks.json). Ocho medidos a768: [medición tablet](tablet-checks.json), scrollWidth igual a clientWidth. Brandbook y catálogo inspeccionados a320 y1200, [catálogo](../screens/evidence/components-1200.png), [portada](../screens/evidence/brandbook-1200.png). Fotos visibles cargadas sin errores en la revisión del brandbook.

Pendientes de implementación, no hallazgos visuales abiertos: teclado/safe-area en teléfono físico, texto200%, lector de pantalla, instalación PWA, recuperación de red real, dinero decimal autoritativo y consistencia entre dominios. El prototipo no implementa backend; su alcance exacto se declara en [README de referencia](../prototypes/README.md).

## Checklist posterior

1. Conservar tokens, fuentes y assets suministrados, y usar contratos de campos/estados.
2. Implementar lógica/servidor bajo un nuevo boundary autorizado.
3. Verificar los gates físicos y de dominio del handoff antes de declarar producto listo.

Decisiones visuales bloqueantes pendientes: ninguna. Refinamiento P3 posible: evaluar tras uso físico si conviene reducir la cantidad de contenido inicial en pantallas de poca altura, sin quitar cifras ni objetivos táctiles.
