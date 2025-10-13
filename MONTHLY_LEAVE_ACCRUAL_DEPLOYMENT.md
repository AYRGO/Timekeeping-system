# 🚀 MONTHLY LEAVE ACCRUAL SYSTEM - HOSTINGER DEPLOYMENT GUIDE

## 📋 Files to Deploy to Hostinger Production

### ✅ **CORE SYSTEM FILES (Required)**

#### 1. **Main Accrual Scripts** (`/public_html/Public/cron/`)
```
monthly_leave_accrual_scheduler.php    ← Production scheduler (runs continuously)
test_monthly_accrual.php              ← Test version (30-second intervals)
```

#### 2. **Monitoring Interfaces** (`/public_html/Public/module/`)
```
monthly_leave_monitor.php             ← Monthly monitoring dashboard
live_leave_credits_ajax.php           ← AJAX endpoint for real-time updates
```

#### 3. **Test Interface** (`/public_html/Public/`)
```
leave_accrual_test.html               ← Testing dashboard with links to all interfaces
```

### 🔧 **OPTIONAL FILES (For Testing)**
```
/public_html/Public/cron/realtime_leave_accrual.php    ← 10-second test system (not needed for production)
/public_html/Public/module/live_leave_credits.php      ← Real-time monitor (for testing only)
```

---

## 🗂️ **File-by-File Deployment Instructions**

### **1. Production Accrual System**
**File**: `Public/cron/monthly_leave_accrual_scheduler.php`
- ✅ **Deploy**: YES - This is your main production system
- 🎯 **Purpose**: Monitors for end-of-month and processes leave accrual
- ⏰ **Schedule**: Runs continuously, checks every hour
- 📝 **Action**: Upload to `/public_html/Public/cron/`

### **2. Monthly Monitoring Dashboard**  
**File**: `Public/module/monthly_leave_monitor.php`
- ✅ **Deploy**: YES - Employee interface
- 🎯 **Purpose**: Shows employees their leave balances and next accrual date
- 📝 **Action**: Upload to `/public_html/Public/module/`

### **3. AJAX Endpoint**
**File**: `Public/module/live_leave_credits_ajax.php` 
- ✅ **Deploy**: YES - Required for monitoring interface
- 🎯 **Purpose**: Provides data for real-time updates
- 📝 **Action**: Upload to `/public_html/Public/module/`

### **4. Test Dashboard**
**File**: `Public/leave_accrual_test.html`
- ✅ **Deploy**: OPTIONAL - Useful for admin testing
- 🎯 **Purpose**: Admin interface to test and monitor the system
- 📝 **Action**: Upload to `/public_html/Public/`

### **5. Test System**
**File**: `Public/cron/test_monthly_accrual.php`
- ⚠️ **Deploy**: OPTIONAL - Only if you want to test the system
- 🎯 **Purpose**: Simulates monthly accrual every 30 seconds for testing
- 📝 **Action**: Upload to `/public_html/Public/cron/` (if needed)

---

## 🏗️ **Database Requirements**

### **Existing Table Structure** ✅
Your `leave_credits` table already has the required columns:
```sql
- id (int)
- employee_id (int) 
- leave_type (varchar)
- balance (decimal)
- carry_over (float)
- monthly_increment (decimal)
- year (int)
- updated_at (datetime)
```

### **No Database Changes Needed** ✅
The system works with your existing database structure.

---

## 🔄 **Hostinger Cron Job Setup**

### **Option 1: Continuous Background Process**
```bash
# Add this cron job to run every hour and maintain the scheduler
0 * * * * /usr/local/bin/php /home/yourusername/public_html/Public/cron/monthly_leave_accrual_scheduler.php >/dev/null 2>&1
```

### **Option 2: Direct Monthly Execution** 
```bash
# Run accrual directly on last day of each month at 11:30 PM
30 23 28-31 * * [ $(date -d tomorrow +\%d) = "01" ] && /usr/local/bin/php /home/yourusername/public_html/Public/cron/monthly_leave_credit.php
```

---

## 🌐 **Access URLs After Deployment**

### **For Employees**:
```
https://yourdomain.com/Public/module/monthly_leave_monitor.php
```

### **For Admin Testing**:
```
https://yourdomain.com/Public/leave_accrual_test.html
```

### **For System Testing** (if deployed):
```
https://yourdomain.com/Public/cron/test_monthly_accrual.php
```

---

## ⚙️ **Configuration Settings**

### **Monthly Accrual Rates** (Currently Set):
- **Sick Leave**: 0.42 days per month (5.04 days annually)
- **Vacation Leave**: 1.25 days per month (15 days annually)
- **Maximum Balance**: 15 days each
- **Vacation Carry-over**: Up to 5 days

### **Timezone Setting**:
```php
date_default_timezone_set('Asia/Manila');  // Already configured
```

---

## 🚨 **MINIMUM REQUIRED FILES FOR PRODUCTION**

If you want the **absolute minimum** deployment:

### **Essential Files Only**:
1. `Public/cron/monthly_leave_accrual_scheduler.php` - Main system
2. `Public/module/monthly_leave_monitor.php` - Employee interface  
3. `Public/module/live_leave_credits_ajax.php` - AJAX support

### **Cron Job**:
```bash
0 * * * * /usr/local/bin/php /home/yourusername/public_html/Public/cron/monthly_leave_accrual_scheduler.php
```

---

## ✅ **Post-Deployment Checklist**

- [ ] Upload required files to Hostinger
- [ ] Set up cron job in Hostinger control panel
- [ ] Test the monthly monitor interface
- [ ] Verify database connectivity
- [ ] Check timezone settings
- [ ] Test accrual logic with test system (optional)

---

## 🎯 **Summary**

**REQUIRED for production**: 3 files
**OPTIONAL for testing**: 2 additional files  
**Database changes**: None needed
**Cron job**: 1 entry required

The system will automatically process leave accrual on the last day of each month! 🚀