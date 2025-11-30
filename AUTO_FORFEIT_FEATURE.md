# Auto-Forfeit Expired Pending Requests

## Overview
This feature automatically marks pending requests as **"Forfeited"** when they pass the present day without admin action. This prevents outdated requests from cluttering the system and ensures only current/future requests remain in pending status.

## Affected Tables & Logic

### 1. Schedule Change Requests (`schedule_change_requests`)
- **Forfeit Condition:** `end_date < TODAY` AND `status = 'Pending'`
- **Action:** Status changed to 'Forfeited'
- **Note Added:** Auto-generated explanation noting the expiration date

### 2. Schedule Swap Requests (`schedule_switch_requests`)
- **Forfeit Condition:** `target_date < TODAY` AND `status = 'pending'`
- **Action:** Status changed to 'forfeited'
- **Note Added:** Admin note explaining auto-forfeit with the swap date

### 3. Monthly Schedule Requests (`month_weekly_schedule`)
- **Forfeit Condition:** Last day of `year-month` < TODAY AND `status = 'pending'`
- **Action:** Status changed to 'forfeited'
- **Note Added:** Admin note indicating the month has ended
- **Logic:** Uses `LAST_DAY(CONCAT(year, '-', LPAD(month, 2, '0'), '-01'))` to calculate month end

## Implementation Files

### Core Scripts
1. **`Public/cron/auto_forfeit_expired_requests.php`**
   - Main forfeit logic
   - Runs UPDATE queries on all three tables
   - Logs results to `logs/forfeit_log.txt`
   - Can be called programmatically or via cron

2. **`Public/cron/run_auto_forfeit.bat`**
   - Windows batch file for Task Scheduler
   - Executes PHP script and logs execution time

### Database Migration
3. **`db/migrations/add_forfeited_status.sql`**
   - Adds 'Forfeited'/'forfeited' to ENUM status columns
   - Creates performance indexes on date columns
   - **MUST BE RUN FIRST** before using the feature

### UI Integration
4. **`Public/views/schedule_request.php`**
   - Auto-executes forfeit script on page load
   - Excludes forfeited requests from pending counts
   - Displays forfeited status with gray badge styling
   - Updated summary statistics to include forfeited count

## Setup Instructions

### Step 1: Update Database Schema
```sql
-- Run the migration script
mysql -u your_username -p your_database < db/migrations/add_forfeited_status.sql
```

Or run in phpMyAdmin:
```sql
ALTER TABLE `schedule_change_requests` 
MODIFY COLUMN `status` ENUM('Pending','Approved','Declined','Rejected','Forfeited') DEFAULT 'Pending';

ALTER TABLE `schedule_switch_requests` 
MODIFY COLUMN `status` ENUM('pending','approved','rejected','cancelled','forfeited') DEFAULT 'pending';

ALTER TABLE `month_weekly_schedule` 
MODIFY COLUMN `status` ENUM('pending','approved','rejected','cancelled','forfeited') DEFAULT 'pending';
```

### Step 2: Test Manual Execution
```bash
# Navigate to cron directory
cd C:\xampp\htdocs\Timekeeping-system\Public\cron

# Run the script manually
php auto_forfeit_expired_requests.php

# Check the log
type ..\..\logs\forfeit_log.txt
```

### Step 3: Set Up Automated Daily Run (Windows Task Scheduler)

**Option A: Via GUI**
1. Open Task Scheduler (`taskschd.msc`)
2. Create New Task:
   - **Name:** Auto-Forfeit Expired Requests
   - **Trigger:** Daily at 12:01 AM
   - **Action:** Start a program
   - **Program:** `C:\xampp\htdocs\Timekeeping-system\Public\cron\run_auto_forfeit.bat`
   - **Start in:** `C:\xampp\htdocs\Timekeeping-system\Public\cron`
3. Save and test

**Option B: Via PowerShell Command**
```powershell
$action = New-ScheduledTaskAction -Execute 'C:\xampp\htdocs\Timekeeping-system\Public\cron\run_auto_forfeit.bat' -WorkingDirectory 'C:\xampp\htdocs\Timekeeping-system\Public\cron'
$trigger = New-ScheduledTaskTrigger -Daily -At 12:01AM
$principal = New-ScheduledTaskPrincipal -UserId "SYSTEM" -LogonType ServiceAccount -RunLevel Highest
Register-ScheduledTask -TaskName "Auto-Forfeit Expired Requests" -Action $action -Trigger $trigger -Principal $principal -Description "Automatically forfeits pending schedule requests that have passed their date"
```

