# 📅 Calendar Scheduling System - Quick Start

## What Was Created

### 1. **Database Tables** (7 tables)
- `employee_daily_schedules` - Daily schedule overrides
- `employee_default_schedules` - Default weekly patterns
- `company_holidays` - Holiday calendar
- `schedule_override_history` - Audit trail
- `rotating_schedule_patterns` - Rotation definitions
- `rotating_schedule_pattern_days` - Rotation details
- `employee_rotating_schedules` - Employee-pattern assignments

### 2. **PHP Class**
- `Public/config/CalendarScheduler.php` - Main scheduling logic

### 3. **Setup Script**
- `Public/tools/setup_calendar_scheduling.php` - One-click installation

### 4. **SQL File**
- `Database/create_calendar_scheduling_tables.sql` - Complete schema

### 5. **Documentation**
- `docs/CALENDAR_SCHEDULING_GUIDE.md` - Full documentation

---

## Installation Steps

### Option 1: Browser Setup (Recommended)

```
1. Open browser: http://localhost/Timekeeping-system/Public/tools/setup_calendar_scheduling.php
2. Wait for completion
3. Done!
```

### Option 2: Manual SQL

```sql
-- Run this in phpMyAdmin or MySQL client
SOURCE c:/xampp/htdocs/Timekeeping-system/Database/create_calendar_scheduling_tables.sql;
```

---

## How It Works

### Schedule Priority (Highest → Lowest):

1. **Daily Override** → Specific dates (vacation, doctor appointment)
2. **Rotating Pattern** → Shift workers (4-day cycles, etc.)
3. **Default Weekly** → Normal Mon-Sun pattern
4. **Official Schedule** → From `employees.official_sched`
5. **Holiday** → Company holidays

---

## Quick Examples

### Get Employee Schedule for a Date

```php
require_once('config/CalendarScheduler.php');
$scheduler = new CalendarScheduler($pdo);

$schedule = $scheduler->getEmployeeScheduleForDate($employee_id, '2025-10-20');
// Returns: time_in, time_out, schedule_type, is_rest_day, is_holiday, source, etc.
```

### Set Daily Override

```php
$scheduler->setDailySchedule(
    $employee_id,
    '2025-10-25',
    5,              // work_schedule_id (from work_schedules table)
    'override',
    'Special request',
    $admin_id
);
```

### Apply Approved Schedule Change Request

```php
// Links to your existing schedule_change_requests table
$scheduler->applyScheduleChangeRequest($request_id, $admin_id);
```

### Set Default Weekly Schedule

```php
// Monday to Friday: 8AM-5PM (work_schedule_id = 5)
for ($day = 1; $day <= 5; $day++) {
    $scheduler->setDefaultWeeklySchedule($employee_id, $day, 5, 0, '2025-01-01');
}

// Weekend: Rest days
$scheduler->setDefaultWeeklySchedule($employee_id, 0, null, 1, '2025-01-01'); // Sunday
$scheduler->setDefaultWeeklySchedule($employee_id, 6, null, 1, '2025-01-01'); // Saturday
```

---

## Integration with Existing System

### ✅ Does NOT Affect:
- Existing `schedule_change_requests` table
- Current request workflow
- Any existing data

### ✅ Adds:
- Automatic calendar application of approved requests
- Daily schedule tracking per employee
- Rotating shift support
- Holiday management
- Complete audit trail

### Integration Point:

```php
// In your schedule request approval page, add this:
if ($request_approved) {
    // Existing approval code...
    
    // NEW: Apply to calendar
    require_once('config/CalendarScheduler.php');
    $scheduler = new CalendarScheduler($pdo);
    $scheduler->applyScheduleChangeRequest($request_id, $_SESSION['admin_id']);
}
```

---

## Database Schema Overview

```
┌─────────────────────────────┐
│ employee_daily_schedules    │ ← Highest Priority (Specific Dates)
├─────────────────────────────┤
│ employee_rotating_schedules │ ← Rotating Shifts
├─────────────────────────────┤
│ employee_default_schedules  │ ← Default Weekly Pattern
├─────────────────────────────┤
│ employees.official_sched    │ ← Fallback
└─────────────────────────────┘

         ┌─────────────────┐
         │ company_holidays│ ← Holiday Check
         └─────────────────┘

         ┌──────────────────────────┐
         │ schedule_override_history│ ← Audit Trail
         └──────────────────────────┘
```

---

## Pre-Loaded Data

The system comes with:
- ✅ Philippine holidays for 2025
- ✅ Sample 4-day rotating pattern
- ✅ All necessary indexes for performance

---

## Files Updated

### New Files:
- `Database/create_calendar_scheduling_tables.sql`
- `Public/config/CalendarScheduler.php`
- `Public/tools/setup_calendar_scheduling.php`
- `docs/CALENDAR_SCHEDULING_GUIDE.md`
- `docs/CALENDAR_SCHEDULING_QUICKSTART.md` (this file)

### Modified Files:
- `Public/module/schedule_content.php` - Now uses CalendarScheduler

---

## Next Steps

1. **Run Setup:**
   - Visit: `http://localhost/Timekeeping-system/Public/tools/setup_calendar_scheduling.php`

2. **Configure Default Schedules:**
   - For each employee, set their default weekly pattern

3. **Test:**
   - View calendar in employee portal
   - Try creating a daily override
   - Test with approved schedule change request

4. **Optional - Rotating Shifts:**
   - If you have shift workers, create rotation patterns
   - Assign employees to patterns

---

## Support

- **Full Documentation:** `docs/CALENDAR_SCHEDULING_GUIDE.md`
- **SQL Schema:** `Database/create_calendar_scheduling_tables.sql`
- **PHP Class:** `Public/config/CalendarScheduler.php`

---

**Created:** October 16, 2025  
**Status:** Ready to Deploy  
**Compatibility:** Fully compatible with existing system
