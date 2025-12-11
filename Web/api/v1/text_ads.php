<?php
// text_ads.php - ShadowPulse text ads wrapper around Revive Adserver.
// Returns JSON suitable for the toolbar's 2-line text ad zone.
//
// Example successful response:
//   { "ok": true, "text": "Your ad text here", "url": "https://..." }
//
// Example failure response:
//   { "ok": false, "text": "Advertise your product here", "url": null }

require __DIR__ . '/cors.php';
header('Content-Type: application/json');

// ---- CONFIG --------------------------------------------------------------

// Default zone id; override with ?zoneid=2 if you want.
$zoneId = isset($_GET['zoneid']) ? (int)$_GET['zoneid'] : 1;
if ($zoneId <= 0) {
    $zoneId = 1;
}

// Base URL to your Revive adserver delivery scripts.
$reviveBase = 'https://vod.fan/adserver/www/delivery';

// HTTP client settings
$timeoutSeconds        = 3;
$connectTimeoutSeconds = 2;

// ---- CALL REVIVE ---------------------------------------------------------

$cb  = mt_rand() . mt_rand(); // simple cache-buster
$url = $reviveBase . '/avw.php?zoneid=' . urlencode((string)$zoneId) .
       '&cb=' . urlencode((string)$cb);

if (!function_exists('curl_init')) {
    // cURL not available - log and fallback.
    error_log('text_ads.php: cURL extension not available');
    echo json_encode([
        'ok'   => false,
        'text' => 'Advertise your product here',
        'url'  => null,
    ]);
    exit;
}

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER  => true,
    CURLOPT_FOLLOWLOCATION  => true,
    CURLOPT_TIMEOUT         => $timeoutSeconds,
    CURLOPT_CONNECTTIMEOUT  => $connectTimeoutSeconds,
    CURLOPT_USERAGENT       => 'ShadowPulse-TextAds/1.0',
]);

$html  = curl_exec($ch);
$error = curl_error($ch);
$http  = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($html === false || $http !== 200) {
    error_log('text_ads.php: Revive request failed: HTTP ' . $http . ' ' . $error);
    echo json_encode([
        'ok'   => false,
        'text' => 'Advertise your product here',
        'url'  => null,
    ]);
    exit;
}

// ---- EXTRACT TEXT & URL --------------------------------------------------

// Typical Revive banners for text ads are small HTML snippets, usually
// an <a> tag with the ad text inside. We try to extract that link & text.
// If that fails, we strip tags and use whatever text remains.

$adText = null;
$adUrl  = null;

// Make sure DOM extension exists
if (class_exists('DOMDocument')) {
    libxml_use_internal_errors(true);
    $dom = new DOMDocument();

    // Wrap in a basic HTML scaffold to keep DOMDocument happy.
    $wrappedHtml = '<!doctype html><html><body>' . $html . '</body></html>';

    if ($dom->loadHTML($wrappedHtml)) {
        $xpath = new DOMXPath($dom);
        $link  = $xpath->query('//a')->item(0);

        if ($link) {
            $adUrl  = $link->getAttribute('href') ?: null;
            $adText = trim($link->textContent);
        } else {
            // Fallback: no <a>, just get the visible text.
            $adText = trim(strip_tags($html));
        }
    }
    libxml_clear_errors();
} else {
    // DOM extension missing, use a simple fallback.
    error_log('text_ads.php: DOM extension not available, using strip_tags fallback');
    $adText = trim(strip_tags($html));
}

// Final fallback if parsing was empty
if ($adText === null || $adText === '') {
    $adText = 'Advertise your product here';
}

echo json_encode([
    'ok'   => true,
    'text' => $adText,
    'url'  => $adUrl ?: null,
]);
