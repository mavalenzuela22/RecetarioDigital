# Pantallas y recorridos · A aceptada

Abrir [referencias navegables](../prototypes/index.html). Cada flujo tiene estructura, campos, estados, reglas y transiciones en [flows.json](flows.json); datos verificables en [fixtures.json](fixtures.json). Las capturas definitivas se guardan en `evidence/` y se enlazan desde el brandbook.

| Recorrido | Referencia | Estado inicial de muestra |
|---|---|---|
| Hoy | [Abrir](../prototypes/index.html?clean=1#hoy) | 24 piezas por preparar; dos entregas; saldo640; ganancia estimada480 |
| Compra | [Abrir](../prototypes/index.html?clean=1#compra) | 1kg de harina por42; normalización0.0420/g |
| Receta | [Abrir](../prototypes/index.html?clean=1#receta) | Edición de versión3, 12 piezas, costo receta16/pieza |
| Producto | [Abrir](../prototypes/index.html?clean=1#producto) | Costo atribuible20; precio40; margen50%; escenarios configurables |
| Pedido | [Abrir](../prototypes/index.html?clean=1#pedido) | 12 roles; total480; anticipo240; saldo240 |
| Cobro y entrega | [Abrir](../prototypes/index.html?clean=1#cobro) | Momento posterior: Ana lista para entregar y pago parcial |
| Producción | [Abrir](../prototypes/index.html?clean=1#produccion) | Agregación de24 piezas por producto y pedido |
| Histórico | [Abrir](../prototypes/index.html?clean=1#historial) | 18ago frente a18sep; costo18→20, precio40 constante |

Los estados temporales de distintas pantallas ilustran distintos momentos de la jornada, no un único servidor sincronizado. El prototipo mantiene algunas interacciones en memoria y reinicia al recargar. Sus cálculos son demostrativos; no reemplazan el dominio servidor. No hay persistencia ni integración con producción.

## Layout móvil

Ancho de flujo máximo480px; móvil320–430 fluido. Padding20, bajo360 padding16. A390 el orden de Hoy es exactamente producción, entregas, resumen, acciones. Scroll vertical conserva la barra inferior; nunca ocultar importes por falta de espacio. El inicio puede requerir desplazamiento para acciones secundarias según alto disponible; Ver producción permanece cerca del cierre y también se abre desde cada fila de producto. Formularios usan guardado inferior sticky y scroll; considerar teclado real y safe-area en implementación.

El render definitivo usa las fuentes incluidas, por eso puede diferir sutilmente de las letras aproximadas por ImageGen. Se mantiene jerarquía y carácter aceptados; el CSS/token final y los contratos medibles prevalecen sobre mediciones de la lámina generada.

## Estados demostrables

Cambiar el selector «Estado» en la referencia sin `clean=1` para normal/vacío/carga/error. Estos son especímenes genéricos del sistema; el texto final específico de cada recorrido está en flows.json y en el catálogo de microcopy. Los estados de error de campo se muestran enviando datos inválidos; abrir escenarios, confirmaciones, cobro y entrega para superposiciones concretas. No usar las rutas de referencia como rutas de producción.

## Handoff del comportamiento

La implementación debe completar las reglas del contrato para varias líneas, múltiples productos, resultados de búsqueda reales, rangos de fechas, creación desde vacío, permisos y datos de servidor. El prototipo ofrece un caso acotado para revisar diseño y transiciones. Que un caso ficticio funcione no acredita persistencia, servidor, exactitud financiera ni prueba física de teléfono.

## Capturas largas

Las referencias completas usan `?clean=1&capture=1`: el contenido es el mismo y la barra se coloca al final del documento para que la captura de toda la página no cubra campos intermedios. La referencia interactiva normal conserva acciones sticky. `hoy-viewport-390.png` y `hoy-viewport-390x900.png` muestran el viewport real sin ese ajuste. No trasladar el modo capture a producción.
