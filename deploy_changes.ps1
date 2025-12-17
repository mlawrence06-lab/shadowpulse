$user = "bxzziug"
$pass = "s57MMJM0GHUabZLij7Z6V5iwwVe5a2"
$hostBase = "ftp://ftp.cluster051.hosting.ovh.net/vod.fan/shadowpulse"

$uploads = @{
    "Web/config/db.php"                          = "$hostBase/config/db.php";
    "Web/api/v1/top_lists.php"                   = "$hostBase/api/v1/top_lists.php";
    "Web/website/reports/top_charts.php"         = "$hostBase/website/reports/top_charts.php";
    "Web/api/v1/run_sql_file.php"                = "$hostBase/api/v1/run_sql_file.php";
    "Web/database/optimize_polling.sql"          = "$hostBase/database/optimize_polling.sql";
    "Web/database/check_stats.sql"               = "$hostBase/database/check_stats.sql";
    "Web/api/v1/vote.php"                        = "$hostBase/api/v1/vote.php";
    "Web/api/v1/get_vote.php"                    = "$hostBase/api/v1/get_vote.php";
    "Web/api/v1/ninja_helper.php"                = "$hostBase/api/v1/ninja_helper.php";
    "Web/api/v1/cors.php"                        = "$hostBase/api/v1/cors.php";
    "Web/api/v1/member_stats.php"                = "$hostBase/api/v1/member_stats.php";
    "Web/api/v1/get_page_context.php"            = "$hostBase/api/v1/get_page_context.php";
    "Web/api/v1/get_stats.php"                   = "$hostBase/api/v1/get_stats.php";
    "Web/database/check_member.sql"              = "$hostBase/database/check_member.sql";
    "Web/api/core/metadata.php"                  = "$hostBase/api/core/metadata.php";
    "Web/database/optimize_one_call.sql"         = "$hostBase/database/optimize_one_call.sql"
    "Web/database/update_vote_cascades.sql"      = "$hostBase/database/update_vote_cascades.sql";
    "Web/database/update_member_stats_logic.sql" = "$hostBase/database/update_member_stats_logic.sql";
    "Web/database/add_last_active_column.sql"    = "$hostBase/database/add_last_active_column.sql";
    "Web/database/optimize_one_call.sql"         = "$hostBase/database/optimize_one_call.sql"
}

foreach ($key in $uploads.Keys) {
    $dest = $uploads[$key]
    Write-Host "Uploading $key to $dest ..."
    # Usecmd /c to run curl.exe to avoid powershell alias issues
    $cmd = "curl.exe -T `"$key`" --user `"$user`:$pass`" `"$dest`" --ftp-create-dirs"
    Invoke-Expression $cmd
}
Write-Host "Deployment complete."
