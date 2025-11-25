# Redundant Files Analysis

## SAFE TO REMOVE (Verified Redundant)

### 1. Duplicate Files
- `UsersAccount/send_credentials copy.php` - Duplicate of send_credentials.php
- `index2.php` - Appears to be a backup/test version of index.php

### 2. Debug/Test Files (Development only - not needed in production)
- `debug_complete.php`
- `debug_hosting.php`
- `debug_leave_credits.php`
- `debug_monthly_approval.php`
- `debug_notifications.php`
- `debug_notifications_array.php`
- `debug_schedule_issue.php`
- `debug_schedule_modal.php`
- `debug_time_logging.php`
- `test_approved_schedule_requests.php`
- `test_cache_working.php`
- `test_email.php`
- `test_email_debug.php`
- `test_final_time_logging.php`
- `test_leave_accrual.php`
- `test_monthly_notifications.php`
- `test_night_shift_fix.php`
- `test_notification_system.php`
- `test_ot_report_columns.php`
- `test_overtime_attachments.php`
- `test_overtime_calculation.php`
- `test_parameter_fix.php`
- `test_public_html_env.php`
- `test_schedule_history.php`
- `test_schedule_integration.php`
- `test_schedule_processing.php`
- `test_schedule_summary.php`
- `test_shift_logic.php`
- `test_switch_db.php`
- `test_time_logging_simple.php`
- `test_time_out_functionality.php`
- `test_weekly_schedule.php`
- `Public/debug_monthly_submit.php`

### 3. One-time Migration/Setup Scripts (Already Applied)
- `migration_add_cancelled_status.php`
- `migration_add_processed_at_hosting.php`
- `migration_add_schedule_history.php`
- `migration_update_ot_table.php`
- `fix_default_schedules_1006.php`
- `fix_effective_from_1006.php`
- `fix_foreign_key.php`
- `setup_default_schedules.php`
- `run_cache_setup.php`
- `quick_cache_setup.php`
- `populate_october_cache.php`
- `reset_november_accrual.php`
- `reset_notifications_for_testing.php`

### 4. Check/Verification Scripts (Temporary diagnostic tools)
- `check_1006_schedule.php`
- `check_all_default_schedules_1006.php`
- `check_auto_accrual.php`
- `check_cache_schedules.php`
- `check_db_structure.php`
- `check_path.php`
- `check_schedule_data.php`
- `check_sept_data.php`
- `check_session.php`
- `check_switch_requests.php`
- `check_switch_table.php`
- `check_table_columns.php`
- `check_table_structure.php`
- `verify_emp_type.php`
- `Public/views/check_admin_session.php`
- `Public/views/check_cdn_loading.php`

### 5. Old SQL Migration Files (Already in database)
- `add_sept_cache.php`
- `add_sept_cache_1006.sql`
- `ADD_SCHEDULES_19_22.sql`
- `populate_cache_1003_1007.sql`
- `populate_daily_schedules.sql`
- `insert_time_logs_oct.sql`
- `reset_to_october.sql`
- `migration_add_is_rest_day_column.sql`
- `migration_add_log_out_date.sql`
- `alter_post_ot_requests.sql`
- `auto_update_daily_schedules.sql`
- `create_daily_schedule_cache.sql`
- `create_employee_daily_schedule_table.sql`
- `create_post2_overtime_requests.sql`
- `create_simple_daily_schedules.sql`
- `create_system_settings_table.sql`
- `add_emp_type_column.sql`
- `view_employee_schedules.sql`

### 6. Old/Obsolete Files
- `default.php` - Likely an old version
- `employee_calendar.php` - Old standalone version (replaced by integrated version)
- `employee_calendar_ajax.php` - Old version
- `process_schedules.php` - Replaced by newer processing system
- `process_approved_schedules.php` - Functionality integrated elsewhere
- `schedule_cache_functions.php` - Functionality integrated
- `clear_cache.php` - Utility script
- `create_attachment_dirs.php` - One-time setup
- `prepare_deployment.php` - One-time deployment tool
- `monitor_cron.php` - Old monitoring script
- `rest_day_preview.html` - Development preview file
- `rest_day_vs_schedule_change_comparison.html` - Development comparison file
- `setup_schedule_system.html` - Setup guide/demo file
- `calendar_section_replacement.txt` - Old code snippet
- `FIX_COMPLETE_SUMMARY.txt` - Old summary

