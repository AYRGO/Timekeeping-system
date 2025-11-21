# Monthly Schedule Request - Testing & Troubleshooting Guide

## Issue Fixed: Form Not Submitting

### What Was Wrong
1. ❌ The `schedule_date` field had `required` attribute even when using monthly schedule
2. ❌ Form validation was checking for date even in monthly mode
3. ❌ No proper handling of monthly request type in validation

### What Was Fixed
1. ✅ Removed `required` attribute from `schedule_date` field
2. ✅ Added conditional validation based on request type (single_day vs monthly)
3. ✅ Added proper logging for both request types
4. ✅ Created auto-processor for approved monthly requests
5. ✅ **Implemented "Present Day to End of Month" logic** in the processor

## Testing Steps

### 1. Database Setup

Run this SQL in phpMyAdmin:
```sql
USE `rss`;

CREATE TABLE IF NOT EXISTS month_weekly_schedule (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    year INT NOT NULL,
    month INT NOT NULL,
    
    -- Weekly schedule configuration (7 days: Sunday to Saturday)
    sunday_schedule_id INT NULL,
    sunday_is_rest_day TINYINT(1) DEFAULT 0,
    monday_schedule_id INT NULL,
    monday_is_rest_day TINYINT(1) DEFAULT 0,
    tuesday_schedule_id INT NULL,
    tuesday_is_rest_day TINYINT(1) DEFAULT 0,
    wednesday_schedule_id INT NULL,
    wednesday_is_rest_day TINYINT(1) DEFAULT 0,
    thursday_schedule_id INT NULL,
    thursday_is_rest_day TINYINT(1) DEFAULT 0,
    friday_schedule_id INT NULL,
    friday_is_rest_day TINYINT(1) DEFAULT 0,
    saturday_schedule_id INT NULL,
    saturday_is_rest_day TINYINT(1) DEFAULT 0,
    
    reason TEXT NOT NULL,
    attachment_path VARCHAR(255) NOT NULL,
    
    status ENUM('pending', 'approved', 'rejected', 'cancelled') DEFAULT 'pending',
    
    created_at TIMESTAMP DEFAULT CURRENT_timestamp,
    processed_at TIMESTAMP NULL,
    processed_by INT NULL,
    admin_notes TEXT NULL,
    
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
    FOREIGN KEY (processed_by) REFERENCES employees(id) ON DELETE SET NULL,
    
    INDEX idx_employee_month (employee_id, year, month),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 2. Verify Table Creation

Visit: `http://localhost/Timekeeping-system/Public/test_monthly_schedule.php`

You should see:
- ✅ Table exists confirmation
- Table structure
- List of records (if any)

### 3. Test Form Submission

1. **Navigate to Schedule Management**
   - Login to the system
   - Go to Schedule View

2. **Click "New Request"**

3. **Select "Monthly Schedule"**
   - Should show purple icon with calendar
   - Form should switch to monthly mode

4. **Fill in the Form:**
   - **Month**: Select current or future month (e.g., "2025-11" for November)
   - **Configure Weekly Schedule:**
     - Monday: Select "7:00 AM - 4:00 PM" (or any schedule)
     - Tuesday: Select "7:00 AM - 4:00 PM"
     - Wednesday: Select "7:00 AM - 4:00 PM"
     - Thursday: Select "7:00 AM - 4:00 PM"
     - Friday: Select "7:00 AM - 4:00 PM"
     - Saturday: Select "Rest Day"
     - Sunday: Select "Rest Day"
   - **Reason**: Enter a reason (e.g., "Requesting standard work schedule")
   - **Attachment**: Upload a PDF or image file

5. **Open Browser Console** (F12)
   - You should see logging like:
   ```
   📝 Monthly form submission data: {
     schedule_month: "2025-11",
     request_type: "monthly",
     selected_schedules: {...},
     has_attachment: true,
     attachment_name: "document.pdf"
   }
   ✅ Form validation passed, submitting...
   ```

6. **Submit the Form**
   - Should redirect to success page
   - Check URL for: `?schedule_change=monthly_success`
   - Should see green success message

### 4. Verify Database Record

1. Go back to: `http://localhost/Timekeeping-system/Public/test_monthly_schedule.php`
2. You should see your new record in the table
3. Check the weekly schedule details are saved correctly

### 5. Test Approval Processing

**Important: This demonstrates the "Present Day to End of Month" feature**

1. **Manually Approve a Request (in phpMyAdmin)**:
   ```sql
   UPDATE month_weekly_schedule 
   SET status = 'approved' 
   WHERE id = [YOUR_REQUEST_ID];
   ```

