"use strict";

/**
 * ShadowPulse - core/config.js
 * Global configuration.
 */

export const SP_CONFIG = {
  VERSION: chrome.runtime.getManifest().version,
  DEFAULT_THEME: "light",
  DEFAULT_SEARCH_ENGINE_NAME: "Bitlist Search",
  DEFAULT_SEARCH_ENGINE_URL: "https://vod.fan/shadowpulse/website/search-bitlist.php",
  DEV_MODE: true,
  REFRESH_INTERVAL_MINUTES: 0.5, // Default to 30 seconds (0.5 mins)
  SITE_PATTERN: /(^|\.)bitcointalk\.org$/i,

  // APIs
  GET_BANNER_AD_API: "https://vod.fan/shadowpulse/api/v1/get_banner_ad.php",
  ADS_BANNER_LINK: "https://vod.fan/shadowpulse/advertise.php",

  GET_STATS_API: "https://vod.fan/shadowpulse/api/v1/get_stats.php",
  GET_VOTE_API: "https://vod.fan/shadowpulse/api/v1/get_vote.php",
  GET_PAGE_CONTEXT_API: "https://vod.fan/shadowpulse/api/v1/get_page_context.php",
  VOTE_API: "https://vod.fan/shadowpulse/api/v1/vote.php",
  MEMBER_BOOTSTRAP_API: "https://vod.fan/shadowpulse/api/v1/bootstrap_member.php",
  MEMBER_RESTORE_API: "https://vod.fan/shadowpulse/api/v1/restore_member.php",
  MEMBER_UPDATE_ACK_API: "https://vod.fan/shadowpulse/api/v1/update_restore_ack.php",
  MEMBER_STATS_API: "https://vod.fan/shadowpulse/api/v1/member_stats.php",
  MEMBER_STATS_UPDATE_API: "https://vod.fan/shadowpulse/api/v1/update_member_stats.php",
  UPDATE_PREFS_API: "https://vod.fan/shadowpulse/api/v1/update_prefs.php",
  SEARCH_LOG_API: "https://vod.fan/shadowpulse/api/v1/log_search.php",
  ROOT_ID: "shadowpulse-root",
  TOOLBAR_HEIGHT: 72,
  TOOLBAR_MAX_WIDTH: 960,
  TOOLBAR_BORDER_RADIUS: 12,
  TOOLBAR_BLUR_PX: 18,
  Z_INDEX_ROOT: 999999,

  ANIM_DURATION_FAST: 150,
  ANIM_DURATION_MEDIUM: 220,

  ACCENT_COLOR: "#7cdcff"
};