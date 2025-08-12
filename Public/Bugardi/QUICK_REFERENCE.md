# 📚 Bugardi System - Quick Reference

## 🗂️ **Current Folder Structure**

```
Public/Bugardi/
├── 📁 scott-approval-system/
│   ├── 📁 admin-tools/
│   │   └── generate_scott_link.php
│   ├── 📁 approval-pages/
│   │   ├── scott_ot_approval.php
│   │   ├── link_ot_approval.php
│   │   └── quick_approval.php
│   ├── 📁 email-notifications/
│   │   ├── scott_notifications.php
│   │   ├── test_scott_email.php
│   │   └── send_scott_notification.php
│   ├── 📁 cron-jobs/
│   │   └── scott_cron_notifications.php
│   └── README.md
├── Admin_dashboard.php
├── request_history.php
├── time_logs.php
├── employee_list.php
├── sidebar.php
├── header.php
└── ORGANIZATION_NOTES.md
```

## 🔗 **Key Links & Access Points**

| Feature | File Path | Access Method |
|---------|-----------|---------------|
| **Admin Dashboard** | `Admin_dashboard.php` | Direct admin access |
| **Generate Scott Links** | `scott-approval-system/admin-tools/generate_scott_link.php` | Via admin dashboard button |
| **Request History** | `request_history.php` | Sidebar navigation |
| **Time Logs** | `time_logs.php` | Sidebar navigation |
| **Scott Approval** | `scott-approval-system/approval-pages/scott_ot_approval.php` | Via generated links |

## ⚙️ **System Workflows**

### **OT Request Process:**
1. Employee submits OT request
2. System triggers `scott_notifications.php`
3. Email sent to Scott with action buttons
4. Scott uses approval link or email buttons
5. `quick_approval.php` processes decision

### **Admin Management:**
1. Access admin dashboard
2. View pending requests with counter badge
3. Generate access links (permanent/temporary)
4. Review request history with delete option

### **Time Logs:**
1. Shows all Bugardi records by default
2. Optional filters for employee/date
3. Clear button to reset filters

## 🛠️ **Troubleshooting Quick Fixes**

| Issue | Solution |
|-------|----------|
| **Link not working** | Check file moved to correct subfolder |
| **Email not sending** | Verify paths in `scott_notifications.php` |
| **Admin button broken** | Update path in `Admin_dashboard.php` |
| **Time logs slow** | Add pagination or date range limits |
| **Approval fails** | Check database connection and token validity |

## 📧 **Email System Components**

- **SMTP Config:** Uses `.env` file in root
- **Templates:** Built into `scott_notifications.php`
- **Testing:** Use `test_scott_email.php`
- **Automation:** `scott_cron_notifications.php` for reminders

## 🔒 **Security Features**

- **Token-based access** for Scott approval pages
- **Company filtering** (Bugardi only)
- **Session-based admin authentication**
- **Confirmation dialogs** for delete actions

---
*Keep this reference handy for quick system navigation and troubleshooting!*
