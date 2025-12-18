"use strict";

/**
 * ShadowPulse - ui/settings.js
 * Settings modal:
 * - Active Vote Counts.
 * - Search Engine preference.
 * - Settings Sync.
 * - Smart Restore (Auto-save + Refresh).
 * - Restore Warning (Dynamic Show/Hide).
 */

import { createEl } from "../core/utils.js";
import { getState, setState } from "../core/state.js";
import { SP_CONFIG } from "../core/config.js";
import { setMemberUuid } from "../core/member.js";
import { restoreMember, updateRestoreAck, fetchMemberStats, savePreferences } from "../core/api.js";

export async function openSettingsModal(root) {
  if (!root) {
    root = document.getElementById(SP_CONFIG.ROOT_ID);
    if (!root) return;
  }

  let backdrop = root.querySelector(".sp-settings-backdrop");
  if (!backdrop) {
    backdrop = buildSettingsModal(root);
    root.appendChild(backdrop);
  }

  // Restore UI state for Theme
  try {
    const currentTheme = await getState("theme", SP_CONFIG.DEFAULT_THEME);
    applyThemeSelection(backdrop, currentTheme);
  } catch (_) { }

  // Restore UI state for Search Engine
  try {
    const currentSearch = await getState("searchEngine", "bitlist");
    const searchSelect = backdrop.querySelector('select[name="sp-search-engine"]');
    if (searchSelect) searchSelect.value = currentSearch;
  } catch (_) { }

  // Populate Member Identity
  try {
    const memberId = await getState("memberId", 0);
    const memberUuid = await getState("memberUuid", null);
    const restoreAck = await getState("memberRestoreAck", false);
    applyMemberIdentity(backdrop, memberId, memberUuid, !!restoreAck);
  } catch (_) { }

  backdrop.classList.add("sp-settings-open");
  resetRestoreUI(backdrop); // Reset on Open

  // Fetch & Populate Statistics
  (async () => {
    try {
      const memberUuid = await getState("memberUuid", null);
      if (memberUuid) {
        const response = await fetchMemberStats(memberUuid);
        if (response && response.stats) {
          applyStats(backdrop, response.stats);
        }
      }
    } catch (err) {
      console.error("[ShadowPulse] Stats fetch failed:", err);
    }
  })();
}

