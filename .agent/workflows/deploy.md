---
description: Deploy website files to the server using Curl (FTP)
---
# Deployment Workflow

This workflow uploads local files to the remote server using `curl.exe` with FTP. This is the **proven method** for this environment.

**Credentials:**
- **User:** `bxzziug`
- **Pass:** `W3YfFZhknNwR9jeD6VDN`
- **Host:** `ftp.cluster051.hosting.ovh.net`
- **Remote Base:** `/vod.fan/shadowpulse/`

## 1. Deploy Single File
Use this pattern to upload a single file. Replace `[LOCAL_REL_PATH]` and `[REMOTE_REL_PATH]` accordingly.

```powershell
curl.exe -u "bxzziug:W3YfFZhknNwR9jeD6VDN" --ftp-create-dirs -T [LOCAL_PATH] ftp://ftp.cluster051.hosting.ovh.net/vod.fan/shadowpulse/[REMOTE_PATH]
```

### Examples

**Deploy Reports Index:**
```powershell
curl.exe -u "bxzziug:W3YfFZhknNwR9jeD6VDN" -T Web/website/reports/index.php ftp://ftp.cluster051.hosting.ovh.net/vod.fan/shadowpulse/website/reports/index.php
```

**Deploy API Endpoint:**
```powershell
curl.exe -u "bxzziug:W3YfFZhknNwR9jeD6VDN" -T Web/api/v1/vote_pyramid.php ftp://ftp.cluster051.hosting.ovh.net/vod.fan/shadowpulse/api/v1/vote_pyramid.php
```

## 2. Deploy Multiple Files (Manual Batches)
For multiple files, execute sequential curl commands.

```powershell
curl.exe -u "bxzziug:W3YfFZhknNwR9jeD6VDN" -T Web/website/reports/pyramid.php ftp://ftp.cluster051.hosting.ovh.net/vod.fan/shadowpulse/website/reports/pyramid.php
curl.exe -u "bxzziug:W3YfFZhknNwR9jeD6VDN" -T Web/website/shadowpulse.css ftp://ftp.cluster051.hosting.ovh.net/vod.fan/shadowpulse/website/shadowpulse.css
```

> **Note:** Always use `curl.exe` (explicit executable) to avoid PowerShell alias conflicts.

## 3. Extension Versioning Strategy
**Rule:** Whenever the **Extension Code** (manifest, JS, CSS) is modified, you **MUST** increment the **patch version** in `manifest.json`.

- Example: `0.30.0` → `0.30.1`
- Exception: Only skip if the user explicitly says "do not bump version".
- Sync: Ensure the `settings.js` or UI references to version match if they are hardcoded (though they should rely on `runtime.getManifest()`).
