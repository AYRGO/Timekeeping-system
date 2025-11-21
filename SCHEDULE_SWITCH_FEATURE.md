# Schedule Switch Feature - Implementation Guide

## ✅ Feature Overview

The **Schedule Switch** feature allows employees to request swapping their schedules between two dates. This is different from a schedule change request - instead of changing one date to a new schedule, the employee swaps schedules between two existing dates.

### Example Use Case:
- **Date A (Nov 20):** Currently scheduled for "Day shift: 6:00 AM – 5:00 PM"
- **Date B (Nov 25):** Currently scheduled for "Rest Day"
- **Switch Request:** Employee wants to swap these - work on Nov 25 instead, take rest on Nov 20

---

## 🎯 Features Implemented

### 1. **UI Integration**
- **Hover Buttons on Calendar Cells:**
  - 🟣 Purple "Switch" button (`fa-exchange-alt` icon)
  - 🟢 Green "Change" button (`fa-edit` icon)
  - Only visible on hover for future dates
  - Hidden for past dates and holidays

### 2. **Schedule Switch Modal**
- **Modern purple-themed modal**
- **Smart date handling:**
  - Date A (source): Auto-filled from clicked calendar date
  - Date B (target): User selects any future date
- **Real-time schedule preview:**
  - Fetches current schedule for both dates via AJAX
  - Shows what schedules will be swapped
  - Visual preview with arrows showing the swap
- **Required fields:**
  - Target date selection
  - Reason for switch (textarea)
  - Supporting document (PDF, JPG, JPEG, PNG)

### 3. **Backend Processing**
- **New database table:** `schedule_switch_requests`
- **Validation checks:**
  - Both dates must be in the future
  - Dates cannot be the same
  - All required fields must be filled
  - File upload with type validation
- **Status tracking:** pending → approved/rejected/cancelled

---

## 🗄️ Database Setup

### Run this SQL command:

```sql
CREATE TABLE IF NOT EXISTS schedule_switch_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    source_date DATE NOT NULL COMMENT 'Date A - The date employee wants to switch from',
    target_date DATE NOT NULL COMMENT 'Date B - The date employee wants to switch to',
    reason TEXT NOT NULL,
    attachment_path VARCHAR(500) DEFAULT NULL,
    status ENUM('pending', 'approved', 'rejected', 'cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    processed_at TIMESTAMP NULL DEFAULT NULL,
    processed_by INT DEFAULT NULL,
    admin_notes TEXT DEFAULT NULL,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
    INDEX idx_employee (employee_id),
    INDEX idx_dates (source_date, target_date),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Schedule switch requests - swap schedules between two dates';
```

**Or run the SQL file:**
```bash
mysql -u root -p rss < create_schedule_switch_table.sql
```

---

## 📁 Files Modified

### 1. **schedule_content.php** (Main calendar display)
**Changes:**
- Added hover action buttons to calendar cells (lines ~375-388)
- Created new Schedule Switch Modal (lines ~500-608)
- Added JavaScript functions for modal handling
- Added success/error message displays

### 2. **time_log_create.php** (Backend handler)
**Changes:**
- Added schedule switch request handler (lines ~15-103)
- File upload handling for switch requests
- Database insertion with validation
- Redirect with success/error messages

### 3. **create_schedule_switch_table.sql** (New file)
- Database schema for switch requests

---

## 🎨 How It Works

### User Flow:

1. **Employee hovers over a future date** on the calendar
   - Two buttons appear: "Switch" (purple) and "Change" (green)

2. **Clicks "Switch" button**
   - Modal opens with Date A pre-filled
   - Shows current schedule for Date A

3. **Selects Date B** (target date)
   - System fetches and displays Date B's current schedule
   - Preview box shows the swap: "Date A schedule ↔ Date B schedule"

4. **Fills in reason** and **uploads supporting document**

5. **Submits request**
   - Validated on backend
   - Saved to database with "pending" status
   - Success message displayed

6. **Admin reviews request** (admin panel - to be implemented)
   - Approves or rejects
   - If approved: Schedules are swapped in the system

---

## 🔧 URL Parameters for Messages

### Success:
```
?schedule_switch=success#scheduleView
```
→ "Schedule switch request submitted successfully!"

### Errors:
- `?schedule_switch=missing_dates` - Missing one or both dates
- `?schedule_switch=same_dates` - Source and target are the same
- `?schedule_switch=past_dates` - One or both dates are in the past
- `?schedule_switch=no_reason` - Reason field empty
- `?schedule_switch=no_attachment` - No file uploaded
- `?schedule_switch=invalid_file` - Wrong file type
- `?schedule_switch=upload_failed` - File upload error
- `?schedule_switch=error` - Database error

---

## 🎯 Next Steps (Admin Panel Implementation Needed)

### To complete the feature, you need to add:

1. **Admin approval interface** (in admin dashboard)
   - List pending switch requests
   - Show both dates and their current schedules
   - Approve/Reject buttons
   - Add admin notes

2. **Processing logic when approved**
   ```php
   // When admin approves:
   // 1. Get schedules for both dates
   // 2. Swap them:
   //    - Date A gets Date B's schedule
   //    - Date B gets Date A's schedule
   // 3. Update employee_daily_schedule_cache table
   // 4. Mark request as approved
   ```

3. **View switch requests in employee dashboard**
   - Show pending/approved/rejected switch requests
   - Display dates being swapped
   - Show status and admin notes

---

## 📋 Testing Checklist

- [ ] Run SQL to create `schedule_switch_requests` table
- [ ] Clear browser cache
- [ ] Log in as employee
- [ ] Navigate to Schedule view
- [ ] Hover over a future date (should see purple + green buttons)
- [ ] Click purple "Switch" button
- [ ] Verify Date A is pre-filled
- [ ] Select Date B (different future date)
- [ ] Verify both schedules display correctly
- [ ] Verify preview shows the swap
- [ ] Fill in reason and upload document
- [ ] Submit and verify success message
- [ ] Check database: `SELECT * FROM schedule_switch_requests;`

---

## 🎨 Design Highlights

- **Purple theme** for switch feature (distinct from green "change" button)
- **Hover-only buttons** to keep calendar clean
- **Real-time feedback** with AJAX schedule fetching
- **Visual preview** of the swap before submission
- **Responsive design** works on mobile/tablet
- **Smooth animations** with Tailwind CSS

---

## 💡 User Benefits

✅ **Flexibility:** Swap work days and rest days easily  
✅ **Transparency:** See exactly what will be swapped before submitting  
✅ **Documentation:** Required attachment ensures legitimate requests  
✅ **Audit trail:** All requests tracked with timestamps and status  

---

## 🔗 Related Features

- **Schedule Change:** Change one date to a new schedule (green button)
- **Monthly Schedule:** Set recurring weekly schedule for a month
- **Rest Day Request:** Request a day off
- **Overtime Request:** Request OT for a specific date

---

**Feature Status:** ✅ Frontend Complete | ⏳ Admin Panel Pending  
**Created:** November 13, 2025  
**Version:** 1.0
