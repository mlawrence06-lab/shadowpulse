"use strict";

/**
 * ShadowPulse - main.js
 * Validates host, creates root, assembles zones and wires up behaviors.
 * UPDATED: Implements new phased loading, consolidated periodic refresh, and new logo logic.
 */

import { SP_CONFIG } from "./core/config.js";
import { spLog, spError, createEl } from "./core/utils.js";
import { getState, setState } from "./core/state.js";
import { fetchBitcoinStats, fetchVoteSummary, fetchPageContext, submitVote, fetchEffectiveVote, bootstrapMember, trackPageView } from "./core/api.js";
import { getOrCreateMemberUuid } from "./core/member.js";
import { getVotingContext } from "./core/pageContext.js";

import { createLogoZone } from "./ui/logo.js";
import { createAdsZone, loadBannerAd } from "./ui/ads.js";
import { createStatsZone, renderStats } from "./ui/stats.js";
import { createVotesZone, setSelected, renderVoteSummary } from "./ui/votes.js";
import { createControlZone } from "./ui/control.js";
import { createSearchPanel, toggleSearchPanel } from "./ui/search.js";
import { openSettingsModal } from "./ui/settings.js";


// Helper function: Get ordinal suffix (e.g., 5th, 1st, 2nd)
function getOrdinalSuffix(n) {
  const v = n % 100;
  if (v < 11 || v > 13) {
    const d = n % 10;
    if (d === 1) return "st";
    if (d === 2) return "nd";
    if (d === 3) return "rd";
  }
  return "th";
}

// Helper: Apply minimized state class
function applyMinimizedState(root, isMinimized) {
  if (isMinimized) root.classList.add("sp-root-minimized");
  else root.classList.remove("sp-root-minimized");
}


function updateLogoVisual(root, header, numeric, rank = null) {
  if (!header || !header._spRefs) return;

  const logoCircle = header._spRefs.logoCircle;
  const logoText = header._spRefs.logoText;
  if (!logoCircle || !logoText) return;

  const value = Number(numeric) || 0;
  const EPSILON = 0.0001;

  // Determine Target Color
  let colorKey = "blue";
  if (value > (3 + EPSILON)) {
    colorKey = "green";
  } else if (value > EPSILON && value < (3 - EPSILON)) {
    colorKey = "red";
  }

  // Determine Target Text
  let newText = "SP";
  if (value) {
    const absFrac = Math.abs(value % 1);
    newText = absFrac < 0.1 ? value.toFixed(0) : value.toFixed(1);
  }

  // Helper to apply visual state
  const applyState = () => {
    logoCircle.removeAttribute('data-vote-color');
    logoCircle.dataset.voteColor = colorKey;
    logoText.textContent = newText;

    // Ensure dark/light stroke updates
    const isDark = (root.classList || { contains: () => false }).contains("sp-theme-dark");
    logoText.style.color = "#f9fafb";
    logoText.style.webkitTextStroke = isDark ? "1px #000" : "1px #fff";

    // Tooltip update
    let tooltip = "ShadowPulse";
    if (rank && rank > 0) {
      tooltip = `Ranked ${rank}${getOrdinalSuffix(rank)}`;
      tooltip += (value > 0) ? ` (Score: ${value.toFixed(1)})` : ` (Score: ---)`;
    } else if (value > 0) {
      tooltip += ` (Score: ${value.toFixed(1)})`;
    }
    logoCircle.title = tooltip;
  };

  // CHECK: If text is changing, animate. If not (just init), set immediately.
  if (logoText.textContent !== newText) {
    // 1. Fade Out
    logoText.classList.add('sp-fade-out');

    // 2. Wait for fade out (300ms) then Swap & Fade In
    setTimeout(() => {
      applyState(); // Change color AND text while hidden
      logoText.classList.remove('sp-fade-out');
    }, 300);
  } else {
    // No text change, apply immediately (e.g. init or redundant call)
    applyState();
  }
}

