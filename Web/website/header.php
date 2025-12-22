<?php
require_once __DIR__ . '/../api/v1/cors.php';

if (!isset($pageTitle)) {
    $pageTitle = 'ShadowPulse';
}
if (!isset($pageSubtitle)) {
    $pageSubtitle = '';
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title><?php echo htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8'); ?> – ShadowPulse</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="/shadowpulse/website/shadowpulse.css">
</head>

<body>
    <div class="top-separator"></div>

    <div class="page-shell">
        <header class="site-header">
            <?php
            // Default active page detection
            if (!isset($activePage)) {
                if (strpos($_SERVER['SCRIPT_NAME'], '/reports/') !== false) {
                    $activePage = 'reports';
                } elseif (strpos($_SERVER['SCRIPT_NAME'], 'search') !== false) {
                    $activePage = 'search';
                } else {
                    $activePage = 'dashboard'; // Default to dashboard for non-report pages
                }
            }
            ?>
            <a href="/shadowpulse/website/" class="brand" style="text-decoration: none;">
                <div class="brand-logo" aria-hidden="true">
                    <img src="/shadowpulse/website/images/logo_web.svg" alt="ShadowPulse Logo" width="48" height="48"
                        style="display:block; border-radius: 50%;">
                </div>
                <div class="brand-text">
                    <div class="brand-name">
                        ShadowPulse
                    </div>
                    <div class="brand-tagline">TAGLINE CONTEST!</div>
                </div>
            </a>

            <?php if (strpos($_SERVER['SCRIPT_NAME'], '/reports/') !== false): ?>
                <div class="header-banner" style="margin: 0 5px;">
                    <!-- Revive Adserver Asynchronous JS Tag - Generated with Revive Adserver v6.0.4 -->
                    <ins data-revive-zoneid="3" data-revive-id="d25be6bbfc14f64ec3435931485e35e2"></ins>
                    <script async src="//vod.fan/adserver/www/delivery/asyncjs.php"></script>
                </div>
            <?php endif; ?>

            <nav class="site-nav" aria-label="Main navigation">
                <a href="/shadowpulse/website/index.php"
                    class="nav-link <?php echo ($activePage === 'dashboard') ? 'nav-link-active' : ''; ?>">
                    <span class="dot" aria-hidden="true"></span>
                    <span>Dashboard</span>
                </a>
                <a href="/shadowpulse/website/search-bitlist.php"
                    class="nav-link <?php echo ($activePage === 'search') ? 'nav-link-active' : ''; ?>">
                    <span class="dot" aria-hidden="true"></span>
                    <span>Search</span>
                </a>
                <a href="/shadowpulse/website/reports/index.php"
                    class="nav-link <?php echo ($activePage === 'reports') ? 'nav-link-active' : ''; ?>">
                    <span class="dot" aria-hidden="true"></span>
                    <span>Reports</span>
                </a>
                <a href="https://vod.fan" class="nav-link">
                    <span class="dot" aria-hidden="true"></span>
                    <span>Fans of Vod</span>
                </a>
            </nav>
        </header>



        <main class="site-main">