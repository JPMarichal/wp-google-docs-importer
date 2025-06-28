# Proyecciones y características deseables para Google Docs Importer

## Adecuación a CodeCanyon (Envato) - Prioridades

### 1. Auditoría y calidad (PRIORIDAD ALTA)
- Ejecutar auditoría automática con WP_DEBUG y PHP E_ALL, corrigiendo todos los warnings/notices.
- Ejecutar wpcs-for-envato y corregir issues de estándares.
- Validar HTML generado con W3C.
- Validar JS con JSHint y eliminar globales innecesarias.

### 3. Documentación y assets (PRIORIDAD ALTA)
- Crear documentación en inglés (PDF o HTML) explicando instalación, personalización y uso, con ejemplos.
- Preparar screenshots, banner, icono y gráficos para la página de producto.
- Documentar y asegurar licencias de todos los recursos incluidos.

### 4. Demo y marketing (PRIORIDAD MEDIA)
- Preparar y publicar un live demo funcional en un servidor propio.
- Añadir capturas destacadas y gráficos limpios para la página del producto.

### 6. Integración de licencias (PRIORIDAD BAJA)
- (Opcional) Integrar validación de purchase code con la Envato API.

---

## Otras mejoras y proyecciones (priorizadas para MVP)

### Prioridad MVP
1. **Soporte para imágenes y archivos embebidos:** Importar imágenes y archivos adjuntos de Google Docs y subirlos a la biblioteca de medios de WordPress, insertándolos correctamente en el contenido.

### Prioridad Media
1. **Importación masiva y automatizada:** Permitir seleccionar varios documentos y realizar la importación en lote, o incluso programar importaciones automáticas desde la carpeta de Google Drive.
2. **Sincronización de actualizaciones:** Si un documento de Google Docs cambia, poder actualizar el post correspondiente en WordPress con un solo clic o de forma automática, manteniendo un historial de cambios.

### Prioridad Baja
1. **Permisos y roles avanzados:** Controlar qué usuarios pueden importar, actualizar o eliminar documentos.
2. **Soporte multisitio:** Permitir funcionar en instalaciones multisitio de WordPress.
