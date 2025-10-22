# Automatic Schedule Change Processing System

## Overview
This system automatically applies approved schedule change requests to the employee calendar by creating entries in the `employee_daily_schedules` table.

## How It Works

### 1. Approval Flow
When an admin approves a schedule change request:
- The request moves from `schedule_change_requests` → `post_schedule_change_requests`
- Status changes to 'approved'
- The system marks it as needing calendar processing

### 2. Automatic Processing
The system processes approved requests in two ways:

#### A. Automatic (On Page Load)
- When an employee opens the Schedule view, the system automatically checks for approved requests
- Processes them silently in the background
- If any are processed, the calendar automatically reloads to show changes

#### B. Manual (Sync Button)
- Blue "Sync" button in the schedule calendar header
- Click to manually trigger processing of approved requests
- Shows confirmation dialog with results
- Reloads calendar to show updated schedules

### 3. What Gets Created
For each approved request with date range (start_date to end_date):
- Creates/updates entries in `employee_daily_schedules` table
- One entry per day in the date range
- Sets `actual_schedule_id` to the approved schedule
- Marks `schedule_source` as 'approved_request'
- Links back to original request with `request_id`

### 4. Database Changes

#### New Columns Added to `post_schedule_change_requests`:
```sql
processed_to_calendar TINYINT(1) DEFAULT 0
processed_at DATETIME NULL
```

These track which requests have already been processed to prevent duplicates.

## Files Created/Modified

### New Files:
1. **`/Public/controller/process_approved_schedule_changes.php`**
   - Standalone script for batch processing
   - Can be run via cron job or manually
   - Processes all unprocessed approved requests
   - Provides detailed console output

2. **`/Public/controller/ajax_process_schedules.php`**
   - AJAX endpoint for real-time processing
   - Called automatically when calendar loads
   - Called manually via Sync button
   - Returns JSON response

3. **`SCHEDULE_AUTO_PROCESSING_GUIDE.md`**
   - This documentation file

### Modified Files:
1. **`/Public/module/schedule_content.php`**
   - Added automatic processing on page load
   - Added manual "Sync" button
   - Added JavaScript processing functions

## Usage

### For Administrators:
1. Approve a schedule change request in the admin panel
2. The request moves to `post_schedule_change_requests` with status='approved'
3. Next time any employee opens their schedule calendar:
   - System automatically processes the approval
   - Calendar entries are created for the date range
   - Calendar reloads to show the new schedule

### For Employees:
1. Your calendar automatically stays in sync with approved requests
2. If you want to force check for new approvals, click the "Sync" button
3. Approved schedule changes appear as green schedule cards on your calendar

### Manual Processing:
If you need to process all approved requests immediately:

```bash
# Via command line
php /Public/controller/process_approved_schedule_changes.php

# Or via browser
http://your-domain/Public/controller/process_approved_schedule_changes.php
```

## How Schedule Priority Works

The calendar follows this priority order (highest to lowest):

1. **Daily Override** (employee_daily_schedules) - GREEN
   - Created by approved schedule change requests
   - Highest priority - overrides everything

2. **Weekly Default** (employee_default_schedules) - GREEN
   - Employee's standard weekly schedule pattern

3. **Holiday** - YELLOW
   - Company holidays take precedence over normal schedules

4. **Weekend** - GRAY
   - Saturday/Sunday default rest days

## Troubleshooting

### Request Not Showing in Calendar?
1. Check if status is 'approved' in `post_schedule_change_requests`
2. Click the "Sync" button to force processing
3. Check browser console for error messages
4. Verify `work_schedule_id`, `start_date`, and `end_date` are not NULL

### Duplicate Entries?
- System automatically updates existing entries instead of creating duplicates
- Each request is marked as processed to prevent re-processing

### Need to Reprocess a Request?
```sql
UPDATE post_schedule_change_requests 
SET processed_to_calendar = 0, processed_at = NULL
WHERE id = [request_id];
```

Then click Sync or reload the schedule page.

## Database Schema

### employee_daily_schedules
```sql
CREATE TABLE IF NOT EXISTS employee_daily_schedules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    schedule_date DATE NOT NULL,
    actual_schedule_id INT NULL,
    is_rest_day TINYINT(1) DEFAULT 0,
    schedule_source VARCHAR(50) DEFAULT 'approved_request',
    request_id INT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL,
    FOREIGN KEY (employee_id) REFERENCES employees(id),
    FOREIGN KEY (actual_schedule_id) REFERENCES work_schedules(id),
    UNIQUE KEY unique_employee_date (employee_id, schedule_date)
);
```

## Benefits

✅ **Automatic** - No manual intervention needed
✅ **Real-time** - Changes reflect immediately
✅ **Reliable** - Transaction-based processing prevents partial updates
✅ **Traceable** - Links back to original request
✅ **Efficient** - Processes only unprocessed requests
✅ **Safe** - Updates existing entries instead of creating duplicates

## Future Enhancements

Potential improvements:
- Email notifications when schedules are updated
- Bulk approval with batch processing
- Schedule conflict detection
- Automatic expiration of temporary schedule changes
- Schedule change history/audit trail
