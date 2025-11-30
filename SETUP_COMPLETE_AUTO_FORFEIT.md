# Quick Setup Guide - Auto-Forfeit Feature

## ✅ What Was Done

### 1. Database Schema Updated
The following ENUM columns were updated to include 'Forfeited'/'forfeited' status:
- `schedule_change_requests.status`
- `schedule_switch_requests.status`
- `month_weekly_schedule.status`

**Migration already run successfully!**

### 2. Files Created
- ✅ `Public/cron/auto_forfeit_expired_requests.php` - Main script
- ✅ `Public/cron/run_auto_forfeit.bat` - Windows task scheduler batch file
- ✅ `db/migrations/add_forfeited_status.sql` - Database migration
- ✅ `AUTO_FORFEIT_FEATURE.md` - Complete documentation

### 3. Files Modified
- ✅ `Public/views/schedule_request.php` - Auto-runs forfeit + UI updates

## 🎯 Current Status

### ✅ Verified Working
- Script executed successfully
- Forfeited 10 expired requests:
  - 7 schedule changes (end_date < today)
  - 3 swap requests (target_date < today)
  - 0 monthly schedules (no expired months found)
- Explanations automatically added to forfeited records
- Log file created at: `logs/forfeit_log.txt`

### ✅ Database Status
```
schedule_change_requests:
  - Forfeited: 7 requests

schedule_switch_requests:
  - Pending: 1 request
  - Approved: 6 requests
  - Forfeited: 3 requests

month_weekly_schedule:
  - No pending expired requests
```

## 📋 Next Steps

### Option 1: Automatic (Recommended)
**The script runs automatically every time an admin visits the schedule requests page.**
- No additional setup needed
- Real-time forfeit on page load
- ✅ Already implemented

### Option 2: Daily Scheduled Task (Optional)
If you want to run it daily at midnight instead of on-demand:

#### Windows Task Scheduler Setup
```powershell
# Run this in PowerShell as Administrator
$action = New-ScheduledTaskAction -Execute 'C:\xampp\htdocs\Timekeeping-system\Public\cron\run_auto_forfeit.bat' -WorkingDirectory 'C:\xampp\htdocs\Timekeeping-system\Public\cron'
$trigger = New-ScheduledTaskTrigger -Daily -At 12:01AM
$principal = New-ScheduledTaskPrincipal -UserId "SYSTEM" -LogonType ServiceAccount -RunLevel Highest
Register-ScheduledTask -TaskName "Auto-Forfeit Expired Requests" -Action $action -Trigger $trigger -Principal $principal -Description "Automatically forfeits pending schedule requests that have passed their date"
```

Or manually:
1. Open Task Scheduler (`taskschd.msc`)
2. Create Task:
   - **Name:** Auto-Forfeit Expired Requests
   - **Trigger:** Daily at 12:01 AM
   - **Action:** Run `C:\xampp\htdocs\Timekeeping-system\Public\cron\run_auto_forfeit.bat`

## 🧪 Testing

### Test the Script Manually
```batch
cd C:\xampp\htdocs\Timekeeping-system\Public\cron
"C:\xampp\php\php.exe" auto_forfeit_expired_requests.php
```

### Check Results
```batch
# View log
type C:\xampp\htdocs\Timekeeping-system\logs\forfeit_log.txt

# Check database
mysql -u root rss -e "SELECT status, COUNT(*) FROM schedule_change_requests GROUP BY status"
```

### Create Test Data
```sql
-- Insert a test request with past end_date
INSERT INTO schedule_change_requests 
(employee_id, start_date, end_date, work_schedule_id, reason, status) 
VALUES 
(1006, '2025-11-01', '2025-11-15', 4, 'Test expired request', 'Pending');

-- Run the script again to forfeit it
```

## 📊 Monitoring

### Check Log File
```batch
type C:\xampp\htdocs\Timekeeping-system\logs\forfeit_log.txt
```

**Latest Entry:**
```
[2025-11-30 14:09:47] Auto-forfeit executed: 7 schedule changes, 3 swaps, 0 monthly schedules. Total: 10 requests forfeited.
```

### View Forfeited Requests
```sql
-- Schedule changes
SELECT id, employee_id, start_date, end_date, status, explanation 
FROM schedule_change_requests 
WHERE status = 'Forfeited';

-- Swap requests
SELECT id, employee_id, source_date, target_date, status, admin_notes 
FROM schedule_switch_requests 
WHERE status = 'forfeited';

-- Monthly schedules
SELECT id, employee_id, year, month, status, admin_notes 
FROM month_weekly_schedule 
WHERE status = 'forfeited';
```

## 🎨 UI Changes

### Status Badge Colors
- **Pending:** Yellow background
- **Approved:** Green background
- **Rejected/Declined:** Red background
- **Forfeited:** Gray background (new)

### Where to See Changes
1. Visit: `http://localhost/Timekeeping-system/Public/views/schedule_request.php`
2. Check tabs:
   - Current Requests
   - Monthly Schedule
   - Swap Requests
   - History
3. Forfeited requests will show with gray badge
4. Pending count excludes forfeited requests

## ⚠️ Important Notes

1. **Forfeited ≠ Deleted**
   - Records remain in database for audit trail
   - Can be queried in History view
   - Includes auto-generated explanation

2. **Status is Final**
   - Once forfeited, status cannot be changed back to pending via UI
   - Admins would need to manually update database if restoration needed

3. **Automatic Execution**
   - Runs every time `schedule_request.php` is loaded
   - Also can run via scheduled task
   - Safe to run multiple times (idempotent)

## 📚 Documentation

Full documentation available in:
- `AUTO_FORFEIT_FEATURE.md` - Complete guide
- `db/migrations/add_forfeited_status.sql` - SQL changes
- `Public/cron/auto_forfeit_expired_requests.php` - Code comments

## ✅ Setup Complete!

The auto-forfeit feature is now **fully operational**. Expired pending requests will be automatically marked as "Forfeited" when admins view the schedule requests page.

**No further action required** unless you want to set up the optional daily scheduled task.
