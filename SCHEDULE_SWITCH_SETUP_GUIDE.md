# Schedule Switch Request - Database Setup Status

## ✅ Files Ready

### 1. Database Schema
- **File**: `create_schedule_switch_table.sql`
- **Status**: ✅ Created and ready to execute
- **Table**: `schedule_switch_requests`

### 2. Backend Handler
- **File**: `Public/views/time_log_create.php`
- **Status**: ✅ Schedule switch handler already implemented (lines 15-97)
- **Action**: Processes `submit_schedule_switch` POST requests
- **Inserts**: Data into `schedule_switch_requests` table

### 3. Frontend Form
- **File**: `Public/module/schedule_content.php`
- **Status**: ✅ Form action path FIXED
- **Path**: Now correctly points to `../views/time_log_create.php`
- **Modal**: Schedule Switch Modal with Date A/B selection

### 4. Admin Approval Page
- **File**: `Public/views/schedule_request.php`
- **Status**: ✅ Switch requests view added
- **Tab**: "Switch Requests" button added to navigation
- **Features**: Approve/Decline actions with schedule swap logic

### 5. Approval Processor
- **File**: `Public/views/process_switch_action.php`
- **Status**: ✅ Created
- **Features**: 
  - Approve: Swaps schedules in `employee_daily_schedule_cache`
  - Decline: Updates status with admin notes

### 6. AJAX Schedule Fetcher
- **File**: `Public/controller/ajax_get_schedule_for_date.php`
- **Status**: ✅ Already exists
- **Purpose**: Fetches employee schedule for date preview

---

## 🚀 SETUP INSTRUCTIONS

### Step 1: Create Database Table

**Option A - Using MySQL Command Line:**
```bash
cd C:\xampp\htdocs\Timekeeping-system
mysql -u root -p rss < create_schedule_switch_table.sql
```

**Option B - Using phpMyAdmin:**
1. Open phpMyAdmin (http://localhost/phpmyadmin)
2. Select database: `rss`
3. Click "SQL" tab
4. Copy contents of `create_schedule_switch_table.sql`
5. Click "Go"

**Option C - Quick Check:**
```bash
cd C:\xampp\htdocs\Timekeeping-system
php check_switch_table.php
```
Then open browser: http://localhost/Timekeeping-system/check_switch_table.php

---

## 🧪 TESTING WORKFLOW

### Employee Side (Test Schedule Switch Request)

1. **Login as Employee**
   - Go to employee dashboard
   - Navigate to "Schedule" section

2. **Submit Switch Request**
   - Hover over a future date on calendar
   - Click purple **"Switch"** button (exchange icon)
   - Modal opens with Date A pre-filled
   - Select Date B (different future date)
   - See schedule preview showing what will swap
   - Enter reason
   - Upload supporting document (PDF/JPG/PNG)
   - Click "Submit Switch Request"

3. **Verify Submission**
   - Should see purple success message: "Schedule switch request submitted successfully!"
   - Check database:
     ```sql
     SELECT * FROM schedule_switch_requests ORDER BY created_at DESC LIMIT 1;
     ```

### Admin Side (Test Approval)

1. **Login as Admin**
   - Go to admin panel
   - Navigate to: Schedule Requests → **Switch Requests** tab

2. **View Switch Request**
   - See employee name
   - Date A with current schedule (purple background)
   - Date B with current schedule (indigo background)
   - Reason and attachment
   - Status: "Pending"

3. **Approve Request**
   - Click green "Approve" button
   - Confirm: "Are you sure you want to approve this schedule switch?"
   - System swaps schedules in `employee_daily_schedule_cache`
   - Status changes to "Approved"
   - Success message appears

4. **Verify Swap**
   - Check employee's calendar
   - Date A should now show Date B's original schedule
   - Date B should now show Date A's original schedule
   - Database check:
     ```sql
     SELECT * FROM employee_daily_schedule_cache 
     WHERE employee_id = ? AND schedule_date IN (?, ?)
     ORDER BY schedule_date;
     ```

### Decline Test

1. Click red "Decline" button
2. Modal opens asking for explanation
3. Enter admin notes
4. Click "Submit"
5. Status changes to "Rejected"
6. Schedules remain unchanged

---

## 📊 Database Table Structure

```sql
schedule_switch_requests (
    id                 INT (Primary Key, Auto Increment)
    employee_id        INT (Foreign Key → employees.id)
    source_date        DATE (Date A - switch from)
    target_date        DATE (Date B - switch to)
    reason             TEXT (Employee's explanation)
    attachment_path    VARCHAR(500) (Supporting document)
    status             ENUM('pending','approved','rejected','cancelled')
    created_at         TIMESTAMP (When submitted)
    processed_at       TIMESTAMP (When approved/declined)
    processed_by       INT (Admin who processed)
    admin_notes        TEXT (Decline reason)
)
```

---

## 🔍 Verification Checklist

- [ ] Database table created successfully
- [ ] Employee can see purple switch button on calendar hover
- [ ] Click switch button opens modal with Date A pre-filled
- [ ] Selecting Date B shows schedule preview
- [ ] Form submits successfully with file upload
- [ ] Success message appears after submission
- [ ] Record appears in `schedule_switch_requests` table
- [ ] Admin can view request in "Switch Requests" tab
- [ ] Admin can see both dates with their schedules
- [ ] Approve button swaps schedules correctly
- [ ] Decline button requires explanation
- [ ] Status updates properly (pending → approved/rejected)
- [ ] Employee calendar reflects swapped schedules after approval

---

## 🐛 Troubleshooting

### Issue: Form doesn't submit
- **Check**: Browser console for JavaScript errors
- **Verify**: Form action path is `../views/time_log_create.php`
- **Ensure**: Session is active (`$_SESSION['user_id']` exists)

### Issue: Database error on submit
- **Check**: Table exists: `SHOW TABLES LIKE 'schedule_switch_requests';`
- **Verify**: Columns match: `DESCRIBE schedule_switch_requests;`
- **Check**: Upload directory exists: `Public/uploads/schedule_switch/`

### Issue: File upload fails
- **Verify**: Directory permissions (0755)
- **Check**: File size < 10MB
- **Ensure**: File type is PDF, JPG, JPEG, or PNG
- **Check**: `upload_max_filesize` in php.ini

### Issue: Schedules don't swap after approval
- **Check**: `employee_daily_schedule_cache` has entries for both dates
- **Verify**: Transaction completed successfully
- **Check**: Error logs in browser and server

---

## 📝 Next Steps After Setup

1. **Create the table** using one of the methods above
2. **Test employee submission** end-to-end
3. **Test admin approval** to verify swap logic
4. **Test admin decline** to verify rejection flow
5. **Check calendar updates** after approval
6. **Train users** on new feature

---

## 💡 Feature Highlights

✨ **One-Click Switch**: Purple button appears on hover for easy access  
🎨 **Visual Preview**: Shows exactly what will be swapped before submission  
📎 **Document Upload**: Requires supporting documentation for accountability  
🔄 **Atomic Swap**: Uses database transactions for data integrity  
📊 **Full Audit Trail**: Tracks who, when, and why schedules were switched  
🎯 **Smart Validation**: Prevents past dates, same dates, missing data  
🔐 **Admin Control**: Requires approval before schedules change  

---

**Status**: Ready for deployment ✅  
**Last Updated**: November 13, 2025  
**Feature**: Schedule Switch Request System
