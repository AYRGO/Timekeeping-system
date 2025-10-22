# Rest Day Feature - Recent Activity Integration

## Overview
Updated the system to properly distinguish between "Schedule Change Request" and "Add a Day Off" in the Recent Activity section based on whether the request is for a rest day or a regular schedule change.

## Implementation Date
October 21, 2025

## Database Changes

### 1. New Column: `is_rest_day`
**Table:** `schedule_change_requests`

```sql
ALTER TABLE schedule_change_requests 
ADD COLUMN is_rest_day TINYINT(1) DEFAULT 0 
COMMENT '1 if this is a rest day request, 0 if regular schedule change';
```

**Purpose:** Flag to differentiate between rest day requests and schedule change requests

**Values:**
- `0` = Regular schedule change (has `work_schedule_id`)
- `1` = Rest day request (`work_schedule_id` is NULL)

## Backend Changes

### 1. Updated Schedule Request Processing
**File:** `Public/module/time_log_create.php`

#### Key Changes:
```php
// Determine if this is a rest day request
$is_rest_day = empty($work_schedule_id);

// Validation now allows empty work_schedule_id for rest days
if (!$is_rest_day && !is_numeric($work_schedule_id)) {
    header("Location: time_log_create.php?schedule_change=invalid_data");
    exit;
}

// Insert with is_rest_day flag
$stmt->execute([
    $employee_id,
    $is_rest_day ? null : $work_schedule_id, // NULL if rest day
    $current_real_schedule_id,
    $reason,
    $start_date,
    $end_date,
    $attachmentPath,
    $is_rest_day ? 1 : 0 // Flag for rest day
]);

// Different redirect based on type
if ($is_rest_day) {
    header("Location: time_log_create.php?rest_day=success");
} else {
    header("Location: time_log_create.php?schedule_change=success");
}
```

## Frontend Changes

### 1. Recent Activity Display
**File:** `Public/module/recent_activity_card.php`

**Updated Display Logic:**
```php
<?php elseif ($type === 'Schedule'): ?>
    <?php
    // Check if this is a rest day request
    $isRestDay = (empty($activity['work_schedule_id']) || ($activity['is_rest_day'] ?? 0) == 1);
    echo $isRestDay ? 'Add a Day Off' : 'Schedule Change Request';
    ?>
```

**Result:**
- Rest day requests display as: **"Add a Day Off"**
- Schedule changes display as: **"Schedule Change Request"**

### 2. Success Messages
**File:** `Public/module/schedule_content.php`

#### Added Rest Day Success Message:
```php
// Display rest day success message
if (isset($_GET['rest_day'])) {
    $rest_day_msg = $_GET['rest_day'];
    if ($rest_day_msg === 'success') {
        echo '<div class="mb-6 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg">
            <i class="fas fa-check-circle mr-2"></i>Day off request submitted successfully! Please wait for approval.
        </div>';
    }
}
```

**File:** `Public/module/time_log_create.php`

#### Added Alert Handler:
```javascript
const alerts = {
    ...
    rest_day: {
        success: "Day off request submitted successfully!"
    },
    ...
};
```

## Form Changes

### Schedule Change Form
**File:** `Public/module/schedule_change_form.php`

#### Rest Day Option:
- Added at the top of schedule selection
- Red theme to distinguish from work schedules
- data-schedule-id="rest_day"
- Sets `work_schedule_id` to empty string when selected

#### Validation:
- Removed `required` attribute from `work_schedule_id`
- Added custom validation to ensure either schedule OR rest day is selected
- Form submission logs `is_rest_day: true/false`

## User Flow

### Submitting a Rest Day Request:

1. **User clicks "New Request"** on schedule calendar

2. **Selects "Rest Day / Day Off"** option (red card at top)
   - work_schedule_id = "" (empty)

3. **Fills in:**
   - Date range
   - Reason
   - Attachment

4. **Submits form**
   - Backend sets `is_rest_day = 1`
   - `work_schedule_id = NULL`
   - Redirects to: `?rest_day=success`

5. **Success message shows:**
   - "Day off request submitted successfully! Please wait for approval."

6. **Recent Activity displays:**
   - **Type:** "Add a Day Off"
   - **Status:** Pending
   - **Date:** Request date

### Submitting a Schedule Change Request:

1. **User clicks "New Request"**

2. **Selects a work schedule** (e.g., "7:00 AM - 4:00 PM")
   - work_schedule_id = 9 (or other ID)

