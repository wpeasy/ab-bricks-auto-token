# WordPress Plugin ZIP Creator
# Creates a UNIX/Linux-compatible ZIP file for WordPress plugin installation

# Get plugin info
$pluginDir = Get-Location
$pluginName = Split-Path -Leaf $pluginDir

# Read version from main plugin file
$mainFile = Get-ChildItem -Filter "*.php" | Where-Object { $_.Name -eq "$pluginName.php" } | Select-Object -First 1
if (-not $mainFile) {
    Write-Error "Main plugin file not found: $pluginName.php"
    exit 1
}

$content = Get-Content $mainFile.FullName -Raw
if ($content -match 'Version:\s*([0-9.]+)') {
    $version = $matches[1]
} else {
    Write-Error "Version not found in $($mainFile.Name)"
    exit 1
}

# Create plugin subfolder if it doesn't exist
$outputDir = Join-Path $pluginDir "plugin"
if (-not (Test-Path $outputDir)) {
    New-Item -ItemType Directory -Path $outputDir | Out-Null
}

# Remove old ZIP files
Get-ChildItem -Path $outputDir -Filter "*.zip" | Remove-Item -Force

# Output ZIP filename
$zipName = "$pluginName-$version.zip"
$zipPath = Join-Path $outputDir $zipName

Write-Host "Creating ZIP: $zipName" -ForegroundColor Green

# Exclusion patterns
$excludePatterns = @(
    '*.md',
    'node_modules',
    'src-svelte',
    '.git',
    '.gitignore',
    '.gitattributes',
    'package.json',
    'package-lock.json',
    'tsconfig.json',
    'vite.config.js',
    'svelte.config.js',
    'postcss.config.js',
    'tailwind.config.js',
    '.claude',
    'plugin',
    '*.log',
    '*.zip',
    'create-plugin-zip.ps1'
)

# Get all files to include
$files = Get-ChildItem -Path $pluginDir -Recurse -File | Where-Object {
    $file = $_
    $relativePath = $file.FullName.Substring($pluginDir.Path.Length + 1)

    # Exclude based on patterns
    $exclude = $false
    foreach ($pattern in $excludePatterns) {
        if ($pattern -like '*\*' -or $pattern -like '*/*') {
            # Directory pattern
            $dirPattern = $pattern -replace '[\\/]', '\\'
            if ($relativePath -like "*$dirPattern*") {
                $exclude = $true
                break
            }
        } elseif ($relativePath -like $pattern -or $relativePath -like "*\$pattern" -or $relativePath -like "*/$pattern") {
            # File pattern
            $exclude = $true
            break
        }
    }
    -not $exclude
}

# Create temporary directory for ZIP structure
$tempDir = Join-Path $env:TEMP "wp-plugin-zip-$(Get-Random)"
$pluginTempDir = Join-Path $tempDir $pluginName
New-Item -ItemType Directory -Path $pluginTempDir -Force | Out-Null

# Copy files to temp directory
foreach ($file in $files) {
    $relativePath = $file.FullName.Substring($pluginDir.Path.Length + 1)
    $targetPath = Join-Path $pluginTempDir $relativePath
    $targetDir = Split-Path -Parent $targetPath

    if (-not (Test-Path $targetDir)) {
        New-Item -ItemType Directory -Path $targetDir -Force | Out-Null
    }

    Copy-Item -Path $file.FullName -Destination $targetPath -Force
}

# Create ZIP using .NET (ensures forward slashes for UNIX compatibility)
Add-Type -Assembly System.IO.Compression.FileSystem
[System.IO.Compression.ZipFile]::CreateFromDirectory($tempDir, $zipPath)

# Clean up temp directory
Remove-Item -Path $tempDir -Recurse -Force

Write-Host "[OK] Created: $zipPath" -ForegroundColor Green
Write-Host "[OK] Size: $([math]::Round((Get-Item $zipPath).Length / 1MB, 2)) MB" -ForegroundColor Green
Write-Host "[OK] Files included: $($files.Count)" -ForegroundColor Green
