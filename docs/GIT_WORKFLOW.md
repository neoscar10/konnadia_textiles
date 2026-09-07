# Git Branching & Deployment Workflow

This repository uses a dual-branch git workflow to ensure code quality, staging safety, and stability across active development and production environments.

---

## Branch Overview

| Branch | Remote Tracking | Purpose | Environment |
|---|---|---|---|
| `development` | `origin/development`<br>`spyder/development` | Active feature integration, bug fixes, and development testing. | Staging / Development |
| `master` | `origin/master`<br>`spyder/master` | Tested, verified, stable code ready for end-user production deployment. | Production |

---

## Remote Repositories

The project maintains two synchronized remote repositories:

1. **Origin**: `https://github.com/neoscar10/konnadia_textiles.git`
2. **Spyder**: `https://github.com/spider30000/Kanodia-Web.git`

All branch pushes (`development` and `master`) must be pushed to **both** remotes to maintain full remote synchronization.

---

## Workflow Rules

### 1. Active Development

- **Always work on `development`**: All new feature branches or direct commits for ongoing work should target `development`.
- **Pushing Updates**: When completing a feature or bugfix, push to both remotes:
  ```bash
  git push origin development
  git push spyder development
  ```

### 2. Testing & Staging Verification

- Deploy the `development` branch to the staging environment (`APP_ENV=local` / staging server).
- Perform end-to-end user acceptance testing (UAT), regression testing, and API verification.

### 3. Production Release Process

Once testing on `development` passes and features are ready for production:

1. Switch to local `master`:
   ```bash
   git checkout master
   ```
2. Pull latest `master` from `origin`:
   ```bash
   git pull origin master
   ```
3. Merge `development` into `master`:
   ```bash
   git merge development
   ```
4. Push updated `master` to both remotes:
   ```bash
   git push origin master
   git push spyder master
   ```
5. Switch back to `development` for ongoing work:
   ```bash
   git checkout development
   ```
