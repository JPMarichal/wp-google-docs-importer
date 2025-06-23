# Repositorio objetivo
$repo = "JPMarichal/wp-google-docs-importer"

# Diccionario de etiquetas necesarias
$labels = @{
    "prioridad:alta" = @{ color = "B60205"; description = "Alta prioridad" }
    "prioridad:media" = @{ color = "FBCA04"; description = "Prioridad media" }
    "prioridad:baja" = @{ color = "0E8A16"; description = "Baja prioridad" }
    "prioridad:mvp" = @{ color = "5319E7"; description = "Prioridad MVP" }
    "code-review" = @{ color = "1D76DB"; description = "Revisión de código" }
    "envato" = @{ color = "006B75"; description = "Requerimiento para Envato" }
    "desinstalacion" = @{ color = "D93F0B"; description = "Proceso de desinstalación" }
    "documentacion" = @{ color = "0B6E99"; description = "Documentación y assets" }
    "marketing" = @{ color = "FFD700"; description = "Marketing y promoción" }
    "tecnica" = @{ color = "C2E0C6"; description = "Mejoras técnicas" }
    "licencias" = @{ color = "F7C6C7"; description = "Gestión de licencias" }
    "feature" = @{ color = "84B6EB"; description = "Nueva funcionalidad" }
}

# Obtener etiquetas existentes
$existingLabels = gh label list --repo $repo --json name | ConvertFrom-Json | ForEach-Object { $_.name }

# Crear las etiquetas que no existen
foreach ($label in $labels.Keys) {
    if (-not ($existingLabels -contains $label)) {
        $meta = $labels[$label]
        Write-Host "Creando etiqueta '$label'..."
        gh label create $label --color $meta.color --description $meta.description --repo $repo
    }
}

# Lista de issues a crear
$issues = @(
    @{
        title = "[CodeCanyon] Auditoría y calidad: WP_DEBUG, E_ALL y wpcs-for-envato"
        body  = "Ejecutar auditoría automática con WP_DEBUG y PHP E_ALL, corrigiendo todos los warnings/notices. Ejecutar wpcs-for-envato y corregir issues de estándares. Revisar exhaustivamente sanitización, escape y validación de datos en todo el código. Revisar que no haya variables dentro de funciones de traducción. Validar HTML generado con W3C. Validar JS con JSHint y eliminar globales innecesarias."
        labels = "prioridad:alta,code-review,envato"
    },
    @{
        title = "[CodeCanyon] Instalación/desinstalación: proceso seguro y exportación de ajustes"
        body  = "Implementar proceso de desinstalación que advierta al usuario, confirme la acción y permita exportar ajustes antes de borrar datos."
        labels = "prioridad:alta,envato,desinstalacion"
    },
    @{
        title = "[CodeCanyon] Documentación y assets para Envato"
        body  = "Crear documentación en inglés (PDF o HTML) explicando instalación, personalización y uso, con ejemplos. Preparar screenshots, banner, icono y gráficos para la página de producto. Documentar y asegurar licencias de todos los recursos incluidos."
        labels = "prioridad:alta,envato,documentacion"
    },
    @{
        title = "[CodeCanyon] Demo y marketing: live demo y capturas"
        body  = "Preparar y publicar un live demo funcional en un servidor propio. Añadir capturas destacadas y gráficos limpios para la página del producto."
        labels = "prioridad:media,envato,marketing"
    },
    @{
        title = "[CodeCanyon] Mejoras técnicas y seguridad"
        body  = "Revisar y mejorar el cacheo de llamadas a APIs externas si es necesario. Revisar que no se duplique jQuery ni otros scripts nativos. Revisar y limpiar CSS para evitar uso excesivo de !important."
        labels = "prioridad:media,envato,tecnica"
    },
    @{
        title = "[CodeCanyon] Integración de licencias Envato (opcional)"
        body  = "(Opcional) Integrar validación de purchase code con la Envato API."
        labels = "prioridad:baja,envato,licencias"
    },
    @{
        title = "[MVP] Selección de tipo de post y taxonomía al importar"
        body  = "Permitir elegir si el documento se importa como post, página o Custom Post Type, y asignar categorías/términos personalizados desde la interfaz de importación."
        labels = "prioridad:mvp,feature"
    },
    @{
        title = "[MVP] Asignación de autor y estado editorial al importar"
        body  = "Permitir elegir el autor del post y el estado (borrador, pendiente de revisión, publicado) al importar."
        labels = "prioridad:mvp,feature"
    },
    @{
        title = "[MVP] Soporte para imágenes y archivos embebidos"
        body  = "Importar imágenes y archivos adjuntos de Google Docs y subirlos a la biblioteca de medios de WordPress, insertándolos correctamente en el contenido."
        labels = "prioridad:mvp,feature"
    },
    @{
        title = "[Media] Importación masiva y automatizada"
        body  = "Permitir seleccionar varios documentos y realizar la importación en lote, o incluso programar importaciones automáticas desde la carpeta de Google Drive."
        labels = "prioridad:media,feature"
    },
    @{
        title = "[Media] Sincronización de actualizaciones de documentos"
        body  = "Si un documento de Google Docs cambia, poder actualizar el post correspondiente en WordPress con un solo clic o de forma automática, manteniendo un historial de cambios."
        labels = "prioridad:media,feature"
    },
    @{
        title = "[Baja] Mapeo de campos personalizados (Custom Fields)"
        body  = "Permitir mapear partes del documento o metadatos de Google Docs a campos personalizados de WordPress (ACF, metaboxes, etc)."
        labels = "prioridad:baja,feature"
    },
    @{
        title = "[Baja] Permisos y roles avanzados"
        body  = "Controlar qué usuarios pueden importar, actualizar o eliminar documentos."
        labels = "prioridad:baja,feature"
    },
    @{
        title = "[Baja] Soporte multisitio"
        body  = "Permitir funcionar en instalaciones multisitio de WordPress."
        labels = "prioridad:baja,feature"
    }
)

# Crear los issues
foreach ($issue in $issues) {
    Write-Host "Creando issue: $($issue.title)..."
    gh issue create --repo $repo --title $issue.title --body $issue.body --label $issue.labels
}