2. **Visit Schedule View Page**
   - The auto-processor will run automatically
   - Check browser console for:
   ```
   ✅ Monthly schedule processor: Processed X monthly request(s)
   🔄 Reloading calendar to show updated schedules...
   ```

3. **Verify Schedule Application**:
   - Check `employee_daily_schedule_cache` table:
   ```sql
   SELECT * FROM employee_daily_schedule_cache 
   WHERE employee_id = [YOUR_ID] 
   AND schedule_date >= CURDATE()
   AND source = 'approved_monthly_request'
   ORDER BY schedule_date;
   ```
   
   **You should see:**
   - ✅ Records start from **TODAY** (not from start of month)
   - ✅ Records end on **LAST DAY OF MONTH**
   - ✅ Each day has the schedule you configured for that day of week
   - ✅ Source is 'approved_monthly_request'

## How "Present Day to End of Month" Works

### Example Scenario:
- **Today**: November 11, 2025 (Monday)
- **Request**: Monthly schedule for November 2025
- **Configuration**: 
  - Monday-Friday: 7:00 AM - 4:00 PM
  - Saturday-Sunday: Rest Day

### What Happens When Approved:

The processor (`ajax_process_monthly_schedules.php`) will:

1. **Calculate Date Range:**
   - Start Date: `2025-11-11` (today)
   - End Date: `2025-11-30` (last day of November)
   - **NOT** from November 1st!

2. **Apply Schedule:**
   ```
   Nov 11 (Mon) → 7:00 AM - 4:00 PM
   Nov 12 (Tue) → 7:00 AM - 4:00 PM
   Nov 13 (Wed) → 7:00 AM - 4:00 PM
   Nov 14 (Thu) → 7:00 AM - 4:00 PM
   Nov 15 (Fri) → 7:00 AM - 4:00 PM
   Nov 16 (Sat) → Rest Day
   Nov 17 (Sun) → Rest Day
   Nov 18 (Mon) → 7:00 AM - 4:00 PM
   ... continues until Nov 30
   ```

3. **Past Dates Ignored:**
   - Nov 1-10 are NOT affected (already passed)
   - Only future dates from today onwards are updated

### Special Cases:

**Case 1: Future Month**
- Request: December 2025 (submitted on Nov 11)
- Result: Applies from Dec 1 to Dec 31

**Case 2: Past Month**
- Request: October 2025 (submitted on Nov 11)
- Result: Skipped entirely (month already passed)

**Case 3: Current Month, Mid-month**
- Request: November 2025 (submitted on Nov 15)
- Result: Applies from Nov 15 to Nov 30 only

## Troubleshooting

### Issue: "Please select a date before submitting"
**Solution**: Make sure you selected "Monthly Schedule" not "Single Day"

### Issue: Form submits but no database record
**Solution**: 
1. Check browser console for errors
2. Check PHP error log
3. Verify file upload permissions on `uploads/schedule_requests/` folder

### Issue: No error but record not in database
**Solution**:
1. Check if table exists: `SHOW TABLES LIKE 'month_weekly_schedule';`
2. Check foreign key constraints: Employee ID must exist in `employees` table
3. Review PHP error log for PDO exceptions

### Issue: Approved but schedule not applying
**Solution**:
1. Check if `employee_daily_schedule_cache` table exists
2. Verify the auto-processor is running (check browser console)
3. Manually run: `http://localhost/Timekeeping-system/Public/controller/ajax_process_monthly_schedules.php`
4. Check `processed_at` field is NULL before processing

### Issue: Schedule applies to past dates
**Solution**: This should NOT happen with the fixed code. If it does:
1. Check the processor logic in `ajax_process_monthly_schedules.php`
2. Verify line with: `if (strtotime($firstDayOfMonth) > $todayTimestamp)`

## Files Changed

1. ✅ `create_month_weekly_schedule.sql` - Database table
2. ✅ `schedule_change_form.php` - Modal form with monthly option
3. ✅ `time_log_create.php` - Handle monthly submissions
4. ✅ `schedule_content.php` - Success messages and auto-processor
5. ✅ `ajax_process_monthly_schedules.php` - Process approved requests (PRESENT DAY TO END OF MONTH)
6. ✅ `test_monthly_schedule.php` - Testing tool

## Next Steps

1. ✅ Test form submission
2. ✅ Verify database records
3. ✅ Test approval processing
4. ✅ Verify "present day to end of month" logic
5. ⏳ Create admin approval interface (optional)
6. ⏳ Add notification system (optional)
