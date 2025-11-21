# Monthly Schedule Request Feature

## Overview
This feature allows employees to submit schedule change requests for an entire month at once, rather than submitting individual requests for each day.

## Database Setup

### 1. Create the Database Table
Run the SQL file to create the new table:

```bash
# In MySQL/phpMyAdmin, execute:
create_month_weekly_schedule.sql
```

This creates the `month_weekly_schedule` table with the following structure:
- Employee information (employee_id, year, month)
- Weekly schedule configuration (7 days: Sunday through Saturday)
- Each day can have: work_schedule_id OR is_rest_day flag
- Reason, attachment, status tracking
- Admin approval fields

## How It Works

### Employee Side

1. **Open Schedule Management**
   - Navigate to Schedule Management section
   - Click "New Request" button

2. **Select Request Type**
   - Two options available:
     - **Single Day**: Change schedule for one specific date (existing feature)
     - **Monthly Schedule**: Set schedule for entire month (new feature)

3. **Configure Monthly Request**
   - Select the month (current month or future months only)
   - Configure weekly schedule:
     - Set schedule for each day of the week (Sunday-Saturday)
     - Each day can be:
       - A specific work schedule (e.g., "7:00 AM - 4:00 PM")
       - Rest Day
       - Or left blank (keeps default)
   - Enter reason for the change
   - Upload supporting document (required)

4. **Submit Request**
   - Request is saved with "pending" status
   - Admin receives notification for approval

### Admin Side

Admins will be able to:
- View pending monthly schedule requests
- Review the weekly configuration
- Approve or reject requests
- Add admin notes

## Features

### Request Type Selector
- Clean, modern UI with radio buttons
- Visual feedback for selected option
- Automatic section switching

### Monthly Schedule Form
- Month picker (prevents past months)
- 7 individual dropdowns for each day of the week
- Each dropdown includes:
  - All available work schedules
  - "Rest Day" option
  - Empty option to keep default
- Visual day indicators with icons
- Informative note about how the schedule applies

### Validation
- Ensures month is selected
- Requires at least one day to have a schedule configured
- Validates attachment upload
- Prevents past month selection

### Database Storage
All monthly requests are stored in `month_weekly_schedule` table with:
- Employee and month identification
- Complete weekly configuration (7 days × 2 fields each)
- Request metadata (reason, attachment, status)
- Approval tracking (processed_by, processed_at, admin_notes)

## File Changes

### New Files
1. `create_month_weekly_schedule.sql` - Database table creation
2. `MONTHLY_SCHEDULE_REQUEST_FEATURE.md` - This documentation

### Modified Files
1. `Public/module/schedule_change_form.php`
   - Added request type selector
   - Added monthly schedule section
   - Added JavaScript for section switching
   - Enhanced form validation

2. `Public/module/time_log_create.php`
   - Added monthly request handling
   - File upload for monthly requests
   - Database insertion logic

3. `Public/module/schedule_content.php`
   - Added success message for monthly requests

## Usage Examples

### Example 1: Regular Work Schedule
Employee wants to work 7:00 AM - 4:00 PM Monday-Friday for January 2026:
- Select "Monthly Schedule"
- Choose "2026-01"
- Set Monday-Friday: "7:00 AM - 4:00 PM"
- Set Saturday-Sunday: "Rest Day"
- Provide reason and attachment

### Example 2: Night Shift Schedule
Employee wants night shifts for February 2026:
- Select "Monthly Schedule"
- Choose "2026-02"
- Set desired days: "10:00 PM - 6:00 AM" (night shift)
- Set rest days as needed
- Provide reason and attachment

### Example 3: Mixed Schedule
Employee wants different schedules for different days:
- Select "Monthly Schedule"
- Choose desired month
- Monday-Wednesday: "7:00 AM - 4:00 PM"
- Thursday-Friday: "1:00 PM - 9:00 PM"
- Weekend: "Rest Day"
- Provide reason and attachment

## Benefits

1. **Efficiency**: Submit one request instead of 20-31 individual requests
2. **Clarity**: See the entire week's schedule at once
3. **Flexibility**: Mix different schedules and rest days
4. **Simplicity**: Clean, intuitive interface
5. **Validation**: Prevents errors and ensures completeness

## Next Steps (Admin Implementation)

To complete this feature, you'll need to:

1. **Admin View Page**
   - List all pending monthly requests
   - Show employee name, month, and status
   - Display the weekly configuration

2. **Approval/Rejection**
   - Approve button → Updates status and processes the schedule
   - Reject button → Updates status with reason
   - Process approved requests by updating employee_daily_schedule_cache

3. **Notification System**
   - Notify admins of new monthly requests
   - Notify employees when requests are approved/rejected

## Technical Notes

- The weekly configuration applies to ALL matching days in the month
  - e.g., If Monday = "7:00 AM - 4:00 PM", ALL Mondays get this schedule
- Past months are blocked at both client and server level
- File uploads are stored in `uploads/schedule_requests/` directory
- Uses transaction-safe database operations
- Proper error handling and logging

## Security Considerations

- CSRF token validation
- File type validation (PDF, JPG, JPEG, PNG only)
- Employee ID validation from session
- Past month prevention
- SQL injection prevention with prepared statements
