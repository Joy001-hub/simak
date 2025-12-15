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



# 4. Restore
Write-Host "4. Restoring source code to original state..." -ForegroundColor Cyan
git checkout .

Write-Host "Secure Build Completed Successfully!" -ForegroundColor Green
Write-Host "Check the 'dist' folder for your production build."
