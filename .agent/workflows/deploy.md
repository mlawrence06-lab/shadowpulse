---
description: Deploy website files to the server using SCP
---
# Deployment Workflow

This workflow uploads local files to the remote server using `scp` (Secure Copy).

**Credentials:**
- **Host:** `ssh.cluster051.hosting.ovh.net`
- **User:** `bxzziug`
- **Pass:** `s57MMJM0GHUabZLij7Z6V5iwwVe5a2`
- **Remote Base:** `~/vod.fan/shadowpulse/`

## 1. Deploy Single File
Replace `[LOCAL_PATH]` with the relative path to your file, and `[REMOTE_PATH]` with the full remote path.

```powershell
scp [LOCAL_PATH] bxzziug@ssh.cluster051.hosting.ovh.net:~/vod.fan/shadowpulse/[REMOTE_PATH]
```

### Examples

**Deploy Reports Index:**
```powershell
scp Web/website/reports/index.php bxzziug@ssh.cluster051.hosting.ovh.net:~/vod.fan/shadowpulse/website/reports/index.php
```

**Deploy API Endpoint:**
```powershell
scp Web/api/v1/vote_pyramid.php bxzziug@ssh.cluster051.hosting.ovh.net:~/vod.fan/shadowpulse/api/v1/vote_pyramid.php
```

## 2. Deploy Multiple Files / Directory
To deploy a directory recursively:

```powershell
scp -r Web/api/v1 bxzziug@ssh.cluster051.hosting.ovh.net:~/vod.fan/shadowpulse/api/
```

## 3. Extension Versioning
**Current Version:** 0.31.16
Ensure `manifest.json` matches the declared version in `scripts/core/config.js` (loaded at runtime).
