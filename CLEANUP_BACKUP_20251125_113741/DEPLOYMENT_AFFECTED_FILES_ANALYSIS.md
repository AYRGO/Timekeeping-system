# Time Adjustment Request Feature - Production Deployment Analysis

## Overview
Deploying `test.php` in production will affect multiple interconnected files across the system. This document provides a comprehensive list of all affected files organized by category.

---

## 📋 PRIMARY FILES AFFECTED

### 1. **Core Request Form File**
- **`Public/module/test.php`** ⭐ MAIN FILE
  - Multi-step time adjustment request form (5 steps)
  - Human verification with word puzzle
  - File upload handling
  - Form data persistence in session
  - CSRF token validation
  - Redirects to `time_log_create.php` on success

---

## 🗄️ DATABASE TABLES AFFECTED

### Tables Used (Must Exist):

1. **`time_adjustment_requests`** (Primary)
   - Stores pending adjustment requests from employees
   - Columns: id, employee_id, log_date, current_time_in, current_time_out, requested_time_in, requested_time_out, reason, status, submitted_at, attachment, created_at, notified, deleted
   - Used for new submissions

2. **`time_logs`** (Supporting)
   - Stores employee time entries
   - Creates new entries if none exist for requested date
   - Used to fetch current time_in/time_out values

3. **`employees`** (Supporting)
   - Validates employee existence and active status
   - Used for employee information

4. **`post_time_adjustment_requests`** (Archive/History)
   - Stores processed (approved/declined) requests
   - Related views pull from this table for history

---

## 📁 UPLOAD DIRECTORIES AFFECTED

### Directory Structure Required:

```
Public/uploads/
├── time_adjustments/          ⭐ NEW - Created if doesn't exist
│   └── attach_*.{pdf,jpg,jpeg,png,doc,docx}
├── time_adjustments/          (Historical - may exist)
└── time_adjustment_attachments/  (Used in employee-edit.php)
```

**Permissions Required:**
- `Public/uploads/time_adjustments/` must be writable (755+)

---

## 🔌 INTERCONNECTED FILES (Critical Dependencies)

### A. **Employee Dashboard & Navigation**

#### `Public/module/time_log_create.php` ⭐ REDIRECT TARGET
- **Relationship:** Test.php redirects here after successful submission
- **Impact:** Must handle `success_message` session variable
- **Line:** Checks for `$_SESSION['success_message']`

#### `Public/module/sidebar.php`
- **Relationship:** Must include link to test.php for employees
- **Needed:** Add menu item "Request Time Adjustment" → `test.php`

#### `Public/module/profile_section.php`
- **Relationship:** May need to display adjustment request counts
- **Needed:** Integration to show pending requests count

---

### B. **Admin/Manager Review & Approval**

#### `Public/views/time_adjustment_list.php` ⭐ ADMIN PANEL
- **Relationship:** Displays all pending time adjustment requests
- **Fetches from:** `time_adjustment_requests` table (pending requests)
- **Features:**
  - View current pending requests (Step 1)
  - View history from `post_time_adjustment_requests` (Step 2)
  - Links to attachment download
  - Action buttons for approve/decline

#### `Public/views/process_time_adjustment.php` ⭐ APPROVAL HANDLER
- **Relationship:** Processes approval/decline of requests
- **Actions:**
  - Updates `time_logs` with approved times
  - Moves request from `time_adjustment_requests` → `post_time_adjustment_requests`
  - Handles approval/decline reasons

#### `Public/controller/update_time_adjustment.php`
- **Relationship:** AJAX endpoint for updating adjustment requests
- **Method:** POST with JSON
- **Fields Validated:** request_id, table_name, requested_time_in, requested_time_out
- **Functions:** Edit pending adjustments

#### `Public/views/admin_homepage.php`
- **Relationship:** Dashboard widget showing pending adjustments count
- **Query:** `SELECT COUNT(*) FROM time_adjustment_requests WHERE status = 'pending'`
- **Display:** Shows Time Adjustment requests in recent activity feed

---

### C. **Employee Profile & History**

#### `Public/views/employee-edit.php`
- **Relationship:** Shows employee's time adjustment request history
- **Tab:** "Time Adjustments" section (line ~1829)
- **Displays:** All requests with status, dates, attachments
- **Download Path:** `uploads/time_adjustment_attachments/`
- **Data Source:** `time_adjustment_requests` table

---

### D. **Configuration & Session Files**

#### `Public/config/db.php` ⭐ DATABASE CONNECTION
- **Relationship:** PDO connection used for all database operations
- **Must Provide:** Working connection to database with proper credentials

#### `Public/config/env.php`
- **Relationship:** Environment configuration, timezone settings
- **Used:** Date/time zone setup in test.php

#### `Public/config/csrf_helper.php`
- **Relationship:** CSRF token generation/validation
- **Used:** For form security

---

### E. **Notification & Activity Logging**

#### Root Level: `reset_notifications_for_testing.php`
- **Relationship:** Test/reset utility for time adjustment notifications
- **Query:** Updates `notified` flag on `time_adjustment_requests`
- **Purpose:** Dev/testing support

---

## 🔐 FILE PERMISSIONS & CONFIGURATIONS

### Required PHP Settings:
```php
// In test.php (already configured):
- session_start()
- error_reporting(E_ALL)
- ini_set('display_errors', 1)
- date_default_timezone_set('Asia/Manila')
```

