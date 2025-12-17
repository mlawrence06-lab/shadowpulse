
$ids = @(5566936, 5213618, 5566235, 5568246)
$urlBase = "https://api.ninjastic.space/posts?limit=1&topic_id="
$sql = "INSERT INTO topics_info (topic_id, topic_title) VALUES "
$values = @()

foreach ($id in $ids) {
    try {
        $uri = "$urlBase$id"
        $json = Invoke-RestMethod -Uri $uri -Method Get
        if ($json -and $json.Count -gt 0) {
            $title = $json[0].topic_title.Replace("'", "''") # Escape SQL
            $values += "($id, '$title')"
            Write-Host "Found: $id -> $title"
        }
    }
    catch {
        Write-Host "Error fetching $id"
    }
}

if ($values.Count -gt 0) {
    $finalSql = $sql + ($values -join ",") + " ON DUPLICATE KEY UPDATE topic_title = VALUES(topic_title);"
    $finalSql | Out-File -Encoding utf8 "populate_topics.sql"
    Write-Host "Generated populate_topics.sql"
}
