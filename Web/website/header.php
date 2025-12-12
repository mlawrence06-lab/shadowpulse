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
    <link rel="stylesheet" href="shadowpulse.css">
</head>

<body>
    <div class="top-separator"></div>

    <div class="page-shell">
        <header class="site-header">
            <div class="brand">
                <div class="brand-logo" aria-hidden="true">
                    <img src="images/logo_web.svg" alt="ShadowPulse Logo" width="32" height="32"
                        style="display:block; border-radius: 50%;">
                </div>
                <div class="brand-text">
                    <div class="brand-name">
                        ShadowPulse
                    </div>
                    <div class="brand-tagline">Bitcointalk Alternative Recognition</div>
                </div>
            </div>

            <nav class="site-nav" aria-label="Main navigation">
                <a href="index.php" class="nav-link">
                    <span class="dot" aria-hidden="true"></span>
                    <span>Dashboard</span>
                </a>
                <a href="members.php" class="nav-link">
                    <span class="dot" aria-hidden="true"></span>
                    <span>Members</span>
                </a>
                <a href="reports/index.php" class="nav-link">
                    <span class="dot" aria-hidden="true"></span>
                    <span>Reports</span>
                </a>
                <a href="search.php" class="nav-link nav-link-active">
                    <span class="dot" aria-hidden="true"></span>
                    <span>Search</span>
                </a>
                <a href="https://vod.fan" class="nav-link">
                    <span class="dot" aria-hidden="true"></span>
                    <span>Fans of Vod</span>
                </a>
            </nav>
        </header>

        <main class="site-main">