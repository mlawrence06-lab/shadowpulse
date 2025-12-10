"use strict";

/**
 * ShadowPulse - core/api.js
 * All external endpoints are configured via SP_CONFIG.
 * FIXED: submitVote returns dummy count/rank for immediate UI update.
 */

import { SP_CONFIG } from "./config.js";
import { spLog } from "./utils.js";
import { getOrCreateMemberUuid } from "./member.js";
import { getState } from "./state.js";


// Simple retry helper for transient network/database outages.
const SP_MAX_RETRIES = 3;
const SP_RETRY_DELAY_MS = 3000;

async function spRetryFetch(url, options, label) {
  let attempt = 0;
  while (attempt < SP_MAX_RETRIES) {
    attempt += 1;
    try {
      const res = await fetch(url, options);
      if (!res.ok) {
        spLog(label + " failed with status " + res.status + " (attempt " + attempt + ")");
      } else {
        if (attempt > 1) {
          spLog(label + " succeeded on retry attempt " + attempt);
        }
        return res;
      }
    } catch (err) {
      spLog(label + " error on attempt " + attempt, err && err.message ? err.message : err);
    }

    if (attempt < SP_MAX_RETRIES) {
      await new Promise((resolve) => setTimeout(resolve, SP_RETRY_DELAY_MS * attempt));
    }
  }

  throw new Error(label + " failed after " + SP_MAX_RETRIES + " attempts");
}

export async function fetchAds() {
  spLog("fetchAds() placeholder. ADS_API =", SP_CONFIG.ADS_API);
  return {
    line1: "Advertise your product here",
    line2: "Contact us for promo slots"
  };
}

export async function fetchBitcoinStats() {
  if (!SP_CONFIG.GET_STATS_API || SP_CONFIG.GET_STATS_API === "***") {
    return {
      price_label: "$68,000.00",
      trend: "up",
      history: [68000, 68100, 68050, 68200]
    };
  }

  try {
    // Add cache buster to prevent browser caching of the JSON
    const url = `${SP_CONFIG.GET_STATS_API}?t=${Date.now()}`;
    const res = await fetch(url);
    if (!res.ok) throw new Error(`HTTP ${res.status}`);
    const json = await res.json();
    return json;
  } catch (err) {
    spLog("fetchBitcoinStats error", err);
    return null; 
  }
}

export async function fetchVoteSummary(voteContext) {
  // Summary placeholder
  return {
    topic_score: 0,
    post_score: 0,
    vote_count: 0,
    rank: 0,
    currentVote: null
  };
}

export async function submitVote(value, voteContext) {
  // 1. Check if API is configured
  if (!SP_CONFIG.VOTE_API || SP_CONFIG.VOTE_API === "***") {
    spLog("submitVote() placeholder. VOTE_API not configured.");
    // Mock response for local testing
    return { ok: true, effectiveValue: value, vote_count: 1, rank: 5, raw: null };
  }

  try {
    // 2. Retrieve the Integer Member ID from storage
    // (This was saved during bootstrap in main.js)
    const memberId = await getState("memberId", 0);

    if (!memberId) {
      console.warn("[ShadowPulse] Cannot vote: Member ID not found. (Bootstrap failed?)");
      return { ok: false, effectiveValue: value, raw: null };
    }

    // 3. Construct Payload for cast_vote.php
    const payload = {
      member_id: Number(memberId), // Send Integer
      vote_category: voteContext && voteContext.voteCategory ? voteContext.voteCategory : "topic",
      target_id: voteContext && typeof voteContext.targetId === "number" ? voteContext.targetId : 0,
      desired_value: Number(value)
    };

    // 4. Send Request
    const res = await fetch(SP_CONFIG.VOTE_API, {
      method: "POST",
      headers: {
        "Content-Type": "application/json"
      },
      body: JSON.stringify(payload)
    });

    if (!res.ok) {
      spLog("submitVote() failed with status", res.status);
      return { ok: false, effectiveValue: value, raw: null };
    }

    const json = await res.json();

    // 5. Parse Response (New Structure: { ok: true, data: { effective_value: X } })
    let effectiveValue = value;
    
    if (json.ok && json.data && typeof json.data.effective_value === "number") {
      effectiveValue = json.data.effective_value;
    } else {
      // Fallback if response structure is unexpected
      effectiveValue = value;
    }

    // Optimistic Count/Rank (Server doesn't send total count in cast_vote yet, so we mock 1)
    return {
      ok: json.ok === true,
      effectiveValue: effectiveValue,
      vote_count: 1, // Placeholder until get_vote refreshes the total
      rank: 0,       // Placeholder
      raw: json
    };

  } catch (err) {
    spLog("submitVote() error", err && err.message ? err.message : err);
    return { ok: false, effectiveValue: value, raw: null };
  }
}

