[CmdletBinding()]
param(
    [Parameter(Mandatory = $true)]
    [string] $PhpExecutable,

    [Parameter(Mandatory = $true)]
    [string] $ComposerPhar,

    [string] $OutputPath,

    [switch] $ReplaceExisting
)

Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

$projectRoot = (Resolve-Path (Join-Path $PSScriptRoot '..')).Path
$phpPath = (Resolve-Path $PhpExecutable).Path
$composerPath = (Resolve-Path $ComposerPhar).Path
$cacheRoot = (Resolve-Path (Join-Path $projectRoot 'storage\cache')).Path
$stageContainer = Join-Path $cacheRoot ('production-package-' + [guid]::NewGuid().ToString('N'))
$stageRoot = Join-Path $stageContainer 'sctech'
$composerTemp = Join-Path $cacheRoot ('composer-temp-' + [guid]::NewGuid().ToString('N'))

if ([string]::IsNullOrWhiteSpace($OutputPath)) {
    $OutputPath = Join-Path (Split-Path $projectRoot -Parent) 'SCTECH-production-upload.zip'
}

$outputFullPath = [System.IO.Path]::GetFullPath($OutputPath)
if (Test-Path -LiteralPath $outputFullPath) {
    if (-not $ReplaceExisting) {
        throw "Archive already exists: $outputFullPath"
    }
    if ([System.IO.Path]::GetExtension($outputFullPath) -ne '.zip') {
        throw "Refusing to replace a non-ZIP target: $outputFullPath"
    }
    [System.IO.File]::Delete($outputFullPath)
}

$runtimeDirectories = @(
    'app',
    'bootstrap',
    'config',
    'database',
    'deploy',
    'docs',
    'lang',
    'public',
    'routes'
)

$runtimeFiles = @(
    '.env.example',
    'AGENTS.md',
    'README.md',
    'composer.json',
    'composer.lock'
)

$forbiddenEntryPatterns = @(
    '^sctech/\.env$',
    '^sctech/node_modules(/|$)',
    '^sctech/resources(/|$)',
    '^sctech/tests(/|$)',
    '^sctech/\.git(/|$)',
    '^sctech/\.phpunit\.cache(/|$)',
    '^sctech/storage/(cache|logs|sessions)/[^.]+',
    '^sctech/public/uploads/[^.]+'
)

function Assert-SafeStagePath {
    param([string] $Path)

    $resolvedParent = [System.IO.Path]::GetFullPath((Split-Path $Path -Parent))
    $expectedParent = [System.IO.Path]::GetFullPath($cacheRoot)
    if (-not $resolvedParent.Equals($expectedParent, [System.StringComparison]::OrdinalIgnoreCase)) {
        throw "Unsafe staging path: $Path"
    }
}

