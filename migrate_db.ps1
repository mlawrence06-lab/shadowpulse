$InputPath = "c:\Users\martin\Documents\Projects\ShadowPulse\Web\database\adserverdatabase.sql"
$OutputPath = "c:\Users\martin\Documents\Projects\ShadowPulse\Web\database\adserverdatabase_migrated.sql"
$Content = Get-Content -Path $InputPath -Raw -Encoding UTF8
$Modified = $Content -replace '`ad_', '`'
Set-Content -Path $OutputPath -Value $Modified -Encoding UTF8
Write-Host "Migrated SQL saved to $OutputPath"
