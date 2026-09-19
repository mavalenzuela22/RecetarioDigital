# Procedencia y límites de las imágenes

Las tres láminas se generaron con la herramienta integrada `image_gen`, una dirección por generación. Se adjuntó la captura actual del shell `audit/evidence/01-shell-390.png` como contexto, no como objetivo a clonar. Los prompts completos y la corrección están en [PROMPTS.md](PROMPTS.md).

Se pidieron tableros de 2048 × 1536 con dos superficies móviles cuyo objetivo de diseño es 390 × 844 CSS px. **El motor devolvió maestros de 1448 × 1086 px.** Se conservan esos originales sin ampliación artificial, recortes ni compresión adicional. Las dimensiones reales y SHA-256 están en [manifest.json](manifest.json). La resolución basta para comparar las direcciones; no equivale a exportaciones de pantalla 1:1 ni a assets listos para producción.

- A: se rechazó la primera versión porque inventó la frase «inventario de cocina» y un selector MXN. Una edición con la misma herramienta corrigió esa frase, dos etiquetas y el selector. `exploration/A/board.png` contiene exclusivamente la versión corregida. No es una cuarta dirección.
- B: generación independiente, lista operativa y compra compacta.
- C: generación independiente, comandas y compra con hoja de edición.

Las fotos son muestras generadas de dirección fotográfica, integradas en las láminas. No representan productos reales, no se usan para comercializar comida y no son archivos maestros de fotografía independientes. No se han extraído como assets de aplicación. Tipografía e iconos son aproximaciones del generador: las familias candidatas y el mapeo de iconos son propuestas para refinar, no archivos tipográficos ni una licencia de assets adjunta.

El brief heredado del shell conserva EmprendimientoOS y su tono en español. Monogramas, encabezados y lemas de las láminas no constituyen logotipos o claims aprobados. El material final de marca, licencias de fuentes/iconos, archivos vectoriales, fotos independientes y exportaciones corresponden a la dirección aceptada en fases posteriores.

Las capturas de auditoría provienen del navegador integrado en esta ejecución. Son capturas del shell servido con assets compilados preexistentes y un contenedor estático Inertia, no evidencia de backend ni dominio funcionando.

## Phase 3 · A seleccionada

El operador eligió A. Se editó el maestro de A mediante image_gen y se conservó el original. La revisión está en refinement/A/board-r1.png; el prompt, los cambios y el gate pendiente se documentan junto a ella. No es una cuarta dirección ni aceptación final.
