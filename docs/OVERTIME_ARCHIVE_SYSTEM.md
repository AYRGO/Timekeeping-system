# Overtime Request Archive System

## Overview
This system automatically moves approved/declined overtime requests from `post_ot_requests` to `post2_overtime_requests` for better data management and performance.

## Database Setup

### 1. Create Archive Table
```sql
CREATE TABLE post2_overtime_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    time_log_id INT NOT NULL,
    time_in TIME NOT NULL,
    time_out TIME NOT NULL,
    ot_duration DECIMAL(4,2) NOT NULL,
    ot_type VARCHAR(50) NOT NULL,
    attachment VARCHAR(255) NOT NULL,
    reason TEXT NOT NULL,
    status ENUM('pending', 'approved', 'declined') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    approved_at TIMESTAMP NULL DEFAULT NULL,
    approved_by INT NULL DEFAULT NULL,
    notified TINYINT(1) DEFAULT 0,
    INDEX idx_employee_id (employee_id),
    INDEX idx_time_log_id (time_log_id),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at)
);
```

### 2. Create Database Trigger (Recommended)
Run the SQL from `Database/create_ot_archive_trigger.sql`:
```sql
-- This trigger automatically moves records when status changes
-- No manual intervention needed
```

## Implementation Methods

### Method 1: Database Trigger (Recommended)
- **Automatic**: Triggers on status update
- **Reliable**: Database-level enforcement
- **Efficient**: Immediate execution
- **Setup**: Run the trigger SQL once

### Method 2: Manual PHP Functions
- **Controlled**: Admin-triggered movement
- **Flexible**: Can add business logic
- **Setup**: Use the provided PHP files

### Method 3: Cron Job
- **Scheduled**: Runs periodically
- **Batch**: Processes multiple records
- **Setup**: Configure cron to run `cron_move_ot_requests.php`

## Files Created

### Core Functions
- `new_overtime.php` - Updated with auto-move function
- `update_overtime_status.php` - Handles admin status updates
- `manual_move_ot_requests.php` - Manual trigger for admins
- `cron_move_ot_requests.php` - Scheduled job script

### Database
- `Database/create_ot_archive_trigger.sql` - SQL trigger

## Features Added

### 1. Automatic Archive Movement
- Records move to `post2_overtime_requests` when approved/declined
- Original table stays clean with only pending requests
- Full data preservation

### 2. Combined History View
- Employee history shows records from both tables
- Seamless user experience
- Chronological ordering

### 3. Admin Control
- Manual trigger available for admins
- Status update handling with automatic archiving
- Error handling and logging

## Usage

### For Employees
- No changes to user interface
- Request history shows all records (active + archived)
- Same submission process

### For Admins
- Update status normally through admin interface
- Records automatically move to archive
- Can manually trigger archive movement if needed

### For Developers
- Use `moveCompletedOTRequests($pdo)` function for manual moves
- Monitor logs for archive operations
- Configure cron if using scheduled approach

## Monitoring

### Check Archive Status
```sql
-- Count pending requests
SELECT COUNT(*) as pending_requests FROM post_ot_requests WHERE status = 'pending';

-- Count archived requests
SELECT COUNT(*) as archived_requests FROM post2_overtime_requests;

-- Check recent archive activity
SELECT status, COUNT(*) as count FROM post2_overtime_requests 
WHERE approved_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) 
GROUP BY status;
```

### Error Logs
- Check PHP error logs for archive operation status
- Monitor database logs for trigger execution
- Watch for transaction rollbacks

## Troubleshooting

### If Records Don't Move Automatically
1. Check if trigger exists: `SHOW TRIGGERS LIKE 'post_ot_requests';`
2. Verify trigger syntax in database
3. Check PHP error logs
4. Manually run `manual_move_ot_requests.php`

### If History Doesn't Show Archived Records
1. Verify `post2_overtime_requests` table exists
2. Check query syntax in `new_overtime.php`
3. Ensure proper JOIN conditions

## Maintenance

### Periodic Tasks
- Monitor archive table size
- Consider partitioning for very large datasets
- Regular backup of archived data
- Clean up old attachment files if needed

### Performance Optimization
- Index archived table properly
- Consider date-based partitioning
- Archive very old records to separate storage

## Security Considerations
- Archive table maintains same security model
- Admin permissions required for manual operations
- Attachment files remain in same location
- Audit trail preserved in archived records