# Schedule Management System - Implementation Guide

## Overview
This document explains the new schedule management functionality added to your timekeeping system.

## What Was Added

### 1. Database Setup (SQL Commands)
Run these commands in your localhost MySQL/phpMyAdmin:

```sql
-- Add the missing schedules (19-22) to work_schedules table
INSERT INTO `work_schedules` (`id`, `name`, `time_in`, `time_out`, `employee_id`, `day_of_week`, `full_text`) VALUES
(19, 'Night Shift 1', '19:00:00', '03:00:00', NULL, NULL, NULL),
(20, 'Night Shift 2', '19:00:00', '04:30:00', NULL, NULL, NULL),
(21, 'Evening Shift 1', '17:00:00', '02:00:00', NULL, NULL, NULL),
(22, 'Evening Shift 2', '17:30:00', '02:00:00', NULL, NULL, NULL);
```

### 2. New "Manage Schedules" Tab in Employee Edit Page

#### Location
`Public/views/employee-edit.php`

#### Features Added

**A. View All Schedules**
- Displays all schedules from `work_schedules` table in a table format
- Shows: ID, Name, Time In, Time Out, Duration, and number of employees using it
- Real-time calculation of schedule duration

**B. Add New Schedule**
- Click "Add New Schedule" button to show form
- Fill in:
  - Schedule Name (optional)
  - Time In (required)
  - Time Out (required)
- Submit to add to database

**C. Edit Existing Schedule**
- Click "Edit" button on any schedule row
- Inline editing for Name, Time In, and Time Out
- Click "Save" to update or "Cancel" to discard changes

**D. Delete Schedule**
- Click "Delete" button on schedules not in use
- Protected: Cannot delete schedules currently assigned to employees
- Confirmation prompt before deletion

**E. Statistics Dashboard**
- Total number of schedules
- Number of schedules in use
- Number of available schedules

### 3. Backend Handlers Added

The following POST handlers were added to `employee-edit.php`:

#### Add Schedule Handler
```php
if (isset($_POST['action']) && $_POST['action'] === 'add_schedule')
```
- Inserts new schedule into `work_schedules` table
- Validates required fields (time_in, time_out)

#### Edit Schedule Handler
```php
if (isset($_POST['action']) && $_POST['action'] === 'edit_schedule')
```
- Updates existing schedule in database
- Validates schedule ID exists

#### Delete Schedule Handler
```php
if (isset($_POST['action']) && $_POST['action'] === 'delete_schedule')
```
- Checks if schedule is in use by any employee
- Prevents deletion if in use
- Deletes schedule if not in use

### 4. Schedule Helper Functions (Optional Enhancement)

Created: `Public/config/schedule_helper.php`

This file provides reusable functions to work with schedules:

#### Available Functions:

**`getAllSchedules($pdo, $format = '24h')`**
- Returns all schedules as an array
- Supports both 24-hour and 12-hour (AM/PM) formats
- Usage:
  ```php
  include('config/schedule_helper.php');
  $schedules = getAllSchedules($pdo, '12h');
  ```

**`getScheduleById($pdo, $scheduleId, $format = '24h')`**
- Get a specific schedule by ID
- Returns schedule details or null if not found

**`getScheduleTimes($pdo, $scheduleId)`**
- Returns schedule times in both formats
- Useful for display purposes

**`getScheduleDuration($pdo, $scheduleId)`**
- Calculates working hours as decimal
- Handles overnight shifts automatically

**`scheduleExists($pdo, $scheduleId)`**
- Check if a schedule ID exists
- Returns boolean

**`getScheduleUsageCount($pdo, $scheduleId)`**
- Count how many employees use this schedule
- Useful for validation before deletion

### 5. JavaScript Functions Added

**`toggleAddScheduleForm()`**
- Shows/hides the add schedule form

**`editSchedule(scheduleId)`**
- Enables inline editing for a schedule row

**`cancelEdit(scheduleId)`**
- Cancels editing and restores display mode

**`saveSchedule(scheduleId)`**
- Submits edited schedule data to backend

**`deleteSchedule(scheduleId)`**
- Confirms and deletes a schedule

## How to Use

### For Admins - Adding a New Schedule