### 7. Redundant Documentation (Status/Summary files that are outdated)
Keep the main guides, remove old status reports:
- `ADJUSTMENT_DETECTION_SUMMARY.md`
- `AFFECTED_FILES_SUMMARY.md`
- `ATTENDANCE_HISTORY_ADJUSTMENT_FIX.md`
- `AUTO_ACCRUAL_FIX_SUMMARY.md`
- `CACHE_IMPLEMENTATION_COMPLETE.md`
- `CALENDAR_SYNC_COMPLETE.md`
- `CALENDAR_SYNC_STATUS.md`
- `COMPLETE_SCHEDULE_SYNC.md`
- `DEPLOYMENT_AFFECTED_FILES_ANALYSIS.md`
- `DEPLOYMENT_READINESS_REPORT.md`
- `EXECUTIVE_SUMMARY.md`
- `FILES_AFFECTED_COMPLETE_LIST.md`
- `FIX_COMPLETE_ATTENDANCE_HISTORY.md`
- `INC_DETECTION_FIX_EXPLAINED.md`
- `MONTHLY_LEAVE_ACCRUAL_DEPLOYMENT.md`
- `REGULAR_EMPLOYEES_ACCRUAL_UPDATE.md`
- `REST_DAY_ACTIVITY_INTEGRATION.md`
- `SCHEDULE_AUTO_UPDATE_STATUS.md`
- `SCHEDULE_SYNC_STATUS.md`
- `TIME_ADJUSTMENT_FIX_SUMMARY.md`
- `VALIDATION_CHECKLIST.md`

## KEEP (Essential Files)

### Active Documentation
- `README_DOCUMENTATION_INDEX.md` - Main index
- `ADVANCED_CALENDAR_SYSTEM_GUIDE.md` - Core feature guide
- `AUTO_ACCRUAL_SETUP_GUIDE.md` - Setup instructions
- `AUTO_ACCRUAL_TESTING_GUIDE.md` - Testing guide
- `DAILY_SCHEDULE_CACHE_GUIDE.md` - Technical guide
- `EMPLOYMENT_TYPE_GUIDE.md` - HR guide
- `FULLSCREEN_CALENDAR_UPDATE.md` - Feature doc
- `LEAVE_ACCRUAL_DOCUMENTATION_INDEX.md` - Index
- `LEAVE_ACCRUAL_FLOW_DIAGRAM.md` - Process diagram
- `LEAVE_ACCRUAL_IMPLEMENTATION_SUMMARY.md` - Implementation guide
- `LEAVE_ACCRUAL_QUICK_START.md` - Quick reference
- `MANUAL_LEAVE_ACCRUAL_GUIDE.md` - User guide
- `MONTHLY_SCHEDULE_REQUEST_FEATURE.md` - Feature doc
- `MONTHLY_SCHEDULE_TESTING_GUIDE.md` - Testing guide
- `NEW_LEAVE_ACCRUAL_POLICY_2026.md` - Current policy
- `NO_CRON_AUTO_ACCRUAL_GUIDE.md` - Alternative setup
- `PRODUCTION_DEPLOYMENT_CHECKLIST.md` - Deployment guide
- `SCHEDULE_AUTO_PROCESSING_GUIDE.md` - Processing guide
- `SCHEDULE_MANAGEMENT_GUIDE.md` - Management guide
- `SCHEDULE_SWITCH_FEATURE.md` - Feature doc
- `SCHEDULE_SWITCH_SETUP_GUIDE.md` - Setup guide
- `SCHEDULE_UI_REDESIGN.md` - UI documentation
- `TECHNICAL_ARCHITECTURE.md` - Architecture doc
- `APPROVED_SCHEDULE_REQUESTS_FEATURE.md` - Feature doc
- `REST_DAY_SCHEDULE_REQUEST.md` - Feature doc
- `MONDAY_COM_DESIGN.md` - Design reference

### Active SQL/Database Files
- `create_month_weekly_schedule.sql` - Table schema (keep for reference)
- `create_schedule_switch_table.sql` - Table schema (keep for reference)
- `hostinger_database_export.sql` - Production backup
- `Database/` folder - Keep all backups

### Configuration Files
- `.env.example`
- `.env.production`
- `composer.json`
- `composer.lock`
- `package.json`
- `package-lock.json`
- `tailwind.config.js`

### Active Application Files
- All files in `Public/` except those listed above for removal
- All files in `src/`
- `index.php` - Main entry point
