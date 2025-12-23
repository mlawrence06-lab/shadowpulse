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
/**
 * Loads the banner ad (partner image) with retry logic.
 */
export async function loadPartnerBanner(zone, img, link) {
    // Retry config
    const MAX_RETRIES = 3;
    const RETRY_DELAY_MS = 2000;

    const attemptLoad = async (retryCount = 0) => {
        try {
            const now = Date.now();

            // 1. Try to Load Cached Ad FIRST (Instant Visual) - Only on first attempt
            if (retryCount === 0) {
                const cached = await getState("cached_partner_data", null);
                if (cached && (now - cached.time < FALLBACK_TTL)) {
                    img.src = cached.data;
                    img.style.display = "block";
                }
            }

            // 2. Network Fetch (Fresh via Proxy)
            const cb = Math.floor(Math.random() * 1e16);
            const baseUrl = SP_CONFIG.GET_BANNER_AD_API || "https://vod.fan/shadowpulse/api/v1/get_banner_ad.php";
            const separator = baseUrl.includes("?") ? "&" : "?";
            const url = `${baseUrl}${separator}zoneid=2&cb=${cb}`;

            const loader = new Image();

            loader.onload = () => {
                img.src = url;
                img.style.display = "block";

                // Cache success
                try {
                    const canvas = document.createElement('canvas');
                    canvas.width = 150;
                    canvas.height = 50;
                    const ctx = canvas.getContext('2d');
                    ctx.drawImage(loader, 0, 0, 150, 50);
                    const dataURL = canvas.toDataURL("image/png");
                    setState("cached_partner_data", { data: dataURL, time: now });
                } catch (e) {
                    // CORS/Taint expected
                }
            };

            loader.onerror = () => {
                if (retryCount < MAX_RETRIES) {
                    // Debug only - don't scare user
                    console.debug(`[ShadowPulse] Ad Network Failed. Retrying (${retryCount + 1}/${MAX_RETRIES})...`);
                    setTimeout(() => attemptLoad(retryCount + 1), RETRY_DELAY_MS * (retryCount + 1)); // Exponential-ish backoff
                } else {
                    console.warn("[ShadowPulse] Ad Network Blocked/Failed after retries.");
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
            };

            loader.src = url;

        } catch (err) {
            console.error("[ShadowPulse] loadPartnerBanner error", err);
        }
    };

    attemptLoad();
}
