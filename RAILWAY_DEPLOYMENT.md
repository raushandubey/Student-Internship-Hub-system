# Railway Deployment Guide

## Environment Variables

Set these in Railway Dashboard → Variables → Raw Editor:

```
APP_NAME=Student Internship Hub
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:H7aEu5IOU0QAE7UIMSf78EHXdMLf1HKyLijhOGlO//I=
APP_URL=https://${{RAILWAY_PUBLIC_DOMAIN}}

DB_CONNECTION=mysql
DB_HOST=${{MYSQLHOST}}
DB_PORT=${{MYSQLPORT}}
DB_DATABASE=${{MYSQLDATABASE}}
DB_USERNAME=${{MYSQLUSER}}
DB_PASSWORD=${{MYSQLPASSWORD}}

CACHE_DRIVER=file
SESSION_DRIVER=file
SESSION_LIFETIME=120
QUEUE_CONNECTION=sync

LOG_CHANNEL=stack
LOG_LEVEL=error

MAIL_MAILER=log
```

## Deployment Steps

1. Push to GitHub
2. Create Railway project from GitHub repo
3. Add MySQL database plugin
4. Set environment variables above
5. Deploy automatically
6. Run migrations: `railway run php artisan migrate --force`
7. Seed admin: `railway run php artisan db:seed --class=AdminSeeder`

## Verify

Visit: `https://your-app.up.railway.app/health`

Expected: `database_connected: true`
