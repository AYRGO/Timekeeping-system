# Employment Type Classification Guide

## Overview
The system now supports THREE employment types with different leave accrual policies.

---

## Employment Types

### 1. **Probationary**
- **Status:** New employees in probation period
- **Leave Accrual:** ❌ None
- **Monthly SL:** 0 days
- **Monthly VL:** 0 days
- **Color Badge:** 🟠 Orange

---

### 2. **Regular** (New Policy - Effective Jan 1, 2026)
- **Status:** Employees regularized AFTER January 1, 2026
- **Leave Accrual:** VL Only
- **One-Time SL Grant:** ✅ 5 days upon regularization
- **Monthly SL:** ❌ 0 days (already got 5 days)
- **Monthly VL:** ✅ 1.25 days/month
- **Color Badge:** 🔵 Blue
- **Policy Notes:**
  - Simplified sick leave system
  - 5-day SL grant is ONE-TIME only
  - VL accrues normally (15 days/year)

---

### 3. **Old_Regular** (Legacy Policy)
- **Status:** Employees hired/regularized BEFORE January 1, 2026
- **Leave Accrual:** BOTH SL + VL
- **Monthly SL:** ✅ 0.42 days/month (5 days/year)
- **Monthly VL:** ✅ 1.25 days/month (15 days/year)
- **Color Badge:** 🟢 Green
- **Policy Notes:**
  - Maintains legacy accrual system
  - Continuous monthly accrual for both leave types
  - Grandfathered into old policy

---

## SQL Setup Commands

### 1. Modify Table to Add Old_Regular Option
```sql
ALTER TABLE employees 
MODIFY COLUMN Emp_Type ENUM('Probationary', 'Regular', 'Old_Regular') 
DEFAULT 'Probationary';
```

### 2. Set Existing Regular Employees to Old_Regular
```sql
-- Option A: Set ALL existing regular employees to Old_Regular
UPDATE employees 
SET Emp_Type = 'Old_Regular' 
WHERE Emp_Type = 'Regular';
```

```sql
-- Option B: Set only employees hired before 2026 to Old_Regular
UPDATE employees 
SET Emp_Type = 'Old_Regular' 
WHERE Emp_Type = 'Regular' 
  AND hire_date < '2026-01-01';
```

```sql
-- Option C: Set all NULL/empty Emp_Type to Old_Regular (for existing employees)
UPDATE employees 
SET Emp_Type = 'Old_Regular' 
WHERE (Emp_Type IS NULL OR Emp_Type = '') 
  AND status = 'active';
```

---

## How It Works

### Auto-Accrual Processing (`process_auto_accrual.php`)

**OLD_REGULAR Employees:**
```php
$leaveRates = [
    'sick' => 0.42,      // 5 days / 12 months
    'vacation' => 1.25   // 15 days / 12 months
];
```

**REGULAR Employees:**
```php
$leaveRates = [
    'vacation' => 1.25   // ONLY VL accrues monthly
];
```

**PROBATIONARY Employees:**
```php
// No automatic accrual
```

### Regularization Process

**NEW Employee (Probationary → Regular):**
1. Change Emp_Type to "Regular"
2. System automatically grants 5 days SL (one-time)
3. Monthly VL accrual begins
4. No monthly SL accrual

**OLD Employee (Set to Old_Regular):**
1. Set Emp_Type to "Old_Regular" via SQL or admin panel
2. No automatic SL grant (should already have credits)
3. Monthly SL + VL accrual continues

---

## Admin Panel Usage

### Viewing Employment Type
- Navigate to employee profile
- **Employment Type** field shows:
  - 🟠 "Probationary"
  - 🔵 "Regular (New Policy)"
  - 🟢 "Old Regular (Legacy)"

### Changing Employment Type
1. Click "Edit Profile"
2. Select from dropdown:
   - **Probationary** - No accrual
   - **Regular (New Policy - VL Only)** - Triggers 5-day SL grant
   - **Old Regular (Legacy - SL + VL)** - For pre-2026 employees
3. Confirmation dialogs explain the implications
4. Click "Update Profile" to save

---

## Best Practices

### ✅ DO:
- Set ALL existing employees to "Old_Regular" via SQL before January 1, 2026
- Use "Regular" ONLY for NEW regularizations after January 1, 2026
- Verify leave credits after changing employment types
- Document who is classified as what and why

### ❌ DON'T:
- Use UI to change existing employees from Regular → Old_Regular (use SQL instead)
- Grant "Regular" status to employees hired before 2026 (they should be Old_Regular)
- Change employment types without understanding the accrual implications

---

## Troubleshooting

### "Old employees not getting SL accrual"
**Solution:** Make sure they're set to `Old_Regular`, not `Regular`
```sql
UPDATE employees SET Emp_Type = 'Old_Regular' WHERE id = [employee_id];
```

### "New employee got double SL credits"
**Solution:** Check if they were set to Old_Regular by mistake
- Should be "Regular" (gets 5 days once)
- NOT "Old_Regular" (gets monthly SL)

### "Employee shows wrong accrual rate"
**Solution:** Verify Emp_Type in database
```sql
SELECT id, fname, lname, Emp_Type, hire_date FROM employees WHERE id = [employee_id];
```

---

## Migration Checklist

- [ ] Run ALTER TABLE to add Old_Regular option
- [ ] Backup employees table
- [ ] Update all existing regular employees to Old_Regular
- [ ] Verify accrual rates in process_auto_accrual.php
- [ ] Test regularization flow with test employee
- [ ] Update HR documentation
- [ ] Train admins on new employment types
- [ ] Set policy effective date (Jan 1, 2026)

---

**Last Updated:** November 13, 2025
**Policy Effective:** January 1, 2026