function buildRoot(theme, isMinimized = false, savedLeft = null) {
  const classes = ["sp-root"];
  if (isMinimized) classes.push("sp-root-minimized");

  const root = createEl("div", classes, { id: SP_CONFIG.ROOT_ID });
  root.style.position = "fixed";
  root.classList.add(`sp-theme-${theme}`);

  // v0.36.35 FIX: Apply Saved Position immediately
  if (typeof savedLeft === "number") {
    root.style.left = savedLeft + "px";
    root.style.transform = "none";
  } else {
    root.style.left = "50%";
    root.style.transform = "translateX(-50%)";
  }

  root.style.bottom = "16px";
  root.style.zIndex = String(SP_CONFIG.Z_INDEX_ROOT);
  document.body.appendChild(root);
  return root;
}

function buildToolbar(root, voteContext) {
  const header = createEl("div", ["sp-toolbar"]);

  // 1) logo (IMMEDIATE)
  const logoZone = createLogoZone(() => { }, root);

  // 2) ads (HIDDEN by default, made visible in hydrateAds)
  const { zone: adsZone, link: adsLink, img: adsImg } = createAdsZone();
  adsZone.classList.add("sp-zone-loading");

  // 3) stats (HIDDEN by default, made visible in hydrateStats *only if data is present*)
  // NOTE: The default "Loading..." text is assumed to be handled in scripts/ui/stats.js
  const { zone: statsZone, priceEl, graphEl } = createStatsZone();
  statsZone.classList.add("sp-zone-loading");

  // 4) votes (HIDDEN by default, made visible in hydrateVoteAndLogo)
  const canVote = voteContext && voteContext.canVote;
  let votesZone;
  let voteButtons = [];
  let votesSummary = null;

  const createdVotes = createVotesZone( // FIX: Uses function name directly
    canVote
      ? async (desiredValue) => {
        // 1. Submit the vote (sends to server, button is optimistically highlighted)
        const res = await submitVote(desiredValue, voteContext);

        if (res && res.ok) {
          // Update immediately via hydration to ensure consistency
          await hydrateFullContext(root, header, voteContext);
        } else {
          // Fallback refresh on error (same behavior, simplify code path)
          await hydrateFullContext(root, header, voteContext);
        }

      }
      : null,
    voteContext
  );

  votesZone = createdVotes.zone;
  voteButtons = createdVotes.buttons;
  votesSummary = createdVotes.summaryEl;
  if (canVote && votesZone) {
    votesZone.classList.add("sp-zone-loading");
  } else if (!votesZone) {
    // Fallback: Create dummy element to prevent appendChild errors later
    votesZone = document.createElement('div');
    votesZone.style.display = 'none';
  }


  // 5) control (IMMEDIATE)
  // 5) control (IMMEDIATE)
  const controlZone = createControlZone({
    // onMinimize Removed (v0.36.34)
    onAutoMin: async () => {
      const current = (await getState("autoMin", false)) === true;
      const next = !current;
      await setState("autoMin", next);

      // Auto Min now applies minimized state immediately implies it stays minimized?
      // Or does it toggle the "Minimized" class?
      // Logic from old onMinimize: applyMinimizedState(root, nowMin);
      // But onAutoMin logic was: applyMinimizedState(root, next);
      // Wait, Auto Min should TOGGLE the PREFERENCE.
      // Does it toggle the STATE?
      // Step 685: onAutoMin: async () => { ... setState("autoMin", next); applyMinimizedState(root, next); }
      // So yes, clicking Auto Min toggles it immediately.
      applyMinimizedState(root, next);
    },
    onSettings: () => {
      openSettingsModal(root);
    },
    onReports: () => {
      window.open("https://vod.fan/shadowpulse/website/reports/index.php", "_blank");
    }
  });

  header.appendChild(logoZone);
  header.appendChild(adsZone);
  header.appendChild(statsZone);
  header.appendChild(votesZone);
  header.appendChild(controlZone);

  const logoCircle = header.querySelector(".sp-logo-circle");
  const logoText = header.querySelector(".sp-logo-text");

  header._spRefs = {
    adsZone,
    adsLink,
    adsImg,
    statsPrice: priceEl,
    statsGraph: graphEl,
    statsZone,
    voteButtons,
    votesSummary,
    votesZone,
    logoCircle,
    logoText,
    voteContext: voteContext || null
  };

  root.appendChild(header);
  return header;
}

