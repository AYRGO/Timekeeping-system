# Production Deployment - Quick Checklist

## 🎯 FILES TO DEPLOY/UPDATE

### Core Application Files
- [ ] `Public/module/test.php` - **MAIN FORM** (new or updated)
- [ ] `Public/module/time_log_create.php` - Add success message handling
- [ ] `Public/module/sidebar.php` - Add navigation link

### Admin/Manager Files
- [ ] `Public/views/time_adjustment_list.php` - Request review page
- [ ] `Public/views/process_time_adjustment.php` - Approval handler
- [ ] `Public/views/admin_homepage.php` - Dashboard widget
- [ ] `Public/controller/update_time_adjustment.php` - AJAX endpoint

### Employee Files
- [ ] `Public/views/employee-edit.php` - History/details tab

### Configuration Files
- [ ] `Public/config/db.php` - Verify database connection
- [ ] `Public/config/env.php` - Verify timezone settings
- [ ] `Public/config/csrf_helper.php` - Verify CSRF setup

---

## 🗂️ DIRECTORIES TO CREATE/VERIFY

```
Public/uploads/time_adjustments/
├── Permissions: 755 (writable)
├── Purpose: Store attachment files
└── File Pattern: attach_*.{pdf,jpg,jpeg,png,doc,docx}
```

---

## 🗄️ DATABASE TABLES TO VERIFY/CREATE

```sql
-- VERIFY EXISTENCE:
1. time_adjustment_requests
   - id (PRIMARY KEY)
   - employee_id (FOREIGN KEY)
   - log_date
   - current_time_in
   - current_time_out
   - requested_time_in
   - requested_time_out
   - reason
   - status (enum: 'Pending', 'Approved', 'Declined')
   - submitted_at
   - attachment
   - created_at
   - notified (tinyint)
   - deleted (tinyint)

2. time_logs
   - employee_id
   - log_date
   - time_in
   - time_out

3. post_time_adjustment_requests
   - (Archive table for processed requests)
   - Same structure as time_adjustment_requests

4. employees
   - id (PRIMARY KEY)
   - fname, lname
   - status ('active', 'inactive', etc.)
```

---

## 🔧 PHP CONFIGURATION VERIFICATION

```ini
; Verify in php.ini:

upload_max_filesize = 10M          ✓ (supports 10MB files)
post_max_size = 50M                ✓ (form data limit)
max_file_uploads = 20              ✓ (multiple file support)
file_uploads = On                  ✓ (enable uploads)

; Session configuration:
session.gc_maxlifetime = 3600      ✓ (1 hour timeout)
session.cookie_httponly = On       ✓ (security)
session.cookie_secure = On         ✓ (HTTPS only if on prod)
```

---

## 🌐 INTEGRATION VERIFICATION

### Sidebar Navigation
```html
<!-- Add to Public/module/sidebar.php -->
<a href="test.php">Request Time Adjustment</a>
```

### Dashboard Widget
```html
<!-- Verify in Public/views/admin_homepage.php -->
- Pending count display working
- Link to time_adjustment_list.php accessible
```

### Employee History
```html
<!-- Verify in Public/views/employee-edit.php -->
- Time Adjustments tab visible
- Attachment download links working
```

---

## 🧪 TESTING CHECKLIST

### Employee Submission
- [ ] Access form at `test.php`
- [ ] Complete human verification puzzle
- [ ] Submit 5-step form successfully
- [ ] File upload works (PDF, DOCX, JPG, PNG)
- [ ] Redirect to dashboard after submit
- [ ] Success message displays
- [ ] Data saved in `time_adjustment_requests` table
- [ ] Attachment file saved in `uploads/time_adjustments/`

### Admin Approval Workflow
- [ ] View pending requests at `time_adjustment_list.php`
- [ ] Download attachment from request
- [ ] Approve request (updates `time_logs`)
- [ ] Decline request with reason
- [ ] Record moved to `post_time_adjustment_requests`
- [ ] Employee sees history in `employee-edit.php`

