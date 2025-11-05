# Heroku Deployment Guide - Tennis Scouting App

This guide will walk you through deploying your Laravel tennis scouting application to Heroku.

## Prerequisites

1. **Heroku Account**: Sign up at [heroku.com](https://signup.heroku.com/)
2. **Heroku CLI**: Install from [devcenter.heroku.com/articles/heroku-cli](https://devcenter.heroku.com/articles/heroku-cli)
3. **Git**: Your project should be in a git repository

## Step 1: Login to Heroku

```bash
heroku login
```

## Step 2: Create a New Heroku App

```bash
# Create app with a unique name (or let Heroku generate one)
heroku create your-tennis-scouting-app

# Or just:
heroku create
```

## Step 3: Add PostgreSQL Database

```bash
# Add Heroku Postgres (free tier)
heroku addons:create heroku-postgresql:essential-0

# Or for hobby tier:
# heroku addons:create heroku-postgresql:mini
```

## Step 4: Add Redis for Queue (Optional but Recommended)

```bash
# Add Heroku Redis for queue management
heroku addons:create heroku-redis:mini
```

## Step 5: Set Environment Variables

```bash
# Set APP_KEY (generate one if you don't have it)
php artisan key:generate --show
heroku config:set APP_KEY=base64:YOUR_GENERATED_KEY_HERE

# Set other Laravel configuration
heroku config:set APP_NAME="Tennis Scouting"
heroku config:set APP_ENV=production
heroku config:set APP_DEBUG=false
heroku config:set APP_URL=https://your-tennis-scouting-app.herokuapp.com

# Database connection will auto-detect PostgreSQL when DATABASE_URL is present (Heroku sets this automatically)
# No need to set DB_CONNECTION manually - it will default to pgsql on Heroku

# Set queue driver to database (or redis if you added it)
heroku config:set QUEUE_CONNECTION=database

# Set session and cache drivers
heroku config:set SESSION_DRIVER=database
heroku config:set CACHE_DRIVER=database

# Set log channel
heroku config:set LOG_CHANNEL=errorlog
heroku config:set LOG_LEVEL=error

# If you have UTR API credentials
heroku config:set UTR_API_KEY=your_utr_api_key_here

# If you have any other API keys or secrets, add them here
```

## Step 6: Configure Buildpacks

Since this app uses Vite/Tailwind for frontend assets, you need both Node.js and PHP buildpacks:

```bash
# Add Node.js buildpack first (for building frontend assets)
heroku buildpacks:add heroku/nodejs

# Add PHP buildpack second (for Laravel)
heroku buildpacks:add heroku/php

# Verify buildpacks are set correctly
heroku buildpacks
```

**Order matters!** Node.js must be first to build assets, then PHP runs the app.

**Note**: If you deployed before adding buildpacks, you'll need to trigger a rebuild:
```bash
git commit --allow-empty -m "Rebuild with correct buildpacks"
git push heroku main
```

## Step 7: Deploy to Heroku

```bash
# Make sure all changes are committed
git add .
git commit -m "Prepare for Heroku deployment"

# Push to Heroku
git push heroku main

# If your main branch is named 'master':
# git push heroku master
```

## Step 8: Run Database Migrations

The migrations will run automatically via the `release.sh` script, but you can also run them manually:

```bash
heroku run php artisan migrate --force
```

## Step 9: Scale Up Worker Dyno (For Background Jobs)

```bash
# Scale up worker dyno to process background jobs (UTR updates, etc.)
heroku ps:scale worker=1
```

**Note**: Worker dynos are not free. If you want to keep costs down, you can run without a worker dyno, but background jobs (like UTR updates) won't process automatically.

## Step 10: Open Your App

```bash
heroku open
```

## Environment Variables Reference

Here's a complete list of environment variables you should set:

```bash
# Core Laravel Settings
APP_NAME="Tennis Scouting"
APP_ENV=production
APP_KEY=base64:YOUR_KEY_HERE
APP_DEBUG=false
APP_URL=https://your-app.herokuapp.com

# Database (DATABASE_URL is set automatically by Heroku Postgres)
# DB_CONNECTION will auto-detect pgsql when DATABASE_URL is present

# Queue
QUEUE_CONNECTION=database

# Session & Cache
SESSION_DRIVER=database
CACHE_DRIVER=database

# Logging
LOG_CHANNEL=errorlog
LOG_LEVEL=error

# UTR API (if you have credentials)
UTR_API_KEY=your_key_here

# Mail (optional - configure if you need email)
# MAIL_MAILER=smtp
# MAIL_HOST=smtp.mailtrap.io
# MAIL_PORT=2525
# MAIL_USERNAME=null
# MAIL_PASSWORD=null
```

## Useful Heroku Commands

```bash
# View logs
heroku logs --tail

# Run artisan commands
heroku run php artisan migrate
heroku run php artisan cache:clear
heroku run php artisan config:clear

# Check dyno status
heroku ps

# Restart dynos
heroku restart

# Open database console
heroku pg:psql

# View config variables
heroku config

# Set a config variable
heroku config:set VARIABLE_NAME=value
```

## Troubleshooting

### 1. Application Error (500)

Check the logs:
```bash
heroku logs --tail
```

Common issues:
- Missing APP_KEY: `heroku config:set APP_KEY=$(php artisan key:generate --show)`
- Database not migrated: `heroku run php artisan migrate --force`
- Config cache issues: `heroku run php artisan config:clear`

### 2. Database Connection Issues

Make sure:
- Heroku Postgres addon is added
- DB_CONNECTION is set to `pgsql`
- DATABASE_URL is set (Heroku does this automatically)

### 3. Queue Jobs Not Processing

- Make sure worker dyno is running: `heroku ps:scale worker=1`
- Check worker logs: `heroku logs --tail --dyno=worker`

### 4. Assets Not Loading

If CSS/JS files aren't loading, make sure:
- They're in the `public/` directory
- You've run `npm run build` before deploying
- APP_URL is set correctly

## Costs

- **Web Dyno**: Free tier available (sleeps after 30 min of inactivity)
- **Postgres Database**:
  - essential-0 (free): 1 GB storage, 20 connections
  - mini ($5/mo): 10 GB storage, 120 connections
- **Redis**: mini ($3/mo) - Optional, for queue management
- **Worker Dyno**: eco ($5/mo) - Optional, for background jobs

**Total Monthly Cost (with worker)**: ~$5-13 depending on database tier

## Automatic Deployments

You can set up automatic deployments from GitHub:

1. Go to your app dashboard on heroku.com
2. Click "Deploy" tab
3. Connect to GitHub
4. Enable automatic deploys from your main branch

## Monitoring

Set up monitoring and error tracking:

```bash
# Add papertrail for log management (optional)
heroku addons:create papertrail:choklad

# View papertrail logs
heroku addons:open papertrail
```

## Security Checklist

Before going live:

- [ ] APP_DEBUG is set to false
- [ ] APP_ENV is set to production
- [ ] All API keys are in config vars (not in code)
- [ ] Database backups are configured
- [ ] HTTPS is enforced (Heroku does this automatically)

## Need Help?

- Heroku Documentation: https://devcenter.heroku.com/
- Laravel on Heroku: https://devcenter.heroku.com/articles/getting-started-with-laravel
- View this project's logs: `heroku logs --tail`
