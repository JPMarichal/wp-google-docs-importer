# Google Docs Importer for WordPress

Google Docs Importer es un plugin único para WordPress que conecta tu Google Drive con tu sitio, permitiendo importar documentos de Google Docs como entradas (posts) estándar, manteniendo el formato y facilitando el workflow editorial. Si deseas que el contenido sea de otro tipo (página o Custom Post Type), deberás cambiarlo manualmente tras la importación.

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
3. Crea un proyecto en Google Cloud Console y habilita las APIs de Google Drive y Google Docs
4. Crea credenciales OAuth 2.0 y configura la URI de redirección: `your-site.com/wp-admin/admin-post.php?action=g2wpi_oauth_callback`
5. Ve a los ajustes del plugin e ingresa tu Client ID, Client Secret y Folder ID de Google Drive
6. Haz clic en "Conectar con Google" para autorizar el plugin

## Uso
1. Ve al menú "Google Docs" en el admin de WordPress
2. Haz clic en "Actualizar listado" para obtener los documentos de tu carpeta de Google Drive
3. Usa la búsqueda para filtrar documentos
4. Haz clic en "Importar" junto a cualquier documento para traerlo como post (borrador)
5. Edita y publica cuando estés listo

## Selección de carpeta de Google Drive

Para cambiar la carpeta de Google Drive desde la que se listan los documentos:

1. En la pantalla principal del plugin, haz clic en el botón **"Cambiar carpeta"** (junto a "Actualizar listado").
2. Se abrirá un popup de Google Picker donde podrás navegar y seleccionar la carpeta deseada de tu Google Drive.
3. Al seleccionar una carpeta, el plugin guardará automáticamente la selección y recargará la página para mostrar los documentos de la nueva carpeta.
4. Puedes repetir este proceso en cualquier momento para cambiar la carpeta activa.

**Nota:** Es necesario haber configurado correctamente las credenciales de Google API en los ajustes del plugin para que el selector funcione.

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

## Changelog

### 1.0.0
* Versión inicial