### Required Directory Permissions:
```bash
Public/uploads/time_adjustments/   → 755 (must be writable)
Public/uploads/                     → 755 (parent directory)
```

### Required File Upload Configuration (php.ini):
```ini
upload_max_filesize = 10M          (Currently allows 10MB)
post_max_size = 50M
max_file_uploads = 20
file_uploads = On
```

---

## 🔄 DATA FLOW & INTEGRATION POINTS

### Complete User Journey:

```
1. Employee clicks link in sidebar.php
   ↓
2. Loads Public/module/test.php
   ├─ Human verification step
   ├─ Date selection (reads from time_logs)
   ├─ Time adjustment specification
   ├─ Reason entry
   ├─ File upload to Public/uploads/time_adjustments/
   └─ Review & Submit
   ↓
3. INSERT into time_adjustment_requests table
   ↓
4. Redirect to time_log_create.php (dashboard)
   ↓
5. Admin Reviews at Public/views/time_adjustment_list.php
   ├─ Fetches pending from time_adjustment_requests
   └─ Shows with download link to attachment
   ↓
6. Admin Approves via process_time_adjustment.php
   ├─ Updates time_logs with new times
   ├─ Moves record to post_time_adjustment_requests
   └─ Creates notification
   ↓
7. Employee sees result in employee-edit.php Time Adjustments tab
```

---

## 📊 DATABASE IMPACT ANALYSIS

### INSERT Operations:
- **Table:** `time_adjustment_requests`
- **Frequency:** Per employee request submission
- **Fields:** 8 columns populated

### SELECT Operations:
- **Tables:** time_logs, time_adjustment_requests, employees
- **Queries:** ~4 reads per submission process

### UPDATE Operations:
- **Tables:** time_logs (when approved)
- **Trigger:** Via `process_time_adjustment.php`

---

## 🚨 CRITICAL CHECKLIST FOR PRODUCTION

- [ ] Ensure `time_adjustment_requests` table exists with all columns
- [ ] Ensure `post_time_adjustment_requests` table exists (for history)
- [ ] Create `Public/uploads/time_adjustments/` directory
- [ ] Set directory permissions to 755
- [ ] Verify database connection in `Public/config/db.php`
- [ ] Verify CSRF helper at `Public/config/csrf_helper.php`
- [ ] Ensure `time_log_create.php` is accessible (redirect target)
- [ ] Configure sidebar.php with link to `test.php`
- [ ] Update admin menu to include `time_adjustment_list.php` link
- [ ] Verify email notifications setup (if used)
- [ ] Test file upload functionality with various file types
- [ ] Test CSRF token validation
- [ ] Verify session management and timeout
- [ ] Set up proper error logging
- [ ] Create backup of current database schema

---

## 🔗 FILE DEPENDENCY GRAPH

```
test.php (MAIN)
├── Reads: env.php, db.php, csrf_helper.php
├── Writes: Public/uploads/time_adjustments/
├── Inserts: time_adjustment_requests table
├── Selects: time_logs, employees tables
├── Redirects: time_log_create.php
│
├── RELATED: time_adjustment_list.php (Admin viewing)
├── RELATED: process_time_adjustment.php (Admin approval)
├── RELATED: update_time_adjustment.php (AJAX updates)
├── RELATED: admin_homepage.php (Dashboard widget)
├── RELATED: employee-edit.php (History view)
└── RELATED: sidebar.php (Navigation link)
```

---

## ⚠️ POTENTIAL ISSUES TO MONITOR

1. **File Upload Failures:**
   - Check directory permissions
   - Monitor disk space
   - Verify MIME type detection

2. **Session/CSRF Issues:**
   - Session timeout conflicts
   - CSRF token expiration (set to 30 min in code)
   - Multiple tab/window submissions

3. **Database Constraints:**
   - Foreign key constraints on employee_id
   - Character encoding for attachment filename
   - Concurrent request handling

4. **Integration Points:**
   - Missing sidebar links
   - Admin panel not accessible
   - Approval process blocked

---

## 📝 DEPLOYMENT SUMMARY

**Total Files to Deploy/Update:** 11 files
- 1 Primary Form File (test.php)
- 2 Processing/Approval Files
- 3 Admin Panel Files
- 2 Configuration Files
- 3 Integration Points

**Database Tables Required:** 3
- time_adjustment_requests ⭐ (NEW)
- time_logs ⭐ (UPDATE)
- employees

**Directories Required:** 1
- Public/uploads/time_adjustments/ (writable)

**Estimated Impact:** High - Affects admin, employee, and database layers

---

## 📋 QUICK REFERENCE - All Connected Files

### **Submission Flow:**
1. `Public/module/test.php` ← User submits request
2. `Public/uploads/time_adjustments/` ← Attachment stored
3. `Database: time_adjustment_requests` ← Record created
4. `Public/module/time_log_create.php` ← Redirect on success

### **Admin Flow:**
5. `Public/views/time_adjustment_list.php` ← View pending requests
6. `Public/views/process_time_adjustment.php` ← Process approval
7. `Public/controller/update_time_adjustment.php` ← AJAX updates
8. `Public/views/admin_homepage.php` ← Dashboard summary

### **Support/History:**
9. `Public/views/employee-edit.php` ← Employee history view
10. `reset_notifications_for_testing.php` ← Testing/debugging
11. Config: `env.php`, `db.php`, `csrf_helper.php`

---

**Last Updated:** 2025-11-04
**Feature:** Time Adjustment Request System
**Status:** Ready for Production Deployment
