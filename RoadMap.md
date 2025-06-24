# Proyecciones y características deseables para Google Docs Importer

## Adecuación a CodeCanyon (Envato) - Prioridades

### 1. Auditoría y calidad (PRIORIDAD ALTA)
- Ejecutar auditoría automática con WP_DEBUG y PHP E_ALL, corrigiendo todos los warnings/notices.
- Ejecutar wpcs-for-envato y corregir issues de estándares.
- Revisar exhaustivamente sanitización, escape y validación de datos en todo el código.
- Revisar que no haya variables dentro de funciones de traducción.
- Validar HTML generado con W3C.
- Validar JS con JSHint y eliminar globales innecesarias.

### 2. Instalación/desinstalación (PRIORIDAD ALTA)
- [COMPLETADO] Proceso de desinstalación seguro: advertencia, confirmación y exportación de datos antes de borrar.
- [COMPLETADO] Exportación e importación de ajustes y de historial de correspondencia desde la interfaz de ajustes.
- [COMPLETADO] Eliminación completa de tablas, opciones y transients del plugin en la desinstalación.
- [COMPLETADO] Restauración sencilla tras reinstalación mediante importación de archivos JSON.
- [COMPLETADO] Mensajes de éxito/error mejorados usando SweetAlert (o alertas estándar si no está disponible).

### 3. Documentación y assets (PRIORIDAD ALTA)
- Crear documentación en inglés (PDF o HTML) explicando instalación, personalización y uso, con ejemplos.
- Preparar screenshots, banner, icono y gráficos para la página de producto.
- Documentar y asegurar licencias de todos los recursos incluidos.

### 4. Demo y marketing (PRIORIDAD MEDIA)
- Preparar y publicar un live demo funcional en un servidor propio.
- Añadir capturas destacadas y gráficos limpios para la página del producto.

### 5. Mejoras técnicas y seguridad (PRIORIDAD MEDIA)
- Revisar y mejorar el cacheo de llamadas a APIs externas si es necesario.
- Revisar que no se duplique jQuery ni otros scripts nativos.
- Revisar y limpiar CSS para evitar uso excesivo de `!important`.

### 6. Integración de licencias (PRIORIDAD BAJA)
- (Opcional) Integrar validación de purchase code con la Envato API.

---

### 7. UX y flujo editorial mejorado (PRIORIDAD ALTA)
- [COMPLETADO] El usuario puede preseleccionar autor, estado, tipo de post (incluyendo CPTs) y categoría principal antes de importar.
- [COMPLETADO] Las selecciones persisten entre recargas e importaciones (persistencia por sesión, sin perderse al navegar o importar).
- [COMPLETADO] Solo se muestra la taxonomía jerárquica principal (categoría) para cada tipo de post.
- [COMPLETADO] El formulario de selección es compacto y está sobre la tabla de documentos.
- [COMPLETADO] El botón de importación utiliza siempre las opciones seleccionadas.
- [COMPLETADO] Mensaje visual bajo las opciones indicando la persistencia.
- [COMPLETADO] Corrección de warnings de sesión y robustez en la gestión de estado.

## Otras mejoras y proyecciones (priorizadas para MVP)

### Prioridad MVP
1. **Soporte para imágenes y archivos embebidos:** Importar imágenes y archivos adjuntos de Google Docs y subirlos a la biblioteca de medios de WordPress, insertándolos correctamente en el contenido.

### Prioridad Media
1. **Importación masiva y automatizada:** Permitir seleccionar varios documentos y realizar la importación en lote, o incluso programar importaciones automáticas desde la carpeta de Google Drive.
2. **Sincronización de actualizaciones:** Si un documento de Google Docs cambia, poder actualizar el post correspondiente en WordPress con un solo clic o de forma automática, manteniendo un historial de cambios.

### Prioridad Baja
1. **Permisos y roles avanzados:** Controlar qué usuarios pueden importar, actualizar o eliminar documentos.
2. **Soporte multisitio:** Permitir funcionar en instalaciones multisitio de WordPress.
