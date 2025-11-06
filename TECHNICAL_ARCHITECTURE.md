# Technical Architecture - Time Adjustment Feature

## System Overview

The Time Adjustment Request feature is a multi-layered system allowing employees to request corrections to their time logs with manager approval.

---

## Database Schema

### Table 1: `time_adjustment_requests`
**Purpose:** Stores NEW/PENDING time adjustment requests

```sql
CREATE TABLE IF NOT EXISTS `time_adjustment_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `employee_id` int(11) NOT NULL,
  `log_date` date NOT NULL,
  `current_time_in` time DEFAULT NULL,
  `current_time_out` time DEFAULT NULL,
  `requested_time_in` time DEFAULT NULL,
  `requested_time_out` time DEFAULT NULL,
  `reason` text DEFAULT NULL,
  `status` enum('Pending','Approved','Declined') DEFAULT 'Pending',
  `submitted_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `attachment` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `notified` tinyint(1) DEFAULT 0,
  `deleted` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `employee_id` (`employee_id`),
  KEY `log_date` (`log_date`),
  KEY `status` (`status`),
  CONSTRAINT `time_adjustment_requests_ibfk_1` FOREIGN KEY (`employee_id`) 
    REFERENCES `employees` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
```

**Column Details:**
| Column | Type | Purpose |
|--------|------|---------|
| `id` | INT(11) PK | Unique request identifier |
| `employee_id` | INT(11) FK | Reference to employees table |
| `log_date` | DATE | Date of the time log being adjusted |
| `current_time_in` | TIME | Original time_in value |
| `current_time_out` | TIME | Original time_out value |
| `requested_time_in` | TIME | New requested time_in |
| `requested_time_out` | TIME | New requested time_out |
| `reason` | TEXT | Employee's explanation |
| `status` | ENUM | Current state (Pending/Approved/Declined) |
| `submitted_at` | DATETIME | When employee submitted |
| `attachment` | VARCHAR(255) | Filename of supporting document |
| `created_at` | DATETIME | Record creation timestamp |
| `notified` | TINYINT(1) | Flag: manager notified (0/1) |
| `deleted` | TINYINT(1) | Soft delete flag (0/1) |

---

### Table 2: `post_time_adjustment_requests`
**Purpose:** Archive of PROCESSED (approved/declined) requests

```sql
CREATE TABLE IF NOT EXISTS `post_time_adjustment_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `employee_id` int(11) NOT NULL,
  `log_date` date NOT NULL,
  `current_time_in` time DEFAULT NULL,
  `current_time_out` time DEFAULT NULL,
  `requested_time_in` time DEFAULT NULL,
  `requested_time_out` time DEFAULT NULL,
  `reason` text DEFAULT NULL,
  `status` enum('Pending','Approved','Declined') DEFAULT 'Pending',
  `submitted_at` datetime DEFAULT NULL,
  `attachment` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `notified` tinyint(1) DEFAULT 0,
  `deleted` tinyint(1) DEFAULT 0,
  `processed_at` datetime DEFAULT NULL,
  `processed_by` int(11) DEFAULT NULL,
  `decline_reason` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `employee_id` (`employee_id`),
  KEY `log_date` (`log_date`),
  KEY `status` (`status`),
  CONSTRAINT `post_time_adjustment_requests_ibfk_1` FOREIGN KEY (`employee_id`) 
    REFERENCES `employees` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
```

**Key Differences from time_adjustment_requests:**
- `processed_at` - Timestamp when manager processed the request
- `processed_by` - ID of manager who processed
- `decline_reason` - Reason if declined

---

### Table 3: `time_logs` (Existing - UPDATE)
**Purpose:** Stores employee time entries

```sql
-- VERIFY columns exist:
ALTER TABLE `time_logs` ADD COLUMN IF NOT EXISTS `employee_id` INT(11) NOT NULL;
ALTER TABLE `time_logs` ADD COLUMN IF NOT EXISTS `log_date` DATE NOT NULL;
ALTER TABLE `time_logs` ADD COLUMN IF NOT EXISTS `time_in` TIME;
ALTER TABLE `time_logs` ADD COLUMN IF NOT EXISTS `time_out` TIME;