function buildSettingsModal(root) {
  const backdrop = createEl("div", ["sp-settings-backdrop"]);
  const dialog = createEl("div", ["sp-settings-modal"]);
  backdrop.appendChild(dialog);

  // === HEADER ===
  const header = createEl("div", ["sp-settings-header"]);
  const logo = createEl("img", ["sp-settings-header-logo"]);
  logo.src = chrome.runtime.getURL("assets/icons/logo.svg");
  logo.style.width = "48px";
  logo.style.height = "48px";
  logo.style.borderRadius = "50%";
  header.appendChild(logo);

  const headerText = createEl("div", ["sp-settings-header-text"]);
  const title = createEl("div", ["sp-settings-title"]);
  title.textContent = "ShadowPulse";
  const version = createEl("div", ["sp-settings-version"]);
  version.textContent = "Version " + SP_CONFIG.VERSION;

  headerText.appendChild(title);
  headerText.appendChild(version);
  header.appendChild(headerText);
  dialog.appendChild(header);

  const body = createEl("div", ["sp-settings-body"]);

  function createCenterBlock() {
    return createEl("div", ["sp-settings-center-block"]);
  }

  // ===== SETTINGS SECTION =====
  const settingsSection = createEl("div", ["sp-settings-section"]);
  const settingsTitle = createEl("div", ["sp-settings-section-title"]);
  settingsTitle.textContent = "Settings";
  settingsSection.appendChild(settingsTitle);

  const settingsBlock = createCenterBlock();

  // 1. Theme
  const themeRow = createEl("div", ["sp-settings-row"]);
  const themeLabel = createEl("span", ["sp-settings-row-label"]);
  themeLabel.textContent = "Theme:";

  const themeSelect = createEl("select", ["sp-settings-select"]);
  themeSelect.name = "sp-theme";
  themeSelect.appendChild(new Option("Light", "light"));
  themeSelect.appendChild(new Option("Dark", "dark"));

  themeSelect.addEventListener("change", async () => {
    const val = themeSelect.value;
    await applyTheme(root, val);
    savePreferences({ theme: val });
  });

  themeRow.appendChild(themeLabel);
  themeRow.appendChild(themeSelect);
  settingsBlock.appendChild(themeRow);

  // 2. Search Engine
  const searchRow = createEl("div", ["sp-settings-row"]);
  const searchLabel = createEl("span", ["sp-settings-row-label"]);
  searchLabel.textContent = "Search Engine:";

  const searchSelect = createEl("select", ["sp-settings-select"]);
  searchSelect.name = "sp-search-engine";
  searchSelect.appendChild(new Option("BitList", "bitlist"));

  searchSelect.addEventListener("change", async () => {
    const val = searchSelect.value;
    await setState("searchEngine", val);
    savePreferences({ search: val });
  });

  searchRow.appendChild(searchLabel);
  searchRow.appendChild(searchSelect);
  settingsBlock.appendChild(searchRow);

  // 3. Bitcoin Source
  const btcRow = createEl("div", ["sp-settings-row"]);
  const btcLabel = createEl("span", ["sp-settings-row-label"]);
  btcLabel.textContent = "Bitcoin Source:";

  const btcSelect = createEl("select", ["sp-settings-select"]);
  btcSelect.appendChild(new Option("Binance Standard", "binance"));

  btcSelect.addEventListener("change", async () => {
    const val = btcSelect.value;
    await setState("btcSource", val);
    savePreferences({ btcSource: val });
  });

  getState("btcSource", "binance").then(val => { btcSelect.value = val; });

  btcRow.appendChild(btcLabel);
  btcRow.appendChild(btcSelect);
  settingsBlock.appendChild(btcRow);

  // 2.5 Custom Name (Now Moved to Bottom of Settings)
  const nameRow = createEl("div", ["sp-settings-row"]);
  const nameLabel = createEl("span", ["sp-settings-row-label"]);
  nameLabel.textContent = "Display Name:";

  // Container
  const nameContainer = createEl("div", ["sp-settings-input-container"]);
  nameContainer.style.display = "flex";
  // EXACT MATCH: Dropdowns are 120px. We match that width.
  nameContainer.style.width = "120px";
  nameContainer.style.gap = "4px";
  nameContainer.style.alignItems = "center";
  nameContainer.style.justifyContent = "flex-end";

  const nameInput = createEl("input", ["sp-settings-input"]);
  nameInput.type = "text";
  nameInput.style.textAlign = "right";
  nameInput.placeholder = "Loading...";
  nameInput.maxLength = 32;
  nameInput.style.flex = "1";
  nameInput.style.minWidth = "0";
  // Light Gray Background to match dropdowns (user request)
  nameInput.style.backgroundColor = "rgba(0,0,0,0.05)";
  nameInput.style.color = "var(--sp-text)"; // Use Theme Variable (Black in Light, White in Dark)
  nameInput.style.border = "1px solid var(--sp-border)";

  const applyBtn = createEl("button", ["sp-settings-button"]);
  applyBtn.textContent = "✓";
  applyBtn.disabled = true;
  applyBtn.style.padding = "0";
  applyBtn.style.cursor = "pointer";
  applyBtn.style.minWidth = "24px";
  applyBtn.style.width = "24px"; // Fixed square
  applyBtn.style.height = "24px";
  applyBtn.style.display = "flex";
  applyBtn.style.alignItems = "center";
  applyBtn.style.justifyContent = "center";

  let originalName = "";

  // Load current name & Member ID for placeholder
  (async () => {
    try {
      const items = await chrome.storage.local.get(['custom_name', 'stats_member_id']);
      if (items.custom_name) {
        nameInput.value = items.custom_name;
        originalName = items.custom_name;
      }

      // Try state first for Member ID
      const memId = await getState("memberId", 0);
      if (memId && memId > 0) {
        nameInput.placeholder = String(memId);
      } else if (items.stats_member_id) {
        nameInput.placeholder = String(items.stats_member_id);
      } else {
        nameInput.placeholder = "Member ID";
      }
    } catch (e) { console.error(e); }
  })();

  // Validation & Dirty Check
  nameInput.addEventListener("input", () => {
    const val = nameInput.value.trim();
    const isValid = /^[a-zA-Z0-9 _-]*$/.test(val);
    const isDirty = val !== originalName;

    if (!isValid) {
      nameInput.style.borderColor = "red";
      nameInput.style.color = "red";
      nameInput.title = "Only alphanumeric characters, spaces, dashes, and underscores allowed.";
      applyBtn.disabled = true;
    } else {
      nameInput.style.borderColor = "var(--sp-border)"; // Reset to default border
      nameInput.style.color = "";
      nameInput.title = ""; // Clear error
      applyBtn.disabled = !isDirty;
    }
    // Clear any previous status colors on editing
    applyBtn.style.backgroundColor = "";
    applyBtn.style.color = "";
    applyBtn.style.borderColor = "";
  });

  // Save Action
  applyBtn.addEventListener("click", async () => {
    const val = nameInput.value.trim();
    applyBtn.disabled = true;
    applyBtn.textContent = "…";

    try {
      // FIXED: Use getState for reliable retrieval from namespaced storage
      const memberUuid = await getState("memberUuid");
      if (!memberUuid) {
        throw new Error("No Member UUID found locally.");
      }

      const res = await fetch("https://vod.fan/shadowpulse/api/v1/update_member_profile.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          member_uuid: memberUuid,
          custom_name: val
        })
      });
      const json = await res.json();

      if (json.ok) {
        const finalName = json.sanitized_name || val;
        chrome.storage.local.set({ custom_name: finalName });
        nameInput.value = finalName;
        originalName = finalName; // Update original so isDirty becomes false

        // Success State (Green)
        nameInput.style.borderColor = "#28a745"; // Green
        nameInput.style.color = "#28a745";
        nameInput.title = "Saved successfully!";

        applyBtn.style.borderColor = "#28a745";
        applyBtn.style.color = "#28a745";

        setTimeout(() => {
          nameInput.style.borderColor = "var(--sp-border)";
          nameInput.style.color = "";
          nameInput.title = "";
          applyBtn.style.borderColor = "";
          applyBtn.style.color = "";
        }, 2000);

        applyBtn.disabled = true; // Stay disabled until input changes again
      } else {
        // Error State (Red)
        console.error("API Error:", json.error);
        nameInput.style.borderColor = "red";
        nameInput.style.color = "red";
        nameInput.title = json.error || "Unknown Error"; // Show tooltip

        applyBtn.style.borderColor = "red";
        applyBtn.style.color = "red";
        applyBtn.disabled = false;
      }
    } catch (e) {
      console.error(e);
      nameInput.style.borderColor = "red";
      nameInput.style.color = "red";
      nameInput.title = e.message || "Network Error";

      applyBtn.style.borderColor = "red";
      applyBtn.style.color = "red";
      applyBtn.disabled = false;
    } finally {
      applyBtn.textContent = "✓";
    }
  });

  nameContainer.appendChild(nameInput);
  nameContainer.appendChild(applyBtn);

  nameRow.appendChild(nameLabel);
  nameRow.appendChild(nameContainer);
  settingsBlock.appendChild(nameRow);

  settingsSection.appendChild(settingsBlock);
  body.appendChild(settingsSection);

  // ===== STATISTICS SECTION =====
  const statsSection = createEl("div", ["sp-settings-section"]);
  const statsTitle = createEl("div", ["sp-settings-section-title"]);
  statsTitle.textContent = "Statistics";
  statsSection.appendChild(statsTitle);

  const statsBlock = createCenterBlock();

  const memberRow = createEl("div", ["sp-settings-row"]);
  const memberLabel = createEl("span", ["sp-settings-row-label"]);
  memberLabel.textContent = "Member Number:";
  const memberValue = createEl("span", ["sp-settings-row-value", "sp-settings-stat-value"]);
  memberValue.dataset.role = "sp-member-id";
  memberValue.textContent = "—";
  memberRow.appendChild(memberLabel);
  memberRow.appendChild(memberValue);
  statsBlock.appendChild(memberRow);

  function makeStatRow(labelText, dataRole, linkUrl) {
    const row = createEl("div", ["sp-settings-row"]);
    const label = createEl("span", ["sp-settings-row-label"]);

    if (linkUrl) {
      const a = createEl("a");
      a.href = linkUrl;
      a.textContent = labelText;
      a.target = "_blank";
      a.style.textDecoration = "none";
      a.style.color = "inherit";
      a.style.cursor = "pointer";
      a.onmouseenter = () => a.style.textDecoration = "underline";
      a.onmouseleave = () => a.style.textDecoration = "none";

      // Append UUID if available
      getState("memberUuid", null).then(uuid => {
        if (uuid) a.href = linkUrl + "?uuid=" + uuid;
      });

      label.appendChild(a);
    } else {
      label.textContent = labelText;
    }

    const value = createEl("span", ["sp-settings-row-value", "sp-settings-stat-value"]);
    if (dataRole) {
      value.dataset.role = dataRole;
    }
    value.textContent = "—";
    row.appendChild(label);
    row.appendChild(value);
    return row;
  }

  const diagUrl = "https://vod.fan/shadowpulse/website/reports/pyramid.php";

  statsBlock.appendChild(makeStatRow("Page Views:", "sp-stat-page-views"));
  // 2) Remove "Active " and apply diagUrl
  statsBlock.appendChild(makeStatRow("Topic Votes:", "sp-stat-topic-votes", diagUrl));
  statsBlock.appendChild(makeStatRow("Post Votes:", "sp-stat-post-votes", diagUrl));
  statsBlock.appendChild(makeStatRow("Searches Made:", "sp-stat-searches"));

  const globalRow = createEl("div", ["sp-settings-row"]);
  globalRow.style.marginTop = "15px";

  const globalLink = createEl("a", ["sp-settings-link"]);
  globalLink.textContent = "Reports Center"; // 4) Rename
  globalLink.target = "_blank";
  globalLink.rel = "noopener noreferrer";

  // 4) New URL
  const reportBaseUrl = "https://vod.fan/shadowpulse/website/reports/index.php";
  globalLink.href = reportBaseUrl;



  globalRow.appendChild(globalLink);
  statsBlock.appendChild(globalRow);

  statsSection.appendChild(statsBlock);
  body.appendChild(statsSection);

  // ===== ACCOUNT SECURITY SECTION (With Toggle) =====
  const secSection = createEl("div", ["sp-settings-section"]);

  const secHeader = createEl("div", ["sp-settings-section-header"]);
  const secTitle = createEl("div", ["sp-settings-section-title"]);
  secTitle.textContent = "Account Security";
  secTitle.style.marginBottom = "0";

  const toggleBtn = createEl("button", ["sp-settings-section-toggle"]);
  toggleBtn.type = "button";
  toggleBtn.textContent = "SHOW";

  secHeader.appendChild(secTitle);
  secHeader.appendChild(toggleBtn);
  secSection.appendChild(secHeader);

  const secBlock = createCenterBlock();
  secBlock.classList.add("sp-settings-security-block");
  secBlock.style.display = "none";

  toggleBtn.addEventListener("click", (e) => {
    e.preventDefault();
    if (secBlock.style.display === "none") {
      secBlock.style.display = "flex";
      toggleBtn.textContent = "HIDE";
    } else {
      secBlock.style.display = "none";
      toggleBtn.textContent = "SHOW";
    }
    resetRestoreUI(backdrop); // Reset on Toggle
  });

  const codeCol = createEl("div", ["sp-settings-col"]);

  const codeLabel = createEl("div", ["sp-settings-label-block"]);
  codeLabel.textContent = "Private Restore Code:";

  const codeGroup = createEl("div", ["sp-settings-input-group"]);

  const codeValue = createEl("span", ["sp-settings-restore-code"]);
  codeValue.textContent = "…";
  codeValue.dataset.role = "sp-restore-code";

  const copyBtn = createEl("button", ["sp-settings-restore-copy"]);
  copyBtn.type = "button";
  copyBtn.textContent = "Copy";
  copyBtn.dataset.role = "sp-restore-copy";

  codeGroup.appendChild(codeValue);
  codeGroup.appendChild(copyBtn);

  codeCol.appendChild(codeLabel);
  codeCol.appendChild(codeGroup);
  secBlock.appendChild(codeCol);

  const ackRow = createEl("label", ["sp-settings-restore-ack"]);
  const ackInput = createEl("input");
  ackInput.type = "checkbox";
  ackInput.dataset.role = "sp-restore-ack";

  const ackText = createEl("span", ["sp-settings-label"]);
  ackText.textContent = "Restore Code Saved Safely:";

  ackRow.appendChild(ackText);
  ackRow.appendChild(ackInput);

  secBlock.appendChild(ackRow);

  const restoreCol = createEl("div", ["sp-settings-col"]);
  restoreCol.style.marginTop = "12px";
  const restoreLabel = createEl("div", ["sp-settings-label-block"]);
  restoreLabel.textContent = "Restore:";

  const restoreGroup = createEl("div", ["sp-settings-input-group"]);
  const pasteInput = createEl("input", ["sp-settings-restore-input"]);
  pasteInput.type = "text";
  pasteInput.placeholder = "Paste code";
  pasteInput.dataset.role = "sp-restore-input";

  const restoreBtn = createEl("button", ["sp-settings-restore-button"]);
  restoreBtn.type = "button";
  restoreBtn.textContent = "Go";
  restoreBtn.dataset.role = "sp-restore-button";

  restoreGroup.appendChild(pasteInput);
  restoreGroup.appendChild(restoreBtn);
  restoreCol.appendChild(restoreLabel);
  restoreCol.appendChild(restoreGroup);

  const warningText = createEl("div", ["sp-settings-warning"]);
  warningText.textContent = "⚠️ This will overwrite all your Settings and Statistics!";
  warningText.dataset.role = "sp-warning-text"; // Added role for access
  warningText.style.display = "none";
  restoreCol.appendChild(warningText);

  pasteInput.addEventListener("input", () => {
    if (pasteInput.value.trim().length > 0) {
      warningText.style.display = "block";
    } else {
      warningText.style.display = "none";
    }
  });

  secBlock.appendChild(restoreCol);

  secSection.appendChild(secBlock);
  body.appendChild(secSection);

  dialog.appendChild(body);

  const footer = createEl("div", ["sp-settings-footer"]);
  const closeBtn = createEl("button", ["sp-settings-close-btn"]);
  closeBtn.type = "button";
  closeBtn.textContent = "Close";
  footer.appendChild(closeBtn);
  dialog.appendChild(footer);

  closeBtn.addEventListener("click", (e) => {
    e.preventDefault();
    backdrop.classList.remove("sp-settings-open");
  });

  backdrop.addEventListener("click", (e) => {
    if (e.target === backdrop) {
      backdrop.classList.remove("sp-settings-open");
    }
  });

  return backdrop;
}

