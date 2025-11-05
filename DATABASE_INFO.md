# Database Configuration

This application supports both SQLite (for local development) and PostgreSQL (for production/Heroku).

## Automatic Database Detection

The application will **automatically detect** which database to use:

- **Local Development**: Uses SQLite by default
  - Database file: `database/database.sqlite`
  - No configuration needed for basic setup

- **Heroku/Production**: Automatically switches to PostgreSQL when `DATABASE_URL` is present
  - Heroku sets `DATABASE_URL` automatically when you add the Postgres addon
  - No need to manually set `DB_CONNECTION=pgsql`

## How It Works

In `config/database.php`:

```php
'default' => env('DB_CONNECTION', env('DATABASE_URL') ? 'pgsql' : 'sqlite'),
```

This checks:
1. If `DB_CONNECTION` is explicitly set, use that
2. Else if `DATABASE_URL` exists (Heroku), use PostgreSQL
3. Else use SQLite (local development)

## Local PostgreSQL Setup (Optional)

If you want to use PostgreSQL locally:

1. Install PostgreSQL
2. Create a database: `createdb tennis_scouting`
3. Update your `.env`:

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=tennis_scouting
DB_USERNAME=postgres
DB_PASSWORD=your_password
```

4. Run migrations: `php artisan migrate`

## Heroku PostgreSQL

On Heroku, the database is automatically configured:

```bash
# Add PostgreSQL addon
heroku addons:create heroku-postgresql:essential-0

# Heroku automatically sets DATABASE_URL
# Your app will automatically detect and use PostgreSQL
```

## Database Tiers on Heroku

- **essential-0**: Free, 1 GB storage, 20 connections
- **mini**: $5/mo, 10 GB storage, 120 connections
- **basic**: $9/mo, 10 GB storage, 120 connections

## Checking Current Database

```bash
# Locally
php artisan tinker
>>> DB::connection()->getDatabaseName();

# On Heroku
heroku run php artisan tinker
>>> DB::connection()->getDatabaseName();
```

## Important Notes

- **Migrations**: The same migrations work for both SQLite and PostgreSQL
- **Schema Differences**: Both databases support the features used in this app
- **Data Portability**: You can dump/import data between databases using Laravel seeders
- **Queue Tables**: Both databases support Laravel's database queue driver
