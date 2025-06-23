# Google Docs Importer for WordPress

Google Docs Importer es un plugin único para WordPress que conecta tu Google Drive con tu sitio, permitiendo importar documentos de Google Docs como entradas (posts) estándar, manteniendo el formato y facilitando el workflow editorial. Si deseas que el contenido sea de otro tipo (página o Custom Post Type), deberás cambiarlo manualmente tras la importación.

## Prerrequisitos

Antes de instalar el plugin, necesitas crear credenciales de Google API para permitir la conexión segura entre tu sitio WordPress y Google Drive/Docs. Sigue estos pasos:

1. Accede a la [Consola de Google Cloud](https://console.cloud.google.com/).
2. Crea un nuevo proyecto (o selecciona uno existente).
3. Habilita las APIs de **Google Drive** y **Google Docs** para tu proyecto.
4. Ve a "Credenciales" y crea un **ID de cliente OAuth 2.0**:
   - Tipo de aplicación: "Aplicación web".
   - Añade la URL de tu sitio WordPress como origen autorizado y la URL de redirección que te indique el plugin en los ajustes.
5. Descarga el archivo JSON de credenciales o copia el **Client ID** y **Client Secret**.
6. Una vez instalado el plugin, introduce estos datos en los ajustes del plugin para completar la conexión.

Consulta la documentación oficial de Google si tienes dudas sobre la creación de credenciales OAuth 2.0.

## ¿Para quién es este plugin?
- **Equipos editoriales y medios digitales** que redactan en Google Docs y publican en WordPress.
- **Agencias de contenido** que gestionan múltiples clientes y necesitan flujos de trabajo eficientes.
- **Redactores, bloggers profesionales y creadores de contenido** que colaboran en Google Docs y desean publicar sin fricciones.
- **Sitios WordPress con flujos de trabajo colaborativos** y necesidad de control editorial.

## Ventajas únicas
- **Importación directa y masiva** desde Google Drive a WordPress, sin copiar/pegar ni perder formato.
- **Sincronización y control de duplicados:** nunca importas dos veces el mismo documento.
- **Workflow editorial optimizado:** los documentos importados quedan como borrador, listos para revisión y publicación.
- **Interfaz moderna y amigable:** búsqueda instantánea, paginación, acciones rápidas (ver, editar, eliminar, importar).
- **Visualización de la categoría o término principal:** detecta y muestra la categoría principal (o término jerárquico) del post importado.
- **Seguimiento del estado de cada documento:** visualiza si un documento está importado, en borrador, publicado, etc.
- **Acciones rápidas:** ver, editar, eliminar, importar, todo desde la misma pantalla.
- **Pensado para equipos y flujos colaborativos:** facilita la revisión, edición y publicación en equipo.

## Características
- Conexión segura a Google Drive mediante OAuth 2.0
- Listado de todos los Google Docs de una carpeta específica
- Importación con formato (negritas, listas, encabezados, etc.)
- Evita duplicados y lleva registro de documentos importados
- Interfaz de administración limpia y moderna
- Búsqueda instantánea y paginación amigable
- Acciones rápidas sobre cada documento
- Importa siempre como post estándar (puedes cambiar el tipo de post manualmente después)
- Detección automática y visualización de la categoría principal o término jerárquico
- Seguimiento del estado editorial de cada documento

## Requisitos
- WordPress 5.6 o superior
- PHP 7.4 o superior
- Credenciales de Google API con acceso a Google Drive y Google Docs

## Instalación
1. Sube la carpeta `google-docs-importer` a `/wp-content/plugins/`
2. Activa el plugin en el menú de plugins de WordPress
3. El plugin creará automáticamente las tablas y opciones necesarias para funcionar.
4. Configura tus credenciales de Google API y carpeta de Google Drive en los ajustes del plugin.
5. ¡Listo para trabajar!

## Exportar tus datos antes de desinstalar

Antes de desinstalar el plugin, puedes exportar:
- **Ajustes**: Descarga un archivo JSON con toda la configuración del plugin.
- **Historial**: Descarga un archivo JSON con la correspondencia entre documentos de Google Docs y posts importados.

Ambas opciones están disponibles en la página de ajustes del plugin.

## Desinstalación

1. Ve a los ajustes del plugin y haz clic en **"Confirmar desinstalación"**.
2. Exporta tus datos si lo deseas.
3. Desinstala el plugin desde el panel de plugins de WordPress.

**Importante:**
- La desinstalación elimina todas las tablas, opciones y datos internos del plugin. No queda rastro en la base de datos.
- Los posts de WordPress importados NO se eliminan.

## Reinstalación y restauración

1. Instala y activa el plugin normalmente.
2. Ve a los ajustes del plugin.
3. Usa los formularios para **importar los ajustes** y **el historial** desde los archivos JSON exportados previamente.
4. El plugin restaurará la configuración y el historial, permitiéndote continuar como si nunca hubieras desinstalado.

**Notas:**
- Si solo importas los ajustes, tendrás la configuración pero no el historial de importaciones.
- Si solo importas el historial, tendrás la correspondencia pero deberás volver a configurar las credenciales.
- Puedes importar ambos archivos en cualquier orden.

## Mensajes y validación
- El plugin utiliza SweetAlert para mostrar mensajes claros de éxito o error al importar/exportar datos.
- Si SweetAlert no está disponible, se usan alertas estándar.

## Sobre la conexión con Google

Cuando conectas el plugin con tu cuenta de Google, la sesión suele mantenerse activa y no tendrás que volver a autorizar salvo en casos poco frecuentes, como:

- Si revocas el acceso desde tu cuenta de Google.
- Si cambias los datos de conexión (Client ID o Client Secret) en los ajustes del plugin.
- Si Google detecta algún problema de seguridad.

En la mayoría de los casos, el plugin renovará la conexión automáticamente. Si alguna vez ves un mensaje pidiéndote reconectar, solo hazlo desde los ajustes del plugin.

**Importante:** Si ves errores de autenticación persistentes, revisa que tu cuenta de Google siga autorizando el acceso y que las credenciales sean correctas.

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

## Idiomas soportados

Este plugin detecta automáticamente el idioma configurado en WordPress y mostrará la interfaz en ese idioma si existe traducción disponible. Actualmente está preparado para:

- Español (es_ES)
- Inglés (en_US)
- Alemán (de_DE)
- Francés (fr_FR)
- Portugués (pt_PT)

Si el idioma de tu sitio no está en la lista, la interfaz aparecerá en inglés por defecto.

## Contribuciones
¡Las contribuciones son bienvenidas! Envía un Pull Request o abre un issue.

## Licencia

Este plugin es software libre; puedes redistribuirlo y/o modificarlo bajo los términos de la Licencia Pública General de GNU tal como fue publicada por la Free Software Foundation; ya sea la versión 2 de dicha licencia, o (a tu elección) cualquier versión posterior.

Este plugin se distribuye con la esperanza de que sea útil, pero SIN NINGUNA GARANTÍA; ni siquiera la garantía implícita de COMERCIABILIDAD o ADECUACIÓN PARA UN PROPÓSITO PARTICULAR. Consulta los detalles en la Licencia Pública General de GNU.

