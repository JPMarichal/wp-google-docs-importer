# Elimina archivos de idiomas que no sean en_US, es_ES, de_DE, fr_FR, pt_PT
$keep = @(
    'google-docs-importer-en_US.po', 'google-docs-importer-en_US.mo',
    'google-docs-importer-es_ES.po', 'google-docs-importer-es_ES.mo',
    'google-docs-importer-de_DE.po', 'google-docs-importer-de_DE.mo',
    'google-docs-importer-fr_FR.po', 'google-docs-importer-fr_FR.mo',
    'google-docs-importer-pt_PT.po', 'google-docs-importer-pt_PT.mo'
)
Get-ChildItem -Path . -File | Where-Object { ($_.Name -like 'google-docs-importer-*.po' -or $_.Name -like 'google-docs-importer-*.mo') -and ($keep -notcontains $_.Name) } | Remove-Item -Force
