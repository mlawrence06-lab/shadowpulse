<?php
// get_page_context.php
// Mega-Endpoint: Fetches Member Stats + Target Vote Summary in one go.

// Handle CORS Inline (Remove require dependency)
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

header('Content-Type: application/json');
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Debug Logging
$logRequest = date('[Y-m-d H:i:s] ') . "CTX_REQ: uuid=" . ($memberUuid ?? 'x') . " cat=" . ($category ?? 'x') . " id=" . ($targetId ?? 'x') . "\n";
file_put_contents(__DIR__ . '/context_debug.log', $logRequest, FILE_APPEND);
// Enable Error Reporting for Debugging (Temporary) - REMOVED
// ini_set('display_errors', 0);
// error_reporting(0);
// session_start(); // REMOVED: Causes blocking with concurrent requests

// 1. Inputs
$memberUuid = isset($_GET['member_uuid']) ? trim($_GET['member_uuid']) : '';
$category = isset($_GET['category']) ? trim($_GET['category']) : '';
$targetId = isset($_GET['target_id']) ? (int) $_GET['target_id'] : 0;

if ($targetId <= 0 || empty($category)) {
    echo json_encode(['ok' => false, 'error' => 'Missing fields']);
    exit;
}

$dbFile = __DIR__ . '/../../config/db.php';
if (!file_exists($dbFile)) {
    die(json_encode(['ok' => false, 'error' => 'DB Config Missing']));
}
require $dbFile;

try {
    $pdo = sp_get_pdo();

    // 2. Call Stored Procedure
    $stmt = $pdo->prepare("CALL shadowpulse_get_page_context(?, ?, ?)");
    $stmt->execute([$memberUuid, $category, $targetId]);

    // 3. Extract Result Set 1: Member Stats & Bootstrap Info
    $memberStats = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$memberStats) {
        $memberStats = ['total_points' => 0, 'calculated_rank' => 1, 'member_id' => 0, 'restore_ack' => 0];
    }

    // Move to next result set
    $stmt->nextRowset();

    // 4. Extract Result Set 2: Context (Vote Summary)
    $context = $stmt->fetch(PDO::FETCH_ASSOC);

    // 5. Extract Result Set 3: BTC History
    $btcRows = [];
    do {
        if ($stmt->nextRowset()) {
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if (!empty($rows) && isset($rows[0]['close_price'])) {
                $btcRows = $rows;
                break;
            }
        }
    } while ($stmt->columnCount() > 0); // Keep iterating until no more rowsets

    $stmt = null; // Close SP

    // 6. Process BTC Stats (Pure PHP Logic)
    $btcData = ['price_label' => 'Loading...', 'trend' => 'neutral', 'history' => []];

    // Check staleness
    $isStale = true;
    if (!empty($btcRows)) {
        // btcRows is DESC (latest first) from SP
        $lastRow = $btcRows[0];
        if (isset($lastRow['candle_time'])) {
            $lastTime = strtotime($lastRow['candle_time']);
            if (time() - $lastTime < 300) {
                $isStale = false;
            }
        }
    }

    if ($isStale) {
        // Only verify/fetch if stale (This involves DB WRITE, so acceptable as strict "One Read" is maintained)
        $btcFile = __DIR__ . '/btc_logic.php';
        if (file_exists($btcFile)) {
            require_once $btcFile;
            // Calls external API + INSERTs
            $btcData = get_btc_data($pdo);
        }
    } else {
        // Formulate response from SP data (No DB Call!)
        // Re-sort to ASC for history chart
        $ascRows = array_reverse($btcRows);
        $price = (float) $btcRows[0]['close_price']; // Latest
        $start = (float) $ascRows[0]['close_price']; // Oldest in window
        $trend = ($price >= $start) ? 'up' : 'down';

        $history = array_map(function ($r) {
            return (float) $r['close_price'];
        }, $ascRows);

        $btcData = [
            'price_val' => $price,
            'price_label' => '$' . number_format($price, 2),
            'trend' => $trend,
            'history' => $history
        ];
    }

    // 6. Structure Response
    $response = [
        'ok' => true,
        'member_info' => [
            'member_id' => (int) ($memberStats['member_id'] ?? 0),
            'restore_ack' => (int) ($memberStats['restore_ack'] ?? 0),
        ],
        'member_stats' => [ // Legacy/Display stats
            'rank' => (int) ($memberStats['calculated_level'] ?? 1),
            'points' => (int) ($memberStats['total_points'] ?? 0),
            'page_views' => (int) ($memberStats['page_views'] ?? 0),
            'topic_votes' => (int) ($memberStats['topic_votes'] ?? 0),
            'post_votes' => (int) ($memberStats['post_votes'] ?? 0)
        ],
        'context' => [
            'user_vote' => [
                'effective' => isset($context['user_effective']) ? (int) $context['user_effective'] : null,
                'desired' => isset($context['user_desired']) ? (int) $context['user_desired'] : null,
            ],
            'summary' => [
                'vote_count' => (int) ($context['vote_count'] ?? 0),
                'score' => (float) ($context['average_score'] ?? 0),
                'rank' => (int) ($context['item_rank'] ?? 0),
                'label' => $context['target_label']
            ]
        ],
        'btc_stats' => $btcData
    ];

    echo json_encode($response);

    // 7. Background Queue
    if (function_exists('fastcgi_finish_request')) {
        fastcgi_finish_request();
    }

    try {
        $qFile = __DIR__ . '/../core/queue_runner.php';
        if (file_exists($qFile)) {
            require_once $qFile;
            if (function_exists('run_ninja_queue')) {
                run_ninja_queue($pdo);
            }
        }
    } catch (Throwable $e) {
        // Queue error ignored
    }

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
?>