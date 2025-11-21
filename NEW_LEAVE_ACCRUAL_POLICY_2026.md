# NEW LEAVE ACCRUAL POLICY - Effective January 1, 2026

## 📋 Executive Summary

The leave accrual system has been **simplified** to eliminate complex, hard-to-automate pro-rated calculations. The new system provides clearer benefits to employees and reduces administrative burden.

---

## 🎯 Key Changes

### 1. **Employment Status Control**
- Leave accruals are now controlled by the `Emp_Type` field in the employees table
- Three possible values:
  - **Probationary** - No automatic leave accruals
  - **Regular** - Receives automatic monthly accruals
  - **Inactive** - No accruals

### 2. **Sick Leave (SL) Policy - MAJOR CHANGE** ✨

#### OLD POLICY (Before Jan 1, 2026):
- Pro-rated SL accrual for remainder of year upon regularization
- Monthly accrual of 0.42 days (5 days ÷ 12 months)
- Full 5-day credit starting in January following regularization

#### NEW POLICY (Effective Jan 1, 2026): 
- **One-time grant of 5 days SL upon regularization**
- **No monthly accrual** - SL is granted in full immediately
- Simpler, more favorable to employees
- Easier to administer and automate

**Example:**
- Employee regularized on March 15, 2026
- Immediately receives: **5 days SL** (full credit)
- No pro-rating, no complex calculations

### 3. **Vacation Leave (VL) Policy - NO CHANGE**

#### Continues as before:
- Monthly accrual of **1.25 days/month** for Regular employees
- Maximum balance: **15 days**
- Carry-over allowed: **Up to 5 days** to next year
- Pro-rated VL from probation period: **Manually encoded by admin**

**Example:**
- Employee regularized on March 15, 2026
- VL accrues starting April 1: **1.25 days**
- Each month after: **+1.25 days**
- Admin manually adds any VL earned during probation

---

## 🔧 System Implementation

### Files Modified

#### 1. **process_auto_accrual.php** (Monthly Processor)
**Changes:**
- Removed SL from monthly accrual loop
- Now only processes VL monthly
- Restricted to Regular employees only

```php
// NEW POLICY (Effective Jan 1, 2026):
// - Sick Leave (SL): One-time 5-day grant upon regularization (NOT monthly accrual)
// - Vacation Leave (VL): Monthly accrual of 1.25 days for Regular employees only
$leaveRates = [
    'vacation' => 1.25  // Only VL accrues monthly
];
```

#### 2. **employee-edit.php** (Admin Employee Management)
**Changes:**
- Added `Emp_Type` field to employee profile display
- Added dropdown to change employment type (Probationary/Regular)
- Automatic SL grant when status changes from Probationary → Regular
- Visual indicator showing policy in UI

**Logic:**
```php
// Detect regularization
$wasRegularized = ($previousEmpType === 'Probationary' && $empType === 'Regular');

if ($wasRegularized) {
    // Grant 5-day SL credit immediately
    // Shows alert: "Employee regularized successfully! 5 days Sick Leave credit granted."
}
```

#### 3. **process_regularization_sl.php** (NEW FILE)
**Purpose:** 
- Standalone processor for one-time SL credit grant
- Called automatically when employee is regularized
- Can also be called manually if needed

**Features:**
- Verifies employee is Regular status
- Checks for existing SL record
- Adds 5 days to existing balance OR creates new record
- Caps at 15-day maximum
- Logs admin who processed

#### 4. **admin_leave_adjustment.php** (NEW FILE)
**Purpose:**
- Admin interface for manual leave credit adjustments
- Used to encode pro-rated VL from probation period

**Features:**
- Select any active employee
- View current leave balances
- Add to existing balance OR set exact amount
- Requires reason for adjustment
- Supports all leave types

---

## 📊 Database Schema

### `employees` Table
```sql
Emp_Type ENUM('Probationary', 'Regular') NOT NULL DEFAULT 'Probationary'
```

