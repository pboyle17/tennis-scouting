# Buildpack Configuration for Heroku

## Why Two Buildpacks?

Your Laravel app uses **Vite** and **Tailwind CSS** for frontend assets, which requires **Node.js** to build the CSS and JavaScript files. Therefore, you need both buildpacks:

1. **heroku/nodejs** - Compiles frontend assets (CSS, JS) with Vite
2. **heroku/php** - Runs your Laravel application

## Setup

### Automatic (Recommended)

Run the quickstart script, which sets up buildpacks automatically:

```bash
./heroku-quickstart.sh
```

### Manual Setup

Add buildpacks in this specific order:

```bash
# 1. Node.js first (for frontend assets)
heroku buildpacks:add heroku/nodejs

# 2. PHP second (for Laravel)
heroku buildpacks:add heroku/php

# Verify
heroku buildpacks
```

**Expected output:**
```
=== your-app-name Buildpack URLs
1. heroku/nodejs
2. heroku/php
```

## Build Process

When you deploy, Heroku will:

1. **Node.js Buildpack**:
   - Detects `package.json`
   - Runs `npm install` (or `npm ci`)
   - Runs `npm run heroku-postbuild` → `npm run build`
   - Builds frontend assets with Vite
   - Outputs compiled CSS/JS to `public/build/`

2. **PHP Buildpack**:
   - Detects `composer.json`
   - Runs `composer install --no-dev --optimize-autoloader`
   - Sets up PHP/Apache
   - Runs `post-install-cmd` scripts (config/route/view cache)

3. **Release Phase** (`release.sh`):
   - Runs database migrations
   - Final setup before app starts

## Troubleshooting

### Assets Not Loading (404 errors)

If CSS/JS files aren't loading:

```bash
# Check if buildpacks are set correctly
heroku buildpacks

# Rebuild with correct buildpacks
git commit --allow-empty -m "Rebuild assets"
git push heroku main

# Check build logs
heroku logs --tail
```

### Wrong Buildpack Order

If buildpacks are in wrong order:

```bash
# Clear all buildpacks
heroku buildpacks:clear

# Add in correct order
heroku buildpacks:add heroku/nodejs
heroku buildpacks:add heroku/php

# Trigger rebuild
git commit --allow-empty -m "Fix buildpack order"
git push heroku main
```

### Node.js Version

By default, Heroku uses the version specified in `package.json` or the latest LTS. To specify a version:

```json
// package.json
{
  "engines": {
    "node": "20.x",
    "npm": "10.x"
  }
}
```

### PHP Version

Heroku detects PHP version from `composer.json`:

```json
// composer.json
{
  "require": {
    "php": "^8.2"
  }
}
```

## Build Output

During deployment, you'll see:

```
-----> Building on the Heroku-22 stack
-----> Using buildpacks:
       1. heroku/nodejs
       2. heroku/php
-----> Node.js app detected
       ...
       Running heroku-postbuild
       > npm run build
       ...
       Build complete
-----> PHP app detected
       ...
       Running composer install
       ...
-----> Release phase
       Running database migrations...
       Deployment completed successfully!
```

## Local Development

For local development, you don't need Heroku's buildpacks:

```bash
# Install dependencies
npm install
composer install

# Build assets (watch mode)
npm run dev

# Or build for production
npm run build
```

## More Information

- [Heroku PHP Buildpack](https://devcenter.heroku.com/articles/php-support)
- [Heroku Node.js Buildpack](https://devcenter.heroku.com/articles/nodejs-support)
- [Using Multiple Buildpacks](https://devcenter.heroku.com/articles/using-multiple-buildpacks-for-an-app)
