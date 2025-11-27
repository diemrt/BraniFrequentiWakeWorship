# PowerShell script to build dist.zip with all necessary files

# Remove existing dist.zip if it exists
if (Test-Path "dist.zip") {
    Remove-Item "dist.zip" -Force
}

# Compress PHP files and specified folders into dist.zip
Compress-Archive -Path *.php, includes, js, css -DestinationPath "dist.zip"

Write-Host "dist.zip created successfully."