### `leave_credits` Table
```sql
- employee_id (FK to employees.id)
- leave_type ('sick', 'vacation', 'paternity', 'maternity', etc.)
- balance (DECIMAL 5,2) - Current available days
- carry_over (FLOAT) - Carried over days (VL only)
- year (INT) - Year these credits apply to
- monthly_increment (DECIMAL 5,2) - Monthly accrual rate
- updated_at (DATETIME) - Last update timestamp
```

---

## 👥 User Workflows

### For HR/Admin: Regularizing an Employee

1. **Go to:** Employee Edit page
2. **Select Employee:** Click on employee to edit
3. **Change Employment Type:** 
   - Change `Employment Type` from "Probationary" to "Regular"
4. **Save:** Click "Update Profile"
5. **System Actions:**
   - ✅ Updates Emp_Type to 'Regular'
   - ✅ Automatically grants 5 days SL
   - ✅ Shows confirmation: "Employee regularized successfully! 5 days Sick Leave credit granted."
   - ✅ Future VL accruals begin next month

### For HR/Admin: Adding Pro-rated VL

1. **Go to:** `admin_leave_adjustment.php`
2. **Select Employee:** Choose newly regularized employee
3. **View Current Credits:** System shows existing balances
4. **Add VL Credit:**
   - Leave Type: Vacation Leave (VL)
   - Adjustment Type: Add to existing balance
   - Amount: e.g., 3.75 (for 3 months probation)
   - Reason: "Pro-rated VL from probation period"
5. **Apply:** Click "Apply Adjustment"

---

## 🧮 Calculation Examples

### Example 1: Employee Regularized Mid-Year

**Scenario:**
- Employee hired: January 1, 2026 (Probationary)
- Regularized: April 1, 2026
- During probation: Earned 3.75 days VL (3 months × 1.25)

**System Actions:**
1. **Upon Regularization (April 1):**
   - SL: **5 days** (automatic one-time grant)
   - VL: **0 days** (starts fresh)

2. **Admin Manual Entry:**
   - Add 3.75 days VL (pro-rated from probation)
   - New VL balance: **3.75 days**

3. **Monthly Accruals (Starting May 1):**
   - May 1: VL = 3.75 + 1.25 = **5.00 days**
   - June 1: VL = 5.00 + 1.25 = **6.25 days**
   - July 1: VL = 6.25 + 1.25 = **7.50 days**

### Example 2: Employee Regularized End of Year

**Scenario:**
- Employee hired: September 1, 2026 (Probationary)
- Regularized: December 1, 2026
- During probation: Earned 3.75 days VL (3 months)

**System Actions:**
1. **Upon Regularization (December 1):**
   - SL: **5 days** (immediate)
   - VL: **0 days**

2. **Admin Manual Entry:**
   - Add 3.75 days VL
   - New VL balance: **3.75 days**

3. **January 1, 2027:**
   - VL: 3.75 + 1.25 = **5.00 days**
   - SL: Still **5 days** (no monthly accrual)

---

## 🔐 Access Control

### Admin Only
- Change employee Emp_Type
- Access admin_leave_adjustment.php
- Manually adjust leave credits

### Employee
- View their own leave balances
- Cannot modify credits
- Benefits from automatic accruals (if Regular)

---

## 📅 Automation Schedule

### Monthly Auto-Accrual (Production Mode)
- **When:** Last day of each month
- **Who:** Regular employees only
- **What:** VL +1.25 days (SL no longer accrues monthly)
- **Trigger:** Automatic via JavaScript on any dashboard

### Testing Mode
- **When:** Every 10 seconds
- **Purpose:** Rapid testing of accrual logic
- **Toggle:** Admin Homepage

---

## ⚠️ Important Notes

### For Administrators

1. **SL is automatic** - No need to manually add upon regularization
2. **VL requires manual entry** - Must encode pro-rated balance from probation
3. **Maximum balances:**
   - SL: 15 days
   - VL: 15 days + 5 carry-over = 20 days max
