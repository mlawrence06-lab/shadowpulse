
Add-Type -AssemblyName System.Drawing

$srcPath = "c:\Users\martin\Documents\Projects\ShadowPulse\assets\bpip_banner_468x60.png"
$destPath = "c:\Users\martin\Documents\Projects\ShadowPulse\assets\bpip_banner_final.png"

$img = [System.Drawing.Image]::FromFile($srcPath)
$targetWidth = 468
$targetHeight = 60

# Resize to 468 wide (maintaining aspect ratio implies height will be 468 for square source)
$resizeHeight = [int]($img.Height * ($targetWidth / $img.Width))
$resizedBmp = new-object System.Drawing.Bitmap $targetWidth, $resizeHeight
$graph = [System.Drawing.Graphics]::FromImage($resizedBmp)
$graph.InterpolationMode = [System.Drawing.Drawing2D.InterpolationMode]::HighQualityBicubic
$graph.DrawImage($img, 0, 0, $targetWidth, $resizeHeight)

# Crop the center 60px height
$cropRect = New-Object System.Drawing.Rectangle 0, (($resizeHeight - $targetHeight) / 2), $targetWidth, $targetHeight
$finalBmp = $resizedBmp.Clone($cropRect, $resizedBmp.PixelFormat)

$finalBmp.Save($destPath, [System.Drawing.Imaging.ImageFormat]::Png)

$img.Dispose()
$resizedBmp.Dispose()
$finalBmp.Dispose()
$graph.Dispose()

Write-Output "Resized and cropped to $destPath"
