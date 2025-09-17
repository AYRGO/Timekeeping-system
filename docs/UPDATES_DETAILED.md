# Timekeeping System — Detailed Update Summary

This document summarizes all changes made since the earlier baseline. It explains behaviours, reasoning, SQL migrations, how the reset flow works, UI changes, and testing/checklist items.

---

## High-level summary (concise)
- Automatic "incomplete" detection added: time-ins older than 12 hours without a time-out are marked automatically (time_out = 'INC', status = 'incomplete').
- Reset flow: pressing "Start New Shift (Reset)" deletes incomplete records for the employee and creates a fresh active row for today (time_out = NULL).
- Confirm-Shift-Complete removed: manual "confirm complete" and the 8-hour restriction were removed from UI and handler.
- Attendance history adapted: it now respects the new `status` column and displays "INC" / "Auto-Incomplete" where applicable, with robust overnight and OT handling.
- Centralized handler: time in/out DB writes moved to time_log_handler.php; time_log_create.php is now UI + security.

---

## Chronology & motivation (detailed)
1. Problem: Incomplete-shift handling originally used an 8-hour auto-mark; users saw stale `time_in` when resetting because the handler created a new row but the UI still pulled the incomplete row.
2. Change: Increased auto-mark threshold to 12 hours (more tolerant), and upon user-initiated reset we now actively delete the incomplete row(s) and insert a new authoritative row for today.
3. Confirm-complete complexity: Manual confirm + 8-hour wait caused UX friction and many variables; removed to simplify UX. If the 8-hour rule is required later, re-introduce via shift_completions logging and gating in the handler.
4. Attendance-history: Extended to include `status` column and to surface whether time_out was auto-marked (`INC`) and compute hours properly while handling overnight shifts.

Rationale:
- Avoid confusing duplicates in time_logs (multiple rows for same day's attempts).
- Auditability vs UX: current setup deletes incomplete rows on reset to provide immediate fresh row; you may prefer to retain incomplete rows for audit (recommendation below).

---

## File-by-file detailed notes

### c:\xampp\htdocs\Timekeeping-system\Public\module\today_attendance_card.php
What changed
- On page load the system:
  - Runs an UPDATE to set time_out = 'INC' and status = 'incomplete' for time_ins older than 12 hours.
  - Excludes `status = 'incomplete'` rows when looking for today's active log and overnight open logs.
  - When showing an incomplete row, marks UI accordingly and surfaces "Start New Shift (Reset)".
- Reset behaviour:
  - The UI posts is_incomplete=1 to time_log_handler.php. After handler deletes incomplete rows and creates a fresh active row, the attendance card is reloaded with `?reset=<timestamp>` to force fresh queries.
- Removed manual confirm-complete controls and the 8-hour restriction UI messaging.
- Modal now only supports `time_in` and `time_out` actions.

Important logic points
- Priority lookup order:
  1. Today's active log (status != 'incomplete')
  2. Open overnight shift (time_out IS NULL and log_date < today, status != 'incomplete')
  3. Latest incomplete (only if no active shift)
- Formatting: when time_out = 'INC', UI shows "INC" in red.
- If you want to preserve incomplete records for auditing instead of deleting on reset, update the handler to not delete and instead create a separate 'reset' marker or soft-flag.

Example SQL used (already in code)
- Auto-mark:
  UPDATE time_logs
  SET time_out = 'INC', status = 'incomplete'
  WHERE employee_id = ? AND time_out IS NULL
    AND TIMESTAMPDIFF(HOUR, CONCAT(log_date, ' ', time_in), NOW()) >= 12
    AND status != 'incomplete';

Notes for reviewers
- Ensure your time zone is correct (Asia/Manila used).
- The UI relies on status column — add it if not present.

---

### c:\xampp\htdocs\Timekeeping-system\Public\module\time_log_handler.php
What changed
- Consolidated time-in/time-out write logic.
- Reset flow:
  - If is_incomplete === '1':
    * DELETE FROM time_logs WHERE employee_id = ? AND status = 'incomplete'
    * DELETE any time_out = 'INC' rows for original log date (optional, implemented)
    * INSERT new row: (employee_id, today, current_time, time_out = NULL, status = 'active')
- Auto-mark cleanup:
  - Update + delete policy used on reset to avoid user seeing stale rows.
- Security:
  - CSRF validation, transactions, and explicit redirects with cache-busting (`?reset=timestamp`).
- Removed confirm_complete handling (no more shift_completions inserts).

Edge cases
- If you prefer audit trails, do NOT delete incomplete rows — instead mark them and still create a new row, or create a "reset_events" table to track resets.

Example insert performed on reset
INSERT INTO time_logs (employee_id, log_date, time_in, time_out, status)
VALUES (?, ?, ?, NULL, 'active');

Testing checklist
- Start with an incomplete row (older than 12 hours). Press "Start New Shift (Reset)". Verify:
  - Old incomplete row is gone (or left as incomplete if you changed handler).
  - A single new row exists for today with time_out NULL.
  - UI shows new time_in and time_out = "—".
- Normal time-out flow should update the active row's time_out and set status = 'completed' (handler updates to 'completed').

---

### c:\xampp\htdocs\Timekeeping-system\Public\module\attendance-history.php
What changed
- SELECT now includes `t.status`.
- Mark auto-incomplete rows (`status='incomplete'` or `time_out='INC'`) and show `INC` in Time Out column.
- Hours worked:
  - Handles overnight: if time_out < time_in then add 1 day to time_out before diff.
  - Deducts 1 hour for lunch; ensures non-negative values.
- Status mapping expanded:
  - Auto-Incomplete, Will Auto-Mark Incomplete (approaching auto-mark threshold), In Progress, On Time, Late, Left Early, No Record, Missing.
- OT column suppressed for auto-incomplete rows (shows N/A).

Notes
- This file also consumes approved post_time_adjustment_requests and uses requested_time_in/out when approved.
- The date-range iterator is reversed so newest appear first. Pagination preserved.

---

### c:\xampp\htdocs\Timekeeping-system\Public\module\time_log_create.php
What changed
- Moved time-in/time-out write logic out to time_log_handler.php.
- Security additions:
  - init_csrf_protection(1800) with validate_csrf_token checks moved into handler for writes.
  - Session timeout and session_regenerate_id usage.
  - Simple per-user rate-limiting for POST requests (15 req/min).
- The page now focuses on UI, schedule lookups, and safety checks.

---

## Database migration (exact SQL)
Add `status` and (optional) shift_completions:

1) Add status to time_logs
ALTER TABLE time_logs ADD COLUMN status ENUM('active','completed','incomplete') DEFAULT 'active';

