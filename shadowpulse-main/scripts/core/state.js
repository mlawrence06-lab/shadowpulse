"use strict";

/**
 * ShadowPulse - core/state.js
 * chrome.storage wrapper for persistent state.
 */

const STORAGE_NAMESPACE = "shadowpulse";

export async function getState(key, defaultValue = null) {
  return new Promise((resolve) => {
    try {
      chrome.storage.local.get([STORAGE_NAMESPACE], (items) => {
        const bucket = items[STORAGE_NAMESPACE] || {};
        if (Object.prototype.hasOwnProperty.call(bucket, key)) {
          resolve(bucket[key]);
        } else {
          resolve(defaultValue);
        }
      });
    } catch (err) {
      console.error("[ShadowPulse] getState error", err);
      resolve(defaultValue);
    }
  });
}

export async function setState(key, value) {
  return new Promise((resolve) => {
    try {
      chrome.storage.local.get([STORAGE_NAMESPACE], (items) => {
        const bucket = items[STORAGE_NAMESPACE] || {};
        bucket[key] = value;
        chrome.storage.local.set({ [STORAGE_NAMESPACE]: bucket }, () => resolve());
      });
    } catch (err) {
      console.error("[ShadowPulse] setState error", err);
      resolve();
    }
  });
}
