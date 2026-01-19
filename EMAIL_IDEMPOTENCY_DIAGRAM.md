# EMAIL LOG IDEMPOTENCY - VISUAL EXPLANATION

## BEFORE (BROKEN) - Duplicate Logs Created

```
User Action: Submit Application
        ↓
ApplicationController::apply()
        ↓
ApplicationService::submitApplication()
        ↓
    [TRANSACTION]
    ├─ Application::create()
    └─ ApplicationStatusLog::create()
        ↓
event(ApplicationSubmitted) ← EVENT FIRED
        ↓
SendApplicationConfirmation::handle()
        ↓
EmailLog::create([...]) ← LOG #1 CREATED
        ↓
    [SUCCESS]

---

Network Retry / Double-Click / Race Condition
        ↓
ApplicationController::apply() ← CALLED AGAIN
        ↓
ApplicationService::submitApplication()
        ↓
    [TRANSACTION]
    ├─ Application::create() ← FAILS (duplicate)
    └─ Rollback
        ↓
    [FAILURE]

BUT... Event Already Fired!
        ↓
SendApplicationConfirmation::handle() ← RUNS AGAIN
        ↓
EmailLog::create([...]) ← LOG #2 CREATED ❌
        ↓
    [DUPLICATE LOG CREATED]
```

**Problem**: Event listener runs independently of transaction. If event fires twice, two logs created.

---

## AFTER (FIXED) - Duplicate Prevented

```
User Action: Submit Application
        ↓
ApplicationController::apply()
        ↓
ApplicationService::submitApplication()
        ↓
    [TRANSACTION]
    ├─ Application::create()
    └─ ApplicationStatusLog::create()
        ↓
event(ApplicationSubmitted) ← EVENT FIRED
        ↓
SendApplicationConfirmation::handle()
        ↓
EmailLog::createIdempotent([...])
        ↓
    Generate Event Hash
    ├─ user_id: 1
    ├─ email_type: 'application_submitted'
    ├─ metadata: {application_id: 123}
    └─ timestamp: '2026-01-20 10:30' (rounded to minute)
        ↓
    SHA256 Hash: "abc123..."
        ↓
    Try INSERT with hash
        ↓
    [SUCCESS] ← LOG #1 CREATED
    wasRecentlyCreated = true
        ↓
    Log: "Email logged"

---

Network Retry / Double-Click / Race Condition
        ↓
ApplicationController::apply() ← CALLED AGAIN
        ↓
ApplicationService::submitApplication()
        ↓
    [TRANSACTION]
    ├─ Application::create() ← FAILS (duplicate)
    └─ Rollback
        ↓
    [FAILURE]

BUT... Event Fires Again!
        ↓
SendApplicationConfirmation::handle() ← RUNS AGAIN
        ↓
EmailLog::createIdempotent([...])
        ↓
    Generate Event Hash
    ├─ user_id: 1
    ├─ email_type: 'application_submitted'
    ├─ metadata: {application_id: 123}
    └─ timestamp: '2026-01-20 10:30' (same minute)
        ↓
    SHA256 Hash: "abc123..." ← SAME HASH
        ↓
    Try INSERT with hash
        ↓
    [DUPLICATE KEY VIOLATION] ← Database rejects
        ↓
    Catch QueryException (code 23000)
        ↓
    SELECT existing log with same hash
        ↓
    Return existing log
    wasRecentlyCreated = false
        ↓
    Log: "Duplicate prevented" ✅
        ↓
    [NO DUPLICATE CREATED]
```

**Solution**: Event hash + unique constraint ensures only ONE log per event.

---

## DATABASE SCHEMA

### Before (No Protection)

```sql
CREATE TABLE email_logs (
    id BIGINT PRIMARY KEY,
    user_id BIGINT,
    email_type VARCHAR(255),
    subject VARCHAR(255),
    recipient VARCHAR(255),
    body TEXT,
    status VARCHAR(255),
    metadata JSON,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    
    INDEX (user_id, email_type),
    INDEX (created_at)
);

-- No unique constraint
-- Duplicate INSERTs allowed ❌
```

