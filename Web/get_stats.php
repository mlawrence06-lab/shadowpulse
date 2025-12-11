<?php
// get_stats.php
// ShadowPulse Backend: Read-Only BTC Stats (No External API Calls)

// 1. FORCE HEADERS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Content-Type: application/json");

// 2. Error Handling
ob_start();
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

$symbol = 'BTCUSDT';
$responsePayload = [];

try {
    // 3. Load Dependencies
    if (!file_exists(__DIR__ . '/db.php')) {
        throw new Exception("db.php not found");
    }
    require_once __DIR__ . '/db.php';

    if (!function_exists('sp_get_pdo')) {
        throw new Exception("sp_get_pdo missing");
    }
    $pdo = sp_get_pdo();

    // 4. Serve Data (READ ONLY)
    // We fetch the last 60 minutes of data stored in the database.
    // If the database is not updated manually/externally, this data will get stale.
    $stmtGet = $pdo->prepare("
        SELECT close_price, open_price 
        FROM (
            SELECT close_price, open_price, candle_time
            FROM btc_price_history
            WHERE symbol = :s
            ORDER BY candle_time DESC
            LIMIT 60
        ) sub
        ORDER BY candle_time ASC
    ");
    $stmtGet->execute([':s' => $symbol]);
    $rows = $stmtGet->fetchAll(PDO::FETCH_ASSOC);

    if (empty($rows)) {
        // No data in DB
        $responsePayload = [
            'price_label' => 'No Data',
            'trend' => 'neutral',
            'history' => []
        ];
    } else {
        $first = reset($rows); // Price ~60 mins ago (in DB terms)
        $last  = end($rows);   // Most recent price (in DB terms)
        
        $price = (float)$last['close_price'];
        $start = (float)$first['close_price']; 
        
        // Trend calculation
        $trend = ($price >= $start) ? 'up' : 'down';
        
        $history = array_map(function($r) { return (float)$r['close_price']; }, $rows);

        $responsePayload = [
            'price_val' => $price,
            'price_label' => '$' . number_format($price, 2),
            'trend' => $trend,
            'history' => array_values($history)
        ];
    }

} catch (Throwable $e) {
    // Return error as JSON
    $responsePayload = [
        'price_label' => 'Error',
        'trend' => 'down',
        'history' => [],
        'debug_error' => $e->getMessage()
    ];
}

ob_clean();
echo json_encode($responsePayload);
exit;
?>