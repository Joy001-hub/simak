# Strip Comments Script for Laravel NativePHP Pre-Build
# This script removes comments from PHP, JS, and Blade files
# preserving license headers if they contain "license" keyword

param(
    [string]$Path = ".",
    [switch]$DryRun = $false
)

$ErrorActionPreference = "Stop"

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "  Comment Stripping Script" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

if ($DryRun) {
    Write-Host "[DRY RUN MODE] No files will be modified" -ForegroundColor Yellow
}

# Directories to skip
$excludeDirs = @(
    'vendor',
    'node_modules',
    '.git',
    'storage',
    'bootstrap\cache'
)

function Should-SkipDirectory($filePath) {
    foreach ($dir in $excludeDirs) {
        if ($filePath -like "*\$dir\*") {
            return $true
        }
    }
    return $false
}

function Remove-Comments($content, $fileType) {
    $original = $content
    
    # Check if first comment block contains license
    $hasLicense = $false
    $licenseBlock = ""
    
    if ($content -match '^(\s*/\*\*?[\s\S]*?\*/\s*)') {
        $firstBlock = $matches[1]
        if ($firstBlock -match 'license|copyright|MIT|GPL|Apache|BSD') {
            $hasLicense = $true
            $licenseBlock = $firstBlock
            $content = $content.Substring($firstBlock.Length)
        }
    }
    
    # Remove multi-line comments (/* */ and /** */)
    $content = [regex]::Replace($content, '/\*[\s\S]*?\*/', '')
    
    # Remove single-line comments (// but not URLs like http://)
    # Only remove // at the start of a line or after whitespace
    $lines = $content -split "`n"
    $cleanedLines = @()
    
    foreach ($line in $lines) {
        # Skip if line is just a comment
        if ($line -match '^\s*//') {
            continue
        }
        
        # Remove inline comments (// after code) but preserve URLs
        # Match // only if not preceded by : (to preserve http://, https://)
        $cleanedLine = $line -replace '(?<!:)//(?!/).*$', ''
        
        # Trim trailing whitespace
        $cleanedLine = $cleanedLine.TrimEnd()
        
        $cleanedLines += $cleanedLine
    }
    
    $content = $cleanedLines -join "`n"
    
    # Remove excessive blank lines (more than 2 consecutive)
    $content = [regex]::Replace($content, '(\r?\n){3,}', "`n`n")
    
    # Restore license block if exists
    if ($hasLicense) {
        $content = $licenseBlock + $content
    }
    
    return $content
}

# Find all PHP, JS, and Blade files
$extensions = @("*.php", "*.js", "*.blade.php")
$files = @()

foreach ($ext in $extensions) {
    $files += Get-ChildItem -Path $Path -Filter $ext -Recurse -File
}

$totalFiles = $files.Count
$processedFiles = 0
$modifiedFiles = 0
$skippedFiles = 0

Write-Host "Found $totalFiles files to process" -ForegroundColor White
Write-Host ""

foreach ($file in $files) {
    $processedFiles++
    
    # Skip excluded directories
    if (Should-SkipDirectory $file.FullName) {
        $skippedFiles++
        continue
    }
    
    $relativePath = $file.FullName.Replace((Get-Location).Path + "\", "")
    
    try {
        $content = Get-Content -Path $file.FullName -Raw -Encoding UTF8
        
        if ([string]::IsNullOrEmpty($content)) {
            continue
        }
        
        $fileType = if ($file.Name -like "*.blade.php") { "blade" } 
                    elseif ($file.Extension -eq ".php") { "php" }
                    else { "js" }
        
        $cleaned = Remove-Comments $content $fileType
        
        # Check if content changed
        if ($cleaned -ne $content) {
            $modifiedFiles++
            
            if ($DryRun) {
                Write-Host "[WOULD MODIFY] $relativePath" -ForegroundColor Yellow
            } else {
                Set-Content -Path $file.FullName -Value $cleaned -Encoding UTF8 -NoNewline
                Write-Host "[MODIFIED] $relativePath" -ForegroundColor Green
            }
        }
    }
    catch {
        Write-Host "[ERROR] $relativePath - $($_.Exception.Message)" -ForegroundColor Red
    }
}

Write-Host ""
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "  Summary" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "Total files scanned: $totalFiles"
Write-Host "Files modified: $modifiedFiles"
Write-Host "Files skipped (vendor/node_modules): $skippedFiles"
Write-Host ""

if ($DryRun) {
    Write-Host "Run without -DryRun to apply changes" -ForegroundColor Yellow
}
