<?php
require __DIR__ . '/cors.php';

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
                <svg width="22" height="22" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" role="img">
                    <path d="M3 13h3l2-6 3.5 10 2.5-7 1 3h4"
                          fill="none"
                          stroke="currentColor"
                          stroke-width="1.6"
                          stroke-linecap="round"
                          stroke-linejoin="round" />
                </svg>
            </div>
            <div class="brand-text">
                <div class="brand-name">
                    <span class="highlight">SHADOW</span><span class="pulse">PULSE</span>
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
