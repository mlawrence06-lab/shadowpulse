<?php
// cron_btc_ticker.php

// 1. Include your existing database connection helper
require_once __DIR__ . '/db.php';

// 2. Configuration
$symbol = 'BTCUSDT';
$apiUrl = "https://api.binance.com/api/v3/klines?symbol={$symbol}&interval=1m&limit=1";

try {
    // Get the PDO instance using your helper function
    $pdo = sp_get_pdo();

    // 3. Ensure the table exists (Run this once, or leave it here for safety)
    // We use DECIMAL for prices to ensure precision (no floating point errors)
    $createTableSql = "
        CREATE TABLE IF NOT EXISTS btc_price_history (
            id BIGINT AUTO_INCREMENT PRIMARY KEY,
            symbol VARCHAR(10) NOT NULL,
            candle_time DATETIME NOT NULL,
            open_price DECIMAL(20, 8) NOT NULL,
            high_price DECIMAL(20, 8) NOT NULL,
            low_price DECIMAL(20, 8) NOT NULL,
            close_price DECIMAL(20, 8) NOT NULL,
            volume DECIMAL(20, 8) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_candle (symbol, candle_time)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ";
    $pdo->exec($createTableSql);

    // 4. Fetch data from Binance
    // We use cURL for better reliability than file_get_contents
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200 || !$response) {
        throw new Exception("Failed to fetch data from Binance. HTTP Code: $httpCode");
    }

    $data = json_decode($response, true);

    if (empty($data) || !isset($data[0])) {
        throw new Exception("Invalid data received from Binance.");
    }

    // 5. Parse the data
    // Binance returns array of arrays. Index 0 is the candlestick data.
    // [0] = Open time (ms), [1] = Open, [2] = High, [3] = Low, [4] = Close, [5] = Volume
    $candle = $data[0];

    $timestampMs = $candle[0];
    $open        = $candle[1];
    $high        = $candle[2];
    $low         = $candle[3];
    $close       = $candle[4];
    $volume      = $candle[5];

    // Convert Binance timestamp (ms) to MySQL datetime format
    $candleTime = date('Y-m-d H:i:s', $timestampMs / 1000);

    // 6. Insert into Database
    // We use INSERT IGNORE so if the cron runs twice in the same minute, it won't crash.
    $sql = "INSERT IGNORE INTO btc_price_history 
            (symbol, candle_time, open_price, high_price, low_price, close_price, volume) 
            VALUES 
            (:symbol, :candle_time, :open, :high, :low, :close, :volume)";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':symbol'      => $symbol,
        ':candle_time' => $candleTime,
        ':open'        => $open,
        ':high'        => $high,
        ':low'         => $low,
        ':close'       => $close,
        ':volume'      => $volume,
    ]);

    // Optional: Output for testing (if running manually)
    if (php_sapi_name() === 'cli') {
        echo "Success: Logged $symbol at $candleTime ($close)\n";
    } else {
        echo "Success: Logged $symbol at $candleTime ($close)";
    }

} catch (Throwable $e) {
    // Log errors to your server's error log
    error_log("ShadowPulse BTC Ticker Error: " . $e->getMessage());
    // Also print if testing
    echo "Error: " . $e->getMessage();
}