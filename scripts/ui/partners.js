"use strict";

/**
 * ShadowPulse - ui/partners.js
 * Loading of partner banners.
 * Updated: Direct fetch without retries or caching.
 */

import { createEl } from "../core/utils.js";
import { SP_CONFIG } from "../core/config.js";

// use the proxy API from config

/**
 * Creates the partners zone container.
 */
export function createPartnersZone() {
    const zone = createEl("div", ["sp-zone", "sp-zone-partners"]);
    zone.style.width = "150px";
    zone.style.height = "50px";
    zone.style.position = "relative";
    // zone.style.backgroundColor = "#1f2937"; // Dark Placeholder REMOVED
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
/**
 * Loads the banner ad (partner image) with retry logic.
 */
export function preloadAd() {
    return new Promise((resolve, reject) => {
        const cb = Math.floor(Math.random() * 1e16);
        const baseUrl = SP_CONFIG.GET_BANNER_AD_API || "https://shadowpulse.live/api/v1/get_banner_ad.php";
        const separator = baseUrl.includes("?") ? "&" : "?";
        const url = `${baseUrl}${separator}zoneid=1&cb=${cb}`;

        const loader = new Image();
        loader.onload = () => resolve(url);
        loader.onerror = () => reject(new Error("Ad Load Failed"));
        loader.src = url;
    });
}

/**
 * Loads the banner ad (partner image).
 * Accepts an optional preloading Promise.
 */
export async function loadPartnerBanner(zone, img, link, adPromise = null) {
    try {
        // Use provided promise or start a new fetch
        const fetcher = adPromise || preloadAd();

        const url = await fetcher;
        img.src = url;
        img.style.display = "block";

    } catch (err) {
        console.warn("[ShadowPulse] Ad Network Failed.", err);
        // Fallback UI
        if (!img.src || img.style.display === "none") {
            const fallbackDiv = createEl("div");
            fallbackDiv.textContent = "ShadowPulse";
            fallbackDiv.style.color = "#4b5563";
            fallbackDiv.style.lineHeight = "50px";
            fallbackDiv.style.textAlign = "center";
            fallbackDiv.style.fontWeight = "bold";
            zone.innerHTML = "";
            zone.appendChild(fallbackDiv);
        }
    }
}
