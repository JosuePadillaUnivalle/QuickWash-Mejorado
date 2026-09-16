param([switch]$Demo)
$ErrorActionPreference = 'Stop'
Set-Location -LiteralPath $PSScriptRoot
$phpExecutable = (Get-Command php -ErrorAction Stop).Source
$phpExtensions = Join-Path (Split-Path $phpExecutable) 'ext'
if (-not (Test-Path 'tools/php.ini')) {
    $ini = @(
        ('extension_dir="' + $phpExtensions + '"'),
        'extension=openssl','extension=mbstring','extension=fileinfo','extension=pdo_sqlite',
        'extension=sqlite3','extension=curl','date.timezone=America/La_Paz','memory_limit=512M'
    )
    $ini | Set-Content 'tools/php.ini'
}
$env:PHPRC = Join-Path $PWD 'tools'
if (-not (Test-Path '.env')) { Copy-Item '.env.example' '.env' }
if (-not (Test-Path 'vendor/autoload.php')) {
    if (-not (Test-Path 'tools/composer.phar')) { Invoke-WebRequest 'https://getcomposer.org/composer-stable.phar' -OutFile 'tools/composer.phar' }
    php tools/composer.phar install --no-interaction
    if ($LASTEXITCODE -ne 0) { throw 'Error al instalar dependencias.' }
}
if ((Get-Content '.env' -Raw) -match '(?m)^APP_KEY=\s*$') {
    php artisan key:generate --no-interaction
    if ($LASTEXITCODE -ne 0) { throw 'Error al generar la clave.' }
}
if (-not (Test-Path 'database/database.sqlite')) { New-Item -ItemType File 'database/database.sqlite' | Out-Null }
php artisan migrate --seed --no-interaction
if ($LASTEXITCODE -ne 0) { throw 'Error en las migraciones.' }
if ($Demo) {
    php artisan db:seed --class=DemoSeeder --no-interaction
    if ($LASTEXITCODE -ne 0) { throw 'Error al cargar datos de demostración.' }
}
Write-Host 'QuickWash Campus listo. Ejecuta INICIAR.cmd y abre http://127.0.0.1:8010'