-- When approval happens, this is updated:
UPDATE time_logs 
SET time_in = ?, time_out = ? 
WHERE employee_id = ? AND log_date = ?
```

---

### Table 4: `employees` (Existing - Required)
**Purpose:** Employee records (referenced by FK)

```sql
-- Must have these columns:
- id (PRIMARY KEY)
- fname
- lname
- email
- status (enum: 'active', 'inactive', etc.)
```

---

## File Upload Architecture

### Upload Path Structure
```
Project Root/
└── Public/
    └── uploads/
        ├── time_adjustments/                    ← NEW
        │   ├── attach_64f3c2a8c1e9.pdf
        │   ├── attach_64f3c2a9d2f1.jpg
        │   ├── attach_64f3c2ab3g2h.docx
        │   └── ...
        ├── overtime/                            (existing)
        ├── profile_images/                      (existing)
        ├── schedule_attachments/                (existing)
        └── leave_attachments/                   (existing)
```

### File Handling Logic (in test.php)

```php
// Upload directory path:
$upload_dir = __DIR__ . "/../uploads/time_adjustments/";

// File naming convention:
$new_filename = uniqid("attach_", true) . "." . $ext;
// Example: attach_64f3c2a8c1e9.70251847.pdf

// Allowed MIME types:
$allowed_types = [
    'application/pdf',
    'image/jpeg',
    'image/jpg',
    'image/png',
    'application/msword',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
];

// Size limit:
$max_size = 10 * 1024 * 1024;  // 10MB

// MIME detection method:
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$detected_type = finfo_file($finfo, $file_tmp);
finfo_close($finfo);
```

### Directory Permissions
```bash
# Owner: Apache/Web Server
# Permissions: 755 (rwxr-xr-x)
chmod 755 Public/uploads/time_adjustments/

# Parent directory
chmod 755 Public/uploads/

