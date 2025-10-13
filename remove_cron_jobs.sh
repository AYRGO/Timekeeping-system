#!/bin/bash
# File: remove_cron_jobs.sh
# Script to help identify and remove leave accrual cron jobs

echo "🔍 HOSTINGER CRON JOB REMOVAL GUIDE"
echo "=================================="
echo ""

echo "📋 STEPS TO REMOVE CRON JOBS:"
echo ""
echo "1. LOG INTO HOSTINGER CONTROL PANEL"
echo "   • Go to https://hpanel.hostinger.com"
echo "   • Login with your credentials"
echo ""

echo "2. NAVIGATE TO CRON JOBS"
echo "   • Click 'Advanced' in the left menu"
echo "   • Click 'Cron Jobs'"
echo ""

echo "3. LOOK FOR THESE CRON JOB PATTERNS:"
echo "   • Any job containing: 'leave_accrual'"
echo "   • Any job containing: 'monthly_leave'"
echo "   • Any job containing: 'hostinger_leave'"
echo "   • Any job containing: 'smart_leave'"
echo ""

echo "4. DELETE EACH MATCHING CRON JOB"
echo "   • Click the 'Delete' button next to each job"
echo "   • Confirm the deletion"
echo ""

echo "5. VERIFY NO JOBS REMAIN"
echo "   • Check that no leave-accrual related jobs exist"
echo "   • The cron jobs list should be clean"
echo ""

echo "📂 FILES YOU CAN DELETE FROM SERVER:"
echo "   /Public/cron/monthly_leave_accrual_scheduler.php"
echo "   /Public/cron/hostinger_leave_accrual.php"
echo "   /Public/cron/smart_leave_accrual_scheduler.php"
echo "   /Public/cron/test_hostinger_connection.php"
echo "   /Public/module/monthly_leave_monitor.php"
echo "   /Public/module/toggle_accrual_mode.php"
echo ""

echo "✅ AFTER COMPLETION:"
echo "   • No more automatic leave credit increases"
echo "   • System completely stopped"
echo "   • You can manually manage leave credits if needed"
echo ""

echo "🆘 IF PROBLEMS PERSIST:"
echo "   • Contact Hostinger support"
echo "   • Ask them to check for any remaining cron jobs"
echo "   • Provide them with your domain name"
echo ""