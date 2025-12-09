"use strict";

/**
 * ShadowPulse - ui/logo.js
 * Circular logo, click toggles search, click+drag moves toolbar horizontally.
 */

import { createEl, clamp } from "../core/utils.js";
import { setState } from "../core/state.js";

/**
 * Create the logo zone.
 *
 * @param {Function} onToggleSearch - callback to toggle the search panel
 * @param {HTMLElement} rootEl      - toolbar root (used for dragging)
 */
export function createLogoZone(onToggleSearch, rootEl) {
  const zone = createEl("div", ["sp-zone", "sp-zone-logo"]);
  const circle = createEl("div", ["sp-logo-circle"]);
  circle.title = "ShadowPulse"; // placeholder tooltip

  const text = createEl("div", ["sp-logo-text"]);
  text.textContent = "SP";
  circle.appendChild(text);

  zone.appendChild(circle);

  // click -> toggle search (actual handler is wired in main.js)
  circle.addEventListener("click", (e) => {
    e.stopPropagation();
    onToggleSearch();
  });

  // drag along bottom: adjust the root's left position, clamped to viewport
  let dragging = false;
  let startX = 0;
  let startLeft = 0;

  circle.addEventListener("mousedown", (e) => {
    dragging = true;
    startX = e.clientX;

    const rect = rootEl.getBoundingClientRect();
    startLeft = rect.left;

    document.body.classList.add("sp-dragging");
    e.preventDefault();
  });

  window.addEventListener("mousemove", (e) => {
    if (!dragging) return;

    const deltaX = e.clientX - startX;

    // Use the ACTUAL toolbar width instead of a fixed max width,
    // so clamping works correctly on all viewport sizes.
    const toolbarRect = rootEl.getBoundingClientRect();
    const toolbarWidth = toolbarRect.width;
    const viewportWidth = window.innerWidth;

    const minLeft = 8;
    const maxLeft = Math.max(minLeft, viewportWidth - toolbarWidth - 8);

    const newLeft = clamp(startLeft + deltaX, minLeft, maxLeft);

    rootEl.style.left = newLeft + "px";
    rootEl.style.transform = "none"; // switch to absolute left-based positioning
  });

  window.addEventListener("mouseup", () => {
  if (!dragging) return;
  dragging = false;
  document.body.classList.remove("sp-dragging");

  // Persist the final horizontal position so it can be restored on future pages.
  const leftStr = rootEl.style.left || "";
  const match = leftStr.match(/^-?\d+/);
  if (match) {
    const finalLeft = parseInt(match[0], 10);
    try {
      setState("toolbarLeft", finalLeft);
    } catch (e) {
      // ignore storage errors
    }
  }
});

  return zone;
}
