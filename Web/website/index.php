<?php
require_once __DIR__ . '/../api/v1/cors.php';

$pageTitle = "Dashboard";
$pageSubtitle = "ShadowPulse Overview";

include __DIR__ . '/header.php';
?>

<section class="content">
    <div class="content-header">
        <div class="content-title">Dashboard</div>
        <div class="content-subtitle">System Overview</div>
    </div>
    <div class="content-body">
        <p>Welcome to the ShadowPulse Dashboard.</p>
    </div>
</section>

<aside class="side-panel">
    <div class="side-panel-title">Quick Links</div>
    <div class="side-panel-highlight">
        Access reports, members, topics and analytics.
    </div>
</aside>

<?php include __DIR__ . '/footer.php'; ?>