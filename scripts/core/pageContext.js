"use strict";

/**
 * ShadowPulse - core/pageContext.js
 * Detects page type, voting context, and resolves titles for Boards/Special pages.
 * FIXED: Restored 'canVote' property so buttons function correctly.
 */

// --- BOARD LIST LOOKUP (Generated from board_list 251202.csv) ---
const BOARD_NAMES = {
  1: "Bitcoin Discussion",
  4: "Bitcoin Technical Support",
  5: "Marketplace",
  6: "Development & Technical Discussion",
  7: "Economics",
  8: "Trading Discussion",
  9: "Off-topic",
  10: "Pycc??? (Russian)",
  11: "Other languages/locations",
  12: "Project Development",
  13: "Français",
  14: "Mining",
  16: "Deutsch (German)",
  17: "Chinese students",
  18: "Pa??oe",
  20: "Tpe??ep?",
  21: "Ma??ep?",
  22: "Ho?????",
  23: "????ec",
  24: "Meta",
  25: "Obsolete (buying)",
  26: "Obsolete (selling)",
  27: "Español (Spanish)",
  28: "Italiano (Italian)",
  29: "Português (Portuguese)",
  30: "?? (Chinese)",
  31: "Mercado y Economía",
  32: "Hardware y Minería",
  33: "Esquina Libre",
  34: "Politics & Society",
  37: "Wallet software",
  39: "Beginners & Help",
  40: "Mining support",
  41: "Pools",
  42: "Mining software (miners)",
  44: "CPU/GPU Bitcoin mining hardware",
  45: "Skandinavisk",
  47: "Discussions générales et utilisation du Bitcoin",
  48: "Mining et Hardware",
  49: "Place de marché",
  50: "Hors-sujet",
  51: "Goods",
  52: "Services",
  53: "Currency exchange",
  54: "Wiki - documentation et traduction",
  55: "Xa???",
  56: "Gambling",
  57: "Speculation",
  59: "Archival",
  60: "Mining (Deutsch)",
  61: "Trading und Spekulation",
  62: "Anfänger und Hilfe",
  63: "Projektentwicklung",
  64: "Off-Topic (Deutsch)",
  65: "Lending",
  66: "?o?ep?",
  67: "Altcoin Discussion",
  69: "Economia & Mercado",
  70: "Mineração em Geral",
  72: "A???ep?a?????e ?p???o?a????",
  73: "Auctions",
  74: "Legal",
  76: "Hardware",
  77: "Press",
  78: "Securities",
  79: "Nederlands (Dutch)",
  80: "Markt",
  81: "Mining speculation",
  82: "??? (Korean)",
  83: "Scam Accusations",
  84: "Service Announcements",
  85: "Service Discussion",
  86: "Meetups",
  87: "Important Announcements",
  89: "India",
  90: "??e?",
  91: "?o?????a",
  92: "?op???a",
  94: "Gokken/lotterijen",
  95: "????? (Hebrew)",
  98: "Electrum",
  99: "MultiBit",
  101: "Mercadillo",
  108: "Român? (Romanian)",
  109: "Anunturi importante",
  110: "Offtopic",
  111: "Market",
  112: "Tutoriale",
  113: "Bine ai venit!",
  114: "Presa",
  115: "Mining (Italiano)",
  116: "Mining (Nederlands)",
  117: "????",
  118: "???",
  119: "??",
  120: "E??????? (Greek)",
  121: "Mining (India)",
  122: "Marketplace (India)",
  123: "Regional Languages (India)",
  124: "Press & News from India",
  125: "Alt Coins (India)",
  126: "Buyer/ Seller Reputations (India)",
  127: "Off-Topic (India)",
  129: "Reputation",
  130: "Primeros pasos y ayuda",
  131: "Primeiros Passos (Iniciantes)",
  132: "Alt-Currencies (Italiano)",
  133: "Türkçe (Turkish)",
  134: "Brasil",
  135: "Portugal",
  136: "A?o??",
  139: "Treffen",
  140: "Presse",
  142: "Polski",
  143: "Beurzen",
  144: "Raduni/Meeting (Italiano)",
  145: "Off-Topic (Italiano)",
  146: "??",
  147: "Alt Coins (Nederlands)",
  148: "Off-topic (Nederlands)",
  149: "Altcoins (Français)",
  150: "Meetings (Nederlands)",
  151: "Altcoins (criptomonedas alternativas)",
  152: "Altcoins (Deutsch)",
  153: "Guide (Italiano)",
  155: "Pazar Alan?",
  156: "Madencilik",
  157: "Alternatif Kripto-Paralar",
  158: "Konu D???",
  159: "Announcements (Altcoins)",
  160: "Mining (Altcoins)",
  161: "Marketplace (Altcoins)",
  162: "Accuse scam/truffe",
  163: "Tablica og?osze?",
  164: "Alternatywne kryptowaluty",
  165: "Crittografia e decentralizzazione",
  166: "Minerit",
  167: "New forum software",
  168: "Bitcoin Wiki",
  169: "Progetti",
  170: "Mercato",
  174: "Yeni Ba?layanlar & Yard?m",
  175: "Trading - analisi e speculazione",
  179: "Altcoins (E???????)",
  180: "Bitcoin Haberleri",
  181: "Criptomoedas Alternativas",
  182: "???? Alt Coins (???)",
  183: "Actualité et News",
  184: "Vos sites et projets",
  185: "Pa?o?a",
  186: "Développement et technique",
  187: "Économie et spéculation",
  188: "Le Bitcoin et la loi",
  189: "Ekonomi",
  190: "Servisler",
  191: "Bahasa Indonesia (Indonesian)",
  192: "Altcoins (Bahasa Indonesia)",
  193: "Jual Beli / Marketplace",
  194: "Mining (Bahasa Indonesia)",
  195: "Mining Discussion (E???????)",
  196: "????",
  197: "Service Announcements (Altcoins)",
  198: "Service Discussion (Altcoins)",
  199: "Pools (Altcoins)",
  201: "Hrvatski (Croatian)",
  205: "Discussioni avanzate e sviluppo",
  206: "Desenvolvimento & Discussões Técnicas",
  208: "Débutants",
  219: "Pilipinas",
  220: "Trgovina",
  221: "Altcoins (Hrvatski)",
  224: "Speculation (Altcoins)",
  228: "Gambling discussion",
  229: "Proje Geli?tirme",
  230: "Bulu?malar",
  237: "O??e?????",
  238: "Bounties (Altcoins)",
  240: "Tokens (Altcoins)",
  241: "??????? (Arabic)",
  242: "??????? ??????? (Altcoins)",
  243: "Altcoins (Pilipinas)",
  250: "Serious discussion",
  251: "Ivory Tower",
  252: "??? (Japanese)",
  253: "????????? ? ????? ?????????",
  255: "??????",
  259: "Altcoins (Monede Alternative)",
  262: "O?cy??e??e Bitcoin",
  266: "???????",
  267: "???????? ??????",
  268: "Pamilihan",
  269: "Marktplatz",
  271: "????? ???????",
  272: "Off-topic (Hrvatski)",
  274: "Others (Pilipinas)",
  275: "Nigeria (Naija)",
  276: "Trading dan Spekulasi",
  277: "Ekonomi - Politik - dan Budaya",
  278: "Topik Lainnya",
  279: "Politics and society (Naija)",
  280: "Off-topic (Naija)"
};

