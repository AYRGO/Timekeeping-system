<?php
/**
 * Calendar Scheduler Class
 * Manages employee daily schedules, default patterns, and rotating shifts
 * Works alongside existing schedule_change_requests table without affecting it
 */

class CalendarScheduler {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Get employee's schedule for a specific date
     * Priority: Daily Override > Rotating Pattern > Default Weekly > Official Schedule
     */
    public function getEmployeeScheduleForDate($employee_id, $date) {
        $result = [
            'date' => $date,
            'employee_id' => $employee_id,
            'work_schedule_id' => null,
            'time_in' => null,
            'time_out' => null,
            'schedule_type' => 'none',
            'is_rest_day' => false,
            'is_holiday' => false,
            'holiday_name' => null,
            'source' => 'none', // daily, rotating, default, official, none
            'notes' => null
        ];
        
        // 1. Check for specific daily schedule (highest priority)
        $dailySchedule = $this->getDailySchedule($employee_id, $date);
        if ($dailySchedule) {
            return array_merge($result, $dailySchedule, ['source' => 'daily']);
        }
        
        // 2. Check for rotating schedule pattern
        $rotatingSchedule = $this->getRotatingSchedule($employee_id, $date);
        if ($rotatingSchedule) {
            return array_merge($result, $rotatingSchedule, ['source' => 'rotating']);
        }
        
        // 3. Check default weekly schedule
        $defaultSchedule = $this->getDefaultWeeklySchedule($employee_id, $date);
        if ($defaultSchedule) {
            return array_merge($result, $defaultSchedule, ['source' => 'default']);
        }
        
        // 4. Fall back to official schedule from employees table
        $officialSchedule = $this->getOfficialSchedule($employee_id);
        if ($officialSchedule) {
            return array_merge($result, $officialSchedule, ['source' => 'official']);
        }
        
        // 5. Check if it's a holiday
        $holiday = $this->getHoliday($date);
        if ($holiday) {
            $result['is_holiday'] = true;
            $result['holiday_name'] = $holiday['holiday_name'];
            $result['schedule_type'] = 'holiday';
        }
        
        return $result;
    }
    
    /**
     * Get specific daily schedule override
     */
    private function getDailySchedule($employee_id, $date) {
        $stmt = $this->pdo->prepare("
            SELECT 
                eds.work_schedule_id,
                ws.time_in,
                ws.time_out,
                eds.schedule_type,
                eds.is_rest_day,
                eds.is_holiday,
                eds.notes
            FROM employee_daily_schedules eds
            LEFT JOIN work_schedules ws ON eds.work_schedule_id = ws.id
            WHERE eds.employee_id = ? AND eds.schedule_date = ?
        ");
        $stmt->execute([$employee_id, $date]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get schedule from rotating pattern
     */
    private function getRotatingSchedule($employee_id, $date) {
        $stmt = $this->pdo->prepare("
            SELECT 
                ers.pattern_id,
                ers.start_date,
                ers.cycle_start_day,
                rsp.cycle_length,
                rspd.work_schedule_id,
                ws.time_in,
                ws.time_out,
                rspd.is_rest_day,
                rspd.day_label as notes
            FROM employee_rotating_schedules ers
            JOIN rotating_schedule_patterns rsp ON ers.pattern_id = rsp.id
            LEFT JOIN rotating_schedule_pattern_days rspd ON rsp.id = rspd.pattern_id
            LEFT JOIN work_schedules ws ON rspd.work_schedule_id = ws.id
            WHERE ers.employee_id = ?
              AND ers.is_active = 1
              AND ers.start_date <= ?
              AND (ers.end_date IS NULL OR ers.end_date >= ?)
            ORDER BY ers.start_date DESC
            LIMIT 1
        ");
        $stmt->execute([$employee_id, $date, $date]);
        $rotation = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$rotation) return null;
        
        // Calculate which day in the cycle this date falls on
        $start_date = new DateTime($rotation['start_date']);
        $current_date = new DateTime($date);
        $days_diff = $start_date->diff($current_date)->days;
        
        // Account for cycle_start_day offset
        $adjusted_diff = $days_diff + ($rotation['cycle_start_day'] - 1);
        $day_in_cycle = ($adjusted_diff % $rotation['cycle_length']) + 1;
        
        // Get the pattern day details
        $stmt = $this->pdo->prepare("
            SELECT 
                rspd.work_schedule_id,
                ws.time_in,
                ws.time_out,
                rspd.is_rest_day,
                rspd.day_label as notes
            FROM rotating_schedule_pattern_days rspd
            LEFT JOIN work_schedules ws ON rspd.work_schedule_id = ws.id
            WHERE rspd.pattern_id = ? AND rspd.day_number = ?
        ");
        $stmt->execute([$rotation['pattern_id'], $day_in_cycle]);
        $pattern_day = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($pattern_day) {
            $pattern_day['schedule_type'] = $pattern_day['is_rest_day'] ? 'rest_day' : 'regular';
            return $pattern_day;
        }
        
        return null;
    }
    
    /**
     * Get default weekly schedule
     */
    private function getDefaultWeeklySchedule($employee_id, $date) {
        $day_of_week = date('w', strtotime($date));
        
        $stmt = $this->pdo->prepare("
            SELECT 
                eds.work_schedule_id,
                ws.time_in,
                ws.time_out,
                eds.is_rest_day
            FROM employee_default_schedules eds
            LEFT JOIN work_schedules ws ON eds.work_schedule_id = ws.id
            WHERE eds.employee_id = ?
              AND eds.day_of_week = ?
              AND eds.effective_from <= ?
              AND (eds.effective_until IS NULL OR eds.effective_until >= ?)
            ORDER BY eds.effective_from DESC
            LIMIT 1
        ");
        $stmt->execute([$employee_id, $day_of_week, $date, $date]);
        $schedule = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($schedule) {
            $schedule['schedule_type'] = $schedule['is_rest_day'] ? 'rest_day' : 'regular';
            return $schedule;
        }
        
        return null;
    }
    
    /**
     * Get official schedule from employees table
     */
    private function getOfficialSchedule($employee_id) {
        $stmt = $this->pdo->prepare("
            SELECT 
                e.official_sched as work_schedule_id,
                ws.time_in,
                ws.time_out
            FROM employees e
            LEFT JOIN work_schedules ws ON e.official_sched = ws.id
            WHERE e.id = ? AND e.official_sched IS NOT NULL
        ");
        $stmt->execute([$employee_id]);
        $schedule = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($schedule) {
            $schedule['schedule_type'] = 'regular';
            $schedule['is_rest_day'] = false;
            return $schedule;
        }
        
        return null;
    }
    
    /**
     * Check if date is a holiday
     */
    private function getHoliday($date) {
        $stmt = $this->pdo->prepare("
            SELECT holiday_name, holiday_type 
            FROM company_holidays 
            WHERE holiday_date = ?
        ");
        $stmt->execute([$date]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Set daily schedule for an employee
     */
    public function setDailySchedule($employee_id, $date, $work_schedule_id, $schedule_type = 'override', $notes = null, $created_by = null) {
        $is_rest_day = ($schedule_type === 'rest_day') ? 1 : 0;
        $is_holiday = ($schedule_type === 'holiday') ? 1 : 0;
        
        $stmt = $this->pdo->prepare("
            INSERT INTO employee_daily_schedules 
                (employee_id, schedule_date, work_schedule_id, schedule_type, is_rest_day, is_holiday, notes, created_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                work_schedule_id = VALUES(work_schedule_id),
                schedule_type = VALUES(schedule_type),
                is_rest_day = VALUES(is_rest_day),
                is_holiday = VALUES(is_holiday),
                notes = VALUES(notes),
                created_by = VALUES(created_by),
                updated_at = CURRENT_TIMESTAMP
        ");
        
        return $stmt->execute([
            $employee_id, $date, $work_schedule_id, $schedule_type, 
            $is_rest_day, $is_holiday, $notes, $created_by
        ]);
    }
    
    /**
     * Set default weekly schedule for an employee
     */
    public function setDefaultWeeklySchedule($employee_id, $day_of_week, $work_schedule_id, $is_rest_day = 0, $effective_from = null) {
        if (!$effective_from) {
            $effective_from = date('Y-m-d');
        }
        
        $stmt = $this->pdo->prepare("
            INSERT INTO employee_default_schedules 
                (employee_id, day_of_week, work_schedule_id, is_rest_day, effective_from)
            VALUES (?, ?, ?, ?, ?)
        ");
        
        return $stmt->execute([$employee_id, $day_of_week, $work_schedule_id, $is_rest_day, $effective_from]);
    }
    
    /**
     * Apply approved schedule change request to daily schedules
     */
    public function applyScheduleChangeRequest($schedule_change_request_id, $applied_by = null) {
        // Get the request details
        $stmt = $this->pdo->prepare("
            SELECT employee_id, start_date, end_date, work_schedule_id, reason
            FROM schedule_change_requests
            WHERE id = ? AND status = 'approved'
        ");
        $stmt->execute([$schedule_change_request_id]);
        $request = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$request) {
            return false;
        }
        
        // Apply schedule for each date in range
        $start = new DateTime($request['start_date']);
        $end = new DateTime($request['end_date']);
        $interval = new DateInterval('P1D');
        $period = new DatePeriod($start, $interval, $end->modify('+1 day'));
        
        foreach ($period as $date) {
            $date_str = $date->format('Y-m-d');
            
            // Get original schedule
            $original = $this->getEmployeeScheduleForDate($request['employee_id'], $date_str);
            
            // Set new daily schedule
            $this->setDailySchedule(
                $request['employee_id'],
                $date_str,
                $request['work_schedule_id'],
                'override',
                'Applied from schedule change request #' . $schedule_change_request_id,
                $applied_by
            );
            
            // Record in history
            $this->recordOverrideHistory(
                $request['employee_id'],
                $date_str,
                $original['work_schedule_id'],
                $request['work_schedule_id'],
                $request['reason'],
                $schedule_change_request_id,
                $applied_by
            );
        }
        
        return true;
    }
    
    /**
     * Record schedule override in history
     */
    private function recordOverrideHistory($employee_id, $date, $original_schedule_id, $new_schedule_id, $reason, $request_id = null, $applied_by = null) {
        $stmt = $this->pdo->prepare("
            INSERT INTO schedule_override_history 
                (employee_id, schedule_date, original_schedule_id, new_schedule_id, override_reason, schedule_change_request_id, applied_by)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        
        return $stmt->execute([
            $employee_id, $date, $original_schedule_id, $new_schedule_id, 
            $reason, $request_id, $applied_by
        ]);
    }
    
    /**
     * Get monthly schedule for an employee (calendar view)
     */
    public function getMonthlySchedule($employee_id, $year, $month) {
        $days_in_month = cal_days_in_month(CAL_GREGORIAN, $month, $year);
        $schedules = [];
        
        for ($day = 1; $day <= $days_in_month; $day++) {
            $date = sprintf('%04d-%02d-%02d', $year, $month, $day);
            $schedules[$date] = $this->getEmployeeScheduleForDate($employee_id, $date);
        }
        
        return $schedules;
    }
    
    /**
     * Delete daily schedule (remove override, revert to default)
     */
    public function deleteDailySchedule($employee_id, $date) {
        $stmt = $this->pdo->prepare("
            DELETE FROM employee_daily_schedules 
            WHERE employee_id = ? AND schedule_date = ?
        ");
        return $stmt->execute([$employee_id, $date]);
    }
}