function applyMemberIdentity(backdrop, memberId, memberUuid, restoreAck) {
  const memberEl = backdrop.querySelector('[data-role="sp-member-id"]');
  const codeEl = backdrop.querySelector('[data-role="sp-restore-code"]');
  const copyBtn = backdrop.querySelector('[data-role="sp-restore-copy"]');
  const inputEl = backdrop.querySelector('[data-role="sp-restore-input"]');
  const restoreBtn = backdrop.querySelector('[data-role="sp-restore-button"]');
  const ackInput = backdrop.querySelector('[data-role="sp-restore-ack"]');
  const toggleBtn = backdrop.querySelector(".sp-settings-section-toggle");
  const secBlock = backdrop.querySelector(".sp-settings-security-block");

  const root = document.getElementById(SP_CONFIG.ROOT_ID);
  const settingsLink = root ? root.querySelector(".sp-ctrl-settings") : null;

  function applySettingsAttention(ack) {
    if (settingsLink) {
      if (!ack) settingsLink.classList.add("sp-attention");
      else settingsLink.classList.remove("sp-attention");
    }
    if (toggleBtn) {
      if (!ack) toggleBtn.classList.add("sp-attention");
      else toggleBtn.classList.remove("sp-attention");
    }
  }

  if (memberEl) {
    if (memberId && memberId > 0) {
      memberEl.textContent = String(memberId);
    } else {
      memberEl.textContent = "(not assigned yet)";
    }
  }

  if (codeEl) {
    codeEl.textContent = memberUuid || "(not assigned yet)";
  }
  if (ackInput) {
    ackInput.checked = !!restoreAck;
  }

  applySettingsAttention(!!restoreAck);

  if (copyBtn && codeEl) {
    copyBtn.onclick = async (e) => {
      e.preventDefault();
      const text = codeEl.textContent || "";
      try {
        await navigator.clipboard.writeText(text);
      } catch (_) { }
    };
  }

  const warningEl = backdrop.querySelector('[data-role="sp-warning-text"]'); // Verify selection

  if (restoreBtn && inputEl) {
    if (warningEl && inputEl) {
      // Reset on input
      inputEl.addEventListener("input", () => {
        if (warningEl.textContent !== "⚠️ This will overwrite all your Settings and Statistics!") {
          warningEl.textContent = "⚠️ This will overwrite all your Settings and Statistics!";
          warningEl.style.color = "#ef4444"; // Reset color to red/default
        }
      });
    }

    restoreBtn.onclick = async (e) => {
      e.preventDefault();
      const code = (inputEl.value || "").trim();
      if (!code) return;

      // Reset State
      inputEl.style.borderColor = "";
      inputEl.title = "";
      if (warningEl) {
        warningEl.textContent = "⚠️ This will overwrite all your Settings and Statistics!";
      }

      try {
        const info = await restoreMember(code);
        if (info && (info.member_uuid || info.ok)) { // Handle 200 OK w/ error body
          if (info.ok === false) {
            throw new Error(info.error || "Invalid Code");
          }

          await new Promise((resolve) => {
            chrome.storage.local.clear(() => resolve());
          });

          await setMemberUuid(info.member_uuid);
          await setState("memberUuid", info.member_uuid);
          await setState("memberId", info.member_id || 0);

          if (info.custom_name) {
            await chrome.storage.local.set({ custom_name: info.custom_name });
          }

          if (info.prefs) {
            if (info.prefs.theme) {
              await setState("theme", info.prefs.theme);
              await applyTheme(root, info.prefs.theme); // Apply Immediately
            }
            if (info.prefs.search) await setState("searchEngine", info.prefs.search);
            if (info.prefs.btc_source) await setState("btcSource", info.prefs.btc_source);
          }

          await setState("memberRestoreAck", true);
          try { await updateRestoreAck(info.member_uuid, true); } catch (_) { }

          // Success Visual
          inputEl.style.borderColor = "#28a745";
          window.location.reload();
        } else {
          throw new Error("Invalid Code");
        }

      } catch (err) {
        // Only log actual system errors, not expected user input errors
        if (err.message !== "Invalid Code") {
          console.error("[ShadowPulse] restoreMember failed", err);
        }

        // Error Visual
        inputEl.style.borderColor = "red";
        // inputEl.title = "Invalid Code or Network Error"; // Removed tooltip

        if (warningEl) {
          warningEl.textContent = "❌ Invalid Code";
          warningEl.style.display = "block";
        }

        // 1) Focus and put cursor at end
        inputEl.focus();
        const len = inputEl.value.length;
        inputEl.setSelectionRange(len, len);
      }
    };
  }

  if (ackInput) {
    ackInput.onchange = async () => {
      const next = !!ackInput.checked;
      try {
        const currentUuid = memberUuid;
        if (currentUuid) {
          await updateRestoreAck(currentUuid, next);
        }
        await setState("memberRestoreAck", next);
        applySettingsAttention(next);

        if (next && secBlock && toggleBtn) {
          secBlock.style.display = "none";
          toggleBtn.textContent = "SHOW";
        }

      } catch (err) {
        console.error("[ShadowPulse] updateRestoreAck failed", err);
      }
    };
  }
}

