# Advanced Calendar Scheduling System - Complete Guide

## 🎯 Overview

The Advanced Calendar Scheduling System provides a comprehensive, priority-based schedule management solution for employees. Admins can now assign schedules at multiple levels with automatic conflict resolution.

---

## 📊 Database Tables

### Core Tables

1. **`employee_daily_schedules`** - Highest Priority
   - Stores date-specific schedule overrides
   - Fields: `employee_id`, `schedule_date`, `work_schedule_id`, `is_rest_day`, `notes`
   - Use: One-time schedule changes or OFF days

2. **`employee_default_schedules`** - Medium Priority
   - Default Monday-Sunday weekly pattern
   - Fields: `employee_id`, `monday`, `tuesday`, `wednesday`, `thursday`, `friday`, `saturday`, `sunday`
   - Use: Regular weekly work schedule

3. **`employee_rotating_schedules`** - Medium Priority
   - Links employees to rotating patterns
   - Fields: `employee_id`, `pattern_id`, `start_date`, `cycle_start_date`, `end_date`
   - Use: Shift workers with rotating schedules

4. **`rotating_schedule_patterns`** - Pattern Definition
   - Defines rotation patterns (e.g., 4-day cycle, 2-week rotation)
   - Fields: `pattern_name`, `description`, `cycle_length`, `is_active`

5. **`rotating_schedule_pattern_days`** - Pattern Details
   - Details of each day in a rotation
   - Fields: `pattern_id`, `day_number`, `work_schedule_id`, `is_rest_day`

6. **`company_holidays`** - Holiday Calendar
   - Company-wide holidays
   - Fields: `holiday_name`, `holiday_date`, `holiday_type`, `is_recurring`
   - Pre-loaded with Philippine 2025 holidays

7. **`schedule_override_history`** - Audit Trail
   - Complete change history
   - Fields: `employee_id`, `change_type`, `change_details`, `changed_by`, `changed_at`

---

## 🔄 Schedule Priority System

The system uses a **strict priority hierarchy** to determine an employee's schedule for any given date:

```
1. Daily Override (employee_daily_schedules) ← HIGHEST PRIORITY
   ↓ (if not found)
2. Rotating Schedule (employee_rotating_schedules + patterns)
   ↓ (if not found)
3. Weekly Default (employee_default_schedules)
   ↓ (if not found)
4. Company Holiday (company_holidays)
   ↓ (if not found)
5. Weekend Detection (Saturday/Sunday)
   ↓ (if not found)
6. No Schedule / Rest Day ← LOWEST PRIORITY
```

### Priority Examples

**Example 1:** Employee has weekly default (Mon-Fri 8-5), but admin sets Oct 20 as OFF
- Result: Oct 20 shows as OFF (daily override wins)

**Example 2:** Employee on 4-day rotating pattern, admin sets specific schedule for Oct 25
- Result: Oct 25 uses the specific schedule (daily override beats rotation)

**Example 3:** Employee has both weekly default and rotating schedule active
- Result: Rotating schedule takes precedence

---

## 💼 Admin Features

### 1. Weekly Schedule Tab
**Location:** Employee Edit → Weekly Schedule Tab

**Features:**
- Assign default schedule for each day of the week (Mon-Sun)
- Select from available work schedules or set as OFF
- Changes apply ongoing until overridden

**Use Case:**
```
Employee: John Doe
Monday: Schedule 5 (8 AM - 5 PM)
Tuesday: Schedule 5 (8 AM - 5 PM)
Wednesday: Schedule 5 (8 AM - 5 PM)
Thursday: Schedule 5 (8 AM - 5 PM)
Friday: Schedule 5 (8 AM - 5 PM)
Saturday: OFF
Sunday: OFF
```

### 2. Rotating Schedule Tab
**Location:** Employee Edit → Rotating Schedule Tab

**Features:**
- Assign predefined rotation patterns
- Set start date and cycle start date
- Optional end date (leave blank for ongoing)
- View pattern preview with all days in cycle
- Remove rotation when no longer needed

**Use Case:**
```
Pattern: 4-Day Shift Rotation
Cycle Length: 4 days
Day 1: Morning Shift (6 AM - 2 PM)
Day 2: Afternoon Shift (2 PM - 10 PM)
Day 3: Night Shift (10 PM - 6 AM)
Day 4: OFF

Employee starts pattern on Oct 1, 2025
Cycle repeats every 4 days indefinitely
```

### 3. Current Schedule Calendar
**Location:** Employee Edit → Current Schedule Tab

**Features:**
- Visual calendar showing computed schedules
- Color-coded status indicators
- Click any date to add override
- Select schedule or mark as OFF
- Add reason/notes for override
- View upcoming daily overrides

**Color Legend:**
- 🟢 Green: Approved daily override
- 🔵 Blue: Weekly default schedule
- 🟣 Purple: Rotating pattern schedule
- 🟡 Yellow: Company holiday
- 🔴 Red: OFF day (manual override)
- ⚫ Gray: Weekend/Rest day

### 4. Schedule Summary Tab
**Location:** Employee Edit → Schedule Summary Tab

