"use strict";

/**
 * ShadowPulse - background/background.js
 * MV3 service worker: logs install & opens tabs on request.
 */

chrome.runtime.onInstalled.addListener((details) => {
  console.log("[ShadowPulse] Installed/updated.", details);
});

chrome.runtime.onMessage.addListener((msg, sender, sendResponse) => {
  if (msg && msg.type === "SP_OPEN_TAB" && typeof msg.url === "string") {
    chrome.tabs.create({ url: msg.url });
    if (sendResponse) sendResponse({ ok: true });
  }
});