function Assert-ArchiveEntries {
    param([System.IO.Compression.ZipArchive] $Archive)

    $entryNames = @($Archive.Entries | ForEach-Object { $_.FullName.Replace('\', '/') })
    foreach ($pattern in $forbiddenEntryPatterns) {
        $match = $entryNames | Where-Object { $_ -match $pattern } | Select-Object -First 1
        if ($null -ne $match) {
            throw "Forbidden archive entry: $match"
        }
    }

    $requiredEntries = @(
        'sctech/public/index.php',
        'sctech/public/.htaccess',
        'sctech/public/assets/build/manifest.json',
        'sctech/public/uploads/.htaccess',
        'sctech/vendor/autoload.php',
        'sctech/database/schema.sql',
        'sctech/bin/console',
        'sctech/.env.example'
    )

    foreach ($required in $requiredEntries) {
        if ($required -notin $entryNames) {
            throw "Required archive entry is missing: $required"
        }
    }
}

Assert-SafeStagePath -Path $stageContainer
Assert-SafeStagePath -Path $composerTemp

try {
    New-Item -ItemType Directory -Path $stageRoot | Out-Null
    New-Item -ItemType Directory -Path $composerTemp | Out-Null

    foreach ($directory in $runtimeDirectories) {
        $source = Join-Path $projectRoot $directory
        Copy-Item -LiteralPath $source -Destination $stageRoot -Recurse
    }

    foreach ($file in $runtimeFiles) {
        Copy-Item -LiteralPath (Join-Path $projectRoot $file) -Destination (Join-Path $stageRoot $file)
    }

    New-Item -ItemType Directory -Path (Join-Path $stageRoot 'bin') | Out-Null
    Copy-Item -LiteralPath (Join-Path $projectRoot 'bin\console') -Destination (Join-Path $stageRoot 'bin\console')

    New-Item -ItemType Directory -Path (Join-Path $stageRoot 'storage\cache') -Force | Out-Null
    New-Item -ItemType Directory -Path (Join-Path $stageRoot 'storage\logs') -Force | Out-Null
    New-Item -ItemType Directory -Path (Join-Path $stageRoot 'storage\sessions') -Force | Out-Null
    foreach ($storageDirectory in @('cache', 'logs', 'sessions')) {
        $gitkeep = Join-Path $projectRoot ("storage\$storageDirectory\.gitkeep")
        if (Test-Path -LiteralPath $gitkeep) {
            Copy-Item -LiteralPath $gitkeep -Destination (Join-Path $stageRoot ("storage\$storageDirectory\.gitkeep"))
        }
    }

    $previousTemp = $env:TEMP
    $previousTmp = $env:TMP
    $env:TEMP = $composerTemp
    $env:TMP = $composerTemp
    $composerExitCode = 1
    try {
        & $phpPath -d "sys_temp_dir=$composerTemp" $composerPath --working-dir=$stageRoot install --no-dev --classmap-authoritative --no-interaction --no-progress --prefer-dist
        $composerExitCode = $LASTEXITCODE
    } finally {
        $env:TEMP = $previousTemp
        $env:TMP = $previousTmp
    }
    if ($composerExitCode -ne 0) {
        throw "Composer production install failed with exit code $composerExitCode"
    }

    foreach ($developmentPackage in @('phpunit', 'phpstan', 'squizlabs')) {
        if (Test-Path -LiteralPath (Join-Path $stageRoot "vendor\$developmentPackage")) {
            throw "Development dependency leaked into production vendor: $developmentPackage"
        }
    }

    $manifestPath = Join-Path $stageRoot 'public\assets\build\manifest.json'
    $manifest = Get-Content -LiteralPath $manifestPath -Raw | ConvertFrom-Json
    foreach ($property in $manifest.PSObject.Properties) {
        $assetRelativePath = $property.Value.TrimStart('/').Replace('/', '\')
        if (-not (Test-Path -LiteralPath (Join-Path (Join-Path $stageRoot 'public') $assetRelativePath))) {
            throw "Manifest references a missing production asset: $($property.Value)"
        }
    }

    Add-Type -AssemblyName System.IO.Compression
    Add-Type -AssemblyName System.IO.Compression.FileSystem
    $outputZip = [System.IO.Compression.ZipFile]::Open(
        $outputFullPath,
        [System.IO.Compression.ZipArchiveMode]::Create
    )
    try {
        $stagePrefix = $stageContainer.TrimEnd('\', '/') + [System.IO.Path]::DirectorySeparatorChar
        foreach ($file in Get-ChildItem -LiteralPath $stageRoot -Recurse -File) {
            if (-not $file.FullName.StartsWith($stagePrefix, [System.StringComparison]::OrdinalIgnoreCase)) {
                throw "File escaped the staging directory: $($file.FullName)"
            }
            $entryName = $file.FullName.Substring($stagePrefix.Length).Replace('\', '/')
            [System.IO.Compression.ZipFileExtensions]::CreateEntryFromFile(
                $outputZip,
                $file.FullName,
                $entryName,
                [System.IO.Compression.CompressionLevel]::Optimal
            ) | Out-Null
        }
    } finally {
        $outputZip.Dispose()
    }

    $zip = [System.IO.Compression.ZipFile]::OpenRead($outputFullPath)
    try {
        Assert-ArchiveEntries -Archive $zip
        $entryCount = $zip.Entries.Count
    } finally {
        $zip.Dispose()
    }

    $archive = Get-Item -LiteralPath $outputFullPath
    $sha256 = (Get-FileHash -LiteralPath $outputFullPath -Algorithm SHA256).Hash.ToLowerInvariant()
    Write-Output "Archive: $($archive.FullName)"
    Write-Output "Bytes: $($archive.Length)"
    Write-Output "Entries: $entryCount"
    Write-Output "SHA256: $sha256"
} finally {
    if (Test-Path -LiteralPath $stageContainer) {
        Assert-SafeStagePath -Path $stageContainer
        Remove-Item -LiteralPath $stageContainer -Recurse -Force
    }
    if (Test-Path -LiteralPath $composerTemp) {
        Assert-SafeStagePath -Path $composerTemp
        Remove-Item -LiteralPath $composerTemp -Recurse -Force
    }
}