**Features:**
- Complete overview of all schedule assignments
- Weekly default schedule table
- Rotating schedule history
- Recent daily overrides (last 20)
- Full change audit trail (last 50)

**Sections:**
1. **Default Weekly Schedule** - Shows Mon-Sun assignment
2. **Rotating Schedule History** - All rotations (active and past)
3. **Daily Overrides** - Recent date-specific changes
4. **Change History** - Complete audit trail with timestamps

---

## 🎨 Visual Indicators

### Calendar Display
Each calendar day shows:
- Date number
- Status badge (Present, Late, Absent, etc.)
- Schedule info or "OFF" indicator
- Time range (if applicable)
- Attendance times (if logged)

### Override Indicators
- **Daily Override**: Shows with green highlight + 📅 icon
- **OFF Day**: Shows "🚫 OFF" in red
- **Rotating**: Purple background
- **Weekly Default**: Blue background
- **Holiday**: Yellow background

---

## 📝 Common Workflows

### Workflow 1: Set Regular Weekly Schedule
1. Go to Employee Edit → Weekly Schedule
2. Select schedule for each day
3. Mark weekends as OFF
4. Click "Update Weekly Schedule"
5. System saves to `employee_default_schedules`
6. History logged in `schedule_override_history`

### Workflow 2: Assign Rotating Shift
1. Go to Employee Edit → Rotating Schedule
2. Select rotation pattern from dropdown
3. Set start date (when rotation begins)
4. Set cycle start date (reference point for counting)
5. Leave end date blank for ongoing
6. Click "Assign Rotating Schedule"
7. System saves to `employee_rotating_schedules`
8. Pattern automatically calculates schedule per day

### Workflow 3: Add One-Time Override
1. Go to Employee Edit → Current Schedule
2. Click on specific date in calendar
3. Select new schedule or choose "OFF"
4. Add reason/notes
5. Click "Create Override"
6. System saves to `employee_daily_schedules`
7. Calendar immediately reflects change

### Workflow 4: View Complete History
1. Go to Employee Edit → Schedule Summary
2. See all schedule assignments in one view
3. Review change audit trail
4. Verify upcoming overrides

---

## 🔐 Data Integrity

### Automatic Conflict Resolution
- Daily overrides automatically take precedence
- No manual approval needed (admin-initiated)
- History preserved even after changes
- Rotating schedules end automatically when new one starts

### Audit Trail
Every change is logged with:
- Employee ID
- Change type (daily_override, weekly_default, rotating_assignment, etc.)
- Complete details (JSON format)
- Who made the change
- When it was made

### Validation
- Dates cannot be in the past for new overrides
- Schedule IDs must exist in work_schedules
- Pattern IDs must be valid and active
- Employee IDs validated before operations

---

## 🚀 Benefits

1. **Flexibility**: Multiple ways to assign schedules
2. **Priority System**: Clear hierarchy prevents conflicts
3. **Audit Trail**: Complete history for compliance
4. **Visual Clarity**: Color-coded calendar easy to understand
5. **Bulk Operations**: Weekly defaults apply to all future dates
6. **One-Time Changes**: Daily overrides for exceptions
7. **Rotating Support**: Perfect for shift workers
8. **Holiday Management**: Company-wide holiday calendar

---

## 📋 Migration from Old System

### Before (Old System)
- Only `employees.official_sched` field
- No weekly patterns
- No rotating schedules
- Manual tracking of overrides

### After (Advanced System)
- Multi-level schedule assignment
- Priority-based resolution
- Automated rotation calculation
- Complete audit trail
- Visual calendar interface

### Existing Data
- Old `official_sched` field still works as fallback
- New system takes priority when configured
- No data loss during transition
- Can enable gradually per employee

---

## 🛠️ Technical Notes

### Performance
- Indexed queries for fast lookups
- Efficient date calculations for rotations
- Cached pattern data
- Optimized priority checks

### Database Queries
Schedule lookup uses cascading queries:
```sql
1. Check employee_daily_schedules
2. Check employee_rotating_schedules → patterns
3. Check employee_default_schedules
4. Check company_holidays
5. Fallback to weekend detection
```

### JSON Storage
Change history stores details as JSON:
```json
{
  "date": "2025-10-20",
  "schedule_id": 5,
  "is_rest_day": false,
  "reason": "Coverage needed"
}
```

---

## 📞 Support & Troubleshooting

### Common Issues

**Q: Daily override not showing?**
A: Verify date format (YYYY-MM-DD) and check employee_daily_schedules table

**Q: Rotating pattern not calculating correctly?**
A: Check cycle_start_date - this is the reference point for day counting

**Q: Weekly default being ignored?**
A: Check if rotating schedule or daily override exists (higher priority)

**Q: Cannot save schedule?**
A: Verify work_schedule_id exists in work_schedules table

---

## 🎉 Summary

The Advanced Calendar Scheduling System provides:
- ✅ 7 integrated database tables
- ✅ Priority-based schedule resolution
- ✅ Admin interface for all assignments
- ✅ Complete audit trail
- ✅ Visual calendar display
- ✅ Support for rotating shifts
- ✅ One-time overrides
- ✅ Weekly defaults
- ✅ Holiday management

**Ready to use!** Access via Employee Edit page.
