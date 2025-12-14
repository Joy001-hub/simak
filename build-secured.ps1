$ErrorActionPreference = "Stop"

Write-Host "starting Secure Build Process..." -ForegroundColor Green

# 1. Backup checks
$gitStatus = git status --porcelain
if ($gitStatus) {
    Write-Host "WARNING: You have uncommitted changes. Please commit or stash them before running this script." -ForegroundColor Red
    Write-Host "Process aborted to prevent data loss."
    exit 1
}

Write-Host "1. Backing up current state (git)..." -ForegroundColor Cyan
# We rely on git being clean, so 'restoring' later just means checkout .

# 2. Strip Comments
Write-Host "2. Stripping comments from PHP files..." -ForegroundColor Cyan
php scripts/strip_comments.php

# 3. Build
Write-Host "3. Building NativePHP application..." -ForegroundColor Cyan
try {
    php artisan native:build win
} catch {
    Write-Host "Build failed! Restoring source code..." -ForegroundColor Red
    git checkout .
    exit 1
}

# 4. Restore
Write-Host "4. Restoring source code to original state..." -ForegroundColor Cyan
git checkout .

Write-Host "Secure Build Completed Successfully!" -ForegroundColor Green
Write-Host "Check the 'dist' folder for your production build."
