"use strict";

/**
 * ShadowPulse - core/member.js
 * Anonymous member identity helper.
 *
 * Responsibilities:
 * - Generate and persist a random member_uuid per extension installation.
 * - Expose helper to retrieve it.
 * - Overwrite it on restore.
 */

import { getState, setState } from "./state.js";
import { spLog } from "./utils.js";

const MEMBER_UUID_KEY = "memberUuid";

/**
 * Get or create the local member UUID for this installation.
 * Uses crypto.randomUUID when available, with a secure random fallback.
 */
export async function getOrCreateMemberUuid() {
  let uuid = await getState(MEMBER_UUID_KEY, null);
  if (uuid && typeof uuid === "string" && uuid.length >= 8) {
    return uuid;
  }

  uuid = generateUuid();
  try {
    await setState(MEMBER_UUID_KEY, uuid);
  } catch (err) {
    spLog("Failed to persist memberUuid:", err);
  }
  return uuid;
}

/**
 * Overwrite the current member UUID (used during restore).
 */
export async function setMemberUuid(uuid) {
  if (!uuid || typeof uuid !== "string") {
    throw new Error("setMemberUuid: invalid uuid");
  }
  await setState(MEMBER_UUID_KEY, uuid);
  spLog("Member UUID updated via restore.");
}

/**
 * Simple UUID v4 generator, with fallback if crypto.randomUUID is unavailable.
 */
function generateUuid() {
  if (typeof crypto !== "undefined" && typeof crypto.randomUUID === "function") {
    return crypto.randomUUID();
  }
  // Fallback: 16-byte random hex string grouped like a UUID
  const bytes = new Uint8Array(16);
  if (typeof crypto !== "undefined" && typeof crypto.getRandomValues === "function") {
    crypto.getRandomValues(bytes);
  } else {
    for (let i = 0; i < bytes.length; i++) {
      bytes[i] = Math.floor(Math.random() * 256);
    }
  }
  const hex = Array.from(bytes, (b) => b.toString(16).padStart(2, "0")).join("");
  return (
    hex.slice(0, 8) + "-" +
    hex.slice(8, 12) + "-" +
    hex.slice(12, 16) + "-" +
    hex.slice(16, 20) + "-" +
    hex.slice(20)
  );
}
