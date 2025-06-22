# Compila los archivos .po a .mo usando msgfmt.exe de GnuWin32
$msgfmt = "C:\Program Files (x86)\GnuWin32\bin\msgfmt.exe"

if (!(Test-Path $msgfmt)) {
    Write-Host "msgfmt.exe no encontrado en $msgfmt. Por favor revisa la ruta."
    exit 1
}

Get-ChildItem -Filter *.po | ForEach-Object {
    $po = $_.FullName
    $mo = [System.IO.Path]::ChangeExtension($po, ".mo")
    & $msgfmt $po -o $mo
    Write-Host "Compilado: $po -> $mo"
}

Write-Host "Compilación finalizada."
