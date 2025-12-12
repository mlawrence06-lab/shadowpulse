<?php
require_once __DIR__ . '/../../api/v1/cors.php';

$pageTitle = "Most Visited Boards";
$pageSubtitle = "Board‑level view analytics";

include __DIR__ . '/header.php';
?>

<section class="content">
    <div class="content-header">
        <div class="content-title">Most visited boards</div>
        <div class="content-subtitle">
            High‑traffic Bitcointalk boards ranked by visits.
        </div>
    </div>

    <div class="content-body">
        <p class="hint">
            This is a stub for the <strong>Most visited: Boards</strong> report.
            Replace the placeholder table/list below with real query results.
        </p>

        <ul class="result-list">
            <li class="result-item">
                <div class="result-title">Example: Bitcoin Discussion</div>
                <div class="result-meta">Rank #1 • 42,000 visits this month</div>
                <div class="result-snippet">
                    Replace this with live board stats (ID, name, total visits, unique visitors, trends, etc.).
                </div>
            </li>
            <li class="result-item">
                <div class="result-title">Example: Altcoin Discussion</div>
                <div class="result-meta">Rank #2 • 33,500 visits this month</div>
                <div class="result-snippet">
                    You can mirror this card style for each board row returned from your database.
                </div>
            </li>
        </ul>
    </div>
</section>

<aside class="side-panel">
    <div class="side-panel-title">Implementation notes</div>
    <div class="side-panel-highlight">
        Use aggregated page‑view data grouped by board ID. Expose filters for time range,
        language, or traffic source in a future iteration.
    </div>
</aside>

<?php include __DIR__ . '/footer.php'; ?>