# Individual files (readable by web server)
chmod 644 Public/uploads/time_adjustments/*.pdf
```

---

## Session & Security Architecture

### Session Variables Used

```php
// Session array structure:
$_SESSION = [
    'employee' => [
        'id' => 1009,                    // Employee ID
        'role' => 'employee',            // User role
        'name' => 'John Doe'
    ],
    
    'adjustment_form' => [
        'log_date' => '2025-07-15',
        'requested_time_in' => '09:00',
        'requested_time_out' => '17:30',
        'reason' => 'Medical appointment',
        'adjustment_type' => 'both',     // 'time_in', 'time_out', 'both'
        'attachment' => 'attach_64f3c2a8c1e9.pdf'
    ],
    
    'csrf_token' => 'abc123def456ghi789jkl',
    'puzzle_expected' => 'orange',           // For human verification
    'human_verified_adjustment' => true,     // Verification flag
    
    'last_activity' => 1234567890,
    'last_regeneration' => 1234567890,
    'success_message' => 'Request submitted!'
];
```

### CSRF Protection Flow

```php
// Step 1: Generate token (csrf_helper.php)
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

// Step 2: Include in form
<input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

// Step 3: Validate on submission (test.php)
if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    die("Invalid CSRF token.");
}
```

### Session Timeout Configuration

```php
// In test.php:
$session_timeout = 3600;      // 1 hour
$csrf_timeout = 1800;         // 30 minutes

// Check on every request:
if (isset($_SESSION['last_activity']) && 
    (time() - $_SESSION['last_activity'] > $session_timeout)) {
    session_unset();
    session_destroy();
    header("Location: ../employee/login.php?expired=1");
    exit;
}

// Regenerate session ID every 15 minutes:
if (!isset($_SESSION['last_regeneration']) || 
    (time() - $_SESSION['last_regeneration'] > 900)) {
    session_regenerate_id(true);
    $_SESSION['last_regeneration'] = time();
}
```

---

## API/AJAX Endpoints

### 1. Primary Submission (POST)
**File:** `Public/module/test.php`
**Method:** POST
**Data:**
```php
[
    'csrf_token' => 'token_value',
    'log_date' => '2025-07-15',
    'requested_time_in' => '09:00',
    'requested_time_out' => '17:30',
    'adjustment_type' => 'both',
    'reason' => 'Medical appointment',
    'attachment' => $_FILES['attachment'],
    'submit_request' => '1'
]
```
**Response:** Redirect to `time_log_create.php`

### 2. Approval Handler (POST)
**File:** `Public/views/process_time_adjustment.php`
**Method:** POST
**Data:**
```php
[
    'request_id' => 123,
    'action' => 'approve' or 'decline',
    'decline_reason' => 'Need more info' // if declining
]
```
**Actions:**
- Approves: Updates time_logs, moves to post_time_adjustment_requests
- Declines: Moves to post_time_adjustment_requests with reason

### 3. AJAX Update Endpoint (POST)
**File:** `Public/controller/update_time_adjustment.php`
**Method:** POST (JSON)
**Data:**
```json
{
    "request_id": 123,
    "table_name": "time_adjustment_requests",
    "requested_time_in": "09:00",
    "requested_time_out": "17:30"
}
```
**Response:**
```json
{
    "success": true,
    "message": "Request updated successfully"
}
```

---

## Form Flow & Data Transformation

### Step 1: Date Selection
```
Input: log_date (YYYY-MM-DD)
↓
Query: SELECT time_in, time_out FROM time_logs 
       WHERE employee_id = ? AND log_date = ?
↓
Display: Current times or "No log found"
```

### Step 2: Time Adjustment Type
```
Input: adjustment_type ('time_in' | 'time_out' | 'both')
↓
Conditional Logic:
- If 'time_in': Show time_in input, hide time_out
- If 'time_out': Hide time_in, show time_out
- If 'both': Show both inputs
↓
Input: requested_time_in, requested_time_out (HH:MM)
```

### Step 3: Reason
```
Input: reason (text, min 10 chars typically)
↓
Storage: Plain text in database
↓
Length: MAX 65,535 chars (TEXT field)
```

### Step 4: Document Upload
```
Input: attachment (file)
↓
Validation:
- MIME type check
- Size check (max 10MB)
- File extension check
↓
Storage:
- Server: Public/uploads/time_adjustments/attach_*.ext
- Database: filename only (VARCHAR 255)
↓
Access: Direct download link from time_adjustment_list.php
```

### Step 5: Review & Submit
```
Summary Display:
- Selected date (formatted)
- Current times (from time_logs)
- Requested times (formatted)
- Adjustment type
- Reason
- Attachment filename
↓
Insert:
INSERT INTO time_adjustment_requests 
(employee_id, log_date, current_time_in, current_time_out, 
 requested_time_in, requested_time_out, reason, attachment)
VALUES (?, ?, ?, ?, ?, ?, ?, ?)
↓
Redirect: time_log_create.php with success message
```

---

## Admin Approval Workflow

### View & Download
```
Admin Access: time_adjustment_list.php
↓
Fetch: SELECT * FROM time_adjustment_requests 
       WHERE status = 'pending'
↓
Display:
- Employee name
- Log date
- Current times
- Requested times
- Reason
- Attachment (download link)
↓
Actions: Approve | Decline | Edit
```

### Approval Process
```
Click Approve:
↓
process_time_adjustment.php handles:
1. Fetch request details
2. BEGIN TRANSACTION
3. Update time_logs with new times
4. Mark status as 'Approved'
5. Move to post_time_adjustment_requests
6. COMMIT TRANSACTION
↓
Result: Approved request moved to history
```

### Decline Process
```
Click Decline:
↓
Prompt for reason (optional)
↓
process_time_adjustment.php:
1. Fetch request
2. Mark status as 'Declined'
3. Add decline_reason
4. Move to post_time_adjustment_requests
↓
Result: Declined request moved to history
```

---

## Error Handling Architecture

### File Upload Errors
```php
$upload_errors = [
    UPLOAD_ERR_OK           => 'File uploaded successfully',
    UPLOAD_ERR_INI_SIZE     => 'File size exceeds server limit',
    UPLOAD_ERR_FORM_SIZE    => 'File size exceeds form limit',
    UPLOAD_ERR_PARTIAL      => 'File was only partially uploaded',
    UPLOAD_ERR_NO_FILE      => 'No file uploaded',
    UPLOAD_ERR_NO_TMP_DIR   => 'Missing temporary upload directory',
    UPLOAD_ERR_CANT_WRITE   => 'Failed to write file to disk',
    UPLOAD_ERR_EXTENSION    => 'File upload stopped by extension'
];
```

### Validation Errors
```php
Errors checked in order:
1. CSRF token mismatch → die("Invalid CSRF token")
2. Missing log_date → show("Please select a date")
3. Missing adjustment_type → show("Please select type")
4. Missing times → show("Please enter times")
5. Missing reason → show("Please provide reason")
6. Missing attachment → show("Document is required")
```

### Database Errors
```php
try {
    // Database operations
} catch (Exception $e) {
    error_log("Time Adjustment Error: " . $e->getMessage());
    $_SESSION['error'] = "Database error occurred";
    // Graceful fallback
}
```

---

## Security Measures

### 1. Input Validation
```php
// Time format validation
if (!preg_match('/^\d{2}:\d{2}$/', $time_input)) {
    throw new Exception("Invalid time format");
}

// Date validation
$date = DateTime::createFromFormat('Y-m-d', $log_date);
if (!$date) {
    throw new Exception("Invalid date format");
}

// File type validation
if (!in_array($detected_mime, $allowed_types)) {
    throw new Exception("Invalid file type");
}
```

### 2. SQL Injection Prevention
```php
// All queries use prepared statements with placeholders:
$stmt = $pdo->prepare(
    "INSERT INTO time_adjustment_requests 
     (employee_id, log_date, reason, attachment) 
     VALUES (:employee_id, :log_date, :reason, :attachment)"
);
$stmt->execute([
    ':employee_id' => $employee_id,
    ':log_date' => $log_date,
    ':reason' => $reason,
    ':attachment' => $filename
]);
```

### 3. XSS Prevention
```php
// Output encoding:
<?= htmlspecialchars($reason) ?>
<?= htmlspecialchars($attachment_name) ?>

// JSON encoding:
echo json_encode(['message' => $message]);
```

### 4. Authentication Check
```php
// Every file checks:
if (!isset($_SESSION['employee']['id'])) {
    header("Location: ../employee/login.php");
    exit;
}
```

### 5. Authorization Check
```php
// Managers/Admins only for approval:
if ($user_role !== 'admin' && $user_role !== 'manager') {
    die("Unauthorized access");
}
```

---

## Performance Considerations

### Database Indexes
```sql
-- Recommended indexes for queries:
CREATE INDEX idx_tar_employee_status 
  ON time_adjustment_requests(employee_id, status);

CREATE INDEX idx_tar_log_date 
  ON time_adjustment_requests(log_date);

CREATE INDEX idx_tar_submitted 
  ON time_adjustment_requests(submitted_at);

CREATE INDEX idx_ptar_employee 
  ON post_time_adjustment_requests(employee_id);

CREATE INDEX idx_ptar_status 
  ON post_time_adjustment_requests(status);
```

### Query Performance
```php
// Optimal query (with indexes):
SELECT tar.*, e.fname, e.lname
FROM time_adjustment_requests tar
JOIN employees e ON tar.employee_id = e.id
WHERE tar.status = 'pending'
ORDER BY tar.submitted_at DESC;

-- Uses: idx_tar_status for WHERE clause
--       FK index for JOIN
--       Efficient sorting with submitted_at
```

---

## Monitoring & Logging

### Error Logging
```php
// Logged to PHP error log:
error_log("SECURITY: Invalid employee ID in session");
error_log("File uploaded successfully: " . $filename);
error_log("Database error during submission - " . $e->getMessage());
error_log("CSRF token mismatch detected");
```

### Audit Trail
```sql
-- Track approvals:
SELECT processed_at, processed_by, status, decline_reason
FROM post_time_adjustment_requests
WHERE employee_id = ?
ORDER BY processed_at DESC;
```

---

**Version:** 1.0
**Last Updated:** 2025-11-04
**Architecture Type:** Multi-Layer Web Application (Form → Processing → Approval → Archive)