function applyThemeSelection(backdrop, theme) {
  const select = backdrop.querySelector('select[name="sp-theme"]');
  if (select) {
    select.value = theme;
  }
}

function formatNumberWithThinSpace(value) {
  const n = Number(value) || 0;
  const negative = n < 0;
  const absStr = String(Math.floor(Math.abs(n)));
  const digits = absStr.split("");
  const parts = [];
  while (digits.length > 3) {
    parts.unshift(digits.splice(digits.length - 3).join(""));
  }
  if (digits.length) {
    parts.unshift(digits.join(""));
  }
  const joined = parts.join("\u2009");
  return negative ? "-" + joined : joined;
}

function formatOrdinal(rank) {
  const n = Number(rank);
  if (!Number.isFinite(n) || n <= 0) return "";
  const v = n % 100;
  let suffix = "th";
  if (v < 11 || v > 13) {
    const d = n % 10;
    if (d === 1) suffix = "st";
    else if (d === 2) suffix = "nd";
    else if (d === 3) suffix = "rd";
  }
  return String(n) + suffix;
}

function formatStatWithRank(value, rank) {
  const n = Number(value) || 0;
  const formattedValue = formatNumberWithThinSpace(value);
  if (n === 0) return formattedValue;
  if (rank == null) return formattedValue;
  const ord = formatOrdinal(rank);
  if (!ord) return formattedValue;
  return formattedValue + " (" + ord + ")";
}

