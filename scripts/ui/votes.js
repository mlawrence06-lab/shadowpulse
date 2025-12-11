"use strict";

/**
 * ShadowPulse - ui/votes.js
 * Handles 4 Modes of the Voting Zone:
 * 1. Voting (Topic/Post) -> Buttons (Top) + Score/Rank (Bottom)
 * 2. Board -> Board Name (Top) + Score/Rank (Bottom)
 * 3. Profile -> Profile Name (Top) + Score/Rank (Bottom)
 * 4. Special -> Page Title (Center) + Subtitle
 */

import { createEl } from "../core/utils.js";

/**
 * Creates the voting zone container and populates it based on the page context.
 */
export function createVotesZone(onVoteSelected, voteContext) {
  const zone = createEl("div", ["sp-zone", "sp-zone-votes"]);
  const ctx = voteContext || { kind: "unknown" };

  // FEATURE REMOVED: Post voting disabled due to API limitations
  if (ctx.kind === 'post') {
    return { zone: null, buttons: [], summaryEl: null };
  }

  const buttons = [];
  let summaryEl = null;

  // --- MODE 1: VOTING (Topic / Post) ---
  if (ctx.kind === "topic" || ctx.kind === "post") {
    zone.classList.add("sp-votes-mode-voting");

    // TOP ROW: Vote Buttons (1-5)
    const topRow = createEl("div", ["sp-votes-row", "sp-votes-top"]);
    for (let i = 1; i <= 5; i++) {
      const btn = createEl("button", ["sp-vote-btn"]);
      btn.type = "button";
      btn.textContent = String(i);
      btn.dataset.value = String(i);

      if (typeof onVoteSelected === "function") {
        btn.addEventListener("click", (e) => {
          e.stopPropagation();
          const value = Number(btn.dataset.value || "0");
          if (!value) return;
          // Set the currently desired value visually before the server responds
          setSelected(buttons, null, value);
          onVoteSelected(value);
        });
      }
      topRow.appendChild(btn);
      buttons.push(btn);
    }
    zone.appendChild(topRow);

    // BOTTOM ROW: Summary Text
    const bottomRow = createEl("div", ["sp-votes-row", "sp-votes-bottom"]);
    summaryEl = createEl("div", ["sp-votes-summary"]);

    // Initial State
    const noun = (ctx.kind === 'post') ? 'Post' : 'Topic';
    summaryEl.textContent = `No ${noun} votes yet`;

    bottomRow.appendChild(summaryEl);
    zone.appendChild(bottomRow);

  }

  // --- MODE 2 & 3: INFO (Board / Profile) ---
  else if (ctx.kind === "board" || ctx.kind === "profile") {
    zone.classList.add("sp-votes-mode-info");

    // TOP ROW: Name (Uses sp-text-primary for truncation)
    const topRow = createEl("div", ["sp-votes-row", "sp-votes-top", "sp-text-primary"]);
    topRow.textContent = ctx.pageTitle || (ctx.kind === "board" ? "Board" : "Profile Name");
    zone.appendChild(topRow);

    // BOTTOM ROW: Rank Stub
    const bottomRow = createEl("div", ["sp-votes-row", "sp-votes-bottom", "sp-text-secondary"]);
    bottomRow.textContent = "Ranked xth";
    zone.appendChild(bottomRow);
  }

  // --- MODE 4: SPECIAL (Home, Search, Donate, etc.) ---
  else {
    zone.classList.add("sp-votes-mode-special");
    const container = createEl("div", ["sp-votes-special-container"]);
    const title = createEl("span", ["sp-special-title"]);
    title.textContent = ctx.pageTitle || "ShadowPulse";
    container.appendChild(title);

    if (ctx.pageSubtitle) {
      const sep = createEl("span", ["sp-special-sep"]);
      sep.textContent = " | ";
      container.appendChild(sep);
      const sub = createEl("span", ["sp-special-subtitle"]);
      sub.textContent = ctx.pageSubtitle;
      container.appendChild(sub);
    }
    zone.appendChild(container);
  }

  return { zone, buttons, summaryEl };
}

/**
 * Update visual state of buttons, distinguishing between effective and desired votes.
 * @param {Array<HTMLElement>} buttons - The array of vote button elements.
 * @param {number|null} effectiveValue - The value currently registered on the server.
 * @param {number|null} desiredValue - The value the user just clicked (or null if updating from server).
 */
export function setSelected(buttons, effectiveValue, desiredValue = null) {
  if (!buttons || !buttons.length) return;

  buttons.forEach((btn) => {
    const v = Number(btn.dataset.value || "0");

    // Remove all classes first
    btn.classList.remove("sp-vote-selected", "sp-vote-desired");
    btn.removeAttribute('title'); // Clear existing tooltip

    if (v === effectiveValue) {
      // 1. If it is the effective vote, apply the strong highlight
      btn.classList.add("sp-vote-selected");
      btn.title = "Effective vote"; // Add tooltip for effective vote
    } else if (v === desiredValue && v !== effectiveValue) {
      // 2. If it is the desired vote, but not yet the effective vote, apply the weaker highlight
      btn.classList.add("sp-vote-desired");
      btn.title = "Desired vote"; // Add tooltip for desired vote
    }
  });
}

// Helper: Format number with commas (e.g. 1,677)
function formatNumber(num) {
  return Number(num).toLocaleString();
}

// Helper: Get ordinal suffix (e.g. 5th, 1st, 2nd)
function getOrdinal(n) {
  const s = ["th", "st", "nd", "rd"];
  const v = n % 100;
  return n + (s[(v - 20) % 10] || s[v] || s[0]);
}

// Update summary text: "1,677 votes (Ranked 5th)"
export function renderVoteSummary(summaryEl, payload, voteContext) {
  if (!summaryEl) return;

  const ctx = voteContext || {};
  const noun = (ctx.kind === 'post') ? 'Post' : 'Topic';
  const defaultText = `No ${noun} votes yet`;

  if (!payload || typeof payload !== "object") {
    summaryEl.textContent = defaultText;
    return;
  }

  const count = Number(payload.vote_count) || 0;
  const rank = Number(payload.rank) || 0;

  if (count > 0) {
    // GRAMMAR FIX: "vote" vs "votes"
    const suffix = count === 1 ? "vote" : "votes";

    let text = `${formatNumber(count)} ${suffix}`;
    if (rank > 0) {
      text += ` (Ranked ${getOrdinal(rank)})`;
    }
    summaryEl.textContent = text;
  } else {
    summaryEl.textContent = defaultText;
  }
}