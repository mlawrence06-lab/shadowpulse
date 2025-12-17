
Add-Type -AssemblyName System.Drawing
$path = "c:\Users\martin\Documents\Projects\ShadowPulse\assets\bpip_banner_468x60.png"
if (Test-Path $path) {
    $img = [System.Drawing.Image]::FromFile($path)
    Write-Output "Width: $($img.Width) Height: $($img.Height)"
    $img.Dispose()
} else {
    Write-Output "File not found"
}
