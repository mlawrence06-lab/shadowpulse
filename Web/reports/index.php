<?php
require __DIR__ . '/cors.php';

$pageTitle = "Reports Home";
$pageSubtitle = "Analytics & Data Exports";

include __DIR__ . '/header.php';
?>

<section class="content">
    <div class="content-header">
        <div class="content-title">Reports Center</div>
        <div class="content-subtitle">
            Most visited: Boards, Topics, Posts, Profiles
        </div>
    </div>

    <div class="content-body">
        <p class="hint">
            Most visited:
            <a href="boards.php">Boards</a>,
            <a href="topics.php">Topics</a>,
            <a href="posts.php">Posts</a>,
            <a href="profiles.php">Profiles</a>
        </p>

        <ul class="result-list">
            <li class="result-item">
                <div class="result-title">
                    <a href="boards.php">Most visited boards</a>
                </div>
                <div class="result-meta">
                    Ranking of boards by total visits / page views.
                </div>
                <div class="result-snippet">
                    Show which Bitcointalk boards receive the most attention over a selected time range.
                </div>
            </li>

            <li class="result-item">
                <div class="result-title">
                    <a href="topics.php">Most visited topics</a>
                </div>
                <div class="result-meta">
                    Leaderboard of high‑traffic discussion threads.
                </div>
                <div class="result-snippet">
                    Highlight trending and historically popular topics, sorted by view counts or custom metrics.
                </div>
            </li>

            <li class="result-item">
                <div class="result-title">
                    <a href="posts.php">Most visited posts</a>
                </div>
                <div class="result-meta">
                    Individual posts with exceptional visit volumes.
                </div>
                <div class="result-snippet">
                    Surface posts that attract repeated attention, bookmarks, or off‑site referrals.
                </div>
            </li>

            <li class="result-item">
                <div class="result-title">
                    <a href="profiles.php">Most visited profiles</a>
                </div>
                <div class="result-meta">
                    Member profiles receiving the most views.
                </div>
                <div class="result-snippet">
                    See which users are checked the most, by rank, role, or recent activity.
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

<?php include __DIR__ . '/footer.php'; ?>
