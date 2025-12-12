<?php
require_once __DIR__ . '/../../api/v1/cors.php';

$pageTitle = "Most Visited Topics";
$pageSubtitle = "Thread‑level view analytics";

include __DIR__ . '/header.php';
?>

<section class="content">
    <div class="content-header">
        <div class="content-title">Most visited topics</div>
        <div class="content-subtitle">
            High‑traffic topics ranked by total visits.
        </div>
    </div>

    <div class="content-body">
        <p class="hint">
            This is a stub for the <strong>Most visited: Topics</strong> report.
            Swap in real topic rows and add filters when ready.
        </p>

        <ul class="result-list">
            <li class="result-item">
                <div class="result-title">
                    <a href="#" onclick="return false;">Example: &ldquo;ShadowPulse feedback and roadmap&rdquo;</a>
                </div>
                <div class="result-meta">
                    Topic ID 123456 • 12,340 visits • Last active 2025‑11‑28
                </div>
                <div class="result-snippet">
                    Show topic title, board, creator, total visits, replies, and last post date.
                </div>
            </li>
        </ul>
    </div>
</section>

<aside class="side-panel">
    <div class="side-panel-title">Implementation notes</div>
    <div class="side-panel-highlight">
        Join topic metadata with your visit logs to compute visit counts
        and time‑windowed rankings (24h, 7d, 30d, all‑time).
    </div>
</aside>

<?php include __DIR__ . '/footer.php'; ?>