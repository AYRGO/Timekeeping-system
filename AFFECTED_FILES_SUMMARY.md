# Time Adjustment Feature - Affected Files Summary

## 📋 Quick Reference

### Total Files Connected: **11 PHP Files + Database + Directories**

---

## 🎯 Files by Category

### **TIER 1: Core Implementation**
```
1. Public/module/test.php ⭐ PRIMARY
   - 1000+ lines
   - Multi-step adjustment request form
   - File upload handler
   - Session management
   - CSRF protection
   - Status: New/Updated file
```

---

### **TIER 2: Processing & Workflow**
```
2. Public/views/time_adjustment_list.php
   - Admin panel for viewing requests
   - Display pending requests with status
   - Download attachment link
   - Approval/Decline buttons
   
3. Public/views/process_time_adjustment.php
   - Handles approval/decline logic
   - Updates time_logs table
   - Moves records to archive table
   - Sends notifications

4. Public/controller/update_time_adjustment.php
   - AJAX endpoint for request updates
   - JSON request/response
   - Real-time editing capability
```

---

### **TIER 3: Dashboard & Navigation**
```
5. Public/module/time_log_create.php
   - Employee dashboard
   - Receives redirect from test.php
   - Displays success messages
   - Shows pending requests count

6. Public/module/sidebar.php
   - Navigation menu
   - ADD: Link to test.php
   - ADD: "Request Time Adjustment" option

7. Public/views/admin_homepage.php
   - Admin dashboard
   - Widget showing pending count
   - Recent activity feed
   - Status summary charts
```

---

### **TIER 4: Employee Records**
```
8. Public/views/employee-edit.php
   - Employee profile page
   - Time Adjustments history tab
   - Shows all requests (pending + processed)
   - Download links for attachments
```

---

### **TIER 5: Configuration**
```
9. Public/config/db.php
   - Database connection
   - PDO initialization
   - Credentials validation

10. Public/config/env.php
    - Environment variables
    - Timezone settings (Asia/Manila)
    - Path configurations

11. Public/config/csrf_helper.php
    - CSRF token generation
    - Token validation
    - Security functions
```

---

### **TIER 6: Utilities & Testing**
```
12. reset_notifications_for_testing.php (root)
    - Development utility
    - Reset notification flags
    - Testing support
```

---

## 🗄️ Database Changes Required

### Tables to CREATE:
```
1. time_adjustment_requests (NEW) ⭐
   - 14 columns
   - Stores pending requests
   - Foreign key: employees(id)

2. post_time_adjustment_requests (NEW) ⭐
   - 17 columns
   - Archives processed requests
   - Foreign key: employees(id)
```

### Tables to VERIFY:
```
3. time_logs (EXISTING)
   - Must have: employee_id, log_date, time_in, time_out
   - Gets updated when requests approved

4. employees (EXISTING)
   - Must have: id, fname, lname, status
   - Referenced by FK constraints
```

---

## 📁 Directories Required

### Upload Directories:
```
Public/uploads/time_adjustments/
├── Permissions: 755 (rwxr-xr-x)
├── Owner: Apache/www-data
├── Purpose: Store attachment files
└── File Pattern: attach_*.{pdf,jpg,jpeg,png,doc,docx}
```

### Existing Directories (Reference):
```
Public/uploads/
├── profile_images/
├── overtime_attachments/
├── schedule_attachments/
├── leave_attachments/
├── checklist/
└── time_adjustments/ ← NEW
```

---

## 🔄 Data Flow Overview

```
Employee Flow:
┌─────────────────────────────────────────┐
│ 1. Employee Access test.php             │
│    - Sidebar link in navigation         │
└──────────────┬──────────────────────────┘
               ↓
┌─────────────────────────────────────────┐
│ 2. Complete 5-Step Form                 │
│    - Date selection                     │
│    - Time adjustment type               │
│    - Enter reason                       │
│    - Upload document                    │
│    - Review & submit                    │
└──────────────┬──────────────────────────┘
               ↓
┌─────────────────────────────────────────┐
│ 3. File Saved & Record Created          │
│    - File → uploads/time_adjustments/   │
│    - Record → time_adjustment_requests  │
└──────────────┬──────────────────────────┘
               ↓
┌─────────────────────────────────────────┐
│ 4. Redirect to Dashboard                │
│    - time_log_create.php                │
│    - Show success message               │
└─────────────────────────────────────────┘

Manager/Admin Flow:
┌─────────────────────────────────────────┐
│ 1. View Pending Requests                │
│    - time_adjustment_list.php           │
│    - Filtered view of pending only      │
└──────────────┬──────────────────────────┘
               ↓
┌─────────────────────────────────────────┐
│ 2. Review Request Details               │
│    - Download attachment                │
│    - Compare current vs requested       │
│    - Read reason                        │
└──────────────┬──────────────────────────┘
               ↓
┌─────────────────────────────────────────┐
│ 3. Approve or Decline                   │
│    - process_time_adjustment.php        │
│    - Update time_logs if approved       │
│    - Move to archive table              │
└──────────────┬──────────────────────────┘
               ↓
┌─────────────────────────────────────────┐
│ 4. Employee Sees Result                 │
│    - employee-edit.php tab              │
│    - View history of all requests       │
│    - See approval status                │
└─────────────────────────────────────────┘
```

---

## 📊 File Dependencies Graph