3. **Fills in form and submits**
   - Backend sets `is_rest_day = 0`
   - `work_schedule_id = 9`
   - Redirects to: `?schedule_change=success`

4. **Success message shows:**
   - "Schedule change request submitted successfully! Please wait for approval."

5. **Recent Activity displays:**
   - **Type:** "Schedule Change Request"
   - **Status:** Pending
   - **Date:** Request date

## Display Differences

| Field | Rest Day Request | Schedule Change Request |
|-------|-----------------|------------------------|
| **Activity Type** | Add a Day Off | Schedule Change Request |
| **work_schedule_id** | NULL | Valid ID (e.g., 9) |
| **is_rest_day** | 1 | 0 |
| **Success Message** | "Day off request submitted" | "Schedule change request submitted" |
| **URL Parameter** | `?rest_day=success` | `?schedule_change=success` |
| **Calendar Display** | Red "REST DAY" card | Purple "APPROVED REQUEST" card |

## Technical Details

### Detection Logic:

Rest day is identified by:
```php
$isRestDay = (empty($activity['work_schedule_id']) || ($activity['is_rest_day'] ?? 0) == 1);
```

This checks:
1. `work_schedule_id` is empty/NULL **OR**
2. `is_rest_day` column equals 1

### Database Query Example:

```sql
-- Get all rest day requests
SELECT * FROM schedule_change_requests 
WHERE is_rest_day = 1 OR work_schedule_id IS NULL;

-- Get all schedule change requests (not rest days)
SELECT * FROM schedule_change_requests 
WHERE is_rest_day = 0 AND work_schedule_id IS NOT NULL;
```

## Migration Instructions

1. **Run the migration SQL:**
   ```bash
   mysql -u username -p database_name < migration_add_is_rest_day_column.sql
   ```

2. **Update existing records:**
   ```sql
   UPDATE schedule_change_requests 
   SET is_rest_day = 1 
   WHERE work_schedule_id IS NULL;
   ```

3. **Verify:**
   ```sql
   SELECT id, employee_id, work_schedule_id, is_rest_day 
   FROM schedule_change_requests 
   LIMIT 10;
   ```

## Testing

### Test Case 1: Submit Rest Day Request
```
1. Open schedule modal
2. Select "Rest Day / Day Off"
3. Choose date range: Oct 25-27, 2025
4. Enter reason
5. Upload attachment
6. Submit
7. ✓ Verify redirect to ?rest_day=success
8. ✓ Check Recent Activity shows "Add a Day Off"
9. ✓ Check database: is_rest_day = 1, work_schedule_id = NULL
```

### Test Case 2: Submit Schedule Change
```
1. Open schedule modal
2. Select "8:00 AM - 4:30 PM"
3. Choose date range
4. Enter reason
5. Upload attachment
6. Submit
7. ✓ Verify redirect to ?schedule_change=success
8. ✓ Check Recent Activity shows "Schedule Change Request"
9. ✓ Check database: is_rest_day = 0, work_schedule_id = 9
```

### Test Case 3: Backward Compatibility
```
1. Check existing requests with NULL work_schedule_id
2. ✓ Should display as "Add a Day Off"
3. ✓ Migration should set is_rest_day = 1
```

## Files Modified

1. ✅ `migration_add_is_rest_day_column.sql` - Database migration
2. ✅ `Public/module/time_log_create.php` - Backend processing
3. ✅ `Public/module/recent_activity_card.php` - Display logic
4. ✅ `Public/module/schedule_content.php` - Success messages
5. ✅ `Public/module/schedule_change_form.php` - Form handling (already done)

## Benefits

✅ **Clear Distinction:** Users can easily tell rest days from schedule changes
✅ **Better UX:** More accurate terminology in notifications
✅ **Data Integrity:** `is_rest_day` flag provides explicit classification
✅ **Backward Compatible:** Existing NULL work_schedule_id records still work
✅ **Consistent:** Same pattern across all views (calendar, recent activity, admin)

## Future Enhancements

- [ ] Add icon differentiation (🛌 for rest days, 📅 for schedule changes)
- [ ] Filter recent activity by type (rest days only, schedule changes only)
- [ ] Bulk rest day approval in admin panel
- [ ] Rest day statistics/reporting

---

**Status:** ✅ Implemented and Active
**Version:** 1.1
**Last Updated:** October 21, 2025
