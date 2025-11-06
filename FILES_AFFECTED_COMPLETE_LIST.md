# Complete List of Affected Files - test.php Deployment

## 📋 ALL FILES AFFECTED (Organized by Type)

---

## 🔴 CRITICAL - Must Deploy/Create

### Form & Submission Layer
```
✓ Public/module/test.php
  - Primary time adjustment request form
  - 1000+ lines of code
  - Multi-step wizard (5 steps)
  - Status: NEW/UPDATE (deploy as-is)
```

### Approval & Processing Layer
```
✓ Public/views/time_adjustment_list.php
  - Admin panel for viewing/managing requests
  - Shows pending and historical requests
  - Status: DEPLOY/CREATE

✓ Public/views/process_time_adjustment.php
  - Handles approval/decline operations
  - Updates time_logs on approval
  - Archives to post_time_adjustment_requests
  - Status: DEPLOY/CREATE

✓ Public/controller/update_time_adjustment.php
  - AJAX endpoint for real-time updates
  - JSON request/response
  - Status: DEPLOY/CREATE
```

### Upload Directory
```
✓ Public/uploads/time_adjustments/
  - Store attachment files
  - Status: CREATE (if doesn't exist)
  - Permissions: 755 (rwxr-xr-x)
  - Owner: Apache/www-data
```

---

## 🟡 IMPORTANT - Must Update/Verify

### Navigation & Dashboard
```
● Public/module/sidebar.php
  - ADD navigation link to test.php
  - ADD "Request Time Adjustment" menu item
  - Status: UPDATE REQUIRED

● Public/module/time_log_create.php
  - VERIFY handles $_SESSION['success_message']
  - VERIFY redirect from test.php works
  - Status: VERIFY + MINOR UPDATE

● Public/views/admin_homepage.php
  - VERIFY dashboard widget shows pending count
  - VERIFY link to time_adjustment_list.php
  - VERIFY recent activity feed includes adjustments
  - Status: VERIFY + MINOR UPDATE
```

### Employee Records
```
● Public/views/employee-edit.php
  - VERIFY Time Adjustments tab displays correctly
  - VERIFY attachment download links work
  - VERIFY history shows all requests
  - Status: VERIFY + MINOR UPDATE
```

---

## 🟢 CONFIGURATION - Must Verify/Ensure

### Database Configuration
```
✓ Public/config/db.php
  - Verify PDO connection is working
  - Verify database credentials are correct
  - Status: VERIFY (no changes usually needed)
```

### Environment Settings
```
✓ Public/config/env.php
  - Verify timezone is set to Asia/Manila
  - Verify include path is correct
  - Status: VERIFY (no changes usually needed)
```

### Security Configuration
```
✓ Public/config/csrf_helper.php
  - Verify CSRF token generation function
  - Verify token validation function
  - Status: VERIFY (no changes usually needed)
```

---

## 🔵 UTILITY FILES (Optional)

### Testing & Debugging
```
○ reset_notifications_for_testing.php (root level)
  - Development utility for resetting flags
  - Not needed in production if using automation
  - Status: OPTIONAL (for testing only)
```

---

## 📊 SUMMARY BY COUNT

| Category | Count | Action |
|----------|-------|--------|
| Deploy/Create | 4 | Primary files |
| Update/Verify | 4 | Integration points |
| Verify Only | 3 | Configuration files |
| Optional | 1 | Testing utilities |
| **TOTAL** | **12** | **PHP files** |

---

## 🗄️ DATABASE OBJECTS (Must Create/Verify)

### Tables to CREATE:
```
1. time_adjustment_requests
   - 14 columns
   - NEW table
   - Stores pending requests

2. post_time_adjustment_requests
   - 17 columns
   - NEW table (archives)
   - Stores processed requests
```

### Tables to VERIFY:
```
3. time_logs
   - Must have: employee_id, log_date, time_in, time_out
   - Gets updated on approval

4. employees
   - Must have: id, fname, lname, status
   - Referenced by foreign keys
```

### Indexes (Recommended):
```
- idx_tar_employee_status
- idx_tar_log_date
- idx_tar_submitted
- idx_ptar_employee
- idx_ptar_status
```

---

## 📁 DIRECTORIES (Must Create/Verify)

```
Public/uploads/time_adjustments/
├── Permissions: 755
├── Purpose: File attachments storage
└── Status: CREATE if doesn't exist
```

---

## 🔗 RELATIONSHIP MAP

```
test.php (Main)
    ↑↓ reads/writes
├── time_adjustment_requests (DB table - NEW)
├── time_logs (DB table - READ)
├── employees (DB table - READ)
└── config/ (DB, ENV, CSRF)

Admin Portal
    ↑↓ reads/writes
├── time_adjustment_list.php
├── process_time_adjustment.php
├── update_time_adjustment.php
└── time_adjustment_requests (DB table)
    └── post_time_adjustment_requests (DB table)

Integration Points
├── sidebar.php (navigation)
├── admin_homepage.php (dashboard)
├── employee-edit.php (history)
└── time_log_create.php (redirect target)
```