```
test.php (MAIN)
    ├── includes
    │   ├── ../config/db.php
    │   ├── ../config/env.php
    │   └── ../config/csrf_helper.php
    │
    ├── inserts into
    │   └── time_adjustment_requests (DB)
    │       └── references employees
    │
    ├── reads from
    │   ├── time_logs (DB)
    │   └── employees (DB)
    │
    ├── writes to
    │   └── /uploads/time_adjustments/
    │
    └── redirects to
        └── time_log_create.php
            ├── displays success_message
            └── references sidebar.php

time_adjustment_list.php (ADMIN)
    ├── includes
    │   └── ../config/db.php
    │
    ├── reads from
    │   ├── time_adjustment_requests
    │   ├── post_time_adjustment_requests
    │   └── employees
    │
    ├── references
    │   └── /uploads/time_adjustments/
    │
    └── calls
        └── process_time_adjustment.php

process_time_adjustment.php (APPROVAL)
    ├── includes
    │   └── ../config/db.php
    │
    ├── updates
    │   ├── time_logs
    │   ├── time_adjustment_requests
    │   └── post_time_adjustment_requests
    │
    └── references
        └── employees

employee-edit.php (PROFILE)
    ├── includes
    │   └── ../config/db.php
    │
    ├── reads from
    │   └── time_adjustment_requests
    │
    └── references
        └── /uploads/time_adjustment_attachments/
```

---

## ✅ Deployment Checklist

### Files to Deploy:
- [ ] `Public/module/test.php`
- [ ] `Public/views/time_adjustment_list.php`
- [ ] `Public/views/process_time_adjustment.php`
- [ ] `Public/controller/update_time_adjustment.php`
- [ ] **UPDATE** `Public/module/time_log_create.php`
- [ ] **UPDATE** `Public/module/sidebar.php`
- [ ] **UPDATE** `Public/views/admin_homepage.php`
- [ ] **UPDATE** `Public/views/employee-edit.php`
- [ ] **VERIFY** `Public/config/db.php`
- [ ] **VERIFY** `Public/config/env.php`
- [ ] **VERIFY** `Public/config/csrf_helper.php`

### Database Changes:
- [ ] Create `time_adjustment_requests` table
- [ ] Create `post_time_adjustment_requests` table
- [ ] Verify `time_logs` table structure
- [ ] Verify `employees` table structure
- [ ] Create required indexes
- [ ] Verify foreign keys

### Directory Setup:
- [ ] Create `Public/uploads/time_adjustments/`
- [ ] Set permissions to 755
- [ ] Verify web server can write

---

## 🔌 Integration Points to Update

### 1. Sidebar Navigation
**File:** `Public/module/sidebar.php`
```html
<!-- Add this link: -->
<a href="test.php" class="menu-item">
    <i class="icon"></i> Request Time Adjustment
</a>
```

### 2. Admin Dashboard Widget
**File:** `Public/views/admin_homepage.php`
```php
// Already has reference, verify:
- Link to time_adjustment_list.php
- Count query for pending requests
- Dashboard card display
```

### 3. Employee History Tab
**File:** `Public/views/employee-edit.php`
```php
// Already has tab structure, verify:
- Queries time_adjustment_requests
- Shows history properly
- Downloads work correctly
```

---

## 🧪 Testing Checklist

### Functionality Tests:
- [ ] Form submission works (all 5 steps)
- [ ] File upload succeeds with valid types
- [ ] File upload rejects invalid types
- [ ] File size limit enforced (10MB)
- [ ] Database record created correctly
- [ ] Approval workflow processes correctly
- [ ] Decline workflow handles properly
- [ ] Redirect after submit works
- [ ] Success message displays
- [ ] History view shows all requests

### Security Tests:
- [ ] CSRF token validation works
- [ ] Session timeout enforced
- [ ] Non-authenticated users blocked
- [ ] SQL injection attempts blocked
- [ ] XSS attempts handled
- [ ] File upload sanitization works
- [ ] Directory traversal prevented

### UI/UX Tests:
- [ ] Form displays correctly on mobile
- [ ] Validation messages show properly
- [ ] Error messages are clear
- [ ] Success messages display
- [ ] Attachment downloads work
- [ ] Table pagination works (if needed)

---

## 🔍 Monitoring Points

### Logs to Check:
```
- PHP error log → File upload errors, DB errors
- MySQL log → Query errors, constraint violations
- Application log → Session errors, CSRF failures
- File system → Upload directory permissions
```

### Metrics to Track:
- Daily submissions count
- Approval success rate
- File upload failure rate
- Average approval time
- Error frequency
- Disk space usage

---

## 📞 Quick Troubleshooting

| Issue | Location | Solution |
|-------|----------|----------|
| Upload fails | `test.php:213` | Check dir permissions |
| DB insert fails | `test.php:301` | Verify table exists |
| CSRF error | `test.php:164` | Clear cookies |
| Session timeout | `test.php:23-31` | Increase timeout value |
| Redirect fails | `test.php:325` | Check file path |
| Approval not updating | `process_time_adjustment.php` | Verify transaction |
| Attachment not found | `time_adjustment_list.php:174` | Check file path |

---

## 📚 Documentation Files Included

1. **DEPLOYMENT_AFFECTED_FILES_ANALYSIS.md** ← Comprehensive technical analysis
2. **PRODUCTION_DEPLOYMENT_CHECKLIST.md** ← Step-by-step deployment guide
3. **TECHNICAL_ARCHITECTURE.md** ← Database schema and API details
4. **AFFECTED_FILES_SUMMARY.md** ← This file (quick reference)

---

**Status:** Ready for Production Deployment
**Version:** 1.0
**Last Updated:** November 4, 2025
**Estimated Deployment Time:** 1-2 hours
**Risk Level:** Low (isolated feature, no core system changes)
