# EmprendimientoOS · Fundación UX/UI

**TSK-002 · A — Cocina cálida artesanal · paquete de diseño completo y listo para handoff.**

La **usuaria primaria / product owner real ("la patrona") aceptó formalmente A — Cocina cálida artesanal** como dirección visual. El operador confirmó esa autoridad de aceptación y aprobó el cierre técnico de TSK-002. La respuesta «dale» autorizó avanzar del refinamiento al paquete completo. Esta aceptación corresponde al sistema de diseño y su handoff, no a un producto implementado. Se completaron las fases 4–6. B y C permanecen como alternativas históricas no elegidas; no se mezclaron estilos.

## Empezar aquí

- [Brandbook visual](brandbook/index.html): identidad, paleta, tipografía, componentes, ejemplos, ocho recorridos y recursos.
- [Referencia interactiva](prototypes/index.html): datos ficticios para revisar pantallas y estados.
- [Brandbook detallado](brandbook/BRANDBOOK.md) y [voz en español](brandbook/VOICE.md).
- [Catálogo de componentes](components/index.html), [especificación](components/SPECIFICATION.md) y [estados](components/status-map.json).
- [Tokens JSON](tokens/tokens.json), [variables CSS](tokens/tokens.css), [campos y recorridos](screens/flows.json) y [datos de ejemplo](screens/fixtures.json).
- [Guía React/Tailwind/Inertia](implementation-guide/IMPLEMENTATION.md), [manifiesto de assets](implementation-guide/asset-manifest.json) y [checklist completo](implementation-guide/HANDOFF-CHECKLIST.md).
- [Revisión visual](implementation-guide/design-qa.md), [verificación cuantitativa](implementation-guide/verification.json) y [cierre](CLOSURE.md).

## Cómo verlo

Los HTML pueden abrirse localmente. Para revisar todas las referencias de forma consistente, desde la raíz ejecutar `python3 docs/design/audit/serve.py` y abrir `http://127.0.0.1:4327/brandbook/index.html`. Servidor solo local; expone docs/design y assets compilados existentes para la auditoría. No inicia Laravel ni accede a datos de negocio. Detener con Ctrl+C.

El paquete es autocontenido: fuentes locales con licencia,33 iconos SVG, maestro culinario, marca EO, iconos192/512/maskable, favicon, splash maestro y tres exports. Los generadores opcionales están dentro del paquete; el orden es tokens/build.py → splashes/export.py → components/build_catalog.py → screens/build_contracts.py → brandbook/build.py → implementation-guide/build_manifest.py. Las capturas se realizan en navegador; los scripts no simulan esa evidencia.

## Autoridad y uso

Las autoridades de producto, arquitectura y UX del repositorio prevalecen. Dentro del paquete: tokens para valores, contratos y especificaciones para comportamiento, referencia renderizada/capturas para composición. Las láminas generadas son historia de la dirección y no sustituyen esos valores. El prototipo demuestra un caso; no copiar su JavaScript como lógica financiera. Ver [límites explícitos](prototypes/README.md).

Los recorridos cubren Hoy, compra, receta/rendimiento, producto/costo/precio, pedido, cobro/entrega, producción e histórico. El dinero se explica con desglose bajo demanda; pago y entrega son estados independientes; falta de costo no se presenta como cero.

## Historia preservada

- [Auditoría Phase1](audit/PHASE-1-AUDIT.md), con límites de inspección del shell.
- [Tres direcciones originales](index.html) y [comparación](exploration/COMPARISON.md).
- [A refinada aceptada](refinement/A/board-r1.png), [registro de revisión](refinement/A/REVIEW-R1.md).
- [Estado legible por máquina](exploration/status.json), procedencia de [exploración](imagery/PROVENANCE.md) y [foto final](imagery/cinnamon-rolls-source.md).

Todo cambio de esta tarea está dentro de `docs/design/**`. Se preservaron los73 archivos anteriores fuera del boundary y el delta previo. No hubo staging, commit, push, PR, merge ni implementación productiva.

**Resultado: completo como fundamento de diseño.** Handoff listo; aceptación formal de gobernanza y pruebas de aplicación/dispositivo físico no se atribuyen a esta entrega.
