# Holiday Type Display in Payroll Report

## Overview
Modified the payroll report to detect and display the specific type of holiday (Regular Holiday, Special Non-Working Holiday, or Special Working Holiday) instead of showing "P" when an employee works on a holiday.

## Changes Made

### 1. Database Changes

#### Added `holiday_type` Column to Cache Table
**File:** `Database/add_holiday_type_to_cache.sql`

The `employee_daily_schedule_cache` table now includes a `holiday_type` column to store the holiday type alongside the holiday name.

```sql
ALTER TABLE `employee_daily_schedule_cache` 
ADD COLUMN `holiday_type` ENUM('regular', 'special_non_working', 'special_working') DEFAULT NULL 
AFTER `holiday_name`;
```

**Migration Script:** `add_holiday_type_column.php`
- Run this script to add the column to the database
- It will also update existing cache records with holiday types from `company_holidays` table

### 2. Cache Rebuild Script

**File:** `Database/rebuild_schedule_cache.php`

Updated to fetch and store `holiday_type` when rebuilding the schedule cache:

**Changes:**
- Line 104: Now fetches `holiday_type` from `company_holidays` table
- Line 92-94: Updated INSERT statement to include `holiday_type` column
- Lines 118-129 & 136-148: Updated execute statements to include `$holidayType` parameter

### 3. Payroll Report Generator

**File:** `Public/controller/generate_payroll_report.php`

#### Updated Schedule Query (Line 114-129)
Added `holiday_type` to the SELECT statement to retrieve it from the cache:
```php
SELECT 
    schedule_date,
    employee_id,
    work_schedule_id,
    is_rest_day,
    is_holiday,
    schedule_name,
    time_in,
    time_out,
    holiday_name,
    holiday_type,  // NEW FIELD
    source
FROM employee_daily_schedule_cache
```

#### Updated Attendance Status Logic (Line 567-591)
Modified the holiday detection logic to show holiday type instead of "P":

**OLD Behavior:**
- Employee works on holiday → Shows "P" (Present)
- Employee doesn't work on holiday → Shows "HOL" (Holiday)

**NEW Behavior:**
- Employee works on **Regular Holiday** → Shows "RH" 
- Employee works on **Special Non-Working Holiday** → Shows "SNWH"
- Employee works on **Special Working Holiday** → Shows "SWH"
- Employee doesn't work on holiday → Shows "HOL" (unchanged)

#### Updated Legend (Line 854-863)
Added new holiday type indicators to the report legend:
- **RH** - Regular Holiday (Worked)
- **SNWH** - Special Non-Working Holiday (Worked)
- **SWH** - Special Working Holiday (Worked)
- **HOL** - Holiday (Not Worked)

## Holiday Types in Database

The `company_holidays` table contains three types of holidays:

1. **regular** - Regular holidays (e.g., New Year's Day, Independence Day)
2. **special_non_working** - Special non-working holidays (e.g., All Saints' Day)
3. **special_working** - Special working holidays (days where offices remain open)

## Installation Steps

### Step 1: Run Database Migration
1. Ensure MySQL/MariaDB is running
2. Execute the migration script:
   ```bash
   php add_holiday_type_column.php
   ```
   
   **OR** manually run the SQL:
   ```bash
   mysql -u root -p u816220874_calendartype < Database/add_holiday_type_to_cache.sql
   ```

### Step 2: Rebuild Schedule Cache
After adding the column, rebuild the schedule cache to populate `holiday_type`:
```bash
php Database/rebuild_schedule_cache.php
```

### Step 3: Test the Payroll Report
1. Navigate to the payroll report page
2. Generate a report for a date range that includes holidays
3. Verify that employees who worked on holidays show the correct holiday type (RH, SNWH, or SWH)
4. Verify that employees who didn't work on holidays still show "HOL"

## Example Output

**Before:**
```
Date       | Status
-----------|--------
2026-04-09 | P        (worked on Araw ng Kagitingan - Regular Holiday)
2026-11-01 | P        (worked on All Saints' Day - Special Non-Working)
```

**After:**
```
Date       | Status
-----------|--------
2026-04-09 | RH       (worked on Araw ng Kagitingan - Regular Holiday)
2026-11-01 | SNWH     (worked on All Saints' Day - Special Non-Working)
```

## Files Modified

1. ✅ `Database/add_holiday_type_to_cache.sql` - SQL migration script (NEW)
2. ✅ `add_holiday_type_column.php` - PHP migration script (NEW)
3. ✅ `Database/rebuild_schedule_cache.php` - Updated to fetch and store holiday_type
4. ✅ `Public/controller/generate_payroll_report.php` - Updated query and display logic

## Rollback Instructions

If you need to rollback these changes:

```sql
-- Remove holiday_type column from cache table
ALTER TABLE employee_daily_schedule_cache DROP COLUMN holiday_type;
```

Then revert the code changes in:
- `Database/rebuild_schedule_cache.php`
- `Public/controller/generate_payroll_report.php`

## Notes

- The holiday type is automatically populated when the schedule cache is rebuilt
- Existing cache entries will be updated with holiday types when you run the migration
- The legend in the payroll report now clearly explains each holiday type indicator
- This change does NOT affect the employee calendar view, only the payroll report