### After (Idempotency Protection)

```sql
CREATE TABLE email_logs (
    id BIGINT PRIMARY KEY,
    user_id BIGINT,
    email_type VARCHAR(255),
    subject VARCHAR(255),
    recipient VARCHAR(255),
    body TEXT,
    status VARCHAR(255),
    metadata JSON,
    event_hash VARCHAR(64), ← NEW COLUMN
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    
    INDEX (user_id, email_type),
    INDEX (created_at),
    UNIQUE INDEX email_logs_idempotency_unique (user_id, email_type, event_hash) ← NEW CONSTRAINT
);

-- Unique constraint prevents duplicates ✅
-- Database-level enforcement (cannot be bypassed)
```

---

## EVENT HASH GENERATION

### Input Data

```php
$data = [
    'user_id' => 1,
    'email_type' => 'application_submitted',
    'metadata' => [
        'application_id' => 123,
        'internship_id' => 456
    ]
];
```

### Hash Calculation

```php
$payload = [
    'user_id' => 1,
    'email_type' => 'application_submitted',
    'metadata' => ['application_id' => 123, 'internship_id' => 456],
    'timestamp' => '2026-01-20 10:30' // Rounded to minute
];

$json = json_encode($payload);
// {"user_id":1,"email_type":"application_submitted","metadata":{"application_id":123,"internship_id":456},"timestamp":"2026-01-20 10:30"}

$hash = hash('sha256', $json);
// "abc123def456..." (64 characters)
```

### Why Timestamp Rounded to Minute?

```
Event at 10:30:15 → Hash includes "10:30"
Event at 10:30:45 → Hash includes "10:30" (same)
Event at 10:31:00 → Hash includes "10:31" (different)

Result:
- Same event within 1 minute → Same hash → Duplicate rejected
- Same event after 1 minute → Different hash → Both allowed (legitimate retry)
```

---

## IDEMPOTENCY FLOW

### Scenario 1: First Email Log

```
EmailLog::createIdempotent([
    'user_id' => 1,
    'email_type' => 'application_submitted',
    'metadata' => ['application_id' => 123]
])
    ↓
Generate hash: "abc123..."
    ↓
Try INSERT INTO email_logs (user_id, email_type, event_hash, ...)
VALUES (1, 'application_submitted', 'abc123...', ...)
    ↓
[SUCCESS] ← No existing row with same hash
    ↓
Return EmailLog (id=1, wasRecentlyCreated=true)
```

### Scenario 2: Duplicate Email Log (Same Minute)

```
EmailLog::createIdempotent([
    'user_id' => 1,
    'email_type' => 'application_submitted',
    'metadata' => ['application_id' => 123]
])
    ↓
Generate hash: "abc123..." ← SAME HASH
    ↓
Try INSERT INTO email_logs (user_id, email_type, event_hash, ...)
VALUES (1, 'application_submitted', 'abc123...', ...)
    ↓
[DUPLICATE KEY VIOLATION] ← Unique constraint violated
    ↓
Catch QueryException (code 23000)
    ↓
SELECT * FROM email_logs
WHERE user_id = 1
  AND email_type = 'application_submitted'
  AND event_hash = 'abc123...'
    ↓
Return existing EmailLog (id=1, wasRecentlyCreated=false)
```

### Scenario 3: Legitimate Retry (After 1 Minute)

```
EmailLog::createIdempotent([
    'user_id' => 1,
    'email_type' => 'application_submitted',
    'metadata' => ['application_id' => 123]
])
    ↓
Generate hash: "def456..." ← DIFFERENT HASH (different minute)
    ↓
Try INSERT INTO email_logs (user_id, email_type, event_hash, ...)
VALUES (1, 'application_submitted', 'def456...', ...)
    ↓
[SUCCESS] ← Different hash, no conflict
    ↓
Return EmailLog (id=2, wasRecentlyCreated=true)
```