### Security Testing
- [ ] CSRF token validation working
- [ ] Session timeout enforced
- [ ] File type restrictions work
- [ ] File size limit (10MB) enforced
- [ ] Non-authenticated access blocked
- [ ] SQL injection attempts blocked

### Error Handling
- [ ] Missing required fields show error
- [ ] Invalid file types rejected
- [ ] Over-sized files rejected
- [ ] Database errors handled gracefully
- [ ] Directory permissions errors logged
- [ ] Session errors handled properly

---

## 🚀 PRE-DEPLOYMENT

### 1. Database Backup
```bash
# Backup before deployment
mysqldump -u root -p database_name > backup_$(date +%Y%m%d_%H%M%S).sql
```

### 2. Verify File Structure
```
Public/
├── module/
│   ├── test.php ✓
│   ├── time_log_create.php ✓
│   └── sidebar.php ✓
├── views/
│   ├── time_adjustment_list.php ✓
│   ├── process_time_adjustment.php ✓
│   ├── admin_homepage.php ✓
│   └── employee-edit.php ✓
├── controller/
│   └── update_time_adjustment.php ✓
├── config/
│   ├── db.php ✓
│   ├── env.php ✓
│   └── csrf_helper.php ✓
└── uploads/
    └── time_adjustments/ (755) ✓
```

### 3. Verify Database Tables
```bash
# In MySQL/phpMyAdmin:
SHOW TABLES LIKE '%time_adjustment%';
DESCRIBE time_adjustment_requests;
DESCRIBE post_time_adjustment_requests;
```

### 4. Set File Permissions
```bash
# On Linux/Unix:
chmod 755 Public/uploads/time_adjustments/
chmod 644 Public/module/test.php
chmod 644 Public/views/time_adjustment_list.php
chmod 644 Public/views/process_time_adjustment.php
chmod 644 Public/controller/update_time_adjustment.php
```

---

## 📊 MONITORING AFTER DEPLOYMENT

### Logs to Monitor
```bash
# Check for errors:
- PHP error logs
- MySQL/Database logs
- File upload logs
- Session logs
```

### Metrics to Track
- [ ] Successful submissions per day
- [ ] File upload success rate
- [ ] Approval processing time
- [ ] Error/failure rates
- [ ] File storage usage

### Common Issues & Fixes
| Issue | Solution |
|-------|----------|
| "Upload dir permission denied" | `chmod 755 Public/uploads/time_adjustments/` |
| "File not found after upload" | Verify correct path in config |
| "Database error" | Check connection in `db.php` |
| "CSRF token mismatch" | Clear cookies, restart browser |
| "Session timeout" | Adjust `$session_timeout` in code |
| "Approval not updating time_logs" | Check FK constraints |

---

## 🔄 ROLLBACK PROCEDURE

### If Issues Occur:
1. Disable feature by commenting out sidebar link
2. Restore from database backup
3. Remove test.php from deployment
4. Restore previous versions of modified files
5. Test on staging first

```bash
# Restore database:
mysql -u root -p database_name < backup_20251104_120000.sql

# Restore files:
git checkout Public/module/sidebar.php  # or manual restore
```

---

## 📞 SUPPORT CONTACTS

- **Database Issues:** Check `Public/config/db.php` credentials
- **File Upload Issues:** Check directory permissions and disk space
- **Session Issues:** Check PHP session configuration
- **CSRF Errors:** Clear browser cookies and try again
- **Admin Approval Issues:** Verify user role has admin access

---

**Deployment Date:** _______________
**Deployed By:** _______________
**Verified By:** _______________
**Status:** ☐ Pending ☐ In Progress ☐ Complete ☐ Failed

---

Generated: 2025-11-04
Feature: Time Adjustment Request System
Version: 1.0