export function getVotingContext() {
  return parseBitcointalkPage(window.location.href);
}

export function parseBitcointalkPage(urlString) {
  // Fix: Bitcointalk uses semicolons; URLSearchParams expects ampersands.
  // We strictly replace ; with & in the search portion only.
  const rawUrl = new URL(urlString, window.location.origin);
  const searchFixed = rawUrl.search.replace(/;/g, "&");
  const params = new URLSearchParams(searchFixed);

  const path = rawUrl.pathname || "/";
  const action = (params.get("action") || "").toLowerCase();
  const sa = (params.get("sa") || "").toLowerCase();

  const ctx = {
    // kind: "home", "board", "topic", "post", "profile", "special", "unknown"
    kind: "unknown",
    voteCategory: null,

    // Ids
    boardId: null,
    topicId: null,
    postId: null,
    userId: null,
    targetId: null,
    storageKey: null,

    // Display Info
    pageTitle: "",
    pageSubtitle: "",

    // Flags
    canVote: false // Default to false
  };

  // Helper: parse leading int
  function parseLeadingInt(raw) {
    if (!raw) return null;
    const m = String(raw).match(/^(\d+)/);
    if (!m) return null;
    const n = Number(m[1]);
    return Number.isNaN(n) ? null : n;
  }

  // --- 1. HOME ---
  if (
    (path === "/" || path === "" || path === "/index.php") &&
    !params.has("board") &&
    !params.has("topic") &&
    !action
  ) {
    ctx.kind = "special";
    ctx.pageTitle = "Bitcointalk Home";
    ctx.pageSubtitle = "";
    return ctx;
  }

  // --- 2. SPECIAL PAGES (By Path) ---
  // MODIFIED: Consolidate title and subtitle
  const specialPathMap = {
    "/more.php": "More Options",
    "/donate.html": "Donation Information",
    "/privacy.php": "Privacy Policy",
    "/sbounties.php": "Security Bounties",
    "/contact.php": "Contact Information"
  };

  if (specialPathMap[path]) {
    ctx.kind = "special";
    ctx.pageTitle = specialPathMap[path];
    ctx.pageSubtitle = "";
    return ctx;
  }

  // --- 3. ACTIONS (index.php?action=...) ---
  if (action) {
    if (action === "search") {
      ctx.kind = "special";
      ctx.pageTitle = "Search";
      ctx.pageSubtitle = "";
      return ctx;
    }
    if (action === "pm") {
      ctx.kind = "special";
      ctx.pageTitle = "Private Messages";
      ctx.pageSubtitle = "";
      return ctx;
    }
    if (action === "mlist") {
      ctx.kind = "special";
      ctx.pageTitle = "Member List (Disabled)";
      ctx.pageSubtitle = "";
      return ctx;
    }
    if (action === "merit") {
      ctx.kind = "special";
      ctx.pageTitle = "Merit Summary";
      ctx.pageSubtitle = "";
      return ctx;
    }

    if (action === "trust") {
      const uRaw = params.get("u");
      ctx.kind = "special";
      ctx.pageTitle = uRaw ? "Trust Summary" : "Modify Trust List";
      ctx.pageSubtitle = "";
      return ctx;
    }

    if (action === "profile") {
      // Profile variants
      const uRaw = params.get("u");
      const userId = parseLeadingInt(uRaw);

      if (!userId) {
        // Own profile
        ctx.kind = "special";
        ctx.pageTitle = "Profile (Self)";
        ctx.pageSubtitle = "";
      } else {
        ctx.kind = "profile";
        ctx.userId = userId;
        ctx.voteCategory = "profile";
        ctx.targetId = userId;

        // Handle specific profile sub-actions
        if (sa === "summary" || !sa) {
          // Standard profile view
          ctx.pageTitle = "Profile"; // Stub will be replaced by API later
          ctx.pageSubtitle = "";     // Rank Stub
        } else if (sa === "showposts") {
          ctx.kind = "special";
          ctx.pageTitle = "Show Posts";
          ctx.pageSubtitle = "";
        } else if (sa === "statpanel") {
          ctx.kind = "special";
          ctx.pageTitle = "User Stats (Disabled)";
          ctx.pageSubtitle = "";
        } else if (sa === "account") {
          ctx.kind = "special";
          ctx.pageTitle = "Account Settings";
          ctx.pageSubtitle = "";
        } else {
          // Fallback for other profile actions
          ctx.pageTitle = "Profile";
          ctx.pageSubtitle = "";
        }
      }
      return ctx;
    }

    // Generic fallback for other actions
    ctx.kind = "special";
    ctx.pageTitle = "Unknown Page";
    ctx.pageSubtitle = "";
    return ctx;
  }

  // --- 4. BOARD / TOPIC / POST (index.php?...) ---

  // Board: board=9 or board=9.0
  if (params.has("board")) {
    const boardRaw = params.get("board");
    const boardId = parseLeadingInt(boardRaw);

    ctx.kind = "board";
    ctx.boardId = boardId;
    ctx.voteCategory = "board";
    ctx.targetId = boardId;

    // Resolve Board Name
    ctx.pageTitle = BOARD_NAMES[boardId] || "Board " + boardId;
    ctx.pageSubtitle = ""; // Rank Stub

    return ctx;
  }

  // Topic/Post: topic=5297994[.msg...]
  if (params.has("topic")) {
    const topicRaw = params.get("topic") || "";
    const postMatch = topicRaw.match(/^(\d+)\.msg(\d+)/);

    if (postMatch) {
      // Post View
      const topicId = parseLeadingInt(postMatch[1]);
      const postId = parseLeadingInt(postMatch[2]);
      ctx.kind = "post";
      ctx.topicId = topicId;
      ctx.postId = postId;
      ctx.voteCategory = "post";
      ctx.targetId = postId;
      ctx.pageTitle = "Post"; // Used if we needed a title, but UI shows buttons
    } else {
      // Topic View
      const topicId = parseLeadingInt(topicRaw);
      ctx.kind = "topic";
      ctx.topicId = topicId;
      ctx.voteCategory = "topic";
      ctx.targetId = topicId;
      ctx.pageTitle = "Topic";
    }

    // Stable storage key for local voting
    if (ctx.targetId != null) {
      ctx.storageKey = `vote:${ctx.voteCategory}:${ctx.targetId}`;
    }

    // ENABLE VOTING!
    ctx.canVote = true;

    return ctx;
  }

  // Fallback
  ctx.kind = "special";
  ctx.pageTitle = "Unknown Page";
  ctx.pageSubtitle = "";

  return ctx;
}