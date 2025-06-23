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
- Implementar proceso de desinstalación que advierta al usuario, confirme la acción y permita exportar ajustes antes de borrar datos.

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

## Otras mejoras y proyecciones (priorizadas para MVP)

### Prioridad MVP
1. **Selección de tipo de post y taxonomía al importar:** Poder elegir si el documento se importa como post, página o Custom Post Type, y asignar categorías/términos personalizados desde la interfaz de importación.
2. **Asignación de autor y estado editorial:** Elegir el autor del post y el estado (borrador, pendiente de revisión, publicado) al importar.
3. **Soporte para imágenes y archivos embebidos:** Importar imágenes y archivos adjuntos de Google Docs y subirlos a la biblioteca de medios de WordPress, insertándolos correctamente en el contenido.

### Prioridad Media
4. **Importación masiva y automatizada:** Permitir seleccionar varios documentos y realizar la importación en lote, o incluso programar importaciones automáticas desde la carpeta de Google Drive.
5. **Sincronización de actualizaciones:** Si un documento de Google Docs cambia, poder actualizar el post correspondiente en WordPress con un solo clic o de forma automática, manteniendo un historial de cambios.

### Prioridad Baja
6. **Mapeo de campos personalizados (Custom Fields):** Permitir mapear partes del documento o metadatos de Google Docs a campos personalizados de WordPress (ACF, metaboxes, etc).
7. **Permisos y roles avanzados:** Controlar qué usuarios pueden importar, actualizar o eliminar documentos.
8. **Soporte multisitio:** Permitir funcionar en instalaciones multisitio de WordPress.
