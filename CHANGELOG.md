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
