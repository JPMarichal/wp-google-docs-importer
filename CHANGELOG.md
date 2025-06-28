# CHANGELOG

## 2025-06-23

### Instalación, desinstalación y restauración segura
- Proceso de desinstalación seguro: advertencia, confirmación y exportación de datos antes de borrar.
- Exportación e importación de ajustes y de historial de correspondencia desde la interfaz de ajustes.
- Eliminación completa de tablas, opciones y transients del plugin en la desinstalación.
- Restauración sencilla tras reinstalación mediante importación de archivos JSON.
- Mensajes de éxito/error mejorados usando SweetAlert (o alertas estándar si no está disponible).

### Soporte multi-carpeta
- Permitir conectar varias carpetas de Google Drive y seleccionar desde cuál importar.
- Implementado: El usuario puede cambiar la carpeta de Google Drive desde la interfaz principal usando el botón "Cambiar carpeta" y el Google Picker. El listado de documentos se actualiza automáticamente según la carpeta seleccionada.

### Opciones persistentes de importación y flujo editorial
- Ahora el usuario puede seleccionar y predefinir el autor, estado editorial, tipo de post (incluyendo CPTs) y la categoría principal antes de importar.
- Las selecciones persisten entre recargas e importaciones hasta que el usuario las cambie (persistencia por sesión).
- Solo se muestra la taxonomía jerárquica principal (categoría) para cada tipo de post.
- El formulario de selección es compacto y se encuentra sobre la tabla de documentos.
- El botón de importación utiliza siempre las opciones seleccionadas.
- Mensaje visual bajo las opciones indicando la persistencia.
- Corrección de warnings de sesión y robustez en la gestión de estado.

## 2025-06-27

### Auditoría y calidad
- Auditoría automática con WP_DEBUG y PHP E_ALL, corrigiendo todos los warnings/notices.
- Corrección de issues de estándares con wpcs-for-envato.
- Revisión exhaustiva de sanitización, escape y validación de datos en todo el código.
- Revisión de que no haya variables dentro de funciones de traducción.
- Validación de HTML generado con W3C.
- Validación de JS con JSHint y eliminación de globales innecesarias.

### Mejoras técnicas y seguridad
- Revisión y mejora del cacheo de llamadas a APIs externas.
- Revisión para evitar duplicidad de jQuery y otros scripts nativos.
- Limpieza de CSS para evitar uso excesivo de `!important`.
