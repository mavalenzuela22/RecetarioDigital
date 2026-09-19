# Arranque y exportaciones

Maestro de composición: [splash-master.png](splash-master.png),1170×2532. La regla reproducible y tres tamaños están en [export-matrix.json](export-matrix.json) y [export.py](export.py). Fondo crema; icono EO80 CSS px, centro vertical43%; nombre18/24 a66px debajo del centro; lema14/20 a94px. Exportador Pillow toma los assets de marca y las fuentes locales. Ejecutar desde cualquier directorio: `python3 docs/design/splashes/export.py` desde raíz; escribe solo aquí.

Exportaciones:360×800@3,390×844@3,430×932@3, todas retrato, PNG opaco. Son matrices de referencia por viewport, no una lista cerrada de modelos de teléfono. Para otra medida, añadir fila con CSS width/height y DPR, exportar y verificar visualmente. No estirar la imagen para ocupar proporciones distintas. En landscape o escritorio, usar composición responsive centrada sin recortar marca.

PWA: la instalación y el splash del sistema dependen de navegador/plataforma. El manifest aporta name,short_name,theme_color,background_color y PNG192/512 any +512maskable. No afirmar que un PNG del paquete activa automáticamente un splash nativo. Para plataformas que admitan startup images explícitas, asociar export exacto con media query de viewport/DPR y orientación, bajo prueba manual de instalación. Nunca condicionar acceso a instalar la PWA.

Arranque dentro de la app: renderizar contenido cuanto antes; si existe carga real mostrar EO48 y «Cargando tu cocina…», role=status. No temporizador mínimo de marca. Si hay error, mostrar conexión/reintento con foco y texto persistente. No esconder formulario sin guardar al intentar actualizar el shell. El sistema no promete sincronización offline; no se agrega service worker ni código PWA en esta tarea.