function attachSearch(root, header) {
  const { panel, input } = createSearchPanel(root);
  root.appendChild(panel);

  const adsZone = header.querySelector(".sp-zone-ads");

  const reposition = () => {
    const headerRect = header.getBoundingClientRect();
    const rootRect = root.getBoundingClientRect();
    const adsRect = adsZone ? adsZone.getBoundingClientRect() : headerRect;

    panel.style.position = "absolute";
    const left = adsRect.left - rootRect.left;
    panel.style.left = left + "px";

    panel.style.width = "auto";
    const contentWidth = panel.scrollWidth;
    const targetWidth = Math.max(contentWidth + 16, adsRect.width + 80);
    panel.style.width = targetWidth + "px";
    panel.style.bottom = "40px";
  };

  reposition();
  window.addEventListener("resize", reposition);

  return { panel, input };
}

async function hydrateAds(header) {
  try {
    const refs = header._spRefs;
    await loadBannerAd(refs.adsZone, refs.adsImg, refs.adsLink);
    // ASAP: Make Ad zone visible
    if (refs.adsZone) {
      refs.adsZone.classList.remove("sp-zone-loading");
    }
  } catch (err) {
    spError("hydrateAds", err);
  }
}

async function hydrateStats(header) {
  try {
    const refs = header._spRefs;
    // Check if UI refs exist before running
    if (!refs || !refs.statsPrice || !refs.statsGraph) return;

    // Fetch bitcoin stats (API call, periodic only)
    const data = await fetchBitcoinStats();

    // FIX: Render the stats regardless of whether data is null or valid.
    // If data is null, renderStats will set text to "Unavailable".
    renderStats(refs.statsPrice, refs.statsGraph, data);

    // FIX: Always remove the loading class after the fetch attempt (success or failure)
    // to ensure the placeholder zone becomes visible, showing "Unavailable" on error.
    refs.statsZone.classList.remove("sp-zone-loading");

  } catch (err) {
    spError("hydrateStats", err);
    // If hydrateStats itself throws an unexpected error (not likely for this function), 
    // display an error state.
    if (header._spRefs && header._spRefs.statsPrice) {
      header._spRefs.statsPrice.textContent = "...";
      refs.statsZone.classList.remove("sp-zone-loading");
    }
  }
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

// scripts/main.js - Replacing hydrateVoteAndLogo

// === UNIFIED HYDRATION ===
// Replaces bootstrapMember + hydrateVote + hydrateStats

async function hydrateFullContext(root, header, voteContext) {
  try {
    const refs = header._spRefs || {};
    const ctx = voteContext || refs.voteContext || null;
    if (!ctx) return;

    // 1. Fetch Unified Context (Member + Vote + Price)
    // This triggers ONE API call (get_page_context.php) -> ONE SP call.
    const cat = ctx.voteCategory || "topic";
    const tid = ctx.targetId || 0;

    // Scrape Metadata
    const meta = {};
    if (cat === 'topic' || cat === 'post' || cat === 'profile') {
      let fullTitle = document.title || "";
      // Remove " - Bitcoin Forum" suffix if present
      fullTitle = fullTitle.replace(" - Bitcoin Forum", "").trim();

      if (cat === 'profile') {
        // Remove "View the profile of " prefix
        fullTitle = fullTitle.replace("View the profile of ", "").trim();
      }
      meta.title = fullTitle;
    }

    const fullData = await fetchPageContext(cat, tid, meta);

    if (!fullData || !fullData.ok) {
      // Handle offline/error?
      return;
    }

    // 2. Process Member Identity (Bootstrap Logic)
    if (fullData.member_info) {
      const prevId = await getState("memberId", 0);
      const prevAck = await getState("memberRestoreAck", false);

      const newId = Number(fullData.member_info.member_id) || 0;
      const newAck = !!fullData.member_info.restore_ack;

      // Identity Change Detection
      if (prevId > 0 && newId > 0 && newId !== prevId) {
        spLog(`Identity Change Detected (${prevId} -> ${newId}). Reloading.`);
        await new Promise(r => chrome.storage.local.clear(r));
        // Save new
        await setState("memberId", newId);
        await setState("memberRestoreAck", newAck);
        window.location.reload();
        return;
      }

      // Standard Save
      if (newId > 0) await setState("memberId", newId);
      await setState("memberRestoreAck", newAck);
    }

    // 3. Process Vote & Logo (Hydrate Vote Logic)
    // Extract Context
    const summary = fullData.context ? fullData.context.summary : {};
    const userVote = fullData.context ? fullData.context.user_vote : {};
    const mStats = fullData.member_stats || {};

    // Settings Link Attention
    const ack = await getState("memberRestoreAck", false);
    const settingsLink = root.querySelector(".sp-ctrl-settings");
    if (settingsLink) {
      settingsLink.classList.remove("sp-attention");
      if (!ack) settingsLink.classList.add("sp-attention");
    }

    // Buttons
    if (refs.voteButtons) {
      const eff = userVote.effective != null ? Number(userVote.effective) : null;
      const des = userVote.desired != null ? Number(userVote.desired) : null;
      setSelected(refs.voteButtons, eff, des);
    }

    // Summary Text
    if (!root.classList.contains("sp-root-minimized") && refs.votesSummary) {
      const uiSummary = {
        vote_count: summary.vote_count || 0,
        topic_score: summary.score || 0,
        rank: Number(summary.rank) || 0,
        currentVote: userVote.effective != null ? Number(userVote.effective) : null,
        target_label: summary.label
      };
      renderVoteSummary(refs.votesSummary, uiSummary, ctx);
      if (refs.votesZone) {
        refs.votesZone.classList.remove("sp-zone-loading");

        // Check Helper Setting and toggle Watermark
        getState("voteHelper", true).then(enabled => {
          const wm = refs.votesZone.querySelector(".sp-vote-watermark");
          if (wm) {
            wm.style.display = enabled ? "block" : "none";
          } else if (enabled) {
            // If enabled but missing (e.g. initially hidden or failed create), create it?
            // Usually createVotesZone (Step 652) handles creation. 
            // If it wasn't created because setting was off, specific logic might be needed.
            // But wait, Step 652 logic was: IF enabled, create.
            // So if it was OFF, wm is null.
            // If I turn it ON, I need to CREATE it dynamically here?
            // Or easier: Always create it in createVotesZone but HIDDEN?
            // No, simpler to just re-create it here if missing.
            const noun = (ctx.kind === 'post') ? 'Post' : 'Topic';
            // Lazy import createEl? Or assume it's available? 
            // createEl is imported in main.js.
            const watermark = createEl("div", ["sp-vote-watermark"]);
            watermark.textContent = noun.toUpperCase();
            refs.votesZone.appendChild(watermark);
          }
        });
      }
    }

    // Logo
    let logoScore = summary.score != null ? Number(summary.score) : 0;
    let memberRank = mStats.rank != null ? Number(mStats.rank) : null;
    updateLogoVisual(root, header, logoScore, memberRank);

    // 4. Process Bitcoin Stats (Price Logic)
    if (fullData.btc_stats && refs.statsPrice && refs.statsGraph) {
      // DEBUG: Log BTC Data

      // Only render if not minimized (or if just un-minimized)
      if (!root.classList.contains("sp-root-minimized")) {
        renderStats(refs.statsPrice, refs.statsGraph, fullData.btc_stats);
        if (refs.statsZone) refs.statsZone.classList.remove("sp-zone-loading");
      }
    } else {
      // Always show zone (will be empty/default if not rendered)
      if (refs.statsZone) refs.statsZone.classList.remove("sp-zone-loading");
      if (refs.statsPrice) refs.statsPrice.textContent = "---";
    }

    // 5. Ads (Separate Hydration due to external image loading, but fast)
    // Kept separate as it doesn't need DB.

  } catch (e) {
    spError("hydrateFullContext", e);
  }
}

// Init Logic
(async function init() {
  try {
    if (!SP_CONFIG.SITE_PATTERN.test(location.hostname)) return;
    if (document.getElementById(SP_CONFIG.ROOT_ID)) return;

    const theme = await getState("theme", SP_CONFIG.DEFAULT_THEME);
    const voteContext = getVotingContext(); // Sync parse

    // v0.36.30 FIX: Fetch AutoMin EARLY to prevent flash
    // v0.36.35 FIX: Fetch Position EARLY to prevent flash
    const [autoMin, savedLeft] = await Promise.all([
      getState("autoMin", false),
      getState("toolbarLeft", null)
    ]);
    const isAutoMin = autoMin === true;

    // 1. Root & Base
    const root = buildRoot("light", isAutoMin, savedLeft);
    const header = buildToolbar(root, voteContext);

    // No longer need to apply it late
    // if (autoMin) applyMinimizedState(root, true);

    updateLogoVisual(root, header, 0);
    const { panel: searchPanel, input: searchInput } = attachSearch(root, header);

    // Member Bootstrap is now implicit in first hydrate
    const memberUuid = await getOrCreateMemberUuid();

    // v0.34.4 FIX: Explicitly bootstrap (register) the member before fetching context.
    // This prevents 500 Errors on the backend if the SP assumes existence.
    try { await bootstrapMember(memberUuid); } catch (e) { }

    spLog("ShadowPulse build " + SP_CONFIG.VERSION + " initialized");
    // spLog("ShadowPulse member UUID", memberUuid);
    // trackPageView(memberUuid); // Removed: Handled in SP now.

    // Saved Position (Applied in buildRoot now)
    // const savedLeft = await getState("toolbarLeft", null);
    // if (typeof savedLeft === "number") { ... }

    // Events
    const logoCircle = header.querySelector(".sp-logo-circle");
    if (logoCircle) {
      // ... (Same event logic, abbreviated for replacement context alignment? 
      // Wait, I need to preserve the event listener logic specifically).
      // I will copy it.
      let logoDownX = null, logoDownY = null;
      logoCircle.addEventListener("mousedown", (e) => { logoDownX = e.clientX; logoDownY = e.clientY; });
      logoCircle.addEventListener("click", async (e) => {
        const dx = Math.abs(e.clientX - (logoDownX || 0));
        const dy = Math.abs(e.clientY - (logoDownY || 0));
        if (dx > 3 || dy > 3) return;

        const isMinimized = root.classList.contains("sp-root-minimized");
        if (isMinimized) {
          applyMinimizedState(root, false);
          try { await setState("autoMin", false); } catch (err) { }
          hydrateAds(header);
          hydrateFullContext(root, header, voteContext);
          return;
        }
        toggleSearchPanel(searchPanel, searchInput);
      }, { capture: true });
    }

    // Auto Min (Moved to start of init)
    // const autoMin = (await getState("autoMin", false)) === true;
    // if (autoMin) applyMinimizedState(root, true);

    // ... imports
    // ... imports removed

    // ... existing code ...

    // === INITIAL LOAD (Parallel) ===
    hydrateAds(header);
    hydrateStats(header); // Fallback
    // injectPostVotes(); // REDACTED: User prefers Toolbar-only design.
    await hydrateFullContext(root, header, voteContext); // Main Context

    // spLog("ShadowPulse initialized."); // Consolidated above

    // === HASH/URL CHANGE LISTENER ===
    // Detects when user clicks "#msg123" to switch context from Topic -> Post
    window.addEventListener("hashchange", async () => {
      const newCtx = getVotingContext();
      await hydrateFullContext(root, header, newCtx);
    });

    // === SETTINGS CLOSED LISTENER ===
    window.addEventListener("sp-settings-closed", async () => {
      const currentCtx = getVotingContext();
      // Re-hydrate everything (Ads, Stats, Votes) to catch settings changes
      hydrateAds(header);
      hydrateStats(header);
      await hydrateFullContext(root, header, currentCtx);
    });

    // === PERIODIC REFRESH ===
    const refreshIntervalMins = SP_CONFIG.REFRESH_INTERVAL_MINUTES || 0.5;
    setInterval(async () => {
      if (document.hidden) return;
      // Re-evaluate context in case URL changed without hash event (rare but safe)
      const currentCtx = getVotingContext();
      await hydrateFullContext(root, header, currentCtx);
    }, refreshIntervalMins * 60 * 1000);

  } catch (err) {
    spError("init error", err);
  }
})();
