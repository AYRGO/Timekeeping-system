# Quick Fix Guide - Production Migration

## ✅ STEP 1: Pull Latest Changes (This fixes the 500 error)
```bash
# SSH into your production server
cd /home/u816220874/domains/harley.resourcestaffonline.com/public_html

# Pull the latest code
git pull origin calendartype
```

**Result:** The payroll report will work again (showing "P" for holidays until migration is run)

---

## ✅ STEP 2: Run Database Migration (To enable holiday types)

### Option A: Via Web Browser (Easiest)
1. Navigate to: `https://harley.resourcestaffonline.com/add_holiday_type_column.php`
2. You should see:
   ```
   ✓ Added holiday_type column to employee_daily_schedule_cache
   ✓ Updated X existing cache records with holiday types
   ✓ Migration complete!
   ```

### Option B: Via SSH (Command Line)
```bash
cd /home/u816220874/domains/harley.resourcestaffonline.com/public_html
php add_holiday_type_column.php
```

---

## ✅ STEP 3: Rebuild Schedule Cache (Required after migration)
```bash
cd /home/u816220874/domains/harley.resourcestaffonline.com/public_html
php Database/rebuild_schedule_cache.php
```

**OR** via web browser:
`https://harley.resourcestaffonline.com/Database/rebuild_schedule_cache.php`

---

## ✅ STEP 4: Test
1. Go to Payroll Report
2. Generate a report for April 1-30, 2026
3. Check if holidays worked show:
   - **RH** (Regular Holiday)
   - **SNWH** (Special Non-Working Holiday)  
   - **SWH** (Special Working Holiday)

---

## What Changed?

**Before migration:**
- Works normally, shows "P" for holiday work

**After migration:**
- Shows specific holiday types (RH, SNWH, SWH) for holiday work

---

## Troubleshooting

**500 Error after pulling code?**
- Wait 2-3 minutes and refresh (cache clearing)
- If still happening, check PHP error logs

**Still showing "P" after migration?**
- Make sure you ran the cache rebuild (Step 3)

**Column already exists error?**
- Safe to ignore, means migration was already run
