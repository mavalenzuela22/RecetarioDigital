# Componentes · contrato v1.0

Autoridad visual: [catálogo renderizable](index.html), [registro medible](registry.json), [tokens](../tokens/tokens.json). La referencia navegable muestra estados con datos ficticios; producción implementará este contrato sobre React y primitivas accesibles. Los nombres de clases del prototipo no son una dependencia arquitectónica.

## Reglas universales

1. Texto principal 16/24; ayuda 14/20; etiquetas siempre persistentes. Cualquier texto ampliado puede crecer verticalmente; nunca ellipsis en importe, error, nombre de acción o saldo.
2. Objetivo mínimo 48×48px, acción principal 52px, 8px entre objetivos. El badge no interactivo puede medir28px; si se vuelve filtro, crece a48px.
3. Foco 2px cacao, separación3px; no se elimina outline sin reemplazo. Presionado usa primary-pressed, no hover como único feedback. Todo botón tiene tipo explícito.
4. Deshabilitado explica causa cerca del control; no ocultar errores deshabilitando el botón Guardar antes de validar. Loading bloquea duplicados y mantiene ancho/etiqueta de acción.
5. Estados identificados por texto además del color. Saldo vigente cacao; vencido requiere fecha/hora y etiqueta explícita. El rojo se reserva a error, pérdida o acción destructiva.

## Entradas

| Control | Valor / interacción | Validación | Accesibilidad |
|---|---|---|---|
| Texto | cadena sin HTML; trim al confirmar, no durante tecleo | requerido por flujo, límite visible | label asociado, nombre estable |
| Cantidad | texto con inputmode decimal; permitir entrada parcial durante edición | >0; máximo3 decimales para cantidad; piezas enteras cuando aplica | unidad visible junto al campo; error descrito |
| Dinero | texto decimal + sufijo MXN no editable | decimal exacto, máximo2 decimales de pago; >=0 o >0 según campo | anunciar moneda; no suprimir el signo de pérdida |
| Costo normalizado | solo lectura | 4 decimales mínimo, ampliar hasta6 si redondearía un valor positivo a0 | decir por gramo/ml/pieza; no pedirlo a usuaria |
| Select | unidad dentro de masa/volumen/conteo | sin convertir dimensiones incompatibles | nativo o primitiva con teclado, foco y selección anunciada |
| Fecha / hora | fecha local y hora del negocio, nunca convertir medianoche a otra fecha por UTC | requerido en pedido; fecha histórica permitida | etiqueta, ejemplo y mensaje específico |
| Search | filtrar nombres visibles; limpiar recupera todos | no hay error por cero coincidencias | etiqueta y cantidad de resultados en aria-live polite |
| Textarea | autosalto y scroll; nota opcional | límite2000 caracteres, mostrar restantes al acercarse | no reemplazar etiqueta por placeholder |

Formato de entrada: aceptar `42.50` o `42,50` como decimal si no hay separador de miles. Para entradas ambiguas como `1,234`, pedir «Escribe el importe sin separador de miles; por ejemplo, 1234.00». No convertir silenciosamente a otra cantidad. Dominio recibe string decimal o minor units; nunca float binario persistido.

Formulario: validar al salir después de interacción y al enviar; no mostrar errores antes del primer intento. Al enviar, enfocar primer campo inválido y mantener los demás. Con red fallida, mantener borrador y botón Reintentar. Si backend devuelve422, asociar errores por nombre; si409, mostrar «Este registro cambió. Revisa la información antes de guardar» con recuperación de borrador; si401, conservar borrador local de sesión y pedir acceso sin afirmar que se guardó. No colocar datos sensibles en URL. Borrador puede vivir en memoria del formulario; almacenamiento de sesión requiere decisión de implementación y aislamiento de usuario.

## Listas, dinero y estados

Filas: alto mínimo64, padding12 vertical, gap12; nombre y descripción a la izquierda, importe alineado a la derecha. A 320px, permitir importe en segunda línea antes que truncarlo. Presionado tiente suave. Si abre detalle, toda la fila es enlace y no contiene botones superpuestos; si tiene edición inline, la fila deja de ser enlace y cada acción tiene objetivo independiente.

