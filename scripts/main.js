"use strict";

/**
 * ShadowPulse - main.js
 * Validates host, creates root, assembles zones and wires up behaviors.
 * UPDATED: Implements new phased loading, consolidated periodic refresh, and new logo logic.
 */

import { SP_CONFIG } from "./core/config.js";
import { spLog, spError, createEl } from "./core/utils.js";
import { getState, setState } from "./core/state.js";
import { fetchBitcoinStats, fetchVoteSummary, submitVote, fetchEffectiveVote, bootstrapMember, trackPageView } from "./core/api.js";
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

function buildRoot(theme) {
  const root = createEl("div", ["sp-root"], { id: SP_CONFIG.ROOT_ID });
  root.style.position = "fixed";
  root.classList.add(`sp-theme-${theme}`);
  root.style.left = "50%";
  root.style.transform = "translateX(-50%)";
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
        // 1. Submit the vote (sends to server, but the button is already highlighted by ui/votes.js)
        await submitVote(desiredValue, voteContext);

        // 2. CRITICAL FIX: Force a full re-hydration from the server.
        // This fetches the CORRECT global average (for the logo) 
        // and the CORRECT total count (for the info zone text).
        await hydrateVoteAndLogo(root, header, voteContext);

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
  const controlZone = createControlZone({
    onMinimize: () => {
      const nowMin = !root.classList.contains("sp-root-minimized");
      applyMinimizedState(root, nowMin);

      // When minimized/restored, immediately re-run load/update
      if (!nowMin) {
        // Trigger ASAP load/refresh when restoring from minimize
        hydrateAds(header);
        hydrateVoteAndLogo(root, header, voteContext);

        // Trigger Stats data refresh
        const isMinimized = root.classList.contains("sp-root-minimized");
        if (!isMinimized) {
          hydrateStats(header);
        }
      } else {
        // If minimized, check effective vote for logo update
        fetchEffectiveVote(voteContext).then(ev => {
          updateLogoVisual(root, header, ev || 0, null);
        });
      }
    },
    onAutoMin: async () => {
      const current = (await getState("autoMin", false)) === true;
      const next = !current;
      await setState("autoMin", next);
      applyMinimizedState(root, next);
    },
    onSettings: () => {
      openSettingsModal(root);
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
      header._spRefs.statsPrice.textContent = "Err";
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

async function hydrateVoteAndLogo(root, header, voteContext) {
  try {
    const refs = header._spRefs || {};
    const ctx = voteContext || refs.voteContext || null;

    // 1. Settings Link Attention
    const restoreAck = await getState("memberRestoreAck", false);
    const settingsLink = root.querySelector(".sp-ctrl-settings");
    if (settingsLink) {
      if (!restoreAck) {
        settingsLink.classList.add("sp-attention");
      } else {
        settingsLink.classList.remove("sp-attention");
      }
    }

    if (!ctx || !ctx.canVote) {
      if (refs.votesZone) refs.votesZone.classList.remove("sp-zone-loading");
      return;
    }

    // --- DECOUPLE VALUES ---
    let logoGlobalScore = 0;   // Logo uses Global Score
    let myPersonalVote = null; // Buttons use Personal Vote (Effective)
    let myDesiredVote = null;  // Buttons use Desired Vote (if different)
    let initialRank = null;

    const summary = await fetchVoteSummary(ctx);

    if (summary) {
      // a. Global Score/Count for Logo and Summary Text
      if (summary.topic_score != null) {
        logoGlobalScore = Number(summary.topic_score);
      }

      // b. Personal Vote for Button Highlight
      if (summary.currentVote != null) {
        myPersonalVote = Number(summary.currentVote);
      }

      // c. Desired Vote (from get_vote.php)
      if (summary.desired_value != null) {
        myDesiredVote = Number(summary.desired_value);
      }

      // d. Rank (Global Rank)
      if (summary.rank != null) {
        initialRank = Number(summary.rank);
      }

      // --- 3. APPLY PERSONAL VOTE TO BUTTONS (CRITICAL FIX) ---
      // Uses the directly imported setSelected function
      const buttons = refs.voteButtons;
      if (buttons && buttons.length) {
        setSelected(buttons, myPersonalVote, myDesiredVote);
      }

      // --- 4. RENDER GLOBAL COUNT/SUMMARY TEXT (CRITICAL FIX) ---
      if (!root.classList.contains("sp-root-minimized")) {
        const summaryEl = refs.votesSummary;
        if (summaryEl) {
          // Uses the directly imported renderVoteSummary function
          renderVoteSummary(summaryEl, summary, ctx);
        }
        if (refs.votesZone) refs.votesZone.classList.remove("sp-zone-loading");
      }
    }

    // Update Logo (Use Global Score/Rank)
    updateLogoVisual(root, header, logoGlobalScore || 0, initialRank || null);

  } catch (err) {
    spError("hydrateVoteAndLogo error", err);
  }
}

async function applyMinimizedState(root, minimized) {
  if (minimized) {
    if (root.dataset) {
      if (root.dataset.prevLeft === undefined) {
        root.dataset.prevLeft = root.style.left || "";
      }
      if (root.dataset.prevTransform === undefined) {
        root.dataset.prevTransform = root.style.transform || "";
      }
    }
    root.classList.add("sp-root-minimized");
    root.style.left = "16px";
    root.style.transform = "none";
  } else {
    root.classList.remove("sp-root-minimized");
    if (root.dataset) {
      if (root.dataset.prevLeft !== undefined) {
        root.style.left = root.dataset.prevLeft;
      }
      if (root.dataset.prevTransform !== undefined) {
        root.style.transform = root.dataset.prevTransform;
      }
    }
    try {
      if (root.style.transform === "none") {
        const leftStr = root.style.left || "";
        const match = leftStr.match(/-?\d+/);
        if (match) {
          const finalLeft = parseInt(match[0], 10);
          setState("toolbarLeft", finalLeft);
        }
      }
    } catch (e) {
      // ignore storage errors
    }
  }
}

(async function init() {
  try {
    // Only run on bitcointalk.org (or matching SITE_PATTERN)
    if (!SP_CONFIG.SITE_PATTERN.test(location.hostname)) {
      spLog("Not bitcointalk.org, aborting ShadowPulse.");
      return;
    }

    // Avoid duplicate roots
    if (document.getElementById(SP_CONFIG.ROOT_ID)) {
      spLog("ShadowPulse root already exists; skipping.");
      return;
    }

    const theme = await getState("theme", SP_CONFIG.DEFAULT_THEME);
    const root = buildRoot(theme); // IMMEDIATE: Draw extension
    const voteContext = getVotingContext();
    const header = buildToolbar(root, voteContext); // IMMEDIATE: Logo and Control/Settings zones visible

    // IMMEDIATE: Ensure logo shows a sane default ("SP") immediately.
    updateLogoVisual(root, header, 0);
    const { panel: searchPanel, input: searchInput } = attachSearch(root, header);

    // Kick off member bootstrap in the background
    (async () => {
      // CRITICAL FIX: Get previous ID before checking server
      const previousId = await getState("memberId", 0);

      const memberUuid = await getOrCreateMemberUuid();
      spLog("ShadowPulse build", SP_CONFIG.VERSION);
      spLog("ShadowPulse member UUID", memberUuid);
      try {
        trackPageView(memberUuid);
        const info = await bootstrapMember(memberUuid);

        if (info && info.member_id != null) {
          const numericId = Number(info.member_id) || 0;

          // Identity Change Detection (e.g., switched from temp ID to restored ID)
          if (numericId > 0 && previousId > 0 && numericId !== previousId) {
            spLog(`Identity Change Detected (${previousId} -> ${numericId}). Wiping local storage.`);

            // WIPE STORAGE and force reload to ensure a clean start
            await new Promise(r => chrome.storage.local.clear(r));

            await setState("memberUuid", info.member_uuid || memberUuid);
            await setState("memberId", numericId);
            await setState("memberRestoreAck", !!info.restore_ack);
            window.location.reload();
            return;
          }

          if (numericId > 0) {
            await setState("memberId", numericId);
            await setState("memberUuid", info.member_uuid || memberUuid);
            await setState("memberRestoreAck", !!info.restore_ack);

            // Re-run vote/logo/settings-highlight logic after bootstrap to use new ID/Ack
            await hydrateVoteAndLogo(root, header, voteContext);
          }
        }
      } catch (err) {
        console.error("[ShadowPulse] bootstrapMember failed", err);
      }
    })();

    const savedLeft = await getState("toolbarLeft", null);
    if (typeof savedLeft === "number") {
      root.style.left = savedLeft + "px";
      root.style.transform = "none";
    }

    // Rewire logo click to toggle the search panel
    let logoDownX = null;
    let logoDownY = null;

    const logoCircle = header.querySelector(".sp-logo-circle");
    if (logoCircle) {
      logoCircle.addEventListener("mousedown", (e) => {
        logoDownX = e.clientX;
        logoDownY = e.clientY;
      });
      logoCircle.addEventListener(
        "click",
        async (e) => {
          const dx = logoDownX == null ? 0 : Math.abs(e.clientX - logoDownX);
          const dy = logoDownY == null ? 0 : Math.abs(e.clientY - logoDownY);

          if (dx > 3 || dy > 3) return; // Treat as drag

          const isMinimized = root.classList.contains("sp-root-minimized");
          if (isMinimized) {
            applyMinimizedState(root, false);
            try {
              const autoMin = (await getState("autoMin", false)) === true;
              if (autoMin) await setState("autoMin", false);
            } catch (e) { /* ignore */ }

            // Re-trigger ASAP load after restore
            hydrateAds(header);
            hydrateVoteAndLogo(root, header, voteContext);
            hydrateStats(header); // Trigger stats refresh immediately on restore
            return;
          }
          toggleSearchPanel(searchPanel, searchInput);
        },
        { capture: true }
      );
    }

    const autoMin = (await getState("autoMin", false)) === true;
    if (autoMin) {
      applyMinimizedState(root, true);
    }

    const isMinimizedNow = root.classList.contains("sp-root-minimized");

    // === HYDRATION (ASAP phase) ===

    // 1. Load Ad & make visible
    await hydrateAds(header);

    // 2. Initial Vote/Logo/Settings Highlight/Votes Zone Visibility
    await hydrateVoteAndLogo(root, header, voteContext);

    // === PERIODIC REFRESH (EVERY xx MINS) ===
    const refreshIntervalMins = SP_CONFIG.REFRESH_INTERVAL_MINUTES || 0.5; // Default to 30 seconds
    const intervalMs = refreshIntervalMins * 60 * 1000;

    // Run initial periodic update immediately if not minimized
    if (!isMinimizedNow) {
      await hydrateStats(header);
    }

    // Consolidate both vote and stats refresh into one timer
    setInterval(async () => {
      // 1. Refresh VOTE status, Logo, and Summary (from get_vote)
      await hydrateVoteAndLogo(root, header, voteContext);

      // 2. Refresh BITCOIN STATS (from last record / API)
      if (!root.classList.contains("sp-root-minimized")) {
        await hydrateStats(header);
      }
    }, intervalMs);

    if (SP_CONFIG.DEV_MODE && !autoMin) {
      applyMinimizedState(root, false);
    }

    spLog("ShadowPulse initialized.");
  } catch (err) {
    spError("init error", err);
  }
})();