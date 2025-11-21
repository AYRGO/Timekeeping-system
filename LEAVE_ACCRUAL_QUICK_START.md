# QUICK START GUIDE - New Leave Accrual System

## 🚀 For HR/Admins

### How to Regularize an Employee

1. **Navigate:** Employee Management → Select Employee → Edit Profile
2. **Change:** Employment Type → Select "Regular"
3. **Save:** Click "Update Profile"
4. **Confirmation:** Alert shows "Employee regularized successfully! 5 days Sick Leave credit granted."

✅ **Done!** Employee now has 5 days SL and will accrue 1.25 days VL monthly.

---

### How to Add Pro-rated VL from Probation

1. **Navigate:** `admin_leave_adjustment.php`
2. **Select Employee:** Choose the newly regularized employee
3. **Fill Form:**
   - Leave Type: **Vacation Leave (VL)**
   - Adjustment Type: **Add to existing balance**
   - Amount: *(Calculate: months in probation × 1.25)*
   - Reason: **"Pro-rated VL from probation period"**
4. **Submit:** Click "Apply Adjustment"

**Example:** 3 months probation = 3 × 1.25 = **3.75 days VL**

---

## 📋 For Employees

### When Do I Get Leave Credits?

**Upon Regularization (One-Time):**
- **Sick Leave:** 5 days (immediately)

**Every Month (While Regular):**
- **Vacation Leave:** +1.25 days (on last day of month)

**Probationary Employees:**
- No automatic accruals
- May receive manual grants for special cases

---

## 🔢 Quick Calculations

### SL Accrual
| Event | SL Credit |
|-------|-----------|
| Regularization | **5.00 days** (one-time) |
| Monthly | **0 days** (no accrual) |
| Maximum | **15 days** |

### VL Accrual (Regular Employees)
| Period | VL Credit |
|--------|-----------|
| Per Month | **1.25 days** |
| Per Year | **15 days** (1.25 × 12) |
| Maximum Balance | **15 days** |
| Carry-Over | **+5 days max** |

---

## 🎯 Common Scenarios

### Scenario 1: New Hire
- **Status:** Probationary
- **SL:** 0 days (no accrual)
- **VL:** 0 days (no accrual)
- **Action:** Wait until regularization

### Scenario 2: Regularized Employee
- **Event:** Changed from Probationary → Regular
- **SL:** **5 days** (auto-granted)
- **VL:** 0 days (admin adds pro-rated)
- **Next Month:** VL +1.25 days

### Scenario 3: Long-term Regular Employee
- **Status:** Regular for 12 months
- **SL:** 5 days (unchanged from regularization)
- **VL:** 15 days (1.25 × 12 months)
- **Total:** **20 days** available

---

## ⚡ Testing Mode vs Production Mode

### Testing Mode (For QA)
- ⚙️ **Frequency:** Every 10 seconds
- 🎯 **Purpose:** Rapid testing
- 🔧 **Toggle:** Admin Homepage

### Production Mode (Live System)
- ⚙️ **Frequency:** Last day of each month
- 🎯 **Purpose:** Real accruals
- ✅ **Default:** Enabled

---

## 🛠️ Troubleshooting

### Issue: SL not granted on regularization
**Solution:** Check employee-edit.php was saved, verify Emp_Type = 'Regular' in database

### Issue: VL not accruing monthly
**Solution:** Verify employee Emp_Type = 'Regular', check accrual mode is enabled

### Issue: Probationary employee receiving accruals
**Solution:** Change Emp_Type to 'Probationary' in employee-edit.php

---

## 📱 Access Points

### Admin Tools
- **Employee Management:** `/Public/views/employee-edit.php`
- **Manual Adjustments:** `/Public/views/admin_leave_adjustment.php`
- **Admin Homepage:** `/Public/views/admin_homepage.php`

### Employee Views
- **Leave Credits:** `/Public/module/leave_credits.php`
- **Request Leave:** `/Public/views/time_log_create.php`

---

## ✅ Pre-Launch Checklist

- [ ] All employees have correct Emp_Type (Regular/Probationary)
- [ ] Old SL monthly_increment values set to 0
- [ ] VL monthly_increment = 1.25 for Regular employees
- [ ] Testing completed in Testing Mode
- [ ] System switched to Production Mode
- [ ] HR team trained on new workflow
- [ ] Employees notified of new policy

---

## 📞 Need Help?

**Policy Questions:** Review `NEW_LEAVE_ACCRUAL_POLICY_2026.md`  
**Implementation Details:** See `LEAVE_ACCRUAL_IMPLEMENTATION_SUMMARY.md`  
**Technical Support:** Contact IT Department

---

**Policy Effective:** January 1, 2026  
**Last Updated:** November 12, 2025
