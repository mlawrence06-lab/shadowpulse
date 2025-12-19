<?php
require_once __DIR__ . '/../../api/v1/cors.php';

$pageTitle = "Reports Home";
$pageSubtitle = "Analytics & Data Exports";

include __DIR__ . '/../header.php';
?>

<section class="content">
    <div class="content-header">
        <div class="content-title">Reports Center</div>
        <div class="content-subtitle">

        </div>
    </div>

    <div class="content-body">
        <ul class="result-list">
            <li class="result-item">
                <div class="result-title">
                    <a href="ranking.php">Ranking Reports</a>
                </div>
                <div class="result-meta">
                    Top ranked members by views, searches, votes, and installs.
                </div>
                <div class="result-snippet">
                    Visual ranking reports using charts and grids.
                </div>
            </li>

            <li class="result-item">
                <div class="result-title">
                    <a href="top_charts.php">Top Charts</a>
                </div>
                <div class="result-meta">
                    Highest rated Members, Boards, Topics, and Posts.
                </div>
                <div class="result-snippet">
                    Leaderboards sorted by Average Score (Votes).
                </div>
            </li>

            <li class="result-item">
                <div class="result-title">
                    <a href="most_visited.php">Most Visited Content</a>
                </div>
                <div class="result-meta">
                    Top ranked Members, Boards, Topics, and Posts by Page Views.
                </div>
                <div class="result-snippet">
                    Highlight trending and historically popular content sorted by total visits.
                </div>
            </li>
        </ul>
    </div>
</section>

<aside class="side-panel">
    <div class="side-panel-title">Reports overview</div>
    <div class="side-panel-highlight">
        These reports are placeholders wired into the ShadowPulse layout.
        Connect them to your database or API to power live “most visited” metrics.
    </div>
    <div class="side-panel-note">
        Start by implementing one report at a time and reusing shared query helpers
        for boards, topics, posts, and profiles.
    </div>
</aside>

<?php include __DIR__ . '/../footer.php'; ?>