# Leave Accrual - Regular Employees Only

## Summary
Updated leave accrual system to only process accruals for employees with `Emp_Type = 'Regular'`.

## Files Updated

### ✅ 1. process_auto_accrual.php (Main Auto-Accrual)
**Location:** `Public/module/process_auto_accrual.php`
**Change:** Line 98
```sql
-- OLD: SELECT id, CONCAT(fname, ' ', lname) as full_name FROM employees WHERE status = 'active'
-- NEW: SELECT id, CONCAT(fname, ' ', lname) as full_name, Emp_Type FROM employees WHERE status = 'active' AND Emp_Type = 'Regular'
```

### ✅ 2. auto_accrual_cron.php (Cron Job)
**Location:** `Public/cron/auto_accrual_cron.php`
**Change:** Line 95
```sql
-- OLD: SELECT id, CONCAT(fname, ' ', lname) as full_name FROM employees WHERE status = 'active'
-- NEW: SELECT id, CONCAT(fname, ' ', lname) as full_name, Emp_Type FROM employees WHERE status = 'active' AND Emp_Type = 'Regular'
```

### Pending Updates (if needed):
- `Public/cron/monthly_accrual_production.php`
- `Public/cron/hostinger_monthly_accrual.php`
- `Public/cron/hostinger_leave_accrual.php`
- `Public/cron/process_monthly_accrual.php`
- `Public/cron/single_run_accrual.php`
- `Public/cron/smart_leave_accrual_scheduler.php`
- `Public/cron/realtime_leave_accrual.php`
- `Public/cron/monthly_leave_accrual_scheduler.php`

## Impact
- **Before:** All active employees received leave accruals
- **After:** Only Regular employees receive leave accruals
- **Probationary employees:** No longer receive automatic leave accruals

## Testing
1. Check employees table for `Emp_Type` column
2. Set some employees to 'Regular' and some to 'Probationary'
3. Run accrual process (Testing mode)
4. Verify only Regular employees get accruals

## Deployment Date
November 12, 2025
