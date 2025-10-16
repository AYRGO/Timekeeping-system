# Calendar-Type Scheduling System

## 📋 Overview

This system provides comprehensive calendar-based scheduling for employees without affecting the existing `schedule_change_requests` table. It supports:

- ✅ Daily schedule overrides
- ✅ Default weekly patterns (Mon-Sun)
- ✅ Rotating shift patterns
- ✅ Holiday calendar management
- ✅ Complete audit trail
- ✅ Integration with existing schedule change requests

---

## 🗄️ Database Structure

### Priority Order (Highest to Lowest):
1. **Daily Override** (`employee_daily_schedules`)
2. **Rotating Pattern** (`employee_rotating_schedules`)
3. **Default Weekly** (`employee_default_schedules`)
4. **Official Schedule** (`employees.official_sched`)
5. **Holiday** (`company_holidays`)

### Core Tables:

#### 1. `employee_daily_schedules`
Stores specific daily schedule assignments (overrides).

```sql
Fields:
- id (PK)
- employee_id (FK → employees)
- schedule_date
- work_schedule_id (FK → work_schedules)
- schedule_type (regular, override, rest_day, holiday, leave, overtime)
- is_rest_day
- is_holiday
- notes
- created_by
```

**Use Case:** When an employee needs a different schedule for specific date(s).

#### 2. `employee_default_schedules`
Default weekly schedule pattern for each day of the week.

```sql
Fields:
- id (PK)
- employee_id (FK → employees)
- day_of_week (0=Sunday, 1=Monday, ..., 6=Saturday)
- work_schedule_id (FK → work_schedules)
- is_rest_day
- effective_from / effective_until
```

**Use Case:** Employee works 8AM-5PM Mon-Fri, rest days on weekends.

#### 3. `company_holidays`
Company-wide holiday calendar.

```sql
Fields:
- id (PK)
- holiday_date
- holiday_name
- holiday_type (regular, special_non_working, special_working)
- is_recurring (1 = applies every year)
```

**Use Case:** Philippine holidays, company events.

#### 4. `schedule_override_history`
Audit trail of all schedule changes.

```sql
Fields:
- id (PK)
- employee_id
- schedule_date
- original_schedule_id
- new_schedule_id
- override_reason
- schedule_change_request_id (FK → schedule_change_requests)
- applied_by
```

**Use Case:** Track who changed what and when.

#### 5. `rotating_schedule_patterns`
Defines rotating shift patterns (e.g., 4-day cycles).

```sql
Fields:
- id (PK)
- pattern_name
- pattern_description
- cycle_length (number of days in rotation)
```

**Use Case:** Security guards with 4-day rotation: 2 morning, 1 evening, 1 rest.

#### 6. `rotating_schedule_pattern_days`
Details of each day in a rotating pattern.

```sql
Fields:
- id (PK)
- pattern_id (FK → rotating_schedule_patterns)
- day_number (1, 2, 3... in the cycle)
- work_schedule_id (FK → work_schedules)
- is_rest_day
```

#### 7. `employee_rotating_schedules`
Assigns employees to rotating patterns.

```sql
Fields:
- id (PK)
- employee_id (FK → employees)
- pattern_id (FK → rotating_schedule_patterns)
- start_date
- cycle_start_day (which day of pattern to start with)
- end_date
```

---

## 🚀 Installation

### Step 1: Run Database Setup

```bash
# Navigate to your browser
http://localhost/Timekeeping-system/Public/tools/setup_calendar_scheduling.php
```

This will:
- Create all 7 tables
- Add Philippine 2025 holidays
- Create sample rotating pattern
- Verify installation

### Step 2: Configure Database (Manual Alternative)

If you prefer to run SQL manually:

```bash
# Import via phpMyAdmin or command line
mysql -u your_username -p your_database < Database/create_calendar_scheduling_tables.sql
```

---

## 💻 Usage

### Using the CalendarScheduler Class

```php
require_once('config/CalendarScheduler.php');
$scheduler = new CalendarScheduler($pdo);
```

### 1. Get Employee Schedule for a Date

```php
$schedule = $scheduler->getEmployeeScheduleForDate($employee_id, '2025-10-20');

// Returns:
Array (
    [date] => 2025-10-20
    [employee_id] => 123
    [work_schedule_id] => 5
    [time_in] => 08:00:00
    [time_out] => 17:00:00
    [schedule_type] => regular
    [is_rest_day] => false
    [is_holiday] => false
    [source] => daily | rotating | default | official
    [notes] => ...
)
```

