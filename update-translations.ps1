# Actualiza y compila las traducciones de todos los idiomas del plugin WordPress
# Este script debe ejecutarse desde la raíz del plugin

Write-Output "Generando archivo .pot..."
wp i18n make-pot . languages/google-docs-importer.pot

Write-Output "Actualizando archivos .po con Gemini..."
$apiKey = "AIzaSyBNwEa8QEdnP1wHqtohvwd5hzP9AJI8Urw"
$potPath = "languages/google-docs-importer.pot"
$potContent = Get-Content $potPath -Raw

$poFiles = Get-ChildItem -Path "languages" -Filter "*.po"

foreach ($poFile in $poFiles) {
    $langCode = $poFile.BaseName -replace "google-docs-importer-", ""
    $prompt = @"
Actúa como un generador de archivos .po para WordPress. Devuélveme únicamente el contenido válido del archivo .po para el idioma $langCode, generado a partir del siguiente archivo base .pot. No incluyas explicaciones, instrucciones, ni marcas de bloque, solo el contenido puro del archivo .po. El contenido del .pot es:

$potContent
"@

    $body = @{
        contents = @(
            @{
                parts = @(
                    @{ text = $prompt }
                )
            }
        )
        generationConfig = @{
            thinkingConfig = @{
                thinkingBudget = -1
            }
        }
    } | ConvertTo-Json -Depth 5

    $response = Invoke-WebRequest -Method Post `
        -Uri "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=$apiKey" `
        -Headers @{ "Content-Type" = "application/json" } `
        -Body $body

    $translation = ($response.Content | ConvertFrom-Json).candidates[0].content.parts[0].text
    # Limpiar líneas en blanco y caracteres invisibles al inicio
    $translationClean = $translation -replace "^(\uFEFF|\s)+", ''
    # Guardar sin BOM (PowerShell 7+)
    try {
        Set-Content -Path $poFile.FullName -Value $translationClean -Encoding utf8NoBOM
    } catch {
        # Fallback para PowerShell 5.x: usar .NET para guardar sin BOM
        [System.IO.File]::WriteAllLines($poFile.FullName, $translationClean -split "`r?`n", [System.Text.UTF8Encoding]::new($false))
    }
    Write-Output "Actualizado: $($poFile.Name)"
}

Write-Output "Todos los archivos .po han sido actualizados."

Write-Output "Compilando archivos .mo..."
Push-Location languages
./compile-mo.ps1
Pop-Location
Write-Output "Traducciones actualizadas y compiladas."
