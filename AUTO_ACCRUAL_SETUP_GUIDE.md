# Auto-Accrual System Setup Guide

## Overview
This system allows you to enable/disable automatic monthly leave accrual processing with a simple toggle switch.

## Features
- ✅ **One-click toggle** ON/OFF on admin dashboard
- ✅ **Automatic processing** on the last day of each month
- ✅ **Visual feedback** - Shows current status (ON/OFF)
- ✅ **Smart tracking** - Prevents duplicate processing
- ✅ **Logging** - All activity is logged for audit purposes

## Setup Instructions

### Step 1: Create the Database Table

Run this SQL in your database:

```sql
-- Run: create_system_settings_table.sql
CREATE TABLE IF NOT EXISTS `system_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL UNIQUE,
  `setting_value` text,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `system_settings` (`setting_key`, `setting_value`) 
VALUES ('auto_accrual_enabled', '0')
ON DUPLICATE KEY UPDATE setting_key = setting_key;

INSERT INTO `system_settings` (`setting_key`, `setting_value`) 
VALUES ('last_auto_accrual_month', '0')
ON DUPLICATE KEY UPDATE setting_key = setting_key;
```

### Step 2: Set Up Daily Cron Job

#### For Windows (Task Scheduler):

1. Open **Task Scheduler**
2. Create new task:
   - **Name**: Leave Accrual Daily Check
   - **Trigger**: Daily at 11:00 PM
   - **Action**: Start a program
   - **Program**: `C:\xampp\php\php.exe`
   - **Arguments**: `C:\xampp\htdocs\Timekeeping-system\Public\cron\auto_accrual_cron.php`

#### For Linux/Unix (Crontab):

```bash
# Edit crontab
crontab -e

# Add this line (runs daily at 11 PM)
0 23 * * * /usr/bin/php /path/to/Timekeeping-system/Public/cron/auto_accrual_cron.php

# Save and exit
```

## How to Use

### Enable Auto-Accrual

1. Go to **Admin Dashboard** (`admin_homepage.php`)
2. Find the reminder banner (shows when accrual is needed)
3. Click the **toggle switch** to turn it **ON** (green)
4. You'll see a success message
5. That's it! The system will now automatically process accruals on the last day of each month

### Disable Auto-Accrual

1. Go to **Admin Dashboard**
2. Click the **toggle switch** to turn it **OFF** (gray)
3. You'll need to process accruals manually again

### Manual Processing (when auto-accrual is OFF)

- Click "Process Manually Now" button in the reminder banner
- Or go to `/Public/module/leave_accrual_manager.php`
- Or go to any employee's Leave Credits tab and click the button

## How It Works

### Auto-Accrual Flow:

1. **Daily Check** - Cron job runs every day at 11 PM
2. **Status Check** - Is auto-accrual enabled?
3. **Date Check** - Is it the last day of the month?
4. **Duplicate Check** - Has this month been processed already?
5. **Process** - Add 1.25 VL + 0.42 SL to all active employees
6. **Mark Complete** - Update last processed month
7. **Log** - Record all activity in `/logs/auto_accrual.log`

### Banner Behavior:

| Condition | Banner Shown |
|-----------|--------------|
| Auto-accrual OFF + Needs processing | Orange reminder with toggle |
| Auto-accrual ON + Needs processing | Orange banner showing "Auto-enabled" |
| Already processed this month | Green success message |
| Not time yet | No banner |

## File Structure

```
/Public
  /cron
    auto_accrual_cron.php          # Daily check script
  /module
    process_monthly_leave_accrual.php  # Manual processing
    toggle_auto_accrual.php        # Toggle ON/OFF handler
    leave_accrual_manager.php      # Admin dashboard
  /views
    admin_homepage.php             # Main dashboard with toggle
  /logs
    auto_accrual.log              # Activity log (auto-created)

/create_system_settings_table.sql  # Database migration
```

## Troubleshooting

### Toggle not working?
- Check browser console for errors
- Verify `system_settings` table exists
- Check file permissions on `toggle_auto_accrual.php`

### Cron job not running?
- Check cron is properly configured
- Verify PHP path is correct
- Check `/logs/auto_accrual.log` for errors
- Test manually: `php /path/to/auto_accrual_cron.php`

### Not processing on last day?
- Check if auto-accrual is enabled (toggle ON)
- Verify cron job is running (check system logs)
- Check `/logs/auto_accrual.log` for details
- Ensure employees have `status = 'active'`

## Testing

### Test the Toggle:
1. Go to admin dashboard
2. Click toggle ON
3. Check database: `SELECT * FROM system_settings WHERE setting_key = 'auto_accrual_enabled'`
4. Should show `setting_value = '1'`

### Test the Cron:
```bash
# Run manually
php /path/to/auto_accrual_cron.php

# Check log
cat /path/to/logs/auto_accrual.log
```

### Test End-of-Month Processing:
1. Temporarily modify cron script to skip date check
2. Run manually
3. Verify leave credits updated
4. Restore date check

## Maintenance

### View Logs:
```bash
# View last 50 lines
tail -n 50 /path/to/logs/auto_accrual.log

# Watch in real-time
tail -f /path/to/logs/auto_accrual.log
```

### Clear Logs:
```bash
# Clear log file
> /path/to/logs/auto_accrual.log
```

### Reset for Testing:
```sql
-- Reset last processed month
UPDATE system_settings SET setting_value = '0' WHERE setting_key = 'last_auto_accrual_month';

-- Check current status
SELECT * FROM system_settings;
```

## FAQ

**Q: Can I change the accrual amounts?**  
A: Yes! Edit the `$leaveRates` array in `auto_accrual_cron.php` and `process_monthly_leave_accrual.php`

**Q: Can I change when it runs?**  
A: Yes! Modify the cron schedule. Default is last day of month at 11 PM.

**Q: What if I forget to enable it?**  
A: The reminder banner will show on your dashboard when accrual is needed.

**Q: Can I manually process even when auto-accrual is ON?**  
A: Yes! You can always process manually if needed.

**Q: Will it process twice if I manually process then auto-accrual runs?**  
A: No! The system checks if the current month has been processed and skips if already done.

## Support

For issues or questions:
1. Check `/logs/auto_accrual.log`
2. Verify database `system_settings` table
3. Test cron job manually
4. Check file permissions

---

**Last Updated**: November 3, 2025  
**Version**: 1.0
