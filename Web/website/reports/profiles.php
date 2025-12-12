<?php
require_once __DIR__ . '/../../api/v1/cors.php';

$pageTitle = "Most Visited Profiles";
$pageSubtitle = "Profile‑view analytics";

include __DIR__ . '/header.php';
?>

<section class="content">
    <div class="content-header">
        <div class="content-title">Most visited profiles</div>
        <div class="content-subtitle">
            Bitcointalk members whose profiles are viewed the most.
        </div>
    </div>

    <div class="content-body">
        <p class="hint">
            This is a stub for the <strong>Most visited: Profiles</strong> report.
            Wire it up to your profile‑view tracking later.
        </p>

        <ul class="result-list">
            <li class="result-item">
                <div class="result-title">
                    <a href="#" onclick="return false;">ExampleUser</a>
                </div>
                <div class="result-meta">
                    UID 987654 • Rank: Sr. Member • 1,200 profile views this month
                </div>
                <div class="result-snippet">
                    Display username, UID, rank, and profile‑view counts,
                    plus trend indicators like month‑over‑month change.
                </div>
            </li>
        </ul>
    </div>
</section>

<aside class="side-panel">
    <div class="side-panel-title">Implementation notes</div>
    <div class="side-panel-highlight">
        Group profile view logs by UID and expose filters by rank, board activity, or time range.
    </div>
</aside>

<?php include __DIR__ . '/footer.php'; ?>