Tarjeta: padding16, radio16, borde1 line; no tarjetas anidadas. Resumen de2 columnas, gap16, divisor1; una columna si texto ampliado exige reflow. Importes tabulares, `$20.00 MXN`; MXN puede declararse una vez por grupo siempre que no se mezcle moneda. Cero es `$0.00`; desconocido es `—` junto a causa, nunca0. Ganancia negativa conserva signo y etiqueta «Pérdida estimada». Margen no existe si precio=0; se muestra «No calculable con precio $0».

Estados exactos en [status-map.json](status-map.json). Entrega y pago son dos ejes. No transformar Confirmado en Pagado ni Entregado en Cobrado. Cancelado conserva historial y pagos; no implica devolución automática. Reembolso es una operación de negocio no definida aquí; informar al operador sin inventar contabilidad.

## Navegación y selección

Barra inferior: Hoy/Pedidos/Recetario/Más, cuatro objetivos iguales; icono24, etiqueta12/16, min52 por item dentro de barra72+safe-area. Activo añade fondo crema-terra y aria-current, mantiene icono de línea. Formularios y detalle usan Volver explícito; no necesitan barra inferior. Más contiene Ingredientes, Registrar compra e Historial, sin pantallas ajenas a v1.

Pestañas: rol tablist/tab/tabpanel con roving tabindex y flechas izquierda/derecha en implementación. El prototipo ilustra selección por toque/clic; no certifica el patrón de teclado completo. Segmentos de escenarios son botones aria-pressed; cambiar selección solo explora, no guarda un precio. Botón Usar este precio abre confirmación de consecuencias históricas.

## Superposiciones y mensajes

Hoja: ancho máximo480, altura máxima85dvh, scroll propio, padding20+safe-area. En móvil se ancla abajo, radio superior24; >=768 puede centrarse con radio24 en todos los bordes. Título y cierre48. Fondo scrim. Foco inicial en título o primer campo según intención; Tab no escapa; Escape cierra sin cambios permanentes. Restaurar foco al disparador. Si hay borrador sucio y cerrar implica perderlo, abrir diálogo Seguir editando/Descartar cambios; acción segura primero.

Confirmación destructiva: título con objeto, consecuencia específica, botón secundario seguro y botón danger explícito. Cancelar pedido no borra registros económicos; eliminar ingrediente con dependencias no se ofrece como borrado físico. En su lugar explicar relación o desactivar según dominio.

Toast:6s, polite status, máximo430 y margen16, sobre barra inferior a88+safe-area. El éxito también permanece en formulario/detalle; un toast no es única evidencia de pago. Errores son persistentes y no desaparecen por timeout. Carga: texto «Cargando…», skeleton oculto a lector, region aria-busy. Si tarda más de10s, mantener estado y ofrecer reintento seguro según petición; no fingir progreso porcentual.

## Vacíos y falta de datos

No registros: explicar qué crear y una acción. Sin resultados: conservar búsqueda y ofrecer Limpiar búsqueda. Sin histórico: explicar que no hay referencia de esa fecha, permitir otra fecha; no interpolar. Costo incompleto: mostrar ingredientes faltantes y suspender ganancia/margen totales. Sin imagen: fondo positive-soft + icono image24 y etiqueta Sin foto, sin obligación de subirla. No se utilizan ilustraciones decorativas adicionales de vacío.

## Variantes tipográficas de pantalla

Cabecera de flujo usa hero32/38 a partir de360px y title28/34 debajo; títulos de sección22/28, excepto el encabezado compacto de producción20/25.4. Label de campo DM Sans500 16/24; botón DM Sans700 16/22.4. Cifra dentro de equivalencias24/28.8; cifra principal28/33.6 y24/28.8 bajo360. Cantidad de fila32/35.2, en Hoy compacto30/33. Son variantes deliberadas, no nuevos estilos a inferir. El fondo de navegación seleccionada es nav-active.
