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


// Retries removed to prevent server hammering.
// All fetches now use standard fetch() and fail fast.

export async function fetchPageContext(category, targetId, meta = {}) {
  if (!SP_CONFIG.GET_PAGE_CONTEXT_API) return null;

  try {
    const memberUuid = await getOrCreateMemberUuid();

    // 2. Fetch
    const url = new URL(SP_CONFIG.GET_PAGE_CONTEXT_API);
    url.searchParams.append("member_uuid", memberUuid);
    url.searchParams.append("category", category);
    url.searchParams.append("target_id", targetId);
    url.searchParams.append("t", Date.now());

    // Optional Metadata (Title, Author)
    if (meta.title) url.searchParams.append("title", meta.title);
    if (meta.author) url.searchParams.append("author", meta.author);

    const res = await fetch(url.toString());
    if (!res.ok) throw new Error(`HTTP ${res.status}`);

    const json = await res.json();

    // 3. Save Cache
    try {
      localStorage.setItem(CACHE_KEY, JSON.stringify({
        timestamp: Date.now(),
        data: json
      }));
    } catch (e) { }

    return json;

  } catch (err) {
    spLog("fetchPageContext error", err);
    return null;
  }
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
    const CACHE_KEY = "sp_btc_stats";
    const CACHE_TTL = 5 * 60 * 1000; // 5 min

    try {
      const cached = localStorage.getItem(CACHE_KEY);
      if (cached) {
        const entry = JSON.parse(cached);
        if (Date.now() - entry.timestamp < CACHE_TTL) {
          return entry.data;
        }
      }
    } catch (e) { }

    const url = `${SP_CONFIG.GET_STATS_API}?t=${Date.now()}`;
    const res = await fetch(url);
    if (!res.ok) throw new Error(`HTTP ${res.status}`);
    const json = await res.json();

    try {
      localStorage.setItem(CACHE_KEY, JSON.stringify({
        timestamp: Date.now(),
        data: json
      }));
    } catch (e) { }

    return json;
  } catch (err) {
    spLog("fetchBitcoinStats error", err);
    return null;
  }
}

