"use strict";

/**
 * ShadowPulse - core/utils.js
 * Small helper utilities and DOM helpers.
 */

export function spLog(...args) {
  const ts = new Date().toISOString();
  console.log("[ShadowPulse]", ts, ...args);
}

export function spError(...args) {
  const ts = new Date().toISOString();
  console.error("[ShadowPulse]", ts, ...args);
}

export function createEl(tag, classes = [], attrs = {}) {
  const el = document.createElement(tag);

  if (typeof classes === "string") {
    if (classes) el.className = classes;
  } else if (Array.isArray(classes) && classes.length) {
    el.className = classes.join(" ");
  }

  for (const [k, v] of Object.entries(attrs)) {
    el.setAttribute(k, v);
  }
  return el;
}


export function clamp(value, min, max) {
  return Math.min(max, Math.max(min, value));
}

export function getOrdinal(n) {
  const s = ["th", "st", "nd", "rd"];
  const v = n % 100;
  return n + (s[(v - 20) % 10] || s[v] || s[0]);
}
