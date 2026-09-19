> Registro histórico de esta fase. Estado final: A refinada aceptada; paquete completo. Véase docs/design/README.md.

# Revisión de entrega · Phase 1–2

> Registro histórico de Phase 2. Actualización: el operador seleccionó A. Ver [refinamiento actual](../refinement/A/REVIEW-R1.md); los estados de selección pendientes que siguen documentan el cierre original.

Fecha: 18 de septiembre de 2026. Estado: **lista para selección; no aceptada para implementar**.

## Cobertura entregada

| Requisito de Phase 2 | A | B | C |
|---|---|---|---|
| Paleta visual y valores hex | Sí | Sí | Sí |
| Tipografía y muestra | Sí | Sí | Sí |
| Superficies / controles | Sí | Sí | Sí |
| Foco / error / estado de muestra | Sí | Sí | Sí |
| Iconografía y dirección de imagen | Sí | Sí | Sí |
| Navegación inferior en español | Sí | Sí | Sí |
| Hoy con preparación, entrega, deuda y estimaciones | Sí | Sí | Sí |
| Compra: hechos humanos + costo derivado | Sí | Sí | Sí |
| Propuesta de interacción diferenciada | Agenda | Lista priorizada | Comandas + hoja |
| Ventajas y tradeoffs documentados | Sí | Sí | Sí |

## Revisión visual realizada

Se inspeccionaron las tres imágenes generadas y luego su carga y presentación en la galería local. Las tres abren completas a tamaño original; la galería escala proporcionalmente y ofrece enlaces al PNG. Los textos principales, importes, fecha, cantidades, costo normalizado y nombres son legibles a tamaño completo. La primera imagen de A se corrigió por introducir inventario y selector de moneda fuera del brief. Solo su revisión corregida forma parte del paquete.

Se verificó en navegador que los tres maestros y las tres capturas de auditoría cargan con `naturalWidth > 0`. Las láminas no son prototipos táctiles: no se declara que el teclado, foco, scroll interno, formularios o navegación de las pantallas dibujadas funcionen.

## Aspectos de refinamiento que no deben copiarse ciegamente

- A/B muestran «Listo a las» o «Listos a las» como objetivo horario; para implementación preferir «Entrega 12:30» y «Entrega 17:00», evitando sugerir un estado ya completado.
- Las muestras de C usan sinónimos «Inicio/Recetas» debajo de algunos iconos. La barra de las pantallas y el mapa común fijan **Hoy/Pedidos/Recetario/Más**. Unificar al refinar.
- Los generadores aproximan letras, curvas, pesos, espaciado e iconos. `candidates.json` expresa intención; no acredita dimensiones CSS de la imagen. Los contrastes se calcularon sobre pares hex candidatos, no sobre todos los píxeles, fotos o estados.
- C tiene más peso serif que el objetivo de cuerpo Manrope; la selección debe conservar la personalidad editorial pero comprobar lectura de cifras con la fuente real.
- A muestra presentación como selector. Permitir texto libre o sugerencias; «Bolsa» no puede convertirse en catálogo de presentaciones obligatorio.
- Los rojos de saldos en A/C sugieren énfasis; un saldo pendiente no equivale a vencido. Decidir un tratamiento neutral para saldo vigente en Phase 3, conservando texto explícito.
- Las fotos apoyan identidad; evitar que la usuaria necesite subir una imagen antes de registrar un producto. Definir placeholder y ausencia de imagen en el sistema seleccionado.
- Ninguna lámina prueba 320px, ampliación al 200%, lector de pantalla o uso con manos ocupadas. La revisión de 320px corresponde al shell, no a estos conceptos.

## Límites y gates

No se midió usabilidad con la usuaria final; no se hizo implementación, pruebas de dominio, build productivo ni validación formal Foundry. No se requieren para comparar tres láminas; tampoco se presentan como realizadas.

La auditoría contiene fortalezas, debilidades, jerarquía, uso móvil con una mano, accesibilidad, formularios ausentes, lectura operativa, carácter y riesgos. Las capturas documentan: 1) apertura, 2) destino de la acción principal y 3) límite de ancho con scrollbar. Su método y límites están explícitos.

El chequeo de integridad compara SHA-256 de todos los archivos inicialmente rastreados/no ignorados fuera de `docs/design/**`; comprueba además que no haya archivos nuevos no ignorados fuera del boundary. Ver [resultado mecánico](verification.json). Esto no certifica artefactos de ejecución ajenos ni reemplaza auditoría de gobernanza.

**Phase 1: entregada con límites de evidencia. Phase 2: tres propuestas entregadas. Phase 3: no iniciada. Phase 4–6: no iniciadas. Selección: pendiente. Aceptación: pendiente. TSK-002 completo: no. Handoff final: no listo.**
