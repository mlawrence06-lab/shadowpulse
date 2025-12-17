
Add-Type -AssemblyName System.Drawing

$srcPath = "c:\Users\martin\Documents\Projects\ShadowPulse\assets\bpip_banner_v2.png"
$destPath = "c:\Users\martin\Documents\Projects\ShadowPulse\assets\bpip_banner_468x60.png"

$img = [System.Drawing.Image]::FromFile($srcPath)
$targetWidth = 468
$targetHeight = 60

# Resize logic: 
# We want width = 468.
# Since img is square (likely 1024x1024), scaling directly to width 468 results in height 468.
# Then we crop the middle 60px.
# This means we are taking the strip from Y = (468-60)/2 = 204 to 264.
# This strip is 60/468 = 12.8% of the image height.
# Looking at the generated image, the text band looks small enough to fit in the middle 12%.

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
