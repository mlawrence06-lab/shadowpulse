"use strict";

/**
 * ShadowPulse - core/config.js
 * Global configuration.
 */

export const SP_CONFIG = {
  VERSION: chrome.runtime.getManifest().version,
  DEFAULT_THEME: "light",
  DEFAULT_SEARCH_ENGINE_NAME: "Bitlist Search",
  DEFAULT_SEARCH_ENGINE_URL: "https://vod.fan/shadowpulse/search.php",
  DEV_MODE: true,
  REFRESH_INTERVAL_MINUTES: 5, // Default to 5 minutes
  SITE_PATTERN: /(^|\.)bitcointalk\.org$/i,

  // APIs
  ADS_API: "***",
  ADS_BANNER_IMAGE: "https://vod.fan/adserver/www/delivery/avw.php?zoneid=1",
  ADS_BANNER_LINK: "https://vod.fan/shadowpulse/advertise.php",
  
  GET_STATS_API: "https://vod.fan/shadowpulse/get_stats.php",   
  GET_VOTE_API: "https://vod.fan/shadowpulse/get_vote.php",
  VOTE_API: "https://vod.fan/shadowpulse/vote.php",
  MEMBER_BOOTSTRAP_API: "https://vod.fan/shadowpulse/bootstrap_member.php",
  MEMBER_RESTORE_API: "https://vod.fan/shadowpulse/restore_member.php",
  MEMBER_UPDATE_ACK_API: "https://vod.fan/shadowpulse/update_restore_ack.php",
  MEMBER_STATS_API: "https://vod.fan/shadowpulse/member_stats.php",
  MEMBER_STATS_UPDATE_API: "https://vod.fan/shadowpulse/update_member_stats.php",
  UPDATE_PREFS_API: "https://vod.fan/shadowpulse/update_prefs.php",
  SEARCH_LOG_API: "https://vod.fan/shadowpulse/log_search.php",
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