/**
 * === Member identity & stats APIs ===
 */

export async function bootstrapMember(memberUuid) {
  if (!SP_CONFIG.MEMBER_BOOTSTRAP_API || SP_CONFIG.MEMBER_BOOTSTRAP_API === "***") {
    spLog("bootstrapMember() placeholder. MEMBER_BOOTSTRAP_API =", SP_CONFIG.MEMBER_BOOTSTRAP_API);
    return {
      member_id: 0,
      member_uuid: memberUuid,
      restore_ack: 0
    };
  }

  const res = await spRetryFetch(
    SP_CONFIG.MEMBER_BOOTSTRAP_API,
    {
      method: "POST",
      headers: {
        "Content-Type": "application/json"
      },
      body: JSON.stringify({ member_uuid: memberUuid })
    },
    "bootstrapMember"
  );

  if (!res.ok) {
    throw new Error("bootstrapMember failed with status " + res.status);
  }
  return await res.json();
}

export async function restoreMember(restoreCode) {
  if (!SP_CONFIG.MEMBER_RESTORE_API || SP_CONFIG.MEMBER_RESTORE_API === "***") {
    spLog("restoreMember() placeholder. MEMBER_RESTORE_API =", SP_CONFIG.MEMBER_RESTORE_API);
    return null;
  }

  const res = await fetch(SP_CONFIG.MEMBER_RESTORE_API, {
    method: "POST",
    headers: {
      "Content-Type": "application/json"
    },
    body: JSON.stringify({ restore_code: restoreCode })
  });

  if (!res.ok) {
    throw new Error("restoreMember failed with status " + res.status);
  }
  return await res.json();
}

export async function updateRestoreAck(memberUuid, restoreAck) {
  if (!SP_CONFIG.MEMBER_UPDATE_ACK_API || SP_CONFIG.MEMBER_UPDATE_ACK_API === "***") {
    spLog("updateRestoreAck() placeholder. MEMBER_UPDATE_ACK_API =", SP_CONFIG.MEMBER_UPDATE_ACK_API);
    return { ok: true };
  }

  const res = await fetch(SP_CONFIG.MEMBER_UPDATE_ACK_API, {
    method: "POST",
    headers: {
      "Content-Type": "application/json"
    },
    body: JSON.stringify({ member_uuid: memberUuid, restore_ack: !!restoreAck })
  });

  if (!res.ok) {
    throw new Error("updateRestoreAck failed with status " + res.status);
  }
  return await res.json();
}

export async function fetchMemberStats(memberUuid) {
  if (!SP_CONFIG.MEMBER_STATS_API || SP_CONFIG.MEMBER_STATS_API === "***") {
    spLog("fetchMemberStats() placeholder. MEMBER_STATS_API =", SP_CONFIG.MEMBER_STATS_API);
    return null;
  }

  const res = await fetch(SP_CONFIG.MEMBER_STATS_API, {
    method: "POST",
    headers: {
      "Content-Type": "application/json"
    },
    body: JSON.stringify({ member_uuid: memberUuid })
  });

  if (!res.ok) {
    throw new Error("fetchMemberStats failed with status " + res.status);
  }
  return await res.json();
}

