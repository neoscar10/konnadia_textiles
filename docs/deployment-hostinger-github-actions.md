# Hostinger Auto Deployment with GitHub Actions via SSH

This project is configured to deploy automatically to Hostinger whenever code is pushed to the `master` branch.

The deployment does not use Hostinger's built-in Git deployment feature. Instead, GitHub Actions connects to the server using SSH and runs deployment commands inside the existing Laravel project folder.

## Deployment Flow

```text
Push to GitHub master branch
        ↓
GitHub Actions starts
        ↓
GitHub Actions connects to Hostinger using SSH
        ↓
Server enters Laravel maintenance mode
        ↓
Latest code is pulled from origin/master
        ↓
Composer production dependencies are installed
        ↓
Database migrations are executed
        ↓
Laravel caches are refreshed
        ↓
Storage symlink is created if needed
        ↓
Application is brought back online
```

## Required GitHub Secrets

Go to:

```text
GitHub Repository → Settings → Secrets and variables → Actions
```

Add these repository secrets:

| Secret Name         | Meaning                                                 |
| ------------------- | ------------------------------------------------------- |
| `HOSTINGER_HOST`    | Hostinger server IP address or SSH hostname             |
| `HOSTINGER_USER`    | Hostinger SSH username                                  |
| `HOSTINGER_PORT`    | SSH port, often `65002` on Hostinger shared hosting     |
| `HOSTINGER_SSH_KEY` | Private SSH key used by GitHub Actions                  |
| `HOSTINGER_PATH`    | Absolute path to the Laravel project root on the server |

Example `HOSTINGER_PATH`:

```text
/home/u123456789/domains/example.com/app
```

## Important Server Requirements

The project must already exist on the Hostinger server and must be a valid Git repository.

The deployment path should contain:

```text
artisan
composer.json
.git/
```

The server must be able to run:

```bash
git
php
composer
```

The server must also be able to access the GitHub repository.

For a private repository, the Hostinger server should have a GitHub deploy key added to the repository.

## Manual Test on Server

Before relying on automatic deployment, SSH into the server and test:

```bash
cd /home/u123456789/domains/example.com/app
git fetch origin master
git reset --hard origin/master
composer install --no-dev --optimize-autoloader --no-interaction
php artisan migrate --force
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan storage:link || true
```

If these commands work manually, GitHub Actions deployment should also work.

## Triggering Deployment

Deployment runs automatically when pushing to `master`:

```bash
git add .
git commit -m "Update project"
git push origin master
```

Then check:

```text
GitHub Repository → Actions
```

Open the latest workflow run to confirm whether deployment passed or failed.

## Notes

The `.env` file should not be committed to GitHub.

The `.env` file should exist directly on the Hostinger server inside the Laravel project root.

The Laravel project root should not be directly exposed publicly. For Laravel, only the `public` folder should be served by the web server.

A recommended structure is:

```text
/home/u123456789/domains/example.com/app
/home/u123456789/domains/example.com/public_html
```

Where:

```text
app
```

contains the Laravel project, and:

```text
public_html
```

serves the Laravel public entry point.
