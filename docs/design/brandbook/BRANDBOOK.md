# EmprendimientoOS · Brandbook v1.0

**Dirección A — Cocina cálida artesanal.** Seleccionada por el operador y refinamiento1 aceptado con «dale» en respuesta a la solicitud de avanzar al paquete completo. Referencia aceptada: [A refinada](../refinement/A/board-r1.png). B y C se conservan como exploración no elegida; no se mezclaron estilos.

Este documento y su [edición visual](index.html), los tokens, componentes y contratos de pantalla forman la autoridad de diseño. Prevalecen siempre las autoridades de producto/arquitectura del repositorio. Ante discrepancia interna: valores en tokens.json → comportamiento en flows.json y especificación de componentes → CSS renderizado → capturas finales → imagen exploratoria. No interpretar un artefacto histórico como especificación vigente.

## Promesa, personalidad y límites

**«Tu cocina. Tu negocio.»** Entender cuánto cuesta, qué preparar y entregar, y qué queda por cobrar. La voz acompaña a una persona capaz: concreta, cálida y respetuosa. El orgullo del oficio se expresa en cuidado editorial, ingredientes reconocibles y claridad económica. No se usa lenguaje infantil, estereotipos de género, chistes de deuda, caricaturas de barrio ni consejos de contabilidad disfrazados de UX.

Principios: trabajo antes que promoción; respuesta antes que desglose; hechos humanos antes que cálculo; historia económica conservada; calidez sin bajo contraste; una acción principal por contexto. La fotografía nunca compite con dinero, cantidad ni error. No hay tablero ejecutivo de gráficas al abrir.

## Marca tipográfica

Nombre de producto exacto: **EmprendimientoOS**. RecetarioDigital es el repositorio, no el nombre visible de la app. Wordmark en DM Sans600, archivo SVG con fuente incorporada. Monograma EO en DM Sans700, crema sobre cacao; no tiene un significado pictográfico adicional. Se adopta como tratamiento tipográfico del monograma ya presente en A, no como nueva dirección de identidad.

Assets en `assets/brand/`. Wordmark ancho mínimo144px, relación520:64 sin distorsión. Área libre mínima: altura de la E, aproximadamente0.5× alto del contenedor. Monograma mínimo24px en favicon,36px en cabecera; contenedor de cabecera36 y radio12. App icon maestro1024 cuadrado y opaco; fondo hasta el borde. No recortar el monograma, recolorear por temporada, añadir utensilios ni encerrar todo el nombre en el icono. La esquina del icono instalado la aplica la plataforma; no hornearla en el PNG maskable.

## Color

Valores exactos y papeles en [tokens.json](../tokens/tokens.json). Canvas crema #FFF8ED, paper blanco #FFFFFF, ink cacao #38291F; primary terracota #93482F; positive hierba #466344. Muted #6C5B4F sigue siendo texto legible, no opacidad baja. Input-border #8A7565 delimita controles; line #DCCFC0 separa contenido pero nunca es el único borde significativo de un campo.

Error #922E25 sobre #FFF0EB; advertencia #735211 sobre #FFF0C7; información #315C68 sobre #E9F0F3. Estado de pago pendiente usa advertencia con texto; saldo monetario ordinario sigue cacao. Marca terracota no significa deuda vencida. Fondo #EAF0E5 para equivalencias/costo derivado. No usar beige sobre beige con poco contraste ni verde como única evidencia de guardado.

## Tipografía y números

Fraunces600 en títulos/cifras destacadas: ejes opsz32, SOFT0, WONK0; DM Sans400/500/600/700 en interfaz, eje óptico fijo14. Fuentes variables incluidas con licencias OFL. Body16/24, label16/24 (500), help14/20, section22/28, title28/34, hero32/38, money28/34, navegación12/16. Tamaños en rem y fuente raíz16; respetar preferencias de texto. No usar Fraunces en párrafos ni fuentes manuscritas para importes.

Números tabulares donde la fuente lo soporte. Importes en es-MX, MXN explícito en grupo, dos decimales en edición/detalle. Resumen de día permite enteros exactos; si existen centavos, mostrarlos. Costo por g/ml conserva4–6 decimales para no aparentar cero. No achicar centavos ni unidad debajo de12px. Detalle económico en [componentes](../components/SPECIFICATION.md).

