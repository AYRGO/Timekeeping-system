# Manual Leave Accrual System

## Overview
This system replaces unreliable cron jobs with a manual button-triggered leave accrual process that administrators can run at the end of each month.

## Features

### Accrual Rates
- **Sick Leave**: +0.42 days per month (5 days per year)
- **Vacation Leave**: +1.25 days per month (15 days per year)

### Balance Limits
- Maximum balance: 15 days
- Vacation Leave carry-over: Up to 5 days (when balance is full)

## How to Use

### Method 1: From Leave Credits Page (Employee View)
1. Navigate to any employee's "Leave Credits" tab
2. If you're logged in as admin, you'll see a green "Process Monthly Accrual" button
3. Click the button and confirm the action
4. View the detailed results modal showing all updates

### Method 2: From Leave Accrual Manager (Admin Dashboard)
1. Navigate to `/Public/module/leave_accrual_manager.php`
2. View the dashboard with:
   - Current month statistics
   - Total active employees
   - Last accrual date
   - Recent activity log
3. Click the big "Process Now" button
4. Confirm and view detailed results

## Files Created/Modified

### New Files
1. **`/Public/module/process_monthly_leave_accrual.php`**
   - Backend AJAX endpoint
   - Processes accrual for all active employees
   - Returns JSON with detailed results

2. **`/Public/module/leave_accrual_manager.php`**
   - Admin dashboard for leave accrual management
   - Shows statistics and recent activity
   - Provides centralized accrual processing

### Modified Files
1. **`/Public/module/leave_credits.php`**
   - Added "Process Monthly Accrual" button (admin only)
   - Added JavaScript function to trigger accrual
   - Added results modal display

## Process Details

### What Happens When You Click "Process Monthly Accrual"

1. **Validation**
   - Checks if user is admin
   - Gets all active employees

2. **For Each Employee**
   - Checks existing leave credit records
   - Calculates new balance with limits:
     - If balance < 15: Add to balance
     - If balance = 15 (Vacation only): Add to carry-over (max 5)
   - Updates or creates leave credit record

3. **Results**
   - Shows summary statistics
   - Displays detailed table of all updates
   - Lists any errors encountered

## Database Schema

The `leave_credits` table should have these columns:
```sql
- id (INT, Primary Key)
- employee_id (INT, Foreign Key)
- leave_type (VARCHAR: 'sick', 'vacation', etc.)
- balance (DECIMAL)
- carry_over (DECIMAL, nullable)
- monthly_increment (DECIMAL)
- year (INT)
- updated_at (TIMESTAMP)
```

## Best Practices

### When to Run
- **Last week of each month** (day 24-31)
- The system includes a warning banner during this period
- Can be run any time, but typically done monthly

### Safety Features
- Confirmation dialog before processing
- No duplicate processing (checks last update date)
- Detailed logging of all changes
- Error handling with detailed error messages
- Transaction-safe processing

### Verification
After running accrual:
1. Check the results modal for any errors
2. Verify employee balances in the leave credits view
3. Review the recent activity log in the manager dashboard

## Troubleshooting

### "Unauthorized access" error
- Make sure you're logged in as an admin
- Check `$_SESSION['employee']['role']` is set to 'admin'

### Accrual not showing immediately
- Clear browser cache
- Refresh the page after closing the results modal
- Check the database directly: `SELECT * FROM leave_credits WHERE updated_at > NOW() - INTERVAL 1 HOUR`

### Some employees not processed
- Check if they have `status = 'active'` in employees table
- Review error messages in the results modal
- Check database logs

## Migration from Cron Jobs

### Old System (Deprecated)
- `/Public/cron/hostinger_monthly_accrual.php`
- Required cron job configuration
- Unreliable execution
- No immediate feedback

### New System (Current)
- Manual button trigger
- Immediate execution
- Real-time feedback
- Full control over timing

### To Disable Old Cron Jobs
If you have existing cron jobs, disable them:
```bash
# SSH into your server
crontab -e

# Comment out or remove the line:
# 0 23 28-31 * * /usr/bin/php /path/to/hostinger_monthly_accrual.php
```

## API Endpoint

### POST `/Public/module/process_monthly_leave_accrual.php`

**Request:**
```javascript
fetch('process_monthly_leave_accrual.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' }
})
```

**Response:**
```json
{
    "success": true,
    "message": "Successfully processed leave accrual for November 2025",
    "summary": {
        "total_employees": 50,
        "processed_employees": 50,
        "total_records_updated": 100,
        "errors_count": 0
    },
    "details": [
        {
            "name": "John Doe",
            "updates": [
                {
                    "type": "Sick",
                    "previous": "3.50",
                    "added": "0.42",
                    "new": "3.92"
                }
            ]
        }
    ],
    "errors": []
}
```

## Support

For issues or questions:
1. Check the error messages in the results modal
2. Review the recent activity log
3. Verify database structure matches expected schema
4. Check admin permissions

---

**Last Updated:** November 3, 2025
**Version:** 1.0