1. Navigate to any employee's edit page
2. Click on "Manage Schedules" tab (last tab)
3. Click "Add New Schedule" button (green button at top)
4. Fill in the form:
   - **Schedule Name**: Optional friendly name (e.g., "Night Shift", "Flex Hours")
   - **Time In**: Required - Use time picker
   - **Time Out**: Required - Use time picker
5. Click "Save Schedule"
6. The new schedule will appear in the table with a unique ID

### For Admins - Editing a Schedule

1. Go to "Manage Schedules" tab
2. Find the schedule you want to edit
3. Click "Edit" button in the Actions column
4. Modify the Name, Time In, or Time Out fields (they become editable)
5. Click "Save" to confirm changes or "Cancel" to discard

### For Admins - Deleting a Schedule

1. Go to "Manage Schedules" tab
2. Find the schedule you want to delete
3. Check the "Employees Using" column:
   - **0 employees**: Delete button is enabled
   - **1+ employees**: Delete is locked (shows lock icon)
4. Click "Delete" button
5. Confirm deletion in the popup
6. Schedule is permanently removed

**Note**: You cannot delete schedules that are currently assigned to employees. You must first reassign those employees to different schedules.

### For Developers - Using Schedule Helper Functions

Instead of hardcoding schedule arrays, you can now use the helper functions:

**Before (Old Way):**
```php
$schedule_times = [
    1 => ['in' => '06:30:00', 'out' => '15:30:00'],
    2 => ['in' => '08:00:00', 'out' => '19:00:00'],
    // ... etc
];
```

**After (New Way):**
```php
include('config/schedule_helper.php');
$schedule_times = getAllSchedules($pdo, '24h'); // or '12h' for AM/PM format
```

## Database Schema

### work_schedules Table Structure
```sql
CREATE TABLE `work_schedules` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL,
  `time_in` time DEFAULT NULL,
  `time_out` time DEFAULT NULL,
  `employee_id` int(11) DEFAULT NULL,
  `day_of_week` enum('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday') DEFAULT NULL,
  `full_text` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

## Security Features

1. **Validation**: All inputs are validated before database operations
2. **SQL Injection Protection**: Uses prepared statements with PDO
3. **Delete Protection**: Prevents deletion of schedules in active use
4. **Confirmation Dialogs**: Requires confirmation for destructive actions
5. **XSS Protection**: All output is escaped with `htmlspecialchars()`

## Future Improvements (Optional)

1. **Bulk Import**: Allow CSV import of multiple schedules
2. **Schedule Templates**: Save common schedule patterns
3. **Schedule History**: Track changes to schedules over time
4. **Schedule Cloning**: Duplicate existing schedules quickly
5. **Advanced Filtering**: Filter schedules by duration, shift type, etc.

## Troubleshooting

### Issue: Schedules not showing in dropdown
**Solution**: Ensure the schedule exists in `work_schedules` table and has valid time_in/time_out values

### Issue: Cannot delete a schedule
**Solution**: Check if employees are assigned to it. Reassign them first, then delete.

### Issue: Time format looks wrong
**Solution**: Adjust the format parameter in helper functions ('24h' or '12h')

### Issue: New schedules get wrong ID
**Solution**: Check AUTO_INCREMENT value in work_schedules table. May need to reset it.

## Files Modified

1. `Public/views/employee-edit.php` - Main file with new tab and handlers
2. `Public/config/schedule_helper.php` - New helper file (optional but recommended)

## Files That Should Eventually Be Updated (Future Work)

These files still use hardcoded schedule arrays and could benefit from using the helper functions:

1. `Public/module/stats/schedule_tracker.php`
2. `Public/module/attendance-history.php`
3. `Public/Bugardi/Admin_dashboard.php`
4. `Public/controller/generate_attendance_report.php`
5. `Public/controller/generate_payroll_report.php`

## Summary

You now have a complete schedule management system where admins can:
- ✅ Add schedules dynamically without touching code
- ✅ Edit existing schedules easily
- ✅ Delete unused schedules safely
- ✅ View schedule statistics and usage
- ✅ No need to manually update database or edit PHP arrays

All schedule data is now centralized in the database, making the system more maintainable and flexible!
