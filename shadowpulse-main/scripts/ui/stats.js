"use strict";

/**
 * ShadowPulse - ui/stats.js
 * Displays Bitcoin Price (colored by trend) + Sparkline Graph.
 */

import { createEl } from "../core/utils.js";

export function createStatsZone() {
  const zone = createEl("div", ["sp-zone", "sp-zone-stats"]);
  
  // Top: Price
  const priceEl = createEl("div", ["sp-stats-price"]);
  priceEl.textContent = ""; // Set to blank as requested
  
  // Bottom: Graph Container
  const graphEl = createEl("div", ["sp-stats-graph"]);
  
  zone.appendChild(priceEl);
  zone.appendChild(graphEl);
  
  return { zone, priceEl, graphEl };
}

export function renderStats(priceEl, graphEl, data) {
  // Guard against missing elements
  if (!priceEl || !graphEl) return;

  // Handle null/error data visually
  if (!data) {
    priceEl.textContent = "Unavailable";
    priceEl.classList.remove("sp-trend-up", "sp-trend-down");
    return;
  }

  // 1. Update Price Text & Color
  priceEl.textContent = data.price_label || "---";
  
  // Reset classes
  priceEl.classList.remove("sp-trend-up", "sp-trend-down");
  if (data.trend === "up") priceEl.classList.add("sp-trend-up");
  if (data.trend === "down") priceEl.classList.add("sp-trend-down");

  // 2. Render Sparkline SVG
  const history = data.history || [];
  if (!Array.isArray(history) || history.length < 2) {
    graphEl.innerHTML = ""; // Clear graph if insufficient data
    return;
  }

  // Calculate min/max for scaling
  let min = Infinity, max = -Infinity;
  for (const v of history) {
    if (v < min) min = v;
    if (v > max) max = v;
  }
  
  // Flat line check
  if (min === max) {
    min -= 1;
    max += 1;
  }
  
  const width = 100; 
  const height = 30;
  const padding = 2; // Keep line away from exact edges

  // Add buffer
  const range = max - min;
  const yMin = min; 
  const yMax = max;
  
  // Generate path "d" string
  let d = "";
  const stepX = width / (history.length - 1);

  history.forEach((val, i) => {
    const x = i * stepX;
    // Normalize Y to 0..1 then flip for SVG coords (0 is top)
    const normalizedY = (val - yMin) / range; 
    
    // Scale to height with padding
    const y = height - padding - (normalizedY * (height - (padding * 2)));

    if (i === 0) d += `M ${x.toFixed(1)} ${y.toFixed(1)}`;
    else d += ` L ${x.toFixed(1)} ${y.toFixed(1)}`;
  });

  // Calculate position for the horizontal starting line (y-coordinate of the first point)
  const startYNormalized = (history[0] - yMin) / range;
  const startY = height - padding - (startYNormalized * (height - (padding * 2)));

  // Color matches trend
  // Note: The hardcoded colors here assume dark theme colors. 
  // In a cross-theme environment, this might need dynamic fetching of the CSS variable.
  const strokeColor = data.trend === "up" ? "#22c55e" : "#ef4444"; 

  graphEl.innerHTML = `
    <svg viewBox="0 0 ${width} ${height}" preserveAspectRatio="none" style="width:100%; height:100%; display:block;">
      <line x1="0" y1="${startY.toFixed(1)}" x2="${width}" y2="${startY.toFixed(1)}" 
            stroke="${strokeColor}" stroke-width="1" stroke-dasharray="2, 1" opacity="0.8" />
            
      <path d="${d}" fill="none" stroke="${strokeColor}" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" vector-effect="non-scaling-stroke" />
    </svg>
  `;
}