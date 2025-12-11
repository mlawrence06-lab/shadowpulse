"use strict";

/**
 * ShadowPulse - ui/ads.js
 * Revive Adserver-based ads zone using an iframe tag.
 *
 * We avoid injecting external <script> tags (blocked by CSP on some hosts)
 * and instead render a 300x50 Revive iframe directly inside the ads zone.
 */

import { createEl } from "../core/utils.js";

// Revive iframe configuration
const REVIVE_IFRAME_BASE = "https://vod.fan/adserver/www/delivery/afr.php";
const REVIVE_ZONE_ID = 2;
const REVIVE_IFRAME_WIDTH = 150;
const REVIVE_IFRAME_HEIGHT = 50;

/**
 * Creates the ads zone for the toolbar.
 * Returns a container with a single iframe pointing at the Revive zone.
 *
 * The return shape still includes link/img placeholders so existing
 * call sites that destructure { zone, link, img } keep working.
 */
export function createAdsZone() {
  const zone = createEl("div", ["sp-zone", "sp-zone-ads"]);

  // cache‑buster to avoid overly-aggressive intermediaries
  const cacheBuster = Math.floor(Math.random() * 1e16);

  const iframeAttrs = {
    src: `${REVIVE_IFRAME_BASE}?zoneid=${REVIVE_ZONE_ID}&cb=${cacheBuster}`,
    width: String(150),
    height: String(REVIVE_IFRAME_HEIGHT),
    frameborder: "0",
    scrolling: "no",
    allow: "autoplay",
    title: "ShadowPulse Ad",
  };

  const iframe = createEl("iframe", ["sp-ads-iframe"], iframeAttrs);
  zone.appendChild(iframe);

  // Keep API compatible with previous image‑based implementation
  const link = null;
  const img = null;

  return { zone, link, img };
}

/**
 * No-op for compatibility: main.js still calls loadBannerAd(...),
 * but Revive handles loading/rotation inside the iframe on its own.
 */
export async function loadBannerAd(zone, img, link) {
  return;
}
