---
description: Connect Hostinger to GitHub for automatic deployment
---

# Connect Hostinger to GitHub via SSH

This workflow guides you through linking your Hostinger website to your GitHub repository using the SSH key you already have.

## Prerequisites

- Access to Hostinger hPanel for your domain (`gradientsound.shop`).
- Access to the GitHub repository you want to deploy (e.g., `gradient_solutions_site`).
- The SSH Key from Hostinger (Public Key).

## Step 1: Add Hostinger's SSH Key to GitHub

1.  **Copy the SSH Key** from Hostinger (if you haven't already).
2.  Go to your **GitHub Repository** page.
3.  Click **Settings** (top right tab).
4.  In the left sidebar, click **Deploy keys**.
5.  Click **Add deploy key** (top right button).
6.  **Title:** `Hostinger Production` (or similar).
7.  **Key:** Paste the SSH Public Key you copied from Hostinger.
    - *It should start with `ssh-rsa` or `ssh-ed25519`.*
8.  **Allow write access:** Leave UNCHECKED (Hostinger only needs to *read*/pull code).
9.  Click **Add key**.

## Step 2: Configure Git in Hostinger

1.  Log in to **Hostinger hPanel**.
2.  Navigate to **Websites** → **Manage** (for `gradientsound.shop`).
3.  Scroll down to the **Advanced** section and click **Git**.
4.  **Repository Settings**:
    - **Repository URL**: Enter the SSH URL of your GitHub repo.
      - Format: `git@github.com:username/repository-name.git`
      - Example: `git@github.com:lmayer5/gradient_solutions_site.git` (Verify exact repo name).
    - **Branch**: `main` (or `master`, whichever is your default).
    - **Directory**: `public_html` (This is crucial!).
      - *Note: If `public_html` isn't empty, Hostinger might complain. You may need to delete the default files or use a subdirectory and move them later. standard practice is to deploy to a subfolder like `deploy` and symlink, but Hostinger often supports direct simple deployment.*
      - **Recommendation:** Leave it blank to deploy to a subfolder like `repositories/repo-name` first to test, OR ensure `public_html` is empty before adding.
      - *Better approach for Hostinger:* Use the default `public_html` if it lets you, or see if it wipes it.
5.  Click **Create**.

## Step 3: Deploy Changes

1.  Once the repository is added, you will see it in the "Manage Repositories" list.
2.  Click **Deploy** (or "Pull" / arrows icon) to fetch the latest code from GitHub to your server.
3.  **Verify**: Go to **File Manager** and check if your files are in `public_html`.

## Step 4: Setup Auto-Deployment (Webhook)

1.  In the Hostinger Git section, look for the **Webhook URL** (usually under "Auto Deployment").
2.  Copy this URL.
3.  Go back to **GitHub Repo Settings** → **Webhooks**.
4.  Click **Add webhook**.
5.  **Payload URL**: Paste the Hostinger Webhook URL.
6.  **Content type**: `application/json`.
7.  **Which events?**: Just the `push` event.
8.  Click **Add webhook**.

Now, every time you push to GitHub, Hostinger will automatically pull the changes!

## Critical Note for Your Project structure (`public_html` subfolder)

Your git repo has a `public_html` folder *inside* it, but Hostinger expects `public_html` to *be* the root.
- **Problem**: Deploying the repo to `public_html` means your site index will be at `gradientsound.shop/public_html/index.html`.
- **Solution**:
    1.  **Option A (Best):** Restructure your repo so `index.html` is at the root.
    2.  **Option B (Htaccess):** Use `.htaccess` to point the domain root to the `public_html` subdirectory.
    3.  **Option C (Hostinger Config):** Change the "Document Root" in Hostinger (Website -> Dashboard -> Change Document Root) to point to `public_html/public_html` (if allowed).