2) Optional: create shift_completions (only if you later reintroduce manual confirm/8-hour)
CREATE TABLE IF NOT EXISTS shift_completions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  employee_id INT NOT NULL,
  time_log_id INT NOT NULL,
  log_date DATE NOT NULL,
  completed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
  FOREIGN KEY (time_log_id) REFERENCES time_logs(id) ON DELETE CASCADE,
  UNIQUE KEY unique_completion (employee_id, log_date)
);

Backup note
- Always run these on a staging DB first and back up time_logs table (mysqldump) before altering schema.

---

## Testing checklist (recommended)
1. Unit tests / manual checks:
   - Create a time_log row with log_date older than 12 hours and time_out NULL. Load attendance page and confirm it's auto-marked (time_out = 'INC', status = 'incomplete').
   - For an incomplete record: press "Start New Shift (Reset)". Confirm old incomplete row is deleted and a new active row for today is created with time_out NULL.
   - Normal shift: time_in today, then time_out — verify status becomes 'completed' and UI shows hours (deduct 1 hour lunch).
   - Overnight: time_in at 22:00 previous day, time_out next day 06:00 — verify hours calculation adds 1 day and deducts lunch correctly.
2. Edge cases:
   - Multiple incomplete rows for same employee — reset should clean them up.
   - OT requests for auto-incomplete days should show as N/A per new rules.
3. Security:
   - Submit POST requests without CSRF token — must be rejected.
   - Rapid POSTs beyond rate-limit — must be rejected and logged.

---

## Known issues & recommendations
- Current reset flow deletes incomplete rows. If audit/history is required, switch to soft-delete or add a "reset_events" table to preserve the incomplete row while creating a new row.
- If the 8-hour waiting policy is required, reintroduce shift_completions table and gating logic in handler (commented in code earlier).
- Consider centralizing the `12` hours threshold into a config constant or DB setting for easier tuning.

---

## Next steps (optional improvements)
- Add unit/integration tests around the handler to assert DB transition states.
- Add an audit table (time_log_changes) capturing when rows are auto-marked, deleted, or reset.
- Add admin view to see auto-mark events and resets per employee.

---

End of document.
