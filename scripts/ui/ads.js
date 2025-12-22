"use strict";

/**
 * ShadowPulse - ui/ads.js
 * Robust Ad Loading: Cache First -> Network -> Update Cache.
 * Fallback to Default on failure.
 */

import { createEl } from "../core/utils.js";
import { getState, setState } from "../core/state.js";

// Revive Adserver config
// 'avw.php' usually returns an Image Redirect (Blob/Data).
const REVIVE_DIRECT_IMG = "https://vod.fan/adserver/www/delivery/avw.php?zoneid=2&cb=";
const FALLBACK_TTL = 6 * 60 * 60 * 1000; // 6 hours

/**
 * Creates the ads zone container.
 */
export function createAdsZone() {
  const zone = createEl("div", ["sp-zone", "sp-zone-ads"]);
  zone.style.width = "150px";
  zone.style.height = "50px";
  zone.style.position = "relative";
  zone.style.backgroundColor = "#1f2937"; // Dark Placeholder
  zone.style.overflow = "hidden";

  // Img Element
  const img = createEl("img", ["sp-ads-img"]);
  img.style.width = "150px";
  img.style.height = "50px";
  img.style.objectFit = "cover";
  img.style.display = "none"; // Hidden until loaded

  zone.appendChild(img);
  return { zone, link: null, img }; // Return img for loader
}

/**
 * Loads the banner ad.
 * 1. Checks Cache (displays immediately if valid).
 * 2. Fetches Fresh (updates display + cache on success).
 * 3. Fallback to Text if both fail.
 */
export async function loadBannerAd(zone, img, link) {
  try {
    const now = Date.now();

    // 1. Try to Load Cached Ad FIRST (Instant Visual)
    const cached = await getState("cached_ad_data", null); // { data: base64, time: ts }
    if (cached && (now - cached.time < FALLBACK_TTL)) {
      img.src = cached.data;
      img.style.display = "block";
    }

    // 2. Network Fetch (Fresh)
    const cb = Math.floor(Math.random() * 1e16);
    const url = `${REVIVE_DIRECT_IMG}${cb}&n=a34f32`;

    // Create a temp loader to verify network success
    const loader = new Image();
    loader.onload = () => {
      // Success!
      img.src = url;
      img.style.display = "block";

      // Try to cache it (Canvas Draw)
      try {
        const canvas = document.createElement('canvas');
        canvas.width = 150;
        canvas.height = 50;
        const ctx = canvas.getContext('2d');
        ctx.drawImage(loader, 0, 0, 150, 50);
        const dataURL = canvas.toDataURL("image/png");
        setState("cached_ad_data", { data: dataURL, time: now });
      } catch (e) {
        // CORS might block this (Tainted Canvas). 
        // Expected if server doesn't send Access-Control-Allow-Origin.
      }
    };

    loader.onerror = () => {
      // Network Failed or Blocked.
      console.warn("[ShadowPulse] Ad Network Failed/Blocked.");

      // If we already set the cached image, do nothing (keep showing it).
      // If no cache (img hidden/empty), show 'Default' text.
      if (!img.src || img.style.display === "none") {
        const fallbackDiv = createEl("div");
        fallbackDiv.textContent = "ShadowPulse";
        fallbackDiv.style.color = "#4b5563";
        fallbackDiv.style.lineHeight = "50px";
        fallbackDiv.style.textAlign = "center";
        fallbackDiv.style.fontWeight = "bold";
        zone.innerHTML = ""; // Clear img
        zone.appendChild(fallbackDiv);
      }
    };

    loader.src = url;
  } catch (err) {
    console.error("[ShadowPulse] loadBannerAd error", err);
  }
}
