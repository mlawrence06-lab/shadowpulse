<?php
// test_ninja_post.php
function fetch_json($url)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_USERAGENT, 'ShadowPulse/0.1');
    $res = curl_exec($ch);
    curl_close($ch);
    return json_decode($res, true);
}

// Test Post: 66145706 (From user screenshot)
$url = "https://api.ninjastic.space/posts?pid=66145706&limit=1";
echo "Fetching: $url\n\n";

$data = fetch_json($url);
echo json_encode($data, JSON_PRETTY_PRINT);
?>