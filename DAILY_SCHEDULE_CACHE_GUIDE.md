# Daily Schedule Cache System - Complete Setup Guide

## 🎯 Problem Solved

The calendars were showing "No schedule" because:
- No pre-computed schedule data existed for past/present/future dates
- Calendars had to do complex priority-based lookups every time
- No single source of truth for "what schedule does this employee have on this date?"

## ✅ Solution: Daily Schedule Cache

Created a **pre-computed cache table** (`employee_daily_schedule_cache`) that stores every employee's schedule for every date, making queries simple and fast.

---

## 📋 Setup Steps (Run in Order)

### Step 1: Setup Default Weekly Schedules
**Run:** http://localhost/Timekeeping-system/setup_default_schedules.php

This creates weekly recurring schedules (Mon-Fri work, Sat-Sun rest) for all employees based on their `official_sched` field.

**What it does:**
- Checks each employee's `official_sched`
- Creates 7 rows in `employee_default_schedules` (one per day of week)
- Sets Mon-Fri to their work schedule, Sat-Sun as rest days

---

### Step 2: Create and Populate Cache
**Run:** http://localhost/Timekeeping-system/run_cache_setup.php

This creates the cache table, stored procedures, triggers, and populates all schedule data.

**What it creates:**
1. **Table:** `employee_daily_schedule_cache` - stores one row per employee per date
2. **Procedures:** 
   - `populate_schedule_cache(employee_id, start_date, end_date)` - populate for one employee
   - `populate_all_employees_schedule_cache(start_date, end_date)` - populate for all employees
3. **Triggers:** Auto-update cache when source data changes
   - After insert/update on `post_schedule_change_requests`
   - After insert/update/delete on `employee_daily_schedules`
   - After insert/update on `employee_default_schedules`
4. **Event:** Daily maintenance event to keep future dates populated
5. **Data:** Populates cache from 6 months ago to 12 months in future

---

### Step 3: Verify Data
**Run:** http://localhost/Timekeeping-system/check_schedule_data.php

This shows:
- Employee's weekly default schedule
- Daily overrides
- Approved change requests
- Cache statistics
- Sample schedule data

---

## 🔄 How It Works

### Priority System (Automatic)
The cache uses this priority when computing schedules:

1. **Priority 1:** Approved schedule change requests (`post_schedule_change_requests`)
2. **Priority 2:** Admin daily overrides (`employee_daily_schedules`)
3. **Priority 3:** Weekly default schedule (`employee_default_schedules`)
4. **Priority 4:** Company holidays (`company_holidays`)
5. **Priority 5:** Weekend fallback (Sat/Sun)

### Auto-Updates
The cache automatically updates when:
- Admin creates/updates/deletes a daily override → triggers refresh for that date
- Employee schedule request is approved → triggers refresh for date range
- Weekly default is changed → triggers refresh for next 6 months
- Daily maintenance event runs → keeps future dates populated

---

## 📊 Database Schema

### employee_daily_schedule_cache
```sql
- id (PK)
- employee_id (FK to employees)
- schedule_date (DATE)
- work_schedule_id (FK to work_schedules)
- is_rest_day (boolean)
- is_holiday (boolean)
- source (varchar) - 'approved_request', 'admin_override', 'weekly_default', 'holiday', 'weekend'
- source_id (reference to source record)
- schedule_name (denormalized)
- time_in (denormalized)
- time_out (denormalized)
- holiday_name (denormalized)
- created_at, updated_at
```

**Key Features:**
- One row per employee per date
- Denormalized for fast queries (no joins needed!)
- Unique index on (employee_id, schedule_date)
- Auto-maintained by triggers

---

## 🚀 Usage in Code

