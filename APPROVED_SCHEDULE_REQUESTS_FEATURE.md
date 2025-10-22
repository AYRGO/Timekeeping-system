# Approved Schedule Requests Feature

## Overview
This feature automatically displays approved schedule change requests from the `post_schedule_change_requests` table directly on the employee calendar. When a schedule change request is approved for a date range, it will override the default schedule and display with a distinctive purple styling.

## Implementation Details

### Database Table
**Table:** `post_schedule_change_requests`

**Key Columns:**
- `employee_id` - The employee requesting the change
- `status` - ENUM('Pending','Approved','Declined')
- `start_date` - Start of the date range
- `end_date` - End of the date range (inclusive)
- `work_schedule_id` - The new work schedule to apply (from `work_schedules` table)
- `reason` - Reason for the schedule change request
- `created_at` - When the request was created

### Priority System
The calendar now checks schedules in this priority order:

1. **PRIORITY 1: Approved Schedule Change Requests** (NEW)
   - Checks `post_schedule_change_requests` table
   - Status must be 'Approved'
   - Date must fall within `start_date` and `end_date` range
   - **Color: Purple (#8b5cf6)**
   - **Badge: "APPROVED REQUEST"**

2. **PRIORITY 2: Daily Overrides**
   - Checks `employee_daily_schedules` table
   - **Color: Green**
   - **Badge: "OVERRIDE"**

3. **PRIORITY 3: Weekly Default Schedules**
   - Checks `employee_default_schedules` table
   - Based on day of week (0=Sunday, 6=Saturday)
   - **Color: Green**

4. **PRIORITY 4: Company Holidays**
   - Checks `company_holidays` table
   - **Color: Amber/Yellow**

5. **PRIORITY 5: Weekend Fallback**
   - Sunday (0) or Saturday (6)
   - **Color: Light Gray**

### Visual Indicators

#### Approved Schedule Requests Display:
- **Purple background** (bg-purple-50)
- **Purple border** (border-purple-200)
- **Purple accent bar** on the left side
- **"APPROVED REQUEST" badge** at the top of the schedule card
- **"Approved" label** in the date header (purple text)

#### Calendar Cell Structure:
```
┌─────────────────────────┐
│ 15        [Approved]    │ ← Date number + Status badge
├─────────────────────────┤
│ ║ APPROVED REQUEST      │ ← Purple accent + Badge
│ ║ Morning Shift         │ ← Schedule name
│ ║ 🕐 8:00 AM           │ ← Time in
│ ║ → 4:30 PM            │ ← Time out
└─────────────────────────┘
```

## How It Works

### SQL Query
```sql
SELECT * FROM post_schedule_change_requests 
WHERE employee_id = ? 
AND status = 'Approved' 
AND ? BETWEEN start_date AND end_date 
ORDER BY created_at DESC 
LIMIT 1
```

### Example Scenario
If an employee has an approved schedule change request:
- **Employee ID:** 75
- **Status:** Approved
- **Start Date:** 2025-08-13
- **End Date:** 2025-08-31
- **New Schedule ID:** 9 (e.g., "8:00 AM - 4:30 PM")

**Result:** Every day from August 13-31, 2025, the calendar will show:
- The new schedule (8:00 AM - 4:30 PM)
- Purple styling
- "APPROVED REQUEST" badge
- This overrides any weekly default schedules

## Benefits

1. **Automatic Override:** Approved requests automatically appear on the calendar without manual processing
2. **Date Range Support:** Handles multi-day schedule changes (e.g., entire month changes)
3. **Visual Distinction:** Purple color clearly indicates approved changes vs regular schedules
4. **Priority-Based:** Ensures approved requests take precedence over default schedules
5. **Real-Time Display:** Changes appear immediately once status is set to 'Approved'

## Usage

### For Employees:
- View your approved schedule changes directly on the calendar
- Purple cards indicate schedule changes that have been approved
- See the exact time range and schedule details

### For Administrators:
1. Review schedule change requests in the admin panel
2. Set status to 'Approved' for valid requests
3. Changes automatically appear on employee calendars
4. No need to manually update `employee_daily_schedules` table

## Files Modified

- **`Public/module/schedule_content.php`**
  - Added new PRIORITY 1 check for approved schedule requests
  - Updated `getScheduleCell_schedule()` function
  - Added purple color scheme for approved requests
  - Added "APPROVED REQUEST" badge display

## Testing

### Test Scenarios:
1. ✅ Single day approved request
2. ✅ Multi-day approved request (date range)
3. ✅ Approved request overrides weekly default
4. ✅ Purple styling displays correctly
5. ✅ Badge shows on schedule card
6. ✅ "Approved" label shows in date header

### Sample Test Data:
```sql
INSERT INTO post_schedule_change_requests 
(employee_id, reason, status, start_date, end_date, work_schedule_id) 
VALUES 
(1, 'Client approved schedule change', 'Approved', '2025-10-22', '2025-10-31', 9);
```

## Future Enhancements

- [ ] Add tooltip showing request reason on hover
- [ ] Display requester/approver information
- [ ] Add ability to click and view full request details
- [ ] Export approved schedule changes to reports
- [ ] Notification when approved request takes effect

---

**Date Implemented:** October 21, 2025
**Version:** 1.0
**Status:** Active
