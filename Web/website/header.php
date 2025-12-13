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
            <div class="brand">
                <div class="brand-logo" aria-hidden="true">
                    <img src="/shadowpulse/website/images/logo_web.svg" alt="ShadowPulse Logo" width="32" height="32"
                        style="display:block; border-radius: 50%;">
                </div>
                <div class="brand-text">
                    <div class="brand-name">
                        ShadowPulse
                    </div>
                    <div class="brand-tagline">Bitcointalk Alternative Recognition</div>
                </div>
            </div>

            <?php
            // Default active page if not set
            if (!isset($activePage)) {
                $activePage = '';
            }
            ?>
            <nav class="site-nav" aria-label="Main navigation">
                <a href="/shadowpulse/website/index.php"
                    class="nav-link <?php echo ($activePage === 'dashboard') ? 'nav-link-active' : ''; ?>">
                    <span class="dot" aria-hidden="true"></span>
                    <span>Dashboard</span>
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