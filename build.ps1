# PowerShell script to build dist.zip with all necessary files

# Remove existing dist.zip if it exists
if (Test-Path "dist.zip") {
    Remove-Item "dist.zip" -Force
}

# Compress all files and folders into dist.zip (excluding build script and dist.zip itself)
Compress-Archive -Path * -DestinationPath "dist.zip"

Write-Host "dist.zip created successfully."