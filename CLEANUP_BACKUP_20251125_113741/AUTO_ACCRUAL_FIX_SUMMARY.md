# Auto-Accrual Fix - Works on Employee Dashboard Too

## Problem
Auto-accrual was only working when viewing the **Admin Homepage**. When employees were on their own dashboard, the accrual processor wasn't being called, so leave credits weren't being updated.

## Root Causes
1. **Authorization Restriction**: `process_auto_accrual.php` only allowed admin users (`role = 'internal'`) to run it
2. **Missing JavaScript Call**: Employee dashboard (`time_log_create.php`) didn't have JavaScript to call the accrual processor

## Solution Implemented

### 1. Removed Admin-Only Restriction
**File:** `Public/module/process_auto_accrual.php`

**Before:**
```php
// Check if user is admin
if (!isset($_SESSION['employee']) || $_SESSION['employee']['role'] !== 'internal') {
    die(json_encode(['success' => false, 'error' => 'Unauthorized']));
}
```

**After:**
```php
// Allow any logged-in user to trigger auto-accrual (it runs in background)
if (!isset($_SESSION['employee']['id'])) {
    die(json_encode(['success' => false, 'error' => 'Not logged in']));
}
```

**Why:** Auto-accrual is a background system process. Any logged-in user can trigger it since it processes accruals for ALL Regular employees, not just the current user.

### 2. Added Accrual Processor to Employee Dashboard
**File:** `Public/module/time_log_create.php`

**Added JavaScript (before `</body>`):**
```javascript
// Auto-Accrual Background Processor (runs on employee dashboard too)
document.addEventListener('DOMContentLoaded', function() {
    function processAutoAccrual() {
        fetch('../module/process_auto_accrual.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success && data.processed > 0) {
                console.log('✅ Auto-accrual processed:', data.processed, 'employees');
            }
        })
        .catch(error => {
            console.error('Auto-accrual check error:', error);
        });
    }
    
    // Run accrual check on page load
    processAutoAccrual();
    
    // Check periodically (every hour)
    setInterval(processAutoAccrual, 3600000);
});
```

## How It Works Now

### Production Mode (Default)
- Checks if today is the **last day of the month**
- If yes AND not already processed this month → Process accruals for all Regular employees
- If no → Returns "waiting" status
- Runs every hour on any dashboard (admin or employee)

### Testing Mode
- Processes accruals **every 10 seconds** (for testing)
- Can be toggled from Admin Homepage
- Allows rapid testing of accrual logic

## Who Gets Accruals

**Only employees with:**
- `status = 'active'` 
- `Emp_Type = 'Regular'`

**Currently:** 0 employees (all 87 are Probationary by default)

## Benefits

✅ **No manual intervention needed** - Runs automatically
✅ **Works from any dashboard** - Admin or employee view
✅ **Processes once per month** - On last day only (production mode)
✅ **Secure** - Still requires login, checks employee validity
✅ **Background operation** - Doesn't interrupt user experience
✅ **Restricted to Regular employees** - Probationary staff excluded

## Testing Steps

1. Set some employees to Regular:
   ```sql
   UPDATE employees SET Emp_Type = 'Regular' WHERE id IN (1, 2, 3);
   ```

2. Enable Testing Mode from Admin Homepage

3. Wait 10 seconds or reload any page (admin or employee)

4. Check `leave_credits` table:
   ```sql
   SELECT * FROM leave_credits WHERE updated_at > NOW() - INTERVAL 5 MINUTE;
   ```

5. Switch back to Production Mode when done testing

## Files Modified

1. ✅ `Public/module/process_auto_accrual.php` - Removed admin-only restriction
2. ✅ `Public/module/time_log_create.php` - Added accrual processor JavaScript
3. ✅ `Public/cron/auto_accrual_cron.php` - Restricted to Regular employees only

## Deployment Date
November 12, 2025