### 2. Set Daily Schedule Override

```php
// Override schedule for specific date
$scheduler->setDailySchedule(
    $employee_id,
    '2025-10-20',
    $work_schedule_id,  // e.g., 5 (from work_schedules table)
    'override',          // type: override, rest_day, holiday, etc.
    'Manager approved',  // notes
    $admin_id           // who made the change
);
```

### 3. Set Default Weekly Schedule

```php
// Set Monday's default schedule
$scheduler->setDefaultWeeklySchedule(
    $employee_id,
    1,                   // 1 = Monday (0=Sunday, 6=Saturday)
    5,                   // work_schedule_id
    0,                   // is_rest_day (0 = no, 1 = yes)
    '2025-01-01'        // effective_from
);

// Set Sunday as rest day
$scheduler->setDefaultWeeklySchedule(
    $employee_id,
    0,                   // 0 = Sunday
    null,                // no work schedule
    1,                   // is_rest_day = true
    '2025-01-01'
);
```

### 4. Apply Approved Schedule Change Request

```php
// When schedule_change_requests.status = 'approved'
$scheduler->applyScheduleChangeRequest(
    $request_id,
    $admin_id
);

// This will:
// 1. Get date range from request
// 2. Create daily overrides for each date
// 3. Record in schedule_override_history
// 4. Link to original request for audit trail
```

### 5. Get Monthly Calendar View

```php
$monthly = $scheduler->getMonthlySchedule($employee_id, 2025, 10);

// Returns array with 31 days:
Array (
    [2025-10-01] => [...schedule details...],
    [2025-10-02] => [...schedule details...],
    ...
    [2025-10-31] => [...schedule details...]
)
```

### 6. Delete Daily Override (Revert to Default)

```php
$scheduler->deleteDailySchedule($employee_id, '2025-10-20');

// Removes the daily override, schedule will fall back to:
// Rotating → Default Weekly → Official Schedule
```

---

## 🔄 Integration with Existing System

### How It Works with `schedule_change_requests`

**Before (Existing System):**
1. Employee submits schedule change request
2. Request stored in `schedule_change_requests` table
3. Admin approves/rejects
4. **NO automatic application to daily calendar**

**After (With New System):**
1. Employee submits schedule change request ✅ (same)
2. Request stored in `schedule_change_requests` ✅ (same)
3. Admin approves/rejects ✅ (same)
4. **NEW:** Admin can click "Apply to Calendar"
5. System automatically creates daily overrides
6. Links back to original request for audit

**Example Integration Code:**

```php
// In your schedule request approval page:
if ($_POST['action'] === 'approve_and_apply') {
    // Approve the request (existing code)
    $stmt = $pdo->prepare("UPDATE schedule_change_requests SET status = 'approved' WHERE id = ?");
    $stmt->execute([$request_id]);
    
    // NEW: Apply to calendar
    require_once('config/CalendarScheduler.php');
    $scheduler = new CalendarScheduler($pdo);
    $scheduler->applyScheduleChangeRequest($request_id, $_SESSION['admin_id']);
    
    echo "Request approved and applied to calendar!";
}
```

---

## 📊 Common Scenarios

### Scenario 1: Regular 9-to-5 Employee

```php
// Set default Mon-Fri schedule
for ($day = 1; $day <= 5; $day++) {
    $scheduler->setDefaultWeeklySchedule($employee_id, $day, 5, 0, '2025-01-01');
}

// Set weekend as rest days
$scheduler->setDefaultWeeklySchedule($employee_id, 0, null, 1, '2025-01-01'); // Sunday
$scheduler->setDefaultWeeklySchedule($employee_id, 6, null, 1, '2025-01-01'); // Saturday
```

### Scenario 2: Rotating Shift Worker