export async function trackSearch(memberUuid) {
  if (!SP_CONFIG.MEMBER_STATS_UPDATE_API || SP_CONFIG.MEMBER_STATS_UPDATE_API === "***") {
    spLog("trackSearch() placeholder. MEMBER_STATS_UPDATE_API =", SP_CONFIG.MEMBER_STATS_UPDATE_API);
    return;
  }

  try {
    const res = await fetch(SP_CONFIG.MEMBER_STATS_UPDATE_API, {
      method: "POST",
      headers: {
        "Content-Type": "application/json"
      },
      body: JSON.stringify({
        member_uuid: memberUuid,
        searches_made: 1
      })
    });

    if (!res.ok) {
      spLog("trackSearch failed with status", res.status);
    }
  } catch (err) {
    spLog("trackSearch error", err && err.message ? err.message : err);
  }
}

export async function logSearchQuery(memberUuid, term, engineId = 1) {
  if (!SP_CONFIG.SEARCH_LOG_API || SP_CONFIG.SEARCH_LOG_API === "***") {
    return;
  }

  try {
    // Fire and forget - we don't await the result
    fetch(SP_CONFIG.SEARCH_LOG_API, {
      method: "POST",
      headers: {
        "Content-Type": "application/json"
      },
      body: JSON.stringify({
        member_uuid: memberUuid,
        search_term: term,
        engine_id: engineId
      })
    });
  } catch (err) {
    // Ignore logging errors
  }
}

export async function trackPageView(memberUuid) {
  if (!SP_CONFIG.MEMBER_STATS_UPDATE_API || SP_CONFIG.MEMBER_STATS_UPDATE_API === "***") {
    spLog("trackPageView() placeholder. MEMBER_STATS_UPDATE_API =", SP_CONFIG.MEMBER_STATS_UPDATE_API);
    return;
  }

  try {
    const res = await spRetryFetch(
      SP_CONFIG.MEMBER_STATS_UPDATE_API,
      {
        method: "POST",
        headers: {
          "Content-Type": "application/json"
        },
        body: JSON.stringify({
          member_uuid: memberUuid,
          page_views: 1
        })
      },
      "trackPageView"
    );

    if (!res.ok) {
      spLog("trackPageView failed with status " + res.status);
    }
  } catch (err) {
    spLog("trackPageView error", err && err.message ? err.message : err);
  }
}

export async function fetchEffectiveVote(voteContext) {
  if (!SP_CONFIG.GET_VOTE_API) return null;
  try {
    const memberUuid = await getOrCreateMemberUuid();
    const url = `${SP_CONFIG.GET_VOTE_API}?member_uuid=${encodeURIComponent(memberUuid)}&vote_category=${encodeURIComponent(voteContext.voteCategory)}&target_id=${voteContext.targetId}`;
    const res = await fetch(url);
    if (!res.ok) return null;
    const data = await res.json();
    return (data && typeof data.effective_value === "number") ? data.effective_value : null;
  } catch (e) { return null; }
}

// ... existing imports ...

// Add this function:
export async function savePreferences(prefs) {
  if (!SP_CONFIG.UPDATE_PREFS_API || SP_CONFIG.UPDATE_PREFS_API === "***") return;

  try {
    const memberUuid = await getOrCreateMemberUuid();
    
    // Map JS keys to PHP keys
    const payload = { member_uuid: memberUuid };
    if (prefs.theme) payload.pref_theme = prefs.theme;
    if (prefs.search) payload.pref_search = prefs.search;
    if (prefs.btcSource) payload.pref_btc_source = prefs.btcSource;

    fetch(SP_CONFIG.UPDATE_PREFS_API, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(payload)
    });
  } catch (err) {
    // ignore
  }
}