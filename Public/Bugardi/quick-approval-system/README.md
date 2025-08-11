# Scott OT Approval System - Documentation

## 📁 **Folder Structure**

```
scott-approval-system/
├── admin-tools/           # Admin dashboard tools
├── approval-pages/        # Scott's approval interface
├── email-notifications/   # Email system files
├── cron-jobs/            # Automated tasks
└── README.md             # This documentation
```

## 🎯 **System Overview**

The Scott OT Approval System is a comprehensive overtime request management solution designed specifically for Bugardi employees. It provides secure, email-based approval workflows with automated notifications.

## 📂 **File Organization**

### **Admin Tools** (`/admin-tools/`)
- `generate_scott_link.php` - Link generator with permanent/temporary options
- Related admin dashboard tools

### **Approval Pages** (`/approval-pages/`)
- `scott_ot_approval.php` - Scott's main approval interface
- `link_ot_approval.php` - Alternative link-based approval page
- `quick_approval.php` - Quick approve/reject processor

### **Email Notifications** (`/email-notifications/`)
- `scott_notifications.php` - Core notification system
- Email templates and SMTP configuration

### **Cron Jobs** (`/cron-jobs/`)
- `scott_cron_notifications.php` - Automated daily reminders
- Background task schedulers

## 🔧 **Key Features**

### **Secure Access**
- SHA256 token-based authentication
- Permanent and temporary (24-hour) access tokens
- No login required for Scott

### **Email Notifications**
- New OT request alerts
- Weekly reminder emails with individual request cards
- One-click approve/reject buttons in emails
- Rich HTML email templates

### **Admin Management**
- Link generation dashboard
- Pending request counters with badges
- Request history management
- Delete functionality with confirmation

### **Automated Workflows**
- Daily cron job for pending request reminders
- Weekly summary emails
- Status tracking and logging

## ⚙️ **Technical Configuration**

### **Environment Variables** (`.env`)
```
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USERNAME=it.resourcestaff@gmail.com
SMTP_PASSWORD=[app_password]
SMTP_FROM_EMAIL=it.resourcestaff@gmail.com
SMTP_FROM_NAME=Resource Staff IT
```

### **Database Tables**
- `overtime_requests` - Main OT requests
- `employees` - Employee information
- `post_overtime_requests` - Processed requests

### **CSS Framework**
- Tailwind CSS v4 with custom Bugardi colors
- `card-bugardi` class for orange branding
- Responsive design patterns

## 🚀 **Usage Instructions**

### **For Administrators:**
1. Access Bugardi admin dashboard
2. Click "Generate Access Link" button
3. Choose permanent or temporary link
4. Send link to Scott via secure channel

### **For Scott (Approver):**
1. Use provided link to access approval page
2. Review overtime requests with employee details
3. Click approve/reject buttons for decisions
4. Optional: Check email for quick approval links

### **Email Workflow:**
1. New OT request triggers instant email to Scott
2. Weekly reminders sent every Monday
3. One-click approval directly from email
4. Status updates reflected immediately

## 🔍 **Troubleshooting**

### **Common Issues:**
- **Email not sending:** Check SMTP credentials in `.env`
- **Links not working:** Verify token generation and expiry
- **CSS not loading:** Ensure `output.css` is compiled
- **Database errors:** Check table structure and permissions

### **Debug Steps:**
1. Check PHP error logs
2. Verify database connections
3. Test SMTP configuration
4. Validate token generation

## 📋 **Maintenance**

### **Regular Tasks:**
- Monitor email delivery rates
- Clean up expired tokens
- Review request processing times
- Update CSS compilation as needed

### **Security Notes:**
- Tokens are single-use for security
- All actions logged for audit trail
- Company filtering prevents unauthorized access
- HTTPS recommended for production

## 🔄 **System Flow**

```
1. Employee submits OT request
2. System generates notification email
3. Scott receives email with quick action buttons
4. Scott approves/rejects via email or web interface
5. System updates database and notifies employee
6. Weekly reminders sent for pending requests
```

## 📞 **Support**

For technical issues or feature requests:
- Check error logs first
- Review this documentation
- Contact system administrator
- Update documentation for fixes

---

**Last Updated:** August 7, 2025
**Version:** 2.0
**Maintained by:** Bugardi IT Team