function applyStats(backdrop, stats) {
  if (!stats || typeof stats !== "object") return;
  const mapping = [
    { role: "sp-stat-page-views", valueKey: "page_views", rankKey: "page_views_rank" },
    { role: "sp-stat-topic-votes", valueKey: "topic_votes", rankKey: "topic_votes_rank" },
    { role: "sp-stat-post-votes", valueKey: "post_votes", rankKey: "post_votes_rank" },
    { role: "sp-stat-searches", valueKey: "searches_made", rankKey: "searches_made_rank" }
  ];

  for (const item of mapping) {
    const el = backdrop.querySelector('[data-role="' + item.role + '"]');
    if (!el) continue;
    let value = stats[item.valueKey];
    if (value == null) value = 0;
    const rank = stats[item.rankKey];
    el.textContent = formatStatWithRank(value, rank);
  }
}

async function applyTheme(root, theme) {
  try {
    root.classList.remove("sp-theme-light", "sp-theme-dark");
    root.classList.add("sp-theme-" + theme);
    await setState("theme", theme);
  } catch (_) { }
}

function resetRestoreUI(backdrop) {
  const input = backdrop.querySelector('[data-role="sp-restore-input"]');
  const warning = backdrop.querySelector('[data-role="sp-warning-text"]');
  if (input) {
    input.value = "";
    input.style.borderColor = "";
  }
  if (warning) {
    warning.textContent = "⚠️ This will overwrite all your Settings and Statistics!";
    warning.style.color = "";
    warning.style.display = "none";
  }
}