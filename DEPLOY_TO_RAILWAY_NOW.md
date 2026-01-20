# 🚀 DEPLOY TO RAILWAY - QUICK START

## ⚡ 3-MINUTE DEPLOYMENT

### 1️⃣ PUSH TO GITHUB (if not already done)
```bash
git add .
git commit -m "Ready for Railway deployment"
git push origin master
```

### 2️⃣ CREATE RAILWAY PROJECT
1. Go to https://railway.app
2. Click "New Project"
3. Select "Deploy from GitHub repo"
4. Choose: `Student-Internship-Hub-system`

### 3️⃣ ADD MYSQL DATABASE
1. In Railway project → Click "+ New"
2. Select "Database" → "MySQL"
3. Wait 30 seconds for provisioning

### 4️⃣ SET ENVIRONMENT VARIABLES
1. Click your web service (not database)
2. Go to "Variables" → "Raw Editor"
3. Paste this:

```env
APP_NAME=Student Internship Hub
APP_KEY=base64:H7aEu5IOU0QAE7UIMSf78EHXdMLf1HKyLijhOGlO//I=
APP_ENV=production
APP_DEBUG=false
APP_URL=https://${{RAILWAY_PUBLIC_DOMAIN}}

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
MAIL_MAILER=log
```

4. Click "Save"

### 5️⃣ WAIT FOR DEPLOYMENT
- Railway auto-deploys (2-5 minutes)
- Watch "Deployments" tab for progress

### 6️⃣ RUN MIGRATIONS
Railway Dashboard → Your Service → Settings → Deploy:
```bash
php artisan migrate --force
```

### 7️⃣ VERIFY
Visit: `https://your-app.up.railway.app/health`

Expected:
```json
{
  "status": "healthy",
  "database_connected": true
}
```

### 8️⃣ SEED ADMIN (Optional)
```bash
railway run php artisan db:seed --class=AdminSeeder
```

Login: `admin@sih.com` / `admin123`

---

## ✅ DONE!

Your app is live at: `https://your-app.up.railway.app`

---

## 🆘 TROUBLESHOOTING

### Database not connected?
Check `/health` endpoint → `database_error` field

### 500 Error?
Railway Dashboard → Logs → Look for PHP errors

### Need help?
See `RAILWAY_DEPLOYMENT_CHECKLIST.md` for detailed guide

---

**Total Time**: 3-5 minutes
**Cost**: FREE (within Railway free tier)
