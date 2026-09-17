param([int]$Puerto = 8010)
$ErrorActionPreference = 'Stop'
Set-Location -LiteralPath $PSScriptRoot
$env:PHPRC = Join-Path $PSScriptRoot 'tools'
Write-Host "Quick Wash: http://127.0.0.1:$Puerto" -ForegroundColor Magenta
$reloj = Start-Process -FilePath (Get-Command php).Source -ArgumentList @('artisan', 'quickwash:sync', '--watch') -WorkingDirectory $PSScriptRoot -WindowStyle Hidden -PassThru
try {
Set-Location -LiteralPath (Join-Path $PSScriptRoot 'public')
php -S "127.0.0.1:$Puerto" -t . ..\vendor\laravel\framework\src\Illuminate\Foundation\resources\server.php

} finally { if (!$reloj.HasExited) { Stop-Process -Id $reloj.Id } }