```php
// Create 4-day rotation pattern
$stmt = $pdo->prepare("INSERT INTO rotating_schedule_patterns (pattern_name, cycle_length) VALUES (?, ?)");
$stmt->execute(['Security 4-Day Cycle', 4]);
$pattern_id = $pdo->lastInsertId();

// Define pattern days
$pdo->exec("
    INSERT INTO rotating_schedule_pattern_days (pattern_id, day_number, work_schedule_id, is_rest_day) VALUES
    ($pattern_id, 1, 8, 0),  -- Day 1: Morning shift (6AM-3PM)
    ($pattern_id, 2, 8, 0),  -- Day 2: Morning shift
    ($pattern_id, 3, 19, 0), -- Day 3: Night shift (7PM-3AM)
    ($pattern_id, 4, NULL, 1) -- Day 4: Rest day
");

// Assign employee to pattern
$stmt = $pdo->prepare("
    INSERT INTO employee_rotating_schedules (employee_id, pattern_id, start_date, cycle_start_day)
    VALUES (?, ?, ?, ?)
");
$stmt->execute([$employee_id, $pattern_id, '2025-01-01', 1]);
```

### Scenario 3: One-Time Schedule Change

```php
// Employee needs to work different hours on specific day
$scheduler->setDailySchedule(
    $employee_id,
    '2025-10-25',
    7,  // Different schedule: 10AM-7PM
    'override',
    'Doctor appointment in morning',
    $admin_id
);
```

---

## 🔍 Querying Tips

### Get All Schedules for a Week

```php
$start_date = '2025-10-20';
$end_date = '2025-10-26';

$schedules = [];
$current = new DateTime($start_date);
$end = new DateTime($end_date);

while ($current <= $end) {
    $date = $current->format('Y-m-d');
    $schedules[$date] = $scheduler->getEmployeeScheduleForDate($employee_id, $date);
    $current->modify('+1 day');
}
```

### Find All Employees with Schedule on Specific Date

```php
$stmt = $pdo->prepare("
    SELECT e.id, e.fname, e.lname, eds.work_schedule_id, ws.time_in, ws.time_out
    FROM employees e
    LEFT JOIN employee_daily_schedules eds ON e.id = eds.employee_id AND eds.schedule_date = ?
    LEFT JOIN work_schedules ws ON eds.work_schedule_id = ws.id
    WHERE eds.is_rest_day = 0
");
$stmt->execute(['2025-10-20']);
$employees_scheduled = $stmt->fetchAll(PDO::FETCH_ASSOC);
```

### Find All Overrides for Employee

```php
$stmt = $pdo->prepare("
    SELECT schedule_date, work_schedule_id, schedule_type, notes
    FROM employee_daily_schedules
    WHERE employee_id = ?
      AND schedule_type = 'override'
      AND schedule_date >= CURDATE()
    ORDER BY schedule_date
");
$stmt->execute([$employee_id]);
$overrides = $stmt->fetchAll(PDO::FETCH_ASSOC);
```

---

## 📝 Notes

- **No Impact on Existing Data:** This system is completely separate from your existing `schedule_change_requests` table
- **Backward Compatible:** Your existing schedule request workflow continues to work
- **Optional Integration:** You can integrate gradually - existing requests still work
- **Audit Trail:** All changes are logged in `schedule_override_history`
- **Flexible:** Supports fixed, rotating, and override schedules simultaneously

---

## 🆘 Troubleshooting

### Issue: Schedule not showing up

**Check priority order:**
```php
$schedule = $scheduler->getEmployeeScheduleForDate($emp_id, $date);
echo "Source: " . $schedule['source']; // daily, rotating, default, official, none
```

### Issue: Rotating pattern not calculating correctly

**Verify pattern setup:**
```sql
SELECT * FROM rotating_schedule_patterns WHERE id = ?;
SELECT * FROM rotating_schedule_pattern_days WHERE pattern_id = ? ORDER BY day_number;
SELECT * FROM employee_rotating_schedules WHERE employee_id = ?;
```

### Issue: Need to reset an employee's schedule

```php
// Remove all daily overrides
$pdo->prepare("DELETE FROM employee_daily_schedules WHERE employee_id = ?")->execute([$emp_id]);

// Remove rotating assignment
$pdo->prepare("DELETE FROM employee_rotating_schedules WHERE employee_id = ?")->execute([$emp_id]);

// Remove default weekly
$pdo->prepare("DELETE FROM employee_default_schedules WHERE employee_id = ?")->execute([$emp_id]);
```

---

## 📞 Support

For questions or issues, refer to:
- Database schema: `Database/create_calendar_scheduling_tables.sql`
- PHP class: `Public/config/CalendarScheduler.php`
- Setup script: `Public/tools/setup_calendar_scheduling.php`

---

**Created:** October 16, 2025  
**Version:** 1.0  
**Compatible with:** Existing Timekeeping System
