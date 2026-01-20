# RAILWAY DEPLOYMENT GUIDE

## SETUP

1. Push code to GitHub
2. Create new Railway project
3. Connect GitHub repository
4. Add MySQL plugin to project

## ENVIRONMENT VARIABLES

Railway Dashboard → Variables → Add:

```
APP_KEY=base64:GENERATE_WITH_php_artisan_key_generate
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-app.railway.app

DB_CONNECTION=mysql
DB_HOST=${MYSQLHOST}
DB_PORT=${MYSQLPORT}
DB_DATABASE=${MYSQLDATABASE}
DB_USERNAME=${MYSQLUSER}
DB_PASSWORD=${MYSQLPASSWORD}

SESSION_DRIVER=file
CACHE_STORE=file
QUEUE_CONNECTION=sync
LOG_LEVEL=error
```

## MYSQL PLUGIN

Railway automatically provides these variables when MySQL plugin is added:
- MYSQLHOST
- MYSQLPORT
- MYSQLDATABASE
- MYSQLUSER
- MYSQLPASSWORD

## DEPLOYMENT

Railway auto-deploys on git push.

## RUN MIGRATIONS

Railway Dashboard → Service → Settings → Deploy → Add command:
```
php artisan migrate --force
```

## VERIFY

Visit: https://your-app.railway.app