### Step 4: Verify Automatic Execution
The script now runs automatically on every page load of `schedule_request.php`, ensuring requests are forfeited as soon as admins view the page.

## Visual Indicators

### Status Badge Colors
- **Pending:** Yellow (`bg-yellow-100 text-yellow-800`)
- **Approved:** Green (`bg-green-100 text-green-800`)
- **Rejected/Declined:** Red (`bg-red-100 text-red-800`)
- **Forfeited:** Gray (`bg-gray-100 text-gray-600`) ← NEW

### Summary Statistics
All summary cards now include forfeited count:
- Total Requests: All statuses
- Pending: Active pending only (excludes forfeited)
- Approved: Successfully approved
- Rejected: Includes rejected, cancelled, and forfeited

## Logging

### Log File Location
`logs/forfeit_log.txt`

### Log Format
```
[2025-11-30 12:01:00] Auto-forfeit executed: 3 schedule changes, 1 swaps, 2 monthly schedules. Total: 6 requests forfeited.
[2025-12-01 12:01:00] Auto-forfeit executed: 0 schedule changes, 0 swaps, 0 monthly schedules. Total: 0 requests forfeited.
```

### Error Logging
Errors are also written to the same log file:
```
[2025-11-30 12:05:00] ERROR in auto-forfeit: SQLSTATE[42S02]: Base table or view not found
```

## Testing Checklist

### Pre-Production Testing
- [ ] Run `add_forfeited_status.sql` on test database
- [ ] Create test pending requests with past dates:
  - [ ] Schedule change request with `end_date` = yesterday
  - [ ] Swap request with `target_date` = last week
  - [ ] Monthly request for last month
- [ ] Execute `auto_forfeit_expired_requests.php`
- [ ] Verify all three test requests now show status = 'Forfeited'/'forfeited'
- [ ] Check `logs/forfeit_log.txt` for confirmation
- [ ] Load `schedule_request.php` and verify:
  - [ ] Forfeited requests excluded from pending count
  - [ ] Gray "Forfeited" badge displays correctly
  - [ ] Summary cards show forfeited count

### Production Deployment
1. Backup database
2. Run migration SQL
3. Deploy new PHP files
4. Test manual execution
5. Set up scheduled task
6. Monitor logs for 1 week

## Benefits

### For Administrators
- ✅ Clean pending queue (only relevant requests)
- ✅ No manual cleanup needed
- ✅ Clear audit trail (forfeited vs manually declined)
- ✅ Automatic enforcement of deadlines

### For System Performance
- ✅ Faster queries (fewer pending records to scan)
- ✅ Better database indexes usage
- ✅ Reduced admin workload

### For Data Integrity
- ✅ Prevents accidental approval of expired requests
- ✅ Maintains historical record (forfeited, not deleted)
- ✅ Consistent status across all request types

## Troubleshooting

### Forfeits Not Happening
1. Check database schema: `SHOW COLUMNS FROM schedule_change_requests LIKE 'status';`
2. Verify 'Forfeited' is in ENUM values
3. Check PHP error logs: `tail -f C:\xampp\apache\logs\error.log`
4. Manually run script and check for errors

### Scheduled Task Not Running
1. Task Scheduler → Right-click task → Run
2. Check "Last Run Result" (0x0 = success)
3. Verify PHP path in batch file
4. Check Windows Event Viewer for task scheduler errors

### Log File Not Created
1. Ensure `logs/` directory exists
2. Check write permissions on directory
3. Script will auto-create directory if missing

## Future Enhancements

- [ ] Email notifications to employees when request is forfeited
- [ ] Dashboard widget showing forfeited requests count
- [ ] Admin setting to configure grace period (e.g., forfeit 1 day after expiry)
- [ ] Restore forfeited request feature (change back to pending)
- [ ] Bulk forfeit action in admin UI

## Related Files

### Modified
- `Public/views/schedule_request.php` - UI integration
- `Public/views/process_schedule_action.php` - May need status handling update
- `Public/views/process_switch_action.php` - May need status handling update
- `Public/views/process_monthly_schedule_action.php` - May need status handling update

### Created
- `Public/cron/auto_forfeit_expired_requests.php` - Main logic
- `Public/cron/run_auto_forfeit.bat` - Task runner
- `db/migrations/add_forfeited_status.sql` - Schema update
- `AUTO_FORFEIT_FEATURE.md` - This documentation

## Support

For issues or questions about this feature:
1. Check logs in `logs/forfeit_log.txt`
2. Review recent database changes
3. Verify all migration steps completed
4. Test with sample expired request

---

**Last Updated:** November 30, 2025  
**Version:** 1.0  
**Status:** Production Ready
