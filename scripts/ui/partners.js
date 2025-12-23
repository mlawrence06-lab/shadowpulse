"use strict";

/**
 * ShadowPulse - ui/partners.js (formerly ads.js)
 * Robust Ad Loading: Cache First -> Network -> Update Cache.
 * Fallback to Default on failure.
 */

import { createEl } from "../core/utils.js";
import { getState, setState } from "../core/state.js";
import { SP_CONFIG } from "../core/config.js";

// use the proxy API from config
const FALLBACK_TTL = 6 * 60 * 60 * 1000; // 6 hours

/**
 * Creates the partners zone container.
 */
export function createPartnersZone() {
    const zone = createEl("div", ["sp-zone", "sp-zone-partners"]);
    zone.style.width = "150px";
    zone.style.height = "50px";
    zone.style.position = "relative";
    zone.style.backgroundColor = "#1f2937"; // Dark Placeholder
    zone.style.overflow = "hidden";

    // Img Element
    const img = createEl("img", ["sp-partners-img"]);
    img.style.width = "150px";
    img.style.height = "50px";
    img.style.objectFit = "cover";
    img.style.display = "none"; // Hidden until loaded

    zone.appendChild(img);
    return { zone, link: null, img }; // Return img for loader
}

/**
 * Loads the banner ad (partner image).
 * 1. Checks Cache (displays immediately if valid).
 * 2. Fetches Fresh from Proxy (updates display + cache on success).
 * 3. Fallback to Text if both fail.
 */
export async function loadPartnerBanner(zone, img, link) {
    try {
        const now = Date.now();

        // 1. Try to Load Cached Ad FIRST (Instant Visual)
        const cached = await getState("cached_partner_data", null); // { data: base64, time: ts }
        if (cached && (now - cached.time < FALLBACK_TTL)) {
            img.src = cached.data;
            img.style.display = "block";
        }

        // 2. Network Fetch (Fresh via Proxy)
        // SP_CONFIG.GET_BANNER_AD_API should be "https://vod.fan/shadowpulse/api/v1/get_banner_ad.php?zoneid=2"
        // We add a random cache buster just in case local cache is stubborn.
        const cb = Math.floor(Math.random() * 1e16);
        const baseUrl = SP_CONFIG.GET_BANNER_AD_API || "https://vod.fan/shadowpulse/api/v1/get_banner_ad.php";

        // Construct URL (handle if ? exists or not)
        const separator = baseUrl.includes("?") ? "&" : "?";
        // Default to zoneid 2 if not in config URL, but usually config has it or we append it?
        // Let's assume URL in config might be base. Let's force zoneid=2 if missing? 
        // Actually, in `ads.js` it was hardcoded `zoneid=2`. Let's allow config to define it, 
        // or append it here.
        // For safety, let's append params.
        const url = `${baseUrl}${separator}zoneid=2&cb=${cb}`;

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
                setState("cached_partner_data", { data: dataURL, time: now });
            } catch (e) {
                // CORS might block this (Tainted Canvas). 
                // Expected if server doesn't send Access-Control-Allow-Origin.
                // Our PHP proxy sets CORS headers so this should actually work now!
            }
        };

        loader.onerror = () => {
            // Network Failed or Blocked.
            console.warn("[ShadowPulse] Partner Network Failed/Blocked.");

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
        console.error("[ShadowPulse] loadPartnerBanner error", err);
    }
}
