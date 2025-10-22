# Rest Day Option in Schedule Change Request

## Overview
Added the ability for employees to request **Rest Days** (days off) through the schedule change request form. This allows employees to request time off without needing to specify a work schedule.

## Implementation Date
October 21, 2025

## Changes Made

### 1. **New "Rest Day" Option in Schedule Selector**

**Location:** `Public/module/schedule_change_form.php`

#### Visual Design:
- **Position:** Always displayed at the top of the schedule grid
- **Color Scheme:** Red theme (to distinguish from work schedules)
- **Icon:** Bed icon (🛌) for rest day
- **Styling:** 
  - Red background on hover (`bg-red-50`)
  - Red border when selected
  - Red accent icon

#### Layout:
```
┌─────────────────────────────────────┐
│ 🛌 REST DAY REQUEST                 │
├─────────────────────────────────────┤
│ 🛏️  Rest Day / Day Off             │
│    Request a day off from work      │
└─────────────────────────────────────┘
```

### 2. **Updated Form Validation**

**Changes:**
- Removed `required` attribute from `work_schedule_id` hidden input
- Added custom validation to check if either a schedule OR rest day is selected
- Form now accepts empty `work_schedule_id` (indicates rest day request)

**Validation Logic:**
```javascript
if (!scheduleSearch.value.trim()) {
    alert('Please select a work schedule or rest day.');
    isValid = false;
}
```

### 3. **Enhanced Search Functionality**

**Keywords that trigger rest day:**
- "rest"
- "off" 
- "day off"
- "rest day"

**Example:** Typing "rest" or "off" will show the rest day option.

### 4. **Updated Selection Handler**

**JavaScript Function:** `selectSchedule()`

**Logic:**
```javascript
if (scheduleId === 'rest_day') {
    document.getElementById('work_schedule_id').value = ''; // Empty = rest day
    document.getElementById('selectedScheduleText').textContent = '🛌 Rest Day / Day Off';
}
```

### 5. **Visual Indicators**

#### When Selected:
- ✅ Green checkmark appears
- Red left border (3px solid #ef4444)
- Light red background (#fef2f2)
- Display shows: "🛌 Rest Day / Day Off"

#### CSS Classes:
```css
.rest-day-card:hover {
    background-color: #fef2f2; /* Light red */
}

.rest-day-card.selected {
    background-color: #fef2f2;
    border-left: 3px solid #ef4444; /* Red border */
}
```

## Backend Processing

### Form Submission Data:

**When Rest Day is Selected:**
```php
$_POST['work_schedule_id'] = ''; // Empty string or NULL
$_POST['date_range'] = '2025-10-25 to 2025-10-27';
$_POST['reason'] = 'Personal matters...';
$_POST['attachment_scr'] = [uploaded file]
```

### Database Storage:

**Table:** `post_schedule_change_requests`

**Rest Day Record:**
```sql
INSERT INTO post_schedule_change_requests 
(employee_id, start_date, end_date, work_schedule_id, reason, status) 
VALUES 
(123, '2025-10-25', '2025-10-27', NULL, 'Personal matters...', 'Pending');
```

**Key Points:**
- `work_schedule_id` = `NULL` indicates a rest day request
- When approved and displayed on calendar, it shows as a rest day (red card)

## Calendar Display

### When Approved Rest Day is Displayed:

**Priority:** PRIORITY 1 (Approved Schedule Change Requests)

**Logic in `schedule_content.php`:**
```php
if ($approvedChange['work_schedule_id']) {
    // Show the work schedule
    $cell['actual_schedule'] = $sched;
} else {
    // No work_schedule_id = Rest Day
    $cell['is_rest_day'] = 1;
    $cell['schedule_color'] = '#ef4444'; // Red
}
```

**Visual Result:**
- Red card background
- "REST DAY" label
- "APPROVED REQUEST" badge
- Purple "Approved" indicator in date header

## User Flow

### Employee Request Process:

1. **Open Schedule Change Modal**
   - Click on a date or "New Request" button

2. **Select Date Range**
   - Choose single day or multiple days

3. **Select Rest Day**
   - Click on "Rest Day / Day Off" option at top of list
   - OR search for "rest", "off", "day off"

4. **Provide Reason**
   - Explain why rest day is needed

5. **Upload Supporting Document**
   - Attach proof/justification (required)

6. **Submit Request**
   - Status: "Pending"
   - Awaits admin approval

### Admin Approval Process:

1. **Review Request**
   - Check reason and attachment

2. **Set Status to "Approved"**
   - Updates `status` column in database

3. **Automatic Calendar Update**
   - Approved rest day appears on employee's calendar
   - Shows as red "REST DAY" card
   - Overrides default work schedule

## Benefits

✅ **Flexibility:** Employees can request days off through same system
✅ **Consistency:** All schedule changes use one unified form
✅ **Clear Visual:** Red theme distinguishes rest days from work schedules
✅ **No Confusion:** Empty `work_schedule_id` clearly indicates rest day
✅ **Searchable:** Easy to find with keywords like "rest" or "off"

## Testing

### Test Scenarios:

#### 1. **Select Rest Day:**
```
1. Open modal
2. Click "Rest Day / Day Off"
3. Verify: Green checkmark appears
4. Verify: Display shows "🛌 Rest Day / Day Off"
```

#### 2. **Search for Rest Day:**
```
1. Type "rest" in search box
2. Verify: Rest day option appears
3. Type "off"
4. Verify: Rest day option still appears
```

#### 3. **Submit Rest Day Request:**
```
1. Select rest day
2. Choose date range
3. Enter reason
4. Upload attachment
5. Submit
6. Check database: work_schedule_id should be NULL
```

#### 4. **View Approved Rest Day:**
```
1. Approve a rest day request (set status = 'Approved')
2. View employee calendar
3. Verify: Date shows red "REST DAY" card
4. Verify: "APPROVED REQUEST" badge visible
```

## Technical Details

### Data Attributes:
```html
<div class="schedule-card rest-day-card"
     data-schedule-id="rest_day"
     data-schedule-name="Rest Day / Day Off"
     data-schedule-time="No Work Hours">
```

### Form Field Value:
- **Work Schedule Selected:** `work_schedule_id = "9"` (schedule ID)
- **Rest Day Selected:** `work_schedule_id = ""` (empty string)

### Console Logging:
```javascript
console.log('📝 Form submission data:', {
    work_schedule_id: workScheduleId || 'REST_DAY',
    is_rest_day: !workScheduleId
});
```

## Files Modified

1. **`Public/module/schedule_change_form.php`**
   - Added rest day HTML section
   - Updated JavaScript for rest day handling
   - Enhanced search to include rest day
   - Updated form validation
   - Added red theme CSS for rest day

2. **`Public/module/schedule_content.php`** (Already handles NULL work_schedule_id)
   - No changes needed - already supports displaying rest days

## Future Enhancements

- [ ] Add rest day statistics/reports
- [ ] Show remaining rest days/PTO balance
- [ ] Add calendar preview before submission
- [ ] Bulk rest day requests (multiple non-consecutive dates)
- [ ] Rest day patterns (every Friday, etc.)

---

**Status:** ✅ Active and Functional
**Version:** 1.0
**Last Updated:** October 21, 2025
