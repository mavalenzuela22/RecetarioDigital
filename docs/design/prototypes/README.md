# Referencia interactiva de diseño

Abrir [index.html](index.html) mediante el servidor local descrito en el [índice](../README.md). No depende de Laravel ni consulta información del negocio. Los datos son ficticios, residen en memoria y se reinician al recargar. Las pantallas son referencias del diseño final; su JavaScript no es una implementación de dominio.

| Recorrido | Interacción demostrada | Alcance pendiente de implementación |
|---|---|---|
| Hoy | Navegar a producción, compra y pedidos | Agregación y actualización del servidor |
| Compra | Normalizar unidades, validar, conservar campos y mostrar carga/éxito | Persistencia append-only, conversión y dinero exactos de servidor |
| Receta | Editar ingredientes/rendimiento y preservar borrador | Resolución real de costos y versionado; una edición de ingrediente muestra costo pendiente |
| Producto | ×2/2.5/3/3.5, precio manual, confirmar nuevo precio | Los costos adicionales y el switch son especímenes; no recalculan el modelo de dominio |
| Pedido | Cambiar cantidad/anticipo, recalcular saldo y validar captura | Caso de una línea; varias líneas están especificadas en flows.json |
| Cobro/entrega | Cobrar hasta saldo, pagar, entregar y confirmar por separado | Persistencia/idempotencia; cancelar muestra confirmación, no ejecuta una operación real |
| Producción | Ver Ana o Luis, vacío por fecha, iniciar preparación en el ejemplo | Agregación real por ventana y estado sincronizado |
| Histórico | Comparar fechas de referencia, error de rango, falta de referencia | Consultas as-of reales y toda la historia |

Las pantallas ilustran momentos distintos de la jornada. El catálogo de Pedidos y algunos resúmenes son datos de muestra, no vistas reactivas completas de un servidor. No copiar mensajes «de ejemplo» a la interfaz productiva. No inferir soporte offline ni guardado real del éxito visual.

Estados genéricos con el selector superior: normal, vacío, cargando, error. Estados específicos de cada recorrido, transiciones y texto final en [flows.json](../screens/flows.json), [microcopy.json](../brandbook/microcopy.json) y [SPECIFICATION.md](../components/SPECIFICATION.md).
