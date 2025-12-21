"use strict";

/**
 * ShadowPulse - ui/control.js
 * Vertical column with three compact text links:
 * MINIMIZE / AUTO MIN / SETTINGS
 * Using <button> elements styled as links for accessibility.
 */

import { createEl } from "../core/utils.js";

/**
 * Create control zone.
 *
 * @param {Object} callbacks
 * @param {Function} callbacks.onMinimize
 * @param {Function} callbacks.onAutoMin
 * @param {Function} callbacks.onSettings
 */
export function createControlZone({ onAutoMin, onSettings, onReports }) {
  const zone = createEl("div", ["sp-zone", "sp-zone-control"]);

  // Removed MINIMIZE link per user request (v0.36.34)
  // zone.appendChild(makeLink("MINIMIZE", "sp-ctrl-minimize", onMinimize));

  zone.appendChild(makeLink("AUTO MIN", "sp-ctrl-auto-min", onAutoMin));
  zone.appendChild(makeLink("SETTINGS", "sp-ctrl-settings", onSettings));
  zone.appendChild(makeLink("REPORTS", "sp-ctrl-reports", onReports));

  return zone;
}

function makeLink(label, cls, handler) {
  // Still use <button> for keyboard focus/enter/space behavior,
  // but visually it will look like a text link via CSS.
  const btn = createEl("button", ["sp-ctrl-link", cls]);
  btn.type = "button";
  btn.textContent = label;
  btn.addEventListener("click", (e) => {
    e.stopPropagation();
    handler();
  });
  return btn;
}
