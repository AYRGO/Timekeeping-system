# Leave Credit Fix - February 2026

## Problem Summary
On **January 31, 2026 at 23:47:12**, all Regular employees incorrectly received 15 VL (vacation leave) credits instead of the proper monthly accrual amount.

## Root Cause
Several initialization scripts were giving **full year credits (15 VL) to ALL employees** without checking their employment type:
- `Public/cron/generate_leave_credits.php`
- `Public/cron/update_leave_credits.php`
- `Public/cron/initialize_leave_credits.php`

## Leave Accrual Policy
- **Probationary Employees**: Get full year credits upfront (15 VL, 5 SL)
- **Regular Employees**: Use monthly accrual (1.25 VL/month, 0.42 SL/month)
- **Maximum Balance**: 15 days for VL/SL
- **Carry-over**: Up to 5 days for vacation leave

## Fix Applied (February 3, 2026)
✅ Ran correction script: `fix_leave_credits_2026.php`
- **Affected Employees**: 78 Regular employees
- **Action**: Corrected from 15 VL to 1.25 VL (January accrual only)
- **Handled Edge Cases**: Accounted for employees who already used some VL
- **Results**: 
  - 74 employees: 15.00 → 1.25 VL
  - 3 employees: 13.00 → 0.00 VL (had used 2 days)
  - 1 employee: 14.00 → 0.25 VL (had used 1 day)

## Prevention - Scripts Fixed
All initialization scripts now include **Emp_Type filtering**:

### 1. generate_leave_credits.php
**BEFORE**: `WHERE status = 'active'`
**AFTER**: `WHERE status = 'active' AND Emp_Type = 'Probationary'`

### 2. update_leave_credits.php
**BEFORE**: `WHERE status = 'active'`
**AFTER**: `WHERE status = 'active' AND Emp_Type = 'Probationary'`

### 3. initialize_leave_credits.php
**BEFORE**: `WHERE status = 'active'`
**AFTER**: `WHERE status = 'active' AND Emp_Type = 'Probationary'`

### 4. process_monthly_accrual.php
**BEFORE**: `WHERE status = 'active'`
**AFTER**: `WHERE status = 'active' AND Emp_Type = 'Regular'`

## How It Works Now
1. **Probationary Employees** → Use initialization scripts (full year credits)
2. **Regular Employees** → Use `process_monthly_accrual.php` (monthly accrual only)
3. Each script explicitly filters by `Emp_Type` to prevent cross-contamination

## Testing Recommendations
- Monitor leave balances after each monthly accrual run
- Verify new Regular employees don't get 15 VL at once
- Confirm Probationary employees still get full year credits
- Run diagnostic script monthly: `check_hostinger_leave_credits.php`

## Files Modified
- ✅ `Public/cron/generate_leave_credits.php` - Added Emp_Type filter
- ✅ `Public/cron/update_leave_credits.php` - Added Emp_Type filter
- ✅ `Public/cron/initialize_leave_credits.php` - Added Emp_Type filter
- ✅ `Public/cron/process_monthly_accrual.php` - Added Emp_Type filter
- ✅ `fix_leave_credits_2026.php` - Correction script (can be removed after verification)
- ✅ `check_hostinger_leave_credits.php` - Diagnostic tool

## Date Fixed
February 3, 2026

## Next Steps
1. ✅ Scripts fixed and deployed
2. ⏳ Monitor February accrual run (Feb 28/29)
3. ⏳ Verify March accrual (Regular employees should have 2.50 VL by March 1)
4. ⏳ Consider removing fix script after 1-2 months of stability