4. **Probationary employees:**
   - Do NOT receive automatic accruals
   - Can still be manually granted leave (special cases)

### For Developers

1. **Never modify SL monthly increment** - Should always be 0 for Regular employees
2. **VL monthly increment** - Always 1.25 for Regular employees
3. **Database triggers** - None implemented; all logic in PHP
4. **Audit logging** - Consider implementing for leave adjustments

---

## 🧪 Testing Checklist

### Before Going Live (Jan 1, 2026)

- [ ] Set all current employees to correct Emp_Type
- [ ] Test regularization flow (Probationary → Regular)
- [ ] Verify 5-day SL grant happens automatically
- [ ] Test manual VL adjustment form
- [ ] Verify monthly VL accrual (only VL, not SL)
- [ ] Test with Testing Mode (10-second intervals)
- [ ] Switch to Production Mode (monthly)
- [ ] Verify Probationary employees get NO accruals
- [ ] Check maximum balance caps (15 days)
- [ ] Test carry-over logic for VL

---

## 📞 Support & Maintenance

### Common Issues

**Issue:** SL not granted upon regularization
- **Check:** Emp_Type changed from Probationary to Regular
- **Check:** Database has leave_credits table
- **Fix:** Run manual grant via process_regularization_sl.php

**Issue:** VL still shows monthly increment for SL
- **Check:** Old records may have monthly_increment = 0.42
- **Fix:** Update to 0 via SQL or manual adjustment

**Issue:** Probationary employee receiving accruals
- **Check:** Employee Emp_Type field
- **Fix:** Change to 'Probationary' in employee-edit.php

### SQL Maintenance Queries

```sql
-- Check all Regular employees
SELECT id, CONCAT(fname, ' ', lname) as name, Emp_Type 
FROM employees 
WHERE Emp_Type = 'Regular' AND status = 'Active';

-- Verify SL monthly_increment is 0 for all
UPDATE leave_credits 
SET monthly_increment = 0 
WHERE leave_type = 'sick';

-- Check VL monthly_increment is 1.25 for Regular employees
SELECT e.id, CONCAT(e.fname, ' ', e.lname) as name, lc.monthly_increment
FROM employees e
JOIN leave_credits lc ON e.id = lc.employee_id
WHERE e.Emp_Type = 'Regular' AND lc.leave_type = 'vacation';
```

---

## 📈 Benefits of New System

### For Employees
✅ **Immediate benefit** - 5 days SL right away, not pro-rated  
✅ **Clear and simple** - Easy to understand when you get leave  
✅ **More favorable** - Better than old pro-rated system  

### For HR/Admin
✅ **Less complexity** - No more pro-rated SL calculations  
✅ **Easier to explain** - Simple policy to communicate  
✅ **Less maintenance** - Fewer edge cases to handle  

### For System
✅ **Fully automated** - SL grant happens on status change  
✅ **Consistent** - Same 5-day grant for everyone  
✅ **Auditable** - Clear trail of when/why credits granted  

---

## 📝 Policy Effective Date

**January 1, 2026**

All employees regularized on or after this date will receive:
- **5 days SL** (one-time, upon regularization)
- **1.25 days VL/month** (ongoing, while Regular)

Employees regularized before 2026 may still have old pro-rated SL balances.

---

## 🔗 Related Files

- `/Public/module/process_auto_accrual.php` - Monthly VL accrual
- `/Public/module/process_regularization_sl.php` - SL one-time grant
- `/Public/views/employee-edit.php` - Admin employee management
- `/Public/views/admin_leave_adjustment.php` - Manual credit adjustment
- `/Public/module/leave_credits.php` - Employee leave view
- `/Public/cron/auto_accrual_cron.php` - Cron job (if used)

---

**Document Version:** 1.0  
**Last Updated:** November 12, 2025  
**Author:** System Administrator  
**Policy Effective:** January 1, 2026