---

## 🎯 DEPLOYMENT SEQUENCE

### Phase 1: Database Setup (10 minutes)
1. Create `time_adjustment_requests` table
2. Create `post_time_adjustment_requests` table
3. Create indexes
4. Verify foreign keys
5. Backup database

### Phase 2: File Deployment (15 minutes)
1. Upload `test.php`
2. Upload `time_adjustment_list.php`
3. Upload `process_time_adjustment.php`
4. Upload `update_time_adjustment.php`
5. Create `time_adjustments/` directory
6. Set permissions 755

### Phase 3: Integration (10 minutes)
1. Update `sidebar.php` (add link)
2. Verify `admin_homepage.php`
3. Verify `employee-edit.php`
4. Verify `time_log_create.php`

### Phase 4: Configuration (5 minutes)
1. Verify `db.php` connection
2. Verify `env.php` settings
3. Verify `csrf_helper.php`
4. Test CSRF tokens

### Phase 5: Testing (20 minutes)
1. Test form submission
2. Test file upload
3. Test approval workflow
4. Test employee history view
5. Test admin dashboard

---

## ⚡ QUICK COPY-PASTE LIST

### Files to Deploy (Copy Path):
```
./Public/module/test.php
./Public/views/time_adjustment_list.php
./Public/views/process_time_adjustment.php
./Public/controller/update_time_adjustment.php
```

### Files to Update (Check These):
```
./Public/module/sidebar.php
./Public/module/time_log_create.php
./Public/views/admin_homepage.php
./Public/views/employee-edit.php
```

### Files to Verify (Config):
```
./Public/config/db.php
./Public/config/env.php
./Public/config/csrf_helper.php
```

### Directories to Create:
```
./Public/uploads/time_adjustments/
chmod 755 ./Public/uploads/time_adjustments/
```

### Database SQL (Create Tables):
```
-- See TECHNICAL_ARCHITECTURE.md for full SQL
-- Tables: time_adjustment_requests, post_time_adjustment_requests
```

---

## 🚨 CRITICAL CHECKS

- [ ] All 4 primary PHP files copied
- [ ] time_adjustments directory created
- [ ] Directory permissions set to 755
- [ ] Database tables created
- [ ] Foreign keys added
- [ ] Sidebar link added
- [ ] Admin dashboard widget verified
- [ ] Employee history tab verified
- [ ] Database backup created
- [ ] Test submission successful
- [ ] Approval workflow tested
- [ ] File downloads working

---

## 📞 SUPPORT

### If Form Won't Load:
- Check file is at: `Public/module/test.php`
- Verify database connection
- Check error logs in PHP

### If Upload Fails:
- Check directory: `Public/uploads/time_adjustments/`
- Verify permissions: 755
- Check disk space

### If Approval Fails:
- Check `time_logs` table exists
- Verify foreign key constraints
- Check user role permissions

### If Redirect Fails:
- Check `time_log_create.php` exists
- Verify path is correct
- Check session variables

---

## 📝 VERIFICATION COMMANDS

```bash
# Check file structure
ls -la ./Public/module/test.php
ls -la ./Public/views/time_adjustment_list.php
ls -la ./Public/controller/update_time_adjustment.php

# Check directory
ls -la ./Public/uploads/time_adjustments/

# Check permissions
stat ./Public/uploads/time_adjustments/ | grep Access

# MySQL - verify tables
SHOW TABLES LIKE '%time_adjustment%';
DESCRIBE time_adjustment_requests;
DESCRIBE post_time_adjustment_requests;
```

---

## ✅ FINAL CHECKLIST

```
Deployment Readiness:
☐ All 4 core PHP files prepared
☐ 2 database tables created with schema
☐ 4 integration points updated
☐ 3 configuration files verified
☐ 1 upload directory created with correct permissions
☐ Database backup created
☐ Navigation link added to sidebar
☐ Admin dashboard widget verified
☐ Error logging configured
☐ File permissions set correctly

Testing Complete:
☐ Form loads without errors
☐ All 5 steps work sequentially
☐ File upload functionality tested
☐ Database inserts successful
☐ Admin can view requests
☐ Approval process works
☐ Employee history displays
☐ Attachments download correctly
☐ Error handling works
☐ CSRF protection active

Production Ready: ✓ YES / ✗ NO
```

---

**Document Created:** November 4, 2025
**Feature:** Time Adjustment Request System
**Version:** 1.0
**Status:** Complete & Ready for Deployment