// Deprecated: Use fetchPageContext where possible.
// Kept for specific refresh actions if needed.
export async function fetchVoteSummary(voteContext) {
  // Redirect to Page Context logic if it matches the current page?
  // For now, simple fetch without retry.
  if (!SP_CONFIG.GET_VOTE_API) return null;

  try {
    const memberUuid = await getOrCreateMemberUuid();
    const cat = voteContext.voteCategory || "topic";
    const tid = voteContext.targetId || 0;

    const url = new URL(SP_CONFIG.GET_VOTE_API);
    url.searchParams.append("member_uuid", memberUuid);
    url.searchParams.append("vote_category", cat);
    url.searchParams.append("target_id", tid);
    url.searchParams.append("t", Date.now());

    const res = await fetch(url.toString());
    if (!res.ok) return null;
    return await res.json();
  } catch (e) { return null; }
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
    const memberUuid = await getOrCreateMemberUuid();

    // 3. Construct Payload for vote.php
    const payload = {
      member_uuid: memberUuid,
      vote_category: voteContext && voteContext.voteCategory ? voteContext.voteCategory : "topic",
      target_id: voteContext && voteContext.targetId ? Number(voteContext.targetId) : 0,
      desired_value: Number(value),
      context_topic_id: voteContext && voteContext.topicId ? Number(voteContext.topicId) : 0
    };

    // 4. Send Request
    // 4. Send Request
    const res = await fetch(
      SP_CONFIG.VOTE_API,
      {
        method: "POST",
        headers: {
          "Content-Type": "application/json"
        },
        body: JSON.stringify(payload)
      }
    );

    if (!res.ok) {
      spLog("submitVote() failed with status", res.status);
      return { ok: false, effectiveValue: value, raw: null };
    }

    const json = await res.json();

    // 5a. CRITICAL: Invalidate Cache for this topic so immediate re-fetch gets fresh data
    // 5a. CRITICAL: Invalidate Cache for this topic so immediate re-fetch gets fresh data
    try {
      const cat = voteContext && voteContext.voteCategory ? voteContext.voteCategory : "topic";
      const val = voteContext && voteContext.targetId ? Number(voteContext.targetId) : 0;
      const tid = isNaN(val) ? 0 : val;

      // Remove specific context cache
      const CTX_KEY = `sp_ctx_v2_${cat}_${tid}`;
      localStorage.removeItem(CTX_KEY);

      // Also remove legacy key if it exists
      const CACHE_KEY = `sp_vote_${cat}_${tid}`;
      localStorage.removeItem(CACHE_KEY);

    } catch (e) { /* ignore */ }

    // 5. Parse Response (Structure: { ok: true, effective_value: X })
    let effectiveValue = value;

    if (json.ok) {
      // DEBUG LOGGING REQUESTED BY USER
      if (json.debug_metadata) {
        console.log("[ShadowPulse] Metadata Debug:", json.debug_metadata);
      }

      if (typeof json.effective_value === "number") {
        effectiveValue = json.effective_value;
      } else if (json.data && typeof json.data.effective_value === "number") {
        // Fallback for legacy/alternative structure
        effectiveValue = json.data.effective_value;
      }
    } else {
      // Fallback if response structure is unexpected
      effectiveValue = value;
    }

    // Optimistic Count/Rank (Server doesn't send total count in cast_vote yet, so we mock 1)
    return {
      ok: json.ok === true,
      effectiveValue: effectiveValue,
      vote_count: (typeof json.vote_count === 'number') ? json.vote_count : 1,
      total_score: (typeof json.total_score === 'number') ? json.total_score : 0,
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

  const res = await fetch(
    SP_CONFIG.MEMBER_BOOTSTRAP_API,
    {
      method: "POST",
      headers: {
        "Content-Type": "application/json"
      },
      body: JSON.stringify({ member_uuid: memberUuid })
    }
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

  // CRITICAL FIX: Get the CURRENT temporary UUID so we can tell the server to delete it
  const currentUuid = await getOrCreateMemberUuid();

  const res = await fetch(SP_CONFIG.MEMBER_RESTORE_API, {
    method: "POST",
    headers: {
      "Content-Type": "application/json"
    },
    body: JSON.stringify({
      restore_code: restoreCode,
      discard_uuid: currentUuid // <-- THIS IS THE NEW FIELD
    })
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

  const memberId = await getState("memberId", 0);

  const res = await fetch(SP_CONFIG.MEMBER_STATS_API, {
    method: "POST",
    headers: {
      "Content-Type": "application/json"
    },
    body: JSON.stringify({
      member_uuid: memberUuid,
      member_id: memberId
    })
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
    const memberId = await getState("memberId", 0);
    const res = await fetch(SP_CONFIG.MEMBER_STATS_UPDATE_API, {
      method: "POST",
      headers: {
        "Content-Type": "application/json"
      },
      body: JSON.stringify({
        member_uuid: memberUuid,
        member_id: memberId,
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
    const memberId = await getState("memberId", 0);
    // Fire and forget - we don't await the result
    fetch(SP_CONFIG.SEARCH_LOG_API, {
      method: "POST",
      headers: {
        "Content-Type": "application/json"
      },
      body: JSON.stringify({
        member_uuid: memberUuid,
        member_id: memberId,
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
    const memberId = await getState("memberId", 0);
    const res = await fetch(
      SP_CONFIG.MEMBER_STATS_UPDATE_API,
      {
        method: "POST",
        headers: {
          "Content-Type": "application/json"
        },
        body: JSON.stringify({
          member_uuid: memberUuid,
          member_id: memberId,
          page_views: 1
        })
      }
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