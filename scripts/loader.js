"use strict";

/**
 * ShadowPulse - loader.js
 * Content script entrypoint.
 * Dynamically imports `scripts/main.js` as an ES module using
 * chrome.runtime.getURL, wrapped in a try/catch for safe failure.
 */

(async () => {
  try {
    const mainUrl = chrome.runtime.getURL("scripts/main.js");
    // console.log("[ShadowPulse] loader.js starting. main URL:", mainUrl);
    await import(mainUrl);
    // console.log("[ShadowPulse] main.js imported successfully.");
  } catch (err) {
    console.error("[ShadowPulse] FAILED TO LOAD main.js");
    console.error(err && err.stack ? err.stack : err);
  }
})();
