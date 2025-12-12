<?php
require_once __DIR__ . '/../api/v1/cors.php';

$pageTitle = "Global Statistics";
$pageSubtitle = "Top ShadowPulse Users";

include __DIR__ . '/header.php';
?>

<section class="shadowpulse-section">
    <div class="container">
        <h1 class="page-title">Global Leaderboards</h1>
        <p>Top users by Views, Searches, and Votes.</p>

        <div class="stat-grid">
            <!-- Placeholder for Stats -->
            <div class="stat-card">
                <h3>Most Page Views</h3>
                <p>Coming Soon...</p>
            </div>
            <div class="stat-card">
                <h3>Most Searches</h3>
                <p>Coming Soon...</p>
            </div>
        </div>
    </div>
</section>

<?php include __DIR__ . '/footer.php'; ?>