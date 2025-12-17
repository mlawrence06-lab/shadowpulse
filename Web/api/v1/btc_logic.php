<?php
// btc_logic.php
// Shared Logic for fetching/caching Bitcoin Price.
// Returns array: ['price_label', 'trend', 'history', 'price_val', 'debug_error'...]

function get_btc_data($pdo)
{
    $symbol = 'BTCUSDT';
    $responsePayload = [
        'price_label' => 'Loading...',
        'trend' => 'neutral',
        'history' => []
    ];

    try {
        // 1. CHECK IF DATA IS STALE (OLDER THAN 5 MINS)
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
            curl_setopt($ch, CURLOPT_TIMEOUT, 5); // Fast timeout to avoid blocking page context heavily
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

        // 2. Serve Data
        $stmtGet = $pdo->prepare("
            SELECT close_price 
            FROM (
                SELECT close_price, candle_time
                FROM btc_price_history
                WHERE symbol = :s
                ORDER BY candle_time DESC
                LIMIT 60
            ) sub
            ORDER BY candle_time ASC
        ");
        $stmtGet->execute([':s' => $symbol]);
        $rows = $stmtGet->fetchAll(PDO::FETCH_ASSOC);

        if (!empty($rows)) {
            $first = reset($rows);
            $last = end($rows);

            $price = (float) $last['close_price'];
            $start = (float) $first['close_price'];
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
        } else {
            $responsePayload['price_label'] = 'No Data';
        }

    } catch (Throwable $e) {
        $responsePayload['debug_error'] = $e->getMessage();
    }

    return $responsePayload;
}
?>