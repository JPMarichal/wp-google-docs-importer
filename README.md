# Google Docs Importer for WordPress

Google Docs Importer conecta tu Google Drive con WordPress y permite importar documentos de Google Docs como entradas estándar, manteniendo el formato y facilitando el flujo editorial. Puedes elegir el tipo de contenido (post, página o cualquier Custom Post Type), el autor, el estado editorial y la categoría principal antes de importar, todo desde un formulario compacto y persistente sobre la tabla de documentos. Estas selecciones se mantienen entre recargas e importaciones, agilizando el trabajo editorial, especialmente en equipos o sitios con varios tipos de contenido.

## Tabla de Contenidos
- [Instalación y configuración](#instalación-y-configuración)
- [Uso básico](#uso-básico)
- [Exportar e importar datos](#exportar-e-importar-datos)
- [Desinstalación](#desinstalación)
- [Restauración](#restauración)
- [Características principales](#características-principales)
- [Idiomas soportados](#idiomas-soportados)
- [Filtros y acciones para desarrolladores](#filtros-y-acciones-para-desarrolladores)
- [Notas y advertencias](#notas-y-advertencias)
- [Contribuciones](#contribuciones)
- [Licencia](#licencia)

## Instalación y configuración

Antes de instalar el plugin, necesitas crear credenciales de Google API para permitir la conexión segura entre tu sitio WordPress y Google Drive/Docs:

1. Accede a la [Consola de Google Cloud](https://console.cloud.google.com/).
2. Crea un nuevo proyecto (o selecciona uno existente).
3. Habilita las APIs de **Google Drive** y **Google Docs** para tu proyecto.
4. Copia la siguiente Redirect URI provisional y pégala en Google Cloud al crear las credenciales OAuth 2.0 (puedes ajustarla según tu dominio):

   ```
   https://TUSITIO.COM/wp-admin/admin-post.php?action=g2wpi_oauth_callback
   ```
   > Una vez instalado el plugin, podrás ver y copiar la Redirect URI exacta desde la pantalla de ajustes.
5. Ve a "Credenciales" y crea un **ID de cliente OAuth 2.0** (tipo "Aplicación web"). Añade la URL de tu sitio WordPress como origen autorizado y la Redirect URI anterior.
6. Crea también una **API Key** desde la consola de Google Cloud (menú de credenciales).
7. Descarga el archivo JSON de credenciales o copia el **Client ID**, **Client Secret** y la **API Key**.
8. Sube la carpeta `google-docs-importer` a `/wp-content/plugins/`.
9. Activa el plugin en el menú de plugins de WordPress.
10. El plugin creará automáticamente las tablas y opciones necesarias para funcionar.
11. Ve a los ajustes del plugin e introduce los tres datos requeridos:
    - **Client ID**
    - **Client Secret**
    - **API Key**
12. (Opcional) Verifica y copia la **Redirect URI** que aparece en la pantalla de ajustes y, si es necesario, actualízala en Google Cloud.
13. Guarda los cambios.
14. Selecciona la carpeta de Google Drive desde la pantalla principal usando el botón "Cambiar carpeta".

## Uso básico
- Importa documentos de Google Docs como posts, páginas o cualquier Custom Post Type público.
- Antes de importar, puedes preseleccionar el autor, el estado editorial (borrador, pendiente, publicado), el tipo de post y la categoría principal. Estas opciones persisten hasta que las cambies.
- Solo se muestra la taxonomía jerárquica principal (categoría) para cada tipo de post.
- Los documentos importados quedan como borrador (o el estado que elijas), listos para revisión y publicación.
- Puedes cambiar el tipo de post manualmente después de la importación si lo deseas.

## Exportar e importar datos
Antes de desinstalar el plugin, puedes exportar:
- **Ajustes**: Descarga un archivo JSON con toda la configuración del plugin.
- **Historial**: Descarga un archivo JSON con la correspondencia entre documentos de Google Docs y posts importados.

Ambas opciones están disponibles en la página de ajustes del plugin.

Para restaurar:
1. Instala y activa el plugin normalmente.
2. Ve a los ajustes del plugin.
3. Usa los formularios para **importar los ajustes** y/o **el historial** desde los archivos JSON exportados previamente.

**Notas:**
- Si solo importas los ajustes, tendrás la configuración pero no el historial de importaciones.
- Si solo importas el historial, tendrás la correspondencia pero deberás volver a configurar las credenciales.
- Puedes importar ambos archivos en cualquier orden.

## Desinstalación
1. Ve a los ajustes del plugin y haz clic en **"Confirmar desinstalación"**.
2. Exporta tus datos si lo deseas.
3. Desinstala el plugin desde el panel de plugins de WordPress.

**Importante:**
- La desinstalación elimina todas las tablas, opciones y datos internos del plugin. No queda rastro en la base de datos.
- Los posts de WordPress importados NO se eliminan.

## Restauración
Sigue los pasos de la sección "Exportar e importar datos" para restaurar la configuración y el historial tras una reinstalación.

## Características principales
- Importación directa y masiva desde Google Drive a WordPress, sin copiar/pegar ni perder formato.
- Selección persistente de autor, estado editorial, tipo de post (incluyendo Custom Post Types) y categoría principal antes de importar, desde un formulario compacto sobre la tabla de documentos.
- Las opciones seleccionadas se mantienen entre recargas e importaciones, facilitando un flujo editorial ágil y consistente.
- Sincronización y control de duplicados: nunca importas dos veces el mismo documento.
- Workflow editorial optimizado: los documentos importados quedan en el estado que elijas (borrador, pendiente, publicado).
- Interfaz moderna y amigable: búsqueda instantánea, paginación, acciones rápidas (ver, editar, eliminar, importar).
- Visualización de la categoría o término principal del post importado.
- Seguimiento del estado editorial de cada documento (importado, borrador, publicado, etc.).
- Acciones rápidas desde la misma pantalla.
- Pensado para equipos y flujos colaborativos.
- Conexión segura a Google Drive mediante OAuth 2.0.
- Importación con formato (negritas, listas, encabezados, etc.).
- Detección automática y visualización de la categoría principal o término jerárquico.

## Idiomas soportados
El plugin detecta automáticamente el idioma configurado en WordPress y mostrará la interfaz en ese idioma si existe traducción disponible. Actualmente está preparado para:
- Español (es_ES)
- Inglés (en_US)
- Alemán (de_DE)
- Francés (fr_FR)
- Portugués (pt_PT)

Si el idioma de tu sitio no está en la lista, la interfaz aparecerá en inglés por defecto.

## Filtros y acciones para desarrolladores
### Filtros
- `gd_importer/post_args` - Modifica los argumentos del post antes de crearlo/actualizarlo
- `gd_importer/document_content` - Modifica el contenido antes de guardarlo
- `gd_importer/document_title` - Modifica el título antes de guardarlo

### Acciones
- `gd_importer/post_imported` - Tras importar un documento
- `gd_importer/post_updated` - Tras actualizar un post existente
- `gd_importer/before_import` - Antes de importar un documento
- `gd_importer/after_import` - Después de importar (éxito o fallo)

## Notas y advertencias
- El plugin utiliza SweetAlert para mostrar mensajes claros de éxito o error al importar/exportar datos. Si SweetAlert no está disponible, se usan alertas estándar.
- Cuando conectas el plugin con tu cuenta de Google, la sesión suele mantenerse activa. Idealmente, solo tendrás que volver a autorizar en casos como revocar el acceso, cambiar credenciales o problemas de seguridad detectados por Google.
- Si ves errores de autenticación persistentes, revisa que tu cuenta de Google siga autorizando el acceso y que las credenciales sean correctas.
- No es necesario configurar la carpeta de Google Drive en los ajustes: puedes seleccionar o cambiar la carpeta en cualquier momento desde la pantalla principal del plugin. Si no has seleccionado ninguna carpeta, la lista de documentos aparecerá vacía.

## Auditoría y calidad
- Cumple con los estándares de codificación de WordPress (WPCS) y ha sido auditado con WP_DEBUG y PHP E_ALL.
- Todo el código ha sido revisado para asegurar la sanitización, escape y validación de datos.
- No hay variables dentro de funciones de traducción, cumpliendo con las buenas prácticas de internacionalización.
- El HTML generado es válido según W3C.
- El JavaScript ha sido validado con JSHint y no expone variables globales innecesarias.

## Mejoras técnicas y seguridad
- El plugin revisa y mejora el cacheo de llamadas a APIs externas cuando es necesario.
- No se duplica la carga de jQuery ni de otros scripts nativos de WordPress.
- El CSS ha sido limpiado para evitar el uso excesivo de `!important`, mejorando la mantenibilidad y compatibilidad visual.

## Contribuciones
¡Las contribuciones son bienvenidas! Envía un Pull Request o abre un issue.

## Licencia
Este plugin es software libre; puedes redistribuirlo y/o modificarlo bajo los términos de la Licencia Pública General de GNU tal como fue publicada por la Free Software Foundation; ya sea la versión 2 de dicha licencia, o (a tu elección) cualquier versión posterior.

Este plugin se distribuye con la esperanza de que sea útil, pero SIN NINGUNA GARANTÍA; ni siquiera la garantía implícita de COMERCIABILIDAD o ADECUACIÓN PARA UN PROPÓSITO PARTICULAR. Consulta los detalles en la Licencia Pública General de GNU.

