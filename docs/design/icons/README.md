# Iconografía de EmprendimientoOS

Familia **Lucide**, SVG oficiales, versión exacta en [package-source.json](package-source.json). Se incluye el subconjunto requerido y [licencia ISC/MIT](LICENSE.txt), obtenido del paquete lucide-static publicado en npm. Correspondencias completas en [map.json](map.json). Los trazos del archivo original son la autoridad: **24×24, stroke2, fill none, linecap/linejoin round**. Se adopta2px final por legibilidad; el1.75px de exploración queda superado.

Tamaños:20 auxiliar,24 control/navegación,40 vacío. Escalar proporcionalmente, nunca cambiar el viewBox. Objetivo independiente mínimo48; el icono no define el objetivo táctil. Mismo peso y caja en toda la interfaz. Activo mantiene geometría y añade fondo/etiqueta; no mezclar relleno y línea arbitrariamente. Colores: currentColor del control; cacao para información, terracota para acción, hierba para éxito, rojo oscuro para error.

En prototipo se usa mask CSS con los SVG locales para heredar color. En React puede utilizarse la misma geometría a través de lucide-react fijado a la versión correspondiente o un wrapper de SVG local. No introducir dependencia nueva sin el contrato de implementación. Con etiqueta visible el icono es aria-hidden; solo icono requiere aria-label en el botón. No usar el sombrero de chef para todo ni cambiar cobros por una metáfora culinaria.

[Catálogo visual](../brandbook/index.html#iconos). Fuente/licencia: https://lucide.dev/license. Conservar avisos al distribuir.
