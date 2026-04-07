# Production Setup - Holiday Types Feature

## Issue Found
The production database was missing the `company_holidays` table.

## ✅ Solution - Run These Steps in Order

### STEP 1: Pull Latest Code
```bash
git pull origin calendartype
```

### STEP 2: Create company_holidays Table & Add Holidays
Navigate to:
```
https://harley.resourcestaffonline.com/setup_company_holidays.php
```

**Expected Output:**
```
========================================
Setting up company_holidays table...
========================================

✓ Created company_holidays table
✓ Added/updated 28 holidays

========================================
✓ Setup complete!
========================================
```

**This will:**
- Create the `company_holidays` table
- Add Philippine holidays for 2025-2026
- Include holiday types (regular, special_non_working)

### STEP 3: Run the Migration (Add holiday_type column)
Navigate to:
```
https://harley.resourcestaffonline.com/add_holiday_type_column.php
```

**Expected Output:**
```
========================================
Adding holiday_type to cache table...
========================================

✓ Added holiday_type column to employee_daily_schedule_cache
✓ Updated X existing cache records with holiday types

========================================
✓ Migration complete!
========================================
```

### STEP 4: Rebuild Schedule Cache
Navigate to:
```
https://harley.resourcestaffonline.com/Database/rebuild_schedule_cache.php
```

This populates the cache with holiday information.

### STEP 5: Test the Payroll Report
1. Go to Payroll Report
2. Generate report for **April 1-30, 2026**
3. Verify that **April 9** (Araw ng Kagitingan) shows:
   - **RH** if employee worked (Regular Holiday)
   - **HOL** if employee didn't work

---

## What Each Holiday Type Means

- **RH** = Regular Holiday (e.g., Independence Day, Christmas)
- **SNWH** = Special Non-Working Holiday (e.g., All Saints' Day, Ninoy Aquino Day)
- **SWH** = Special Working Holiday (if any are added)

---

## Holidays Added (2025-2026)

### Regular Holidays:
- New Year's Day (Jan 1) - Recurring
- Araw ng Kagitingan (Apr 9) - Recurring
- Maundy Thursday (varies)
- Good Friday (varies)
- Labor Day (May 1) - Recurring
- Independence Day (Jun 12) - Recurring
- National Heroes Day (last Mon of Aug)
- Bonifacio Day (Nov 30) - Recurring
- Christmas Day (Dec 25) - Recurring
- Rizal Day (Dec 30) - Recurring

### Special Non-Working Holidays:
- Ninoy Aquino Day (Aug 25)
- All Saints' Day (Nov 1)
- Christmas Eve (Dec 24)
- New Year's Eve (Dec 31)

---

## Troubleshooting

**Issue: Table already exists error**
- This is fine! Skip to Step 3

**Issue: Still showing "P" instead of RH**
- Make sure you completed Step 4 (rebuild cache)
- Clear browser cache

**Issue: Holidays not appearing in report**
- Verify holidays exist: Check the company_holidays table
- Rebuild the schedule cache (Step 4)

---

## Files Created

1. ✅ `setup_company_holidays.php` - Creates table and adds holidays
2. ✅ `add_holiday_type_column.php` - Adds holiday_type column to cache

---

**All set! The payroll report will now show specific holiday types! 🎉**
