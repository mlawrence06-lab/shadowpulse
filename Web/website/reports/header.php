<?php
require __DIR__ . '/cors.php';

if (!isset($pageTitle)) { $pageTitle = "Reports"; }
if (!isset($pageSubtitle)) { $pageSubtitle = ""; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title><?php echo htmlspecialchars($pageTitle); ?> – ShadowPulse Reports</title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<link rel="stylesheet" href="../shadowpulse.css">
</head>
<body>
<div class="top-separator"></div>
<div class="page-shell">
<header class="site-header">
    <div class="brand">
        <div class="brand-logo">
            <svg width="22" height="22" viewBox="0 0 24 24">
                <path d="M3 13h3l2-6 3.5 10 2.5-7 1 3h4" fill="none" stroke="currentColor" stroke-width="1.6"/>
            </svg>
        </div>
        <div class="brand-text">
            <div class="brand-name"><span class="highlight">SHADOW</span><span class="pulse">PULSE</span></div>
            <div class="brand-tagline">Reports Suite</div>
        </div>
    </div>
    <nav class="site-nav">
        <a href="../index.php" class="nav-link"><span class="dot"></span><span>Dashboard</span></a>
        <a href="index.php" class="nav-link nav-link-active"><span class="dot"></span><span>Reports</span></a>
            <a href="https://vod.fan" class="nav-link">
                <span class="dot" aria-hidden="true"></span>
                <span>Fans of Vod</span>
            </a>
    </nav>
</header>
<main class="site-main">