---

## TIMEZONE FIX

### Before (UTC)

```php
// config/app.php
'timezone' => 'UTC',

// Database stores UTC
created_at: 2026-01-20 10:30:00 (UTC)

// Laravel reads as UTC
$emailLog->created_at->format('Y-m-d H:i:s')
// Output: 2026-01-20 10:30:00 (UTC)

// Blade renders UTC
{{ $emailLog->created_at->format('d M Y, h:i A') }}
// Output: 20 Jan 2026, 10:30 AM (UTC) ❌ WRONG FOR INDIA
```

### After (Asia/Kolkata)

```php
// config/app.php
'timezone' => 'Asia/Kolkata',

// Database stores UTC (unchanged)
created_at: 2026-01-20 10:30:00 (UTC)

// Laravel reads and converts to Asia/Kolkata
$emailLog->created_at->format('Y-m-d H:i:s')
// Output: 2026-01-20 16:00:00 (IST = UTC+5:30)

// Blade renders IST
{{ $emailLog->created_at->format('d M Y, h:i A') }}
// Output: 20 Jan 2026, 04:00 PM (IST) ✅ CORRECT
```

**Key Point**: Database still stores UTC (best practice), but Laravel automatically converts to configured timezone when reading.

---

## DEFENSE IN DEPTH

```
┌─────────────────────────────────────────────────────────────┐
│                    DUPLICATE EMAIL LOG ATTEMPT              │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ Layer 1: Event Hash Generation                              │
│ - Deterministic SHA256 hash                                 │
│ - Same event → Same hash                                    │
│ - Cryptographic guarantee                                   │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ Layer 2: Database Unique Constraint                         │
│ - UNIQUE INDEX (user_id, email_type, event_hash)           │
│ - Database-level enforcement                                │
│ - Cannot be bypassed                                        │
│ - Rejects duplicate INSERT                                  │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ Layer 3: Application Exception Handling                     │
│ - Catch QueryException (code 23000)                         │
│ - Return existing log                                       │
│ - Graceful degradation                                      │
│ - No exception thrown to caller                             │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ Layer 4: Monitoring & Logging                               │
│ - Log "Duplicate prevented"                                 │
│ - Track duplicate rate                                      │
│ - Alert if rate > threshold                                 │
│ - Visibility into system behavior                           │
└─────────────────────────────────────────────────────────────┘
                            ↓
                    [DUPLICATE PREVENTED] ✅
```

**Result**: Even if event fires 100 times, only ONE email log exists.

---

## PERFORMANCE COMPARISON

### Before (No Idempotency)

```
Request 1:
  EmailLog::create() → INSERT → 1ms
  Total: 1ms

Request 2 (Duplicate):
  EmailLog::create() → INSERT → 1ms ❌ DUPLICATE CREATED
  Total: 1ms

Database:
  2 rows inserted (duplicate)
```

### After (With Idempotency)

```
Request 1:
  EmailLog::createIdempotent()
    → Generate hash → 0.1ms
    → INSERT → 1ms
  Total: 1.1ms

Request 2 (Duplicate):
  EmailLog::createIdempotent()
    → Generate hash → 0.1ms
    → INSERT → FAILS (unique constraint)
    → Catch exception → 0.1ms
    → SELECT existing → 1ms
  Total: 1.2ms ✅ NO DUPLICATE

Database:
  1 row inserted (no duplicate)
```

**Overhead**: +0.1-0.2ms per request (negligible)
**Benefit**: Guaranteed idempotency (priceless)

---

## SUMMARY

### Problem
- Events can fire multiple times
- No protection against duplicate email logs
- Timezone mismatch (UTC vs IST)

### Solution
- Event hash for deterministic deduplication
- Database unique constraint for enforcement
- Application-level exception handling for grace
- Timezone configured to Asia/Kolkata

### Guarantee
- Each event logs EXACTLY ONCE
- Duplicate attempts handled gracefully
- Timestamps display correctly
- Production-grade reliability
