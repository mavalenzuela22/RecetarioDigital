# Checklist de entrega · TSK-002

Resultado: **listo para implementación bajo un contrato posterior**. Este checklist acepta la completitud del paquete de diseño; no sustituye aceptación formal de Foundry, implementación de dominios ni validación en dispositivos físicos.

| Criterio del documento rector | Evidencia | Resultado |
|---|---|---|
| 1. Dirección aceptada | **A — Cocina cálida artesanal** aceptada formalmente por la usuaria primaria / product owner real ("la patrona"); el operador confirmó esa autoridad de aceptación y aprobó el cierre técnico del TSK-002. La aceptación corresponde al sistema de diseño, no a un producto implementado. | Cumplido |
| 2. Brandbook y sistema | [Visual](../brandbook/index.html), [especificación](../brandbook/BRANDBOOK.md), [voz](../brandbook/VOICE.md) | Cumplido |
| 3. Ocho recorridos en español | [Índice de pantallas](../screens/README.md), [capturas](../brandbook/index.html#pantallas), [contratos](../screens/flows.json) | Cumplido |
| 4. Tokens/componentes/estados | [Tokens](../tokens/tokens.json), [CSS](../tokens/tokens.css), [catálogo](../components/index.html), [registro](../components/registry.json) | Cumplido |
| 5. Assets y fuentes reproducibles | [Manifiesto](asset-manifest.json), maestros, exportadores y licencias locales | Cumplido |
| 6. Icono/PWA/splash | [Manifest de ejemplo](manifest.example.json), [splashes](../splashes/README.md), [matriz](../splashes/export-matrix.json) | Cumplido |
| 7. Microcopy y layout es-MX | [Catálogo de textos](../brandbook/microcopy.json), referencias reales del navegador | Cumplido |
| 8. Mobile-first | Ocho flujos a320; capturas390; objetivos48/52; [mediciones](mobile-320-checks.json) | Cumplido en referencia local |
| 9. Stack vigente | [Mapeo React/Tailwind/Inertia](IMPLEMENTATION.md), [tipos](contracts.ts), [CSS Tailwind](tailwind-reference.css) | Cumplido |
| 10. Handoff autónomo | Orden de lectura, precedencia, valores, estados, fixtures, assets y límites del prototipo | Cumplido |
| 11. Producción intacta | [Verificación](verification.json),73 hashes anteriores preservados | Cumplido |

## Puerta de entrada para una pantalla nueva

1. Consultar autoridades de producto y determinar recorrido/entidad sin añadir módulos.
2. Elegir patrón del catálogo; usar tokens y variantes cuantitativas, nunca medir una lámina generada.
3. Usar el contrato de campos/estados, etiquetas españolas y catálogo de iconos.
4. Recibir cálculos del servidor con completitud y referencia histórica; dinero y entrega mantienen estados separados.
5. Construir dentro del stack existente, según el nuevo boundary de implementación.
6. Probar errores sin pérdida de captura, teclado/foco,320/390/768/1200, área segura y texto ampliado; verificar dinero/historia con pruebas de dominio.

## Lo que no se afirma

No se ejecutaron pruebas de backend ni se implementaron los dominios. No se certifica WCAG completa, soporte real PWA, VoiceOver/TalkBack, teclado físico/virtual ni experiencia de una semana. Esas verificaciones pertenecen a implementación. No hay decisiones visuales bloqueantes pendientes; las limitaciones de demostración están en [prototypes/README.md](../prototypes/README.md).
