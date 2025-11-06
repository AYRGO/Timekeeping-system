# 🧪 Auto-Accrual Testing Mode Guide

## Quick Start - Testing Mode

### Step 1: Enable Auto-Accrual
1. Open your **Admin Homepage**
2. Find the "Automatic Leave Accrual" section
3. Click the **toggle switch** to ENABLE it (should turn green)

### Step 2: Switch to Testing Mode
1. Once enabled, you'll see two buttons appear:
   - **Testing (10s)** - Orange button
   - **Production (Monthly)** - Blue button
2. Click **"Testing (10s)"** button
3. You'll see:
   - ⚠️ Warning message about testing mode
   - "Every 10 seconds" in the description

### Step 3: Run the Accrual Script
Open PowerShell or Command Prompt and run:

```bash
cd C:\xampp\htdocs\Timekeeping-system
.\run_accrual_testing.bat
```

**OR** manually run every 10 seconds:
```bash
cd C:\xampp\htdocs\Timekeeping-system\Public\cron
php auto_accrual_cron.php
```

### Step 4: Watch It Work
- The script will add credits **every 10 seconds**:
  - **Sick Leave**: 0.42 days per cycle
  - **Vacation Leave**: 1.25 days per cycle
- Check the employee's leave credits page to see it updating!
- Look at the console output to see processing logs

### Step 5: Switch to Production When Ready
1. Go back to Admin Homepage
2. Click **"Production (Monthly)"** button
3. The system will now:
   - Only run on the **last day of each month**
   - At **11:00 PM**
   - Process once per month only

---

## 🎯 What Each Mode Does

### Testing Mode (10 seconds)
- ✅ Runs **every time the cron is executed**
- ✅ No date checks (runs immediately)
- ✅ Perfect for **testing and verification**
- ⚠️ **DO NOT use in production!**
- 💡 Great for seeing results quickly

### Production Mode (Monthly)
- ✅ Runs **only on last day of month**
- ✅ Checks if already processed (prevents duplicates)
- ✅ Safe for **live deployment**
- ✅ One-time processing per month
- 💡 Use this when you go live!

---

## 📊 How to Verify It's Working

### Check Leave Credits Page
1. Go to an employee's **Leave Credits** section
2. Refresh the page every 10 seconds
3. Watch the balances increase!

### Check the Logs
Look at: `Public/logs/auto_accrual.log`

You'll see entries like:
```
[2025-11-06 12:00:00] === AUTO-ACCRUAL CRON CHECK STARTED ===
[2025-11-06 12:00:00] Auto-accrual is ENABLED. Checking mode...
[2025-11-06 12:00:00] Accrual mode: TESTING
[2025-11-06 12:00:00] TESTING MODE - Running accrual cycle...
[2025-11-06 12:00:01] Found 5 active employees
[2025-11-06 12:00:02] === AUTO-ACCRUAL COMPLETED ===
[2025-11-06 12:00:02] Mode: TESTING
[2025-11-06 12:00:02] Employees processed: 5
[2025-11-06 12:00:02] Errors: 0
```

---

## 🚨 Important Reminders

1. **Always switch to Production before going live!**
2. Testing mode will keep adding credits indefinitely
3. In testing mode, balances can exceed maximum limits temporarily
4. Production mode respects all limits and rules
5. Only one mode can be active at a time

---

## 🔄 Switching Between Modes

### From Testing → Production
1. Click "Production (Monthly)" button
2. Page reloads
3. ✅ Safe for live use

### From Production → Testing
1. Click "Testing (10s)" button
2. Page reloads
3. ⚠️ Remember to switch back!

---

## 💻 Windows Task Scheduler (For Production)

### For Production Mode (Monthly):
```
Program: C:\xampp\php\php.exe
Arguments: C:\xampp\htdocs\Timekeeping-system\Public\cron\auto_accrual_cron.php
Schedule: Daily at 11:00 PM
```

### For Testing Mode (Every 10 seconds):
Just run the `run_accrual_testing.bat` script manually!

---

## 🎉 You're All Set!

Now you can:
- ✅ Test accruals in real-time (10 seconds)
- ✅ Verify everything works correctly
- ✅ Switch to production when ready
- ✅ Let it run automatically every month

Happy testing! 🚀
