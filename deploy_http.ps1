$files = @{
    "btc_logic.php"             = "Web/api/v1/btc_logic.php";
    "get_page_context.php"      = "Web/api/v1/get_page_context.php";
    "run_sql_file.php"          = "Web/api/v1/run_sql_file.php";
    "alter_sp_full_context.sql" = "Web/database/alter_sp_full_context.sql"
}

$url = "https://vod.fan/shadowpulse/api/v1/writer.php"

foreach ($key in $files.Keys) {
    $path = $files[$key]
    Write-Host "Deploying $key from $path..."
    if (Test-Path $path) {
        $content = Get-Content $path -Raw
        $body = @{
            pass    = "bxzziug_secret"
            file    = $key
            content = $content
        }
        try {
            $resp = Invoke-WebRequest -Method Post -Uri $url -Body $body -UserAgent "ShadowPulse-Deployer"
            Write-Host "Success: $($resp.Content)"
        }
        catch {
            Write-Error "Failed to deploy $key : $_"
        }
    }
    else {
        Write-Error "File not found: $path"
    }
}
