-- Dashboard performance indexes
-- Safe to run repeatedly on MariaDB/MySQL installations that support IF NOT EXISTS.

CREATE INDEX IF NOT EXISTS idx_time_adjust_employee_status_date
    ON post_time_adjustment_requests (employee_id, status, log_date, id);

CREATE INDEX IF NOT EXISTS idx_leave_employee_status_dates
    ON post_leave_requests (employee_id, status, end_date, start_date);

CREATE INDEX IF NOT EXISTS idx_post_ot_employee_status_created
    ON post_ot_requests (employee_id, status, created_at);

CREATE INDEX IF NOT EXISTS idx_post2_ot_employee_status_created
    ON post2_overtime_requests (employee_id, status, created_at);

CREATE INDEX IF NOT EXISTS idx_overtime_employee_created
    ON overtime_requests (employee_id, created_at);

CREATE INDEX IF NOT EXISTS idx_schedule_change_employee_created
    ON schedule_change_requests (employee_id, created_at);

-- Older snapshots do not yet contain the processing markers used by the
-- employee-side calendar synchronizer. Keep schema changes in a migration
-- instead of altering the table during an AJAX request.
ALTER TABLE post_schedule_change_requests
    ADD COLUMN IF NOT EXISTS processed_to_calendar TINYINT(1) NOT NULL DEFAULT 0 AFTER status,
    ADD COLUMN IF NOT EXISTS processed_at DATETIME NULL AFTER processed_to_calendar;

CREATE INDEX IF NOT EXISTS idx_post_schedule_processing
    ON post_schedule_change_requests (employee_id, status, processed_to_calendar, created_at);

CREATE INDEX IF NOT EXISTS idx_month_schedule_processing
    ON month_weekly_schedule (employee_id, status, processed_at, created_at);

CREATE INDEX IF NOT EXISTS idx_announcements_feed
    ON announcements (deleted, created_at);

CREATE INDEX IF NOT EXISTS idx_comments_announcement_deleted_created
    ON comments (announcement_id, deleted, created_at);

CREATE INDEX IF NOT EXISTS idx_reactions_announcement_type
    ON post_reactions (announcement_id, reaction_type);

CREATE INDEX IF NOT EXISTS idx_reactions_employee_announcement
    ON post_reactions (employee_id, announcement_id);

CREATE INDEX IF NOT EXISTS idx_system_settings_key_id
    ON system_settings (setting_key, id);
