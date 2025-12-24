"use strict";

/**
 * ShadowPulse - ui/search.js
 * Popup search panel above the toolbar.
 * Uses DEFAULT_SEARCH_ENGINE_* from config, with "***" as URL placeholder.
 */

import { createEl } from "../core/utils.js";
import { SP_CONFIG } from "../core/config.js";
import { getState } from "../core/state.js";
// UPDATED: Imported logSearchQuery
import { trackSearch, logSearchQuery } from "../core/api.js";

/**
 * Create the search panel.
 * Returns the panel element and input so callers can reposition and focus.
 */
export function createSearchPanel(rootEl) {
  const panel = createEl("div", "sp-search-panel");
  panel.dataset.open = "false";

  const input = createEl("input", "sp-search-input");
  input.type = "text";
  input.autocomplete = "off";
  input.placeholder = SP_CONFIG.DEFAULT_SEARCH_ENGINE_NAME
    ? "Search via " + SP_CONFIG.DEFAULT_SEARCH_ENGINE_NAME
    : "Search";
  panel.appendChild(input);

  const button = createEl("button", "sp-search-btn");
  button.type = "button";
  button.textContent = "Search";
  panel.appendChild(button);

  async function performSearch() {
    const term = (input.value || "").trim();
    if (!term) {
      input.focus();
      return;
    }

    let engine = await getState("searchEngine", "bitlist");
    let baseUrl = "https://shadowpulse.live/search-bitlist.php";

    if (engine === "bitlist") {
      baseUrl = "https://shadowpulse.live/search-bitlist.php";
    }
    // Add other engines here in future

    let url;
    if (baseUrl.indexOf("?") === -1) {
      url = baseUrl + "?q=" + encodeURIComponent(term);
    } else {
      const sep = baseUrl.indexOf("q=") === -1 ? "&q=" : "";
      url = baseUrl + sep + encodeURIComponent(term);
    }

    try {
      const memberUuid = await getState("memberUuid", null);
      if (memberUuid) {
        // Increment general search counter
        trackSearch(memberUuid);

        // ADDED: Log specific search term for history
        logSearchQuery(memberUuid, term, 1);
      }
    } catch (_) {
      // ignore state errors; search still proceeds
    }

    window.open(url, "_blank", "noopener,noreferrer");
    input.value = "";
    panel.dataset.open = "false";
  }

  input.addEventListener("keydown", (evt) => {
    if (evt.key === "Enter") {
      evt.preventDefault();
      performSearch();
    } else if (evt.key === "Escape") {
      panel.dataset.open = "false";
    }
  });

  button.addEventListener("click", (evt) => {
    evt.preventDefault();
    performSearch();
  });

  return { panel, input };
}

/**
 * Toggle the search panel open/closed with a small delay before focusing input,
 * so it lines up with the CSS opening animation.
 */
export async function toggleSearchPanel(panel, input) {
  const isOpen = panel.dataset.open === "true";
  if (isOpen) {
    panel.dataset.open = "false";
  } else {
    // Dynamic Placeholder
    const engine = await getState("searchEngine", "bitlist");
    if (engine === "bitlist") {
      input.placeholder = "Search via Bitlist Search";
    } else {
      input.placeholder = "Search via " + engine.charAt(0).toUpperCase() + engine.slice(1);
    }

    panel.dataset.open = "true";
    setTimeout(() => {
      input.focus();
      input.select();
    }, 250);
  }
}