## Espacio, forma y jerarquía

Escala0/4/8/12/16/20/24/32/40/48/64px. Padding de pantalla20; a ancho<360,16. Sección20; hoy compacto12–16. Tarjeta16; controles12; chip999; hoja24. Borde1, foco2 separado3. Elevación ligera0 4 16 rgba(cacao,.05); hoja0 -8 32 rgba(cacao,.15). No apilar sombras ni tarjetas dentro de tarjetas. Delimitar por espacio y reglas antes que cajas.

Diseño fluido desde320. Columna de captura max480; >=768 expansión contextual o columna centrada, sin estirar campos a todo el escritorio; >=1200 listado/detalle opcional con ancho total1120. La navegación y los datos siguen el mismo modelo móvil. La versión de referencia se centra para inspección; no introduce un framework diferente.

## Accesibilidad y uso físico

Objetivos48 mínimo, primarios52; separación8. Foco visible y orden lógico. Etiquetas permanentes, no hover obligatorio. Anunciar cambios relevantes con aria-live polite, errores persistentes asociados al campo, regiones de carga aria-busy. Contraste de texto objetivo4.5:1 normal,3:1 grande; controles y foco3:1. Resultado de pares exactos en [verificación](../implementation-guide/verification.json); no se reclama conformidad WCAG integral.

Área segura inferior adicional a acciones/barra, `viewport-fit=cover`. Con teclado, permitir scroll y llevar al campo/error, no comprimir la tipografía. No basar la operación en swipe. No bloqueos por foto opcional. Con una mano, priorizar acciones inferiores y tarjetas amplias. Quedan pruebas físicas de teléfono, texto ampliado, lector y teclado como gates de implementación, no decisiones estéticas abiertas.

## Movimiento

Presionado80ms, cambios160ms, hojas220ms; easing cubic-bezier(0.2,0,0,1). Traslación de presionado1px máximo, sin rebote. Skeleton estático; loader de rotación1s solo con movimiento permitido. Preferencia reduced-motion:0ms, sin animación, etiqueta de estado intacta. Ningún evento de éxito depende de animación. No retrasar render por un splash ornamental.

## Imágenes y motivos culinarios

Foto maestra [roles de canela](../imagery/cinnamon-rolls-master.png), creada para esta dirección; luz lateral, plato crema, colores veraces, sin texto incorporado. Hero350×180 y thumbnail104×64, cover, posición65–70% horizontal/50% vertical. Maestro1448×1086 se conserva sin escalarlo como falsa alta resolución. No es fotografía de un producto real; al operar, usar imágenes de la usuaria por almacenamiento Laravel, no exigirlas.

Placeholder: icono Lucide image sobre positive-soft, «Sin foto». Vacíos: icono funcional, título y una acción, sin ilustración adicional. No fondos de papel falso, sombras de utensilios, gradientes ni confeti. La referencia de ficha de cocina vive en tipografía, separadores y superficies. El acento hoja ornamental de la lámina inicial no es necesario para operación y no se adopta como recurso independiente.

## Correcto / incorrecto

La [edición visual](index.html#uso) demuestra comparaciones, no solo prohibiciones:

- Saldo vigente cacao + fecha frente a saldo rojo sin vencimiento.
- $0.0420/g frente a $0.04/g que altera la información.
- Ganancia estimada frente a efectivo cobrado, presentados como magnitudes distintas.
- Error junto al campo y datos conservados frente a un mensaje genérico que borra la captura.
- Un costo principal y desglose bajo demanda frente a tres tarjetas anidadas.

## Paquete de marca y arranque

Se incluyen maestro EO PNG/SVG,192/512 para PWA, maskable512,180 para acceso Apple, favicon ICO y32/48 PNG, wordmark SVG, composición splash y matriz de exportación. Las capturas de splash son referencias/exportaciones, no promesa de que todo navegador PWA muestre una pantalla de arranque idéntica. La app carga directamente; si hay espera real, mostrar monograma y «Cargando tu cocina…» con salida de error/reintento. Detalle en [splashes](../splashes/README.md).
