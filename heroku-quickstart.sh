#!/bin/bash

echo "========================================="
echo "Tennis Scouting App - Heroku Quick Setup"
echo "========================================="
echo ""

# Check if Heroku CLI is installed
if ! command -v heroku &> /dev/null; then
    echo "❌ Heroku CLI is not installed."
    echo "Please install it from: https://devcenter.heroku.com/articles/heroku-cli"
    exit 1
fi

echo "✓ Heroku CLI is installed"
echo ""

# Ask for app name
read -p "Enter your Heroku app name (or press Enter for auto-generated): " APP_NAME

# Create Heroku app
if [ -z "$APP_NAME" ]; then
    echo "Creating Heroku app with auto-generated name..."
    heroku create
else
    echo "Creating Heroku app: $APP_NAME..."
    heroku create "$APP_NAME"
fi

if [ $? -ne 0 ]; then
    echo "❌ Failed to create Heroku app"
    exit 1
fi

echo "✓ Heroku app created"
echo ""

# Add buildpacks
echo "Adding buildpacks (Node.js + PHP)..."
heroku buildpacks:add heroku/nodejs
heroku buildpacks:add heroku/php

if [ $? -ne 0 ]; then
    echo "⚠️  Warning: Failed to add buildpacks. You may need to add them manually."
else
    echo "✓ Buildpacks added (Node.js for assets, PHP for Laravel)"
fi

echo ""

# Add PostgreSQL
echo "Adding PostgreSQL database (essential-0 free tier)..."
heroku addons:create heroku-postgresql:essential-0

if [ $? -ne 0 ]; then
    echo "⚠️  Warning: Failed to add PostgreSQL. You may need to add it manually."
else
    echo "✓ PostgreSQL added"
fi

echo ""

# Generate APP_KEY
echo "Generating APP_KEY..."
APP_KEY=$(php artisan key:generate --show)
heroku config:set APP_KEY="$APP_KEY"

echo "✓ APP_KEY set"
echo ""

# Set basic config
echo "Setting basic configuration..."
heroku config:set APP_NAME="Tennis Scouting"
heroku config:set APP_ENV=production
heroku config:set APP_DEBUG=false
heroku config:set QUEUE_CONNECTION=database
heroku config:set SESSION_DRIVER=database
heroku config:set CACHE_DRIVER=database
heroku config:set LOG_CHANNEL=errorlog
heroku config:set LOG_LEVEL=error

echo "✓ Configuration set (DB will auto-detect PostgreSQL)"
echo ""

# Ask about UTR API key
read -p "Do you have a UTR API key? (y/n): " HAS_UTR
if [ "$HAS_UTR" = "y" ] || [ "$HAS_UTR" = "Y" ]; then
    read -p "Enter your UTR API key: " UTR_KEY
    heroku config:set UTR_API_KEY="$UTR_KEY"
    echo "✓ UTR API key set"
fi

echo ""
echo "========================================="
echo "Setup Complete! Next steps:"
echo "========================================="
echo ""
echo "1. Deploy your app:"
echo "   git push heroku main"
echo ""
echo "2. (Optional) Scale up worker for background jobs:"
echo "   heroku ps:scale worker=1"
echo ""
echo "3. Open your app:"
echo "   heroku open"
echo ""
echo "4. View logs:"
echo "   heroku logs --tail"
echo ""
echo "For more details, see HEROKU_DEPLOYMENT.md"
echo ""
