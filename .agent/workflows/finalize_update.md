---
description: Finalize a task by incrementing the version number and notifying the user.
---

# Finalize Update Workflow

Use this workflow **EVERY TIME** you complete a code change that requires the user to reload the extension.

1.  **Read Manifest**:
    -   `view_file` the `manifest.json` file.

2.  **Increment Version**:
    -   Identify the current `version` (e.g., `0.33.3`).
    -   Increment the **PATCH** number (the last number) by 1 (e.g., `0.33.3` -> `0.33.4`).
    -   **DO NOT** change the minor version unless explicitly instructed.
    -   Use `replace_file_content` to update `manifest.json`.

3.  **Notify User**:
    -   Call `notify_user`.
    -   Explicitly state: "Version updated to X.X.X. Please reload."
