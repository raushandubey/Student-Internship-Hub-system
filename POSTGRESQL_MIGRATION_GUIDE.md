# POSTGRESQL MIGRATION GUIDE - COMMON MISTAKES

## MISTAKE 1: Wrong DB_CONNECTION

```bash
❌ DB_CONNECTION=mysql
✅ DB_CONNECTION=pgsql
```

**Impact:** Laravel tries to use MySQL driver for PostgreSQL database → connection fails

---

## MISTAKE 2: Wrong DB_PORT

```bash
❌ DB_PORT=3306  # MySQL port
✅ DB_PORT=5432  # PostgreSQL port
```

**Impact:** Connection timeout or refused

---

## MISTAKE 3: Missing libpq-dev

```dockerfile
❌ RUN apt-get install -y libpng-dev libjpeg-dev
✅ RUN apt-get install -y libpng-dev libjpeg-dev libpq-dev
```

**Impact:** Build fails with "Cannot find libpq-fe.h"

---

## MISTAKE 4: Installing MySQL Extensions

```dockerfile
❌ RUN docker-php-ext-install pdo_mysql pdo_pgsql
✅ RUN docker-php-ext-install pdo_pgsql pgsql
```

**Impact:** Unnecessary bloat, potential conflicts

---

## MISTAKE 5: Using External Database URL

```bash
❌ DB_HOST=external-hostname.render.com
✅ DB_HOST=dpg-xxxx-a.oregon-postgres.render.com  # Internal URL
```

**Impact:** Connection fails or slow (external URL is for local connections)

---

## MISTAKE 6: Running Migrations During Build

```dockerfile
❌ RUN php artisan migrate  # In Dockerfile
✅ # Run migrations after deployment via Render dashboard
```

**Impact:** Build fails (no database connection during build)

---

## MISTAKE 7: Caching Config During Build

```dockerfile
❌ RUN php artisan config:cache  # In Dockerfile
✅ # Run in start.sh after env vars loaded
```

**Impact:** Wrong config cached (no env vars during build)

---

## MISTAKE 8: Using root User

```bash
❌ DB_USERNAME=root
✅ DB_USERNAME=<actual-username-from-render>
```

**Impact:** PostgreSQL on Render doesn't allow root user

---

## MISTAKE 9: Not Handling DB Unavailability

```bash
❌ SESSION_DRIVER=database  # App crashes if DB down
✅ SESSION_DRIVER=file      # App boots even if DB down
```

**Impact:** Entire site returns 500 if database temporarily unavailable

---

## MISTAKE 10: Wrong Hostname Format

```bash
❌ DB_HOST=your-mysql-host.oregon-mysql.render.com  # Placeholder
❌ DB_HOST=localhost
❌ DB_HOST=127.0.0.1
✅ DB_HOST=dpg-<random>-a.oregon-postgres.render.com
```

**Impact:** DNS lookup fails → getaddrinfo error

---

## CORRECT BUILD SEQUENCE

### During Docker Build (Dockerfile):
1. ✅ Install system dependencies (including libpq-dev)
2. ✅ Install PHP extensions (pdo_pgsql, pgsql)
3. ✅ Install Composer dependencies
4. ✅ Generate autoloader
5. ❌ DO NOT run artisan commands
6. ❌ DO NOT cache config
7. ❌ DO NOT run migrations

### During Runtime (start.sh):
1. ✅ Configure Nginx port
2. ✅ Set permissions
3. ✅ Clear stale cache
4. ✅ Verify environment variables
5. ✅ Test database connection (non-blocking)
6. ✅ Cache config (with env vars)
7. ✅ Run package discovery
8. ✅ Cache routes and views
9. ✅ Start services

### After Deployment (Manual):
1. ✅ Run migrations via Render dashboard
2. ✅ Run seeders via Render dashboard
3. ✅ Verify application works

---

## POSTGRESQL VS MYSQL DIFFERENCES

### Connection:
- MySQL: `DB_CONNECTION=mysql`, port 3306
- PostgreSQL: `DB_CONNECTION=pgsql`, port 5432

### Extensions:
- MySQL: `pdo_mysql`
- PostgreSQL: `pdo_pgsql`, `pgsql`

### System Libraries:
- MySQL: None required (built-in)
- PostgreSQL: `libpq-dev` required

### SQL Syntax:
- MySQL: `AUTO_INCREMENT`, `UNSIGNED`, backticks
- PostgreSQL: `SERIAL`, `CHECK`, double quotes

### Case Sensitivity:
- MySQL: Case-insensitive by default
- PostgreSQL: Case-sensitive (use lowercase or quotes)

---

## VERIFICATION COMMANDS

### Check PHP Extensions:
```bash
php -m | grep pdo_pgsql
php -m | grep pgsql
```

### Test Database Connection:
```bash
php artisan tinker
>>> DB::connection()->getPdo();
>>> DB::select('SELECT version()');
```

### Check Config:
```bash
php artisan config:show database.connections.pgsql
```

### Test Query:
```bash
php artisan tinker
>>> DB::table('users')->count();
```

---

## RENDER FREE TIER LIMITATIONS

### No Shell Access:
- Cannot run `php artisan` commands interactively
- Must use build commands or manual deploy
- Cannot debug with tinker

### Workarounds:
- Use `/health` endpoint for monitoring
- Use logs for debugging
- Use build hooks for migrations
- Use manual deploy for one-time commands

### What Works:
- ✅ Environment variables
- ✅ Build logs
- ✅ Runtime logs
- ✅ Manual deploy with custom commands
- ✅ Automatic redeployment on git push

### What Doesn't Work:
- ❌ Interactive shell
- ❌ SSH access
- ❌ Real-time debugging
- ❌ Running artisan commands on demand
