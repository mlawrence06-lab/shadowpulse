<?php
require_once __DIR__ . '/../../api/v1/cors.php';

$pageTitle = "Most Visited Posts";
$pageSubtitle = "Post‑level view analytics";

include __DIR__ . '/header.php';
?>

<section class="content">
    <div class="content-header">
        <div class="content-title">Most visited posts</div>
        <div class="content-subtitle">
            Individual posts that receive repeated attention.
        </div>
    </div>

    <div class="content-body">
        <p class="hint">
            This is a stub for the <strong>Most visited: Posts</strong> report.
            Later, populate it from your ShadowPulse visit logs.
        </p>

        <ul class="result-list">
            <li class="result-item">
                <div class="result-title">
                    <a href="#" onclick="return false;">Example post title snippet…</a>
                </div>
                <div class="result-meta">
                    Post ID 654321 • Topic 123456 • 4,200 visits
                </div>
                <div class="result-snippet">
                    Use this area to show a short excerpt from the post body with ellipsis and basic meta.
                </div>
            </li>
        </ul>
    </div>
</section>

<aside class="side-panel">
    <div class="side-panel-title">Implementation notes</div>
    <div class="side-panel-highlight">
        Track unique post URLs and aggregate visits. Consider showing referrer breakdown and
        repeat visitor ratios per post.
    </div>
</aside>

<?php include __DIR__ . '/footer.php'; ?>