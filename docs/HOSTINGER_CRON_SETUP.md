# Hostinger Cron Job Setup Guide for Monthly Leave Accrual

## Overview
The monthly leave accrual script (`Public/cron/monthly_leave_credit.php`) needs to be scheduled to run automatically at the end of each month on your Hostinger hosting.

## Hostinger Cron Job Configuration

### Step 1: Access Hostinger Control Panel
1. Log into your Hostinger control panel
2. Navigate to **Advanced** → **Cron Jobs**
3. Click **Create Cron Job**

### Step 2: Configure Monthly Cron Job
Set up the cron job with the following configuration:

**Cron Expression (Run on last day of each month at 11:30 PM):**
```
30 23 28-31 * *
```

**Command:**
```bash
php /home/u123456789/domains/yourdomain.com/public_html/Public/cron/monthly_leave_credit.php
```

**Alternative command format (if the above doesn't work):**
```bash
/usr/bin/php /home/u123456789/domains/yourdomain.com/public_html/Public/cron/monthly_leave_credit.php
```

### Step 3: Verify Path Structure
Replace the path components with your actual Hostinger values:
- `u123456789` → Your actual Hostinger username
- `yourdomain.com` → Your actual domain name
- Ensure the path matches your file structure

### Step 4: Test the Cron Job
To test if the setup works:
1. Set a temporary cron to run every 5 minutes: `*/5 * * * *`
2. Use the same command path
3. Check if it executes properly
4. Once confirmed, change back to monthly schedule

## Alternative Scheduling Options

### Option 1: More Conservative (Every Month on 1st at Midnight)
```
0 0 1 * *
```
This runs on the 1st of each month, processing accruals for the previous month.

### Option 2: End of Month (More Precise)
```
0 0 L * *
```
Some systems support `L` for "last day of month"

### Option 3: Multiple Attempts (Recommended)
Set up 4 cron jobs for the last 4 days of each month:
```
30 23 28 * * php /path/to/monthly_leave_credit.php
30 23 29 * * php /path/to/monthly_leave_credit.php  
30 23 30 * * php /path/to/monthly_leave_credit.php
30 23 31 * * php /path/to/monthly_leave_credit.php
```

The script is designed to be safe to run multiple times - it won't double-process the same month.

## Monitoring and Logs

### Check Execution
After setup, monitor the cron job execution:
1. Check Hostinger cron job logs in the control panel
2. Look for email notifications (if configured)
3. Verify leave balances are updated in your database

### Email Notifications
To receive email notifications when the script runs, add this to your cron command:
```bash
php /path/to/monthly_leave_credit.php > /path/to/logs/leave_accrual.log 2>&1 && mail -s "Leave Accrual Completed" youremail@domain.com < /path/to/logs/leave_accrual.log
```

## Troubleshooting

### Common Issues:
1. **Permission Errors**: Ensure the PHP file has execute permissions (755)
2. **Path Issues**: Use absolute paths, not relative ones
3. **PHP Version**: Ensure Hostinger uses compatible PHP version
4. **Database Connection**: Verify the database connection works from cron context

### Manual Testing:
You can manually trigger the script via SSH (if available) or create a web-accessible test file:

```php
<?php
// test_cron.php - Remove after testing
if ($_GET['secret'] === 'your_secret_key_here') {
    include('Public/cron/monthly_leave_credit.php');
} else {
    echo 'Access denied';
}
?>
```

Access: `https://yourdomain.com/test_cron.php?secret=your_secret_key_here`

## Security Notes
- Never expose cron scripts to public web access without authentication
- Use absolute paths in cron commands
- Monitor logs for any errors or security issues
- Consider IP restrictions if creating web-accessible test files

## Final Configuration Summary

**Recommended Cron Job Settings:**
- **When**: 30 23 28-31 * * (11:30 PM on days 28-31 of each month)
- **Command**: `/usr/bin/php /home/[username]/domains/[domain]/public_html/Public/cron/monthly_leave_credit.php`
- **Email Notifications**: Enable to get notified of execution

This setup ensures your employees automatically receive their monthly leave accruals:
- **Vacation Leave**: 1.25 days per month
- **Sick Leave**: 0.42 days per month

The system will process all 87 active employees and provide detailed logging of the accrual process.