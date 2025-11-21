# LEAVE ACCRUAL SYSTEM FLOW - Visual Guide

## 📊 System Architecture

```
┌─────────────────────────────────────────────────────────────────┐
│                    LEAVE ACCRUAL SYSTEM                          │
│                  (Effective Jan 1, 2026)                         │
└─────────────────────────────────────────────────────────────────┘

┌──────────────────────────────────────────────────────────────────┐
│  EMPLOYEE STATUS CONTROL                                          │
├──────────────────────────────────────────────────────────────────┤
│                                                                   │
│  ┌─────────────────┐        ┌─────────────────┐                 │
│  │  PROBATIONARY   │ ────▶  │    REGULAR      │                 │
│  │                 │        │                 │                 │
│  │  ❌ No SL       │        │  ✅ 5 days SL   │                 │
│  │  ❌ No VL       │        │  ✅ +1.25 VL/mo │                 │
│  └─────────────────┘        └─────────────────┘                 │
│                                                                   │
└──────────────────────────────────────────────────────────────────┘

┌──────────────────────────────────────────────────────────────────┐
│  SICK LEAVE (SL) - ONE-TIME GRANT                                │
├──────────────────────────────────────────────────────────────────┤
│                                                                   │
│  EVENT: Employee Regularization                                  │
│  ┌────────────────────────────────────────────────────────────┐ │
│  │                                                            │ │
│  │  Admin Changes:                                           │ │
│  │  Probationary ──────▶ Regular                             │ │
│  │                                                            │ │
│  │  System Action:                                           │ │
│  │  1. Update Emp_Type = 'Regular' ✅                        │ │
│  │  2. Grant 5.00 days SL ✅                                 │ │
│  │  3. Set SL monthly_increment = 0 ✅                       │ │
│  │  4. Show success alert ✅                                 │ │
│  │                                                            │ │
│  └────────────────────────────────────────────────────────────┘ │
│                                                                   │
│  MONTHLY: NO ACCRUAL                                             │
│  ┌────────────────────────────────────────────────────────────┐ │
│  │  SL Balance: Remains at 5.00 days (unchanged)            │ │
│  │  No monthly additions ✅                                   │ │
│  └────────────────────────────────────────────────────────────┘ │
│                                                                   │
└──────────────────────────────────────────────────────────────────┘

┌──────────────────────────────────────────────────────────────────┐
│  VACATION LEAVE (VL) - MONTHLY ACCRUAL                           │
├──────────────────────────────────────────────────────────────────┤
│                                                                   │
│  UPON REGULARIZATION:                                            │
│  ┌────────────────────────────────────────────────────────────┐ │
│  │                                                            │ │
│  │  System Action: Set VL monthly_increment = 1.25 ✅        │ │
│  │  Initial Balance: 0.00 days                               │ │
│  │                                                            │ │
│  │  Admin Manual Entry (Pro-rated from Probation):           │ │
│  │  Example: 3 months probation × 1.25 = 3.75 days          │ │
│  │  Tool: admin_leave_adjustment.php                         │ │
│  │                                                            │ │
│  └────────────────────────────────────────────────────────────┘ │
│                                                                   │
│  MONTHLY AUTO-ACCRUAL (Last Day of Month):                      │
│  ┌────────────────────────────────────────────────────────────┐ │
│  │                                                            │ │
│  │  Month 1: 0.00 + 1.25 = 1.25 days ✅                      │ │
│  │  Month 2: 1.25 + 1.25 = 2.50 days ✅                      │ │
│  │  Month 3: 2.50 + 1.25 = 3.75 days ✅                      │ │
│  │  ...                                                       │ │
│  │  Month 12: 13.75 + 1.25 = 15.00 days (MAX) ✅            │ │
│  │                                                            │ │
│  │  Carry-Over: Up to 5 days to next year                   │ │
│  │  Maximum Total: 15 + 5 = 20 days                          │ │
│  │                                                            │ │
│  └────────────────────────────────────────────────────────────┘ │
│                                                                   │
└──────────────────────────────────────────────────────────────────┘

┌──────────────────────────────────────────────────────────────────┐
│  AUTOMATED PROCESSING                                             │
├──────────────────────────────────────────────────────────────────┤
│                                                                   │
│  ┌─────────────────────────────────────────────────────────────┐│
│  │  process_auto_accrual.php                                   ││
│  │  ────────────────────────────────────────────────────────── ││
│  │                                                              ││
│  │  PRODUCTION MODE: Last day of each month                    ││
│  │  TESTING MODE: Every 10 seconds (for QA)                    ││
│  │                                                              ││
│  │  1. Check if auto-accrual is enabled ✅                     ││
│  │  2. Check accrual mode (production/testing) ✅              ││
│  │  3. Query: WHERE Emp_Type = 'Regular' ✅                    ││
│  │  4. Process VL only (NOT SL) ✅                             ││
│  │  5. Add 1.25 days to VL balance ✅                          ││
│  │  6. Cap at 15-day maximum ✅                                ││
│  │  7. Handle carry-over (VL only) ✅                          ││
│  │  8. Update last_auto_accrual_month ✅                       ││
│  │                                                              ││
│  └─────────────────────────────────────────────────────────────┘│
│                                                                   │
└──────────────────────────────────────────────────────────────────┘

┌──────────────────────────────────────────────────────────────────┐
│  ADMIN WORKFLOWS                                                  │
├──────────────────────────────────────────────────────────────────┤
│                                                                   │
│  WORKFLOW 1: Regularize Employee                                 │
│  ──────────────────────────────────────────────────────────────  │
│  1. Open employee-edit.php                                       │
│  2. Select employee                                              │
│  3. Click "Edit Profile"                                         │
│  4. Change Employment Type: Probationary → Regular               │
│  5. Click "Update Profile"                                       │
│  6. System grants 5 days SL automatically ✅                     │
│  7. Alert confirms success                                       │
│                                                                   │
│  WORKFLOW 2: Add Pro-rated VL                                    │
│  ──────────────────────────────────────────────────────────────  │
│  1. Open admin_leave_adjustment.php                              │
│  2. Select newly regularized employee                            │
│  3. View current credits                                         │
│  4. Calculate VL: months_probation × 1.25                        │
│  5. Enter:                                                       │
│     - Leave Type: Vacation Leave                                 │
│     - Adjustment Type: Add to existing                           │
│     - Amount: [calculated days]                                  │
│     - Reason: "Pro-rated VL from probation"                      │
│  6. Click "Apply Adjustment" ✅                                  │
│                                                                   │
└──────────────────────────────────────────────────────────────────┘

┌──────────────────────────────────────────────────────────────────┐
│  EXAMPLE TIMELINE                                                 │
├──────────────────────────────────────────────────────────────────┤
│                                                                   │
│  EMPLOYEE: John Doe                                              │
│  HIRED: January 1, 2026 (Probationary)                           │
│  REGULARIZED: April 1, 2026                                      │
│                                                                   │
│  ┌────────────────────────────────────────────────────────────┐ │
│  │ JANUARY 2026                                               │ │
│  │ Status: Probationary                                       │ │
│  │ SL: 0 days  |  VL: 0 days                                  │ │
│  └────────────────────────────────────────────────────────────┘ │
│                                                                   │
│  ┌────────────────────────────────────────────────────────────┐ │
│  │ FEBRUARY 2026                                              │ │
│  │ Status: Probationary                                       │ │
│  │ SL: 0 days  |  VL: 0 days                                  │ │
│  └────────────────────────────────────────────────────────────┘ │
│                                                                   │
│  ┌────────────────────────────────────────────────────────────┐ │
│  │ MARCH 2026                                                 │ │
│  │ Status: Probationary                                       │ │
│  │ SL: 0 days  |  VL: 0 days                                  │ │
│  └────────────────────────────────────────────────────────────┘ │
│                                                                   │
│  ┌────────────────────────────────────────────────────────────┐ │
│  │ APRIL 1, 2026 ⭐ REGULARIZATION DAY                        │ │
│  │ Status: Regular                                            │ │
│  │ SL: 5.00 days (auto-granted) ✅                            │ │
│  │ VL: 3.75 days (admin adds pro-rated: 3 months × 1.25) ✅  │ │
│  └────────────────────────────────────────────────────────────┘ │
│                                                                   │
│  ┌────────────────────────────────────────────────────────────┐ │
│  │ APRIL 30, 2026 (Month End)                                │ │
│  │ Status: Regular                                            │ │
│  │ SL: 5.00 days (unchanged)                                  │ │
│  │ VL: 3.75 + 1.25 = 5.00 days ✅                             │ │
│  └────────────────────────────────────────────────────────────┘ │
│                                                                   │
│  ┌────────────────────────────────────────────────────────────┐ │
│  │ MAY 31, 2026                                               │ │
│  │ Status: Regular                                            │ │
│  │ SL: 5.00 days (unchanged)                                  │ │
│  │ VL: 5.00 + 1.25 = 6.25 days ✅                             │ │
│  └────────────────────────────────────────────────────────────┘ │
│                                                                   │
│  ┌────────────────────────────────────────────────────────────┐ │
│  │ DECEMBER 31, 2026 (After 9 months as Regular)             │ │
│  │ Status: Regular                                            │ │
│  │ SL: 5.00 days (unchanged)                                  │ │
│  │ VL: 3.75 + (9 × 1.25) = 15.00 days (MAX) ✅               │ │
│  └────────────────────────────────────────────────────────────┘ │
│                                                                   │
└──────────────────────────────────────────────────────────────────┘

┌──────────────────────────────────────────────────────────────────┐
│  DATABASE STRUCTURE                                               │
├──────────────────────────────────────────────────────────────────┤
│                                                                   │
│  employees                     leave_credits                     │
│  ──────────────────            ─────────────────────────────     │
│  id (PK)                       id (PK)                           │
│  fname                         employee_id (FK) ──────┐          │
│  lname                         leave_type             │          │
│  Emp_Type ◄─────────────┐      balance                │          │
│    • Probationary       │      carry_over             │          │
│    • Regular ───────────┘      monthly_increment      │          │
│  status                        year                   │          │
│  ...                           updated_at             │          │
│                                                        │          │
│  RELATIONSHIP: One employee → Many leave_credits ─────┘          │
│                                                                   │
│  LEAVE TYPES:                                                    │
│  • sick        → SL (monthly_increment = 0)                      │
│  • vacation    → VL (monthly_increment = 1.25 for Regular)       │
│  • paternity   → Manual only                                     │
│  • maternity   → Manual only                                     │
│  • solo_parent → Manual only                                     │
│  • bereavement → Manual only                                     │
│                                                                   │
└──────────────────────────────────────────────────────────────────┘

┌──────────────────────────────────────────────────────────────────┐
│  FILES REFERENCE                                                  │
├──────────────────────────────────────────────────────────────────┤
│                                                                   │
│  MODIFIED FILES:                                                 │
│  ✏️ /Public/module/process_auto_accrual.php                      │
│     → Monthly VL accrual (VL only, not SL)                       │
│                                                                   │
│  ✏️ /Public/views/employee-edit.php                              │
│     → Added Emp_Type field, SL grant on regularization           │
│                                                                   │
│  NEW FILES:                                                      │
│  ➕ /Public/module/process_regularization_sl.php                 │
│     → Standalone SL grant processor                              │
│                                                                   │
│  ➕ /Public/views/admin_leave_adjustment.php                     │
│     → Manual leave credit adjustment tool                        │
│                                                                   │
│  ➕ NEW_LEAVE_ACCRUAL_POLICY_2026.md                             │
│     → Complete policy documentation                              │
│                                                                   │
│  ➕ LEAVE_ACCRUAL_IMPLEMENTATION_SUMMARY.md                      │
│     → Implementation guide & testing                             │
│                                                                   │
│  ➕ LEAVE_ACCRUAL_QUICK_START.md                                 │
│     → Quick reference for daily use                              │
│                                                                   │
│  ➕ LEAVE_ACCRUAL_FLOW_DIAGRAM.md (this file)                    │
│     → Visual system architecture                                 │
│                                                                   │
└──────────────────────────────────────────────────────────────────┘

╔══════════════════════════════════════════════════════════════════╗
║  🎉 SYSTEM READY FOR DEPLOYMENT - JANUARY 1, 2026                ║
╚══════════════════════════════════════════════════════════════════╝
