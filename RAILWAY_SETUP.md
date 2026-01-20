# RAILWAY DEPLOYMENT SETUP

## STEP 1: GENERATE APP_KEY

```bash
php artisan key:generate --show
```

Copy the output (e.g., `base64:xxxxx...`)

## STEP 2: RAILWAY ENVIRONMENT VARIABLES

Railway Dashboard → Your Service → Variables → Raw Editor

Paste this (replace APP_KEY with generated value):

```
APP_NAME=Student Internship Hub
APP_KEY=base64:YOUR_GENERATED_KEY_HERE
APP_ENV=production
APP_DEBUG=false
APP_URL=${
{RAILWAY_PUBLIC_DOMAIN}}

DB_CONNECTION=mysql
DB_HOST=${MYSQLHOST}
DB_PORT=${MYSQLPORT}
DB_DATABASE=${MYSQLDATABASE}
DB_USERNAME=${MYSQLUSER}
DB_PASSWORD=${MYSQLPASSWORD}

SESSION_DRIVER=file
CACHE_STORE=file
QUEUE_CONNECTION=sync

LOG_CHANNEL=stack
LOG_LEVEL=error

MAIL_MAILER=log
```

## STEP 3: VERIFY MYSQL PLUGIN

Railway Dashboard → Your Project → Check that MySQL database is added.

Railway automatically provides:
- MYSQLHOST
- MYSQLPORT
- MYSQLDATABASE
- MYSQLUSER
- MYSQLPASSWORD

## STEP 4: DEPLOY

```bash
git add .
git commit -m "Configure for Railway with MySQL"
git push origin master
```

Railway auto-deploys.

## STEP 5: RUN MIGRATIONS

Railway Dashboard → Service → Settings → Deploy → Add command:

```
php artisan migrate --force
```

Click "Deploy"

## STEP 6: VERIFY DEPLOYMENT

Visit: `https://your-app.up.railway.app/health`

Expected response:
```json
{
  "status": "healthy",
  "php_version": "8.2.x",
  "pdo_drivers": ["mysql", ...],
  "pdo_mysql_loaded": true,
  "mysqli_loaded": true,
  "database_connected": true,
  "database_error": null
}
```

If `database_connected: false`, check environment variables.

## STEP 7: TEST APPLICATION

Visit: `https://your-app.up.railway.app`

Expected: Homepage loads without 500 error.

## TROUBLESHOOTING

### PDO MySQL not found
- Check nixpacks.toml includes `php82Extensions.pdo_mysql`
- Redeploy

### Database connection failed
- Verify Railway MySQL plugin is added
- Verify environment variables use `${MYSQLHOST}` format
- Check `/health` endpoint for actual values

### 500 Error
- Check Railway logs
- Visit `/health` endpoint
- Verify APP_KEY is set