### Simple Query (Direct SQL)
```php
// Get today's schedule
$stmt = $pdo->prepare("
    SELECT schedule_name, time_in, time_out, is_rest_day, is_holiday, holiday_name
    FROM employee_daily_schedule_cache
    WHERE employee_id = ? AND schedule_date = ?
");
$stmt->execute([$employee_id, date('Y-m-d')]);
$schedule = $stmt->fetch(PDO::FETCH_ASSOC);

if ($schedule['is_holiday']) {
    echo "HOLIDAY: " . $schedule['holiday_name'];
} elseif ($schedule['is_rest_day']) {
    echo "REST DAY";
} else {
    echo $schedule['schedule_name'] . " " . $schedule['time_in'] . " - " . $schedule['time_out'];
}
```

### Using Helper Functions
```php
require_once 'schedule_cache_functions.php';

// Get single date
$schedule = getScheduleFromCache($pdo, $employee_id, '2025-10-22');

// Get date range (for calendar)
$schedules = getScheduleRangeFromCache($pdo, $employee_id, '2025-10-01', '2025-10-31');
foreach ($schedules as $date => $schedule) {
    echo "$date: " . $schedule['actual_schedule']['name'] . "\n";
}
```

---

## 🔧 Maintenance

### Manual Cache Refresh
```sql
-- Refresh specific employee for date range
CALL populate_schedule_cache(1, '2025-10-01', '2025-12-31');

-- Refresh all employees for date range
CALL populate_all_employees_schedule_cache('2025-10-01', '2025-12-31');

-- Full rebuild
TRUNCATE employee_daily_schedule_cache;
CALL populate_all_employees_schedule_cache(
    DATE_SUB(CURDATE(), INTERVAL 6 MONTH),
    DATE_ADD(CURDATE(), INTERVAL 12 MONTH)
);
```

### Check Cache Health
```sql
-- Cache statistics
SELECT 
    source,
    COUNT(*) as total_days,
    COUNT(DISTINCT employee_id) as employees,
    MIN(schedule_date) as earliest_date,
    MAX(schedule_date) as latest_date
FROM employee_daily_schedule_cache
GROUP BY source;

-- Find missing dates for employee
SELECT CURDATE() + INTERVAL seq DAY as missing_date
FROM (
    SELECT 0 as seq UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7
) dates
WHERE NOT EXISTS (
    SELECT 1 FROM employee_daily_schedule_cache 
    WHERE employee_id = 1 
    AND schedule_date = CURDATE() + INTERVAL seq DAY
);
```

---

## 📁 Files Created

1. **create_daily_schedule_cache.sql** - Main SQL script (table, procedures, triggers, events)
2. **schedule_cache_functions.php** - PHP helper functions for using cache
3. **setup_default_schedules.php** - Setup weekly defaults for all employees
4. **run_cache_setup.php** - Execute SQL script and populate cache
5. **check_schedule_data.php** - Diagnostic tool (already existed)

---

## 🎯 Next Steps

After running the setup:

1. ✅ Calendars will now show schedules for past, present, and future dates
2. ✅ Queries are simple: just SELECT from cache table
3. ✅ No more complex priority-based lookups
4. ✅ Cache auto-updates when schedules change
5. ✅ Daily maintenance keeps future dates populated

---

## 🐛 Troubleshooting

**Calendar still empty?**
- Check if `employee_default_schedules` has data → Run setup_default_schedules.php
- Check if cache has data → Run run_cache_setup.php
- Verify with check_schedule_data.php

**Cache not updating?**
- Check if triggers are created: `SHOW TRIGGERS LIKE 'after_%';`
- Manually refresh: `CALL populate_schedule_cache(employee_id, start_date, end_date);`

**Old code still using priority-based lookups?**
- Update to use `getScheduleFromCache()` functions
- Or query cache table directly

---

## ✨ Benefits

- **Fast:** No complex joins or priority logic in queries
- **Simple:** One table, one query to get schedule
- **Complete:** Has past, present, and future dates pre-computed
- **Automatic:** Self-maintaining via triggers and events
- **Flexible:** Can rebuild cache anytime if needed
- **Auditable:** Stores source of each schedule decision

---

Created: October 22, 2025
