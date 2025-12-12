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
    if (!file_exists(__DIR__ . '/../../config/db.php')) {
        throw new Exception("db.php not found");
    }
    require_once __DIR__ . '/../../config/db.php';

    if (!function_exists('sp_get_pdo')) {
        throw new Exception("sp_get_pdo missing");
    }
    $pdo = sp_get_pdo();

    // 4. CHECK IF DATA IS STALE (OLDER THAN 5 MINS)
    $stmtLast = $pdo->prepare("SELECT candle_time FROM btc_price_history WHERE symbol = :s ORDER BY candle_time DESC LIMIT 1");
    $stmtLast->execute([':s' => $symbol]);
    $lastRow = $stmtLast->fetch(PDO::FETCH_ASSOC);

    $isStale = true;
    if ($lastRow && isset($lastRow['candle_time'])) {
        $lastTime = strtotime($lastRow['candle_time']);
        if (time() - $lastTime < 300) { // 5 minutes
            $isStale = false;
        }
    }

    if ($isStale) {
        // FETCH FROM BINANCE (Limit 60 candles, 1m interval)
        $url = "https://api.binance.com/api/v3/klines?symbol=BTCUSDT&interval=1m&limit=60";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $json = curl_exec($ch);
        curl_close($ch);

        $data = json_decode($json, true);
        if (is_array($data) && count($data) > 0) {
            // Insert/Update DB
            $stmtInsert = $pdo->prepare("
                INSERT IGNORE INTO btc_price_history (symbol, candle_time, open_price, high_price, low_price, close_price, volume)
                VALUES (:sym, :time, :open, :high, :low, :close, :vol)
            ");

            foreach ($data as $candle) {
                // Binance format: [time, open, high, low, close, vol, ...]
                // Time is ms timestamp
                $ts = $candle[0] / 1000;
                $dt = date('Y-m-d H:i:s', $ts);

                $stmtInsert->execute([
                    ':sym' => $symbol,
                    ':time' => $dt,
                    ':open' => $candle[1],
                    ':high' => $candle[2],
                    ':low' => $candle[3],
                    ':close' => $candle[4],
                    ':vol' => $candle[5]
                ]);
            }
        }
    }

    // 5. Serve Data (Now Guaranteed Fresh-ish)
    // We fetch the last 60 minutes of data stored in the database.
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
        $last = end($rows);   // Most recent price (in DB terms)

        $price = (float) $last['close_price'];
        $start = (float) $first['close_price'];

        // Trend calculation
        $trend = ($price >= $start) ? 'up' : 'down';

        $history = array_map(function ($r) {
            return (float) $r['close_price'];
        }, $rows);

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