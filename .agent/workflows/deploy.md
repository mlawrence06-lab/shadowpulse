---
description: Deploy website files to the server
---
# Deployment Workflow

This workflow uploads the local `Web` directory to the remote server using `scp`.

**Prerequisite:** You should have SSH Key authentication set up for `bxzziug@vod.fan` to avoid typing the password every time.

## 1. Upload Files
// turbo
```bash
cd Web
scp -i ..\.agent\ssh\shadowpulse_key -r . bxzziug@ssh.cluster051.hosting.ovh.net:/home/bxzziug/vod.fan/shadowpulse/
```

```bash
ssh -i ..\.agent\ssh\shadowpulse_key bxzziug@ssh.cluster051.hosting.ovh.net "chmod -R 755 /home/bxzziug/vod.fan/shadowpulse/ && find /home/bxzziug/vod.fan/shadowpulse/ -type f -exec chmod 644 {} \;"
```

## 2. Confirmation
User: Deployment complete.
