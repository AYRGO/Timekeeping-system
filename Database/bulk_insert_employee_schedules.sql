-- ===================================================================
-- BULK INSERT EMPLOYEE SCHEDULES
-- Generated: December 10, 2025
-- Purpose: Insert weekly default schedules for all employees
-- ===================================================================

-- First, let's verify the work_schedules we need exist:
-- Schedule ID Reference (from employee-edit.php):
-- 1:  06:30 AM - 03:30 PM
-- 2:  08:00 AM - 07:00 PM  
-- 3:  07:30 AM - 04:30 PM
-- 4:  07:00 AM - 04:00 PM
-- 5:  08:00 AM - 05:00 PM
-- 6:  09:00 AM - 06:00 PM
-- 7:  10:00 AM - 07:00 PM
-- 8:  06:00 AM - 03:00 PM
-- 9:  08:00 AM - 04:30 PM
-- 10: 07:40 AM - 04:40 PM
-- 11: 06:30 AM - 03:00 PM
-- 12: 06:30 AM - 05:30 PM
-- 13: 07:00 AM - 06:00 PM
-- 14: 06:00 AM - 05:00 PM
-- 15: 06:00 AM - 04:00 PM
-- 16: 08:30 AM - 04:30 PM
-- 17: 06:00 AM - 12:00 PM
-- 18: 06:00 AM - 02:30 PM
-- 19: 07:00 PM - 03:00 AM (Night shift)
-- 20: 07:00 PM - 04:30 AM (Night shift)
-- 21: 05:00 PM - 02:00 AM (Evening shift)
-- 22: 05:30 PM - 02:00 AM (Evening shift)

-- Additional schedules needed (will be created if not exist):
-- 23: 05:00 AM - 02:00 PM (Hammerhire schedule)
-- 24: 05:30 AM - 02:30 PM (Torres schedule)
-- 25: 06:00 PM - 03:00 AM (Dimla night shift)

-- Create missing schedules
INSERT IGNORE INTO work_schedules (id, name, time_in, time_out) VALUES
(23, '5AM-2PM', '05:00:00', '14:00:00'),
(24, '5:30AM-2:30PM', '05:30:00', '14:30:00'),
(25, '6PM-3AM Night', '18:00:00', '03:00:00');

-- ===================================================================
-- STEP 1: Clear existing default schedules for these employees
-- ===================================================================

-- We'll delete and re-insert to ensure clean data
-- (Uncomment when ready to execute)
-- DELETE FROM employee_default_schedules WHERE employee_id IN (
--     SELECT id FROM employees WHERE 
--     fname = 'Jillian Lao' AND lname = 'Agas' OR
--     fname = 'Ian Myco' AND lname = 'Aguilar' 
--     -- ... (add all employees)
-- );

-- ===================================================================
-- STEP 2: Insert Weekly Default Schedules
-- ===================================================================
-- Day of week: 0=Sunday, 1=Monday, 2=Tuesday, 3=Wednesday, 4=Thursday, 5=Friday, 6=Saturday

-- Agas, Jillian Lao - Wed-Fri 6AM-5PM Sat 7am-6pm | OFF Sun-Tues
INSERT INTO employee_default_schedules (employee_id, day_of_week, work_schedule_id, is_rest_day, effective_from)
SELECT id, 0, NULL, 1, '2025-01-01' FROM employees WHERE fname = 'Jillian Lao' AND lname = 'Agas'
UNION ALL
SELECT id, 1, NULL, 1, '2025-01-01' FROM employees WHERE fname = 'Jillian Lao' AND lname = 'Agas'
UNION ALL
SELECT id, 2, NULL, 1, '2025-01-01' FROM employees WHERE fname = 'Jillian Lao' AND lname = 'Agas'
UNION ALL
SELECT id, 3, 14, 0, '2025-01-01' FROM employees WHERE fname = 'Jillian Lao' AND lname = 'Agas' -- Wed 6AM-5PM
UNION ALL
SELECT id, 4, 14, 0, '2025-01-01' FROM employees WHERE fname = 'Jillian Lao' AND lname = 'Agas' -- Thu 6AM-5PM
UNION ALL
SELECT id, 5, 14, 0, '2025-01-01' FROM employees WHERE fname = 'Jillian Lao' AND lname = 'Agas' -- Fri 6AM-5PM
UNION ALL
SELECT id, 6, 13, 0, '2025-01-01' FROM employees WHERE fname = 'Jillian Lao' AND lname = 'Agas'; -- Sat 7AM-6PM

-- Aguilar, Ian Myco - Tue-Fri 6AM-5PM | Sat-Mon OFF
INSERT INTO employee_default_schedules (employee_id, day_of_week, work_schedule_id, is_rest_day, effective_from)
SELECT id, 0, NULL, 1, '2025-01-01' FROM employees WHERE fname = 'Ian Myco' AND lname = 'Aguilar'
UNION ALL
SELECT id, 1, NULL, 1, '2025-01-01' FROM employees WHERE fname = 'Ian Myco' AND lname = 'Aguilar'
UNION ALL
SELECT id, 2, 14, 0, '2025-01-01' FROM employees WHERE fname = 'Ian Myco' AND lname = 'Aguilar' -- Tue 6AM-5PM
UNION ALL
SELECT id, 3, 14, 0, '2025-01-01' FROM employees WHERE fname = 'Ian Myco' AND lname = 'Aguilar' -- Wed 6AM-5PM
UNION ALL
SELECT id, 4, 14, 0, '2025-01-01' FROM employees WHERE fname = 'Ian Myco' AND lname = 'Aguilar' -- Thu 6AM-5PM
UNION ALL
SELECT id, 5, 14, 0, '2025-01-01' FROM employees WHERE fname = 'Ian Myco' AND lname = 'Aguilar' -- Fri 6AM-5PM
UNION ALL
SELECT id, 6, NULL, 1, '2025-01-01' FROM employees WHERE fname = 'Ian Myco' AND lname = 'Aguilar';

-- Alimurong, Joel Lusung - M-Tue;Th-Fri 7am-6pm | Wed, Sat-Sun OFF
INSERT INTO employee_default_schedules (employee_id, day_of_week, work_schedule_id, is_rest_day, effective_from)
SELECT id, 0, NULL, 1, '2025-01-01' FROM employees WHERE fname = 'Joel Lusung' AND lname = 'Alimurong'
UNION ALL
SELECT id, 1, 13, 0, '2025-01-01' FROM employees WHERE fname = 'Joel Lusung' AND lname = 'Alimurong' -- Mon 7AM-6PM
UNION ALL
SELECT id, 2, 13, 0, '2025-01-01' FROM employees WHERE fname = 'Joel Lusung' AND lname = 'Alimurong' -- Tue 7AM-6PM
UNION ALL
SELECT id, 3, NULL, 1, '2025-01-01' FROM employees WHERE fname = 'Joel Lusung' AND lname = 'Alimurong'
UNION ALL
SELECT id, 4, 13, 0, '2025-01-01' FROM employees WHERE fname = 'Joel Lusung' AND lname = 'Alimurong' -- Thu 7AM-6PM
UNION ALL
SELECT id, 5, 13, 0, '2025-01-01' FROM employees WHERE fname = 'Joel Lusung' AND lname = 'Alimurong' -- Fri 7AM-6PM
UNION ALL
SELECT id, 6, NULL, 1, '2025-01-01' FROM employees WHERE fname = 'Joel Lusung' AND lname = 'Alimurong';

-- Alvarez, John Bryan - M-F 8AM-4:30PM
INSERT INTO employee_default_schedules (employee_id, day_of_week, work_schedule_id, is_rest_day, effective_from)
SELECT id, 0, NULL, 1, '2025-01-01' FROM employees WHERE fname = 'John Bryan' AND lname = 'Alvarez'
UNION ALL
SELECT id, 1, 9, 0, '2025-01-01' FROM employees WHERE fname = 'John Bryan' AND lname = 'Alvarez'
UNION ALL
SELECT id, 2, 9, 0, '2025-01-01' FROM employees WHERE fname = 'John Bryan' AND lname = 'Alvarez'
UNION ALL
SELECT id, 3, 9, 0, '2025-01-01' FROM employees WHERE fname = 'John Bryan' AND lname = 'Alvarez'
UNION ALL
SELECT id, 4, 9, 0, '2025-01-01' FROM employees WHERE fname = 'John Bryan' AND lname = 'Alvarez'
UNION ALL
SELECT id, 5, 9, 0, '2025-01-01' FROM employees WHERE fname = 'John Bryan' AND lname = 'Alvarez'
UNION ALL
SELECT id, 6, NULL, 1, '2025-01-01' FROM employees WHERE fname = 'John Bryan' AND lname = 'Alvarez';

-- Antonio, Vincent Kevin Santos - Mon-Tue;Thu-Fri 7AM-6PM | Wed, Sat, Sun OFF
INSERT INTO employee_default_schedules (employee_id, day_of_week, work_schedule_id, is_rest_day, effective_from)
SELECT id, 0, NULL, 1, '2025-01-01' FROM employees WHERE fname = 'Vincent Kevin Santos' AND lname = 'Antonio'
UNION ALL
SELECT id, 1, 13, 0, '2025-01-01' FROM employees WHERE fname = 'Vincent Kevin Santos' AND lname = 'Antonio'
UNION ALL
SELECT id, 2, 13, 0, '2025-01-01' FROM employees WHERE fname = 'Vincent Kevin Santos' AND lname = 'Antonio'
UNION ALL
SELECT id, 3, NULL, 1, '2025-01-01' FROM employees WHERE fname = 'Vincent Kevin Santos' AND lname = 'Antonio'
UNION ALL
SELECT id, 4, 13, 0, '2025-01-01' FROM employees WHERE fname = 'Vincent Kevin Santos' AND lname = 'Antonio'
UNION ALL
SELECT id, 5, 13, 0, '2025-01-01' FROM employees WHERE fname = 'Vincent Kevin Santos' AND lname = 'Antonio'
UNION ALL
SELECT id, 6, NULL, 1, '2025-01-01' FROM employees WHERE fname = 'Vincent Kevin Santos' AND lname = 'Antonio';

-- Angeles, Christine Khlaryss - M-F 6am-3pm
INSERT INTO employee_default_schedules (employee_id, day_of_week, work_schedule_id, is_rest_day, effective_from)
SELECT id, 0, NULL, 1, '2025-01-01' FROM employees WHERE fname = 'Christine Khlaryss' AND lname = 'Angeles'
UNION ALL
SELECT id, 1, 8, 0, '2025-01-01' FROM employees WHERE fname = 'Christine Khlaryss' AND lname = 'Angeles'
UNION ALL
SELECT id, 2, 8, 0, '2025-01-01' FROM employees WHERE fname = 'Christine Khlaryss' AND lname = 'Angeles'
UNION ALL
SELECT id, 3, 8, 0, '2025-01-01' FROM employees WHERE fname = 'Christine Khlaryss' AND lname = 'Angeles'
UNION ALL
SELECT id, 4, 8, 0, '2025-01-01' FROM employees WHERE fname = 'Christine Khlaryss' AND lname = 'Angeles'
UNION ALL
SELECT id, 5, 8, 0, '2025-01-01' FROM employees WHERE fname = 'Christine Khlaryss' AND lname = 'Angeles'
UNION ALL
SELECT id, 6, NULL, 1, '2025-01-01' FROM employees WHERE fname = 'Christine Khlaryss' AND lname = 'Angeles';

-- Arnigo, Cedrick - M-F 9am-6pm
INSERT INTO employee_default_schedules (employee_id, day_of_week, work_schedule_id, is_rest_day, effective_from)
SELECT id, 0, NULL, 1, '2025-01-01' FROM employees WHERE fname = 'Cedrick' AND lname = 'Arnigo'
UNION ALL
SELECT id, 1, 6, 0, '2025-01-01' FROM employees WHERE fname = 'Cedrick' AND lname = 'Arnigo'
UNION ALL
SELECT id, 2, 6, 0, '2025-01-01' FROM employees WHERE fname = 'Cedrick' AND lname = 'Arnigo'
UNION ALL
SELECT id, 3, 6, 0, '2025-01-01' FROM employees WHERE fname = 'Cedrick' AND lname = 'Arnigo'
UNION ALL
SELECT id, 4, 6, 0, '2025-01-01' FROM employees WHERE fname = 'Cedrick' AND lname = 'Arnigo'
UNION ALL
SELECT id, 5, 6, 0, '2025-01-01' FROM employees WHERE fname = 'Cedrick' AND lname = 'Arnigo'
UNION ALL
SELECT id, 6, NULL, 1, '2025-01-01' FROM employees WHERE fname = 'Cedrick' AND lname = 'Arnigo';

-- Austria, Louis Fernand Baluyot - M-Th 9am-6pm | Fri 7am-4pm
INSERT INTO employee_default_schedules (employee_id, day_of_week, work_schedule_id, is_rest_day, effective_from)
SELECT id, 0, NULL, 1, '2025-01-01' FROM employees WHERE fname = 'Louis Fernand Baluyot' AND lname = 'Austria'
UNION ALL
SELECT id, 1, 6, 0, '2025-01-01' FROM employees WHERE fname = 'Louis Fernand Baluyot' AND lname = 'Austria'
UNION ALL
SELECT id, 2, 6, 0, '2025-01-01' FROM employees WHERE fname = 'Louis Fernand Baluyot' AND lname = 'Austria'
UNION ALL
SELECT id, 3, 6, 0, '2025-01-01' FROM employees WHERE fname = 'Louis Fernand Baluyot' AND lname = 'Austria'
UNION ALL
SELECT id, 4, 6, 0, '2025-01-01' FROM employees WHERE fname = 'Louis Fernand Baluyot' AND lname = 'Austria'
UNION ALL
SELECT id, 5, 4, 0, '2025-01-01' FROM employees WHERE fname = 'Louis Fernand Baluyot' AND lname = 'Austria' -- Fri 7AM-4PM
UNION ALL
SELECT id, 6, NULL, 1, '2025-01-01' FROM employees WHERE fname = 'Louis Fernand Baluyot' AND lname = 'Austria';

-- Bacongallo, Nika Nueva - M-Tue; Thu-Fri 6am-5pm | Wed, Sat, Sun OFF
INSERT INTO employee_default_schedules (employee_id, day_of_week, work_schedule_id, is_rest_day, effective_from)
SELECT id, 0, NULL, 1, '2025-01-01' FROM employees WHERE fname = 'Nika Nueva' AND lname = 'Bacongallo'
UNION ALL
SELECT id, 1, 14, 0, '2025-01-01' FROM employees WHERE fname = 'Nika Nueva' AND lname = 'Bacongallo'
UNION ALL
SELECT id, 2, 14, 0, '2025-01-01' FROM employees WHERE fname = 'Nika Nueva' AND lname = 'Bacongallo'
UNION ALL
SELECT id, 3, NULL, 1, '2025-01-01' FROM employees WHERE fname = 'Nika Nueva' AND lname = 'Bacongallo'
UNION ALL
SELECT id, 4, 14, 0, '2025-01-01' FROM employees WHERE fname = 'Nika Nueva' AND lname = 'Bacongallo'
UNION ALL
SELECT id, 5, 14, 0, '2025-01-01' FROM employees WHERE fname = 'Nika Nueva' AND lname = 'Bacongallo'
UNION ALL
SELECT id, 6, NULL, 1, '2025-01-01' FROM employees WHERE fname = 'Nika Nueva' AND lname = 'Bacongallo';

-- Balderas, Glory Ann - M-Fri 7am-4pm
INSERT INTO employee_default_schedules (employee_id, day_of_week, work_schedule_id, is_rest_day, effective_from)
SELECT id, 0, NULL, 1, '2025-01-01' FROM employees WHERE fname = 'Glory Ann' AND lname = 'Balderas'
UNION ALL
SELECT id, 1, 4, 0, '2025-01-01' FROM employees WHERE fname = 'Glory Ann' AND lname = 'Balderas'
UNION ALL
SELECT id, 2, 4, 0, '2025-01-01' FROM employees WHERE fname = 'Glory Ann' AND lname = 'Balderas'
UNION ALL
SELECT id, 3, 4, 0, '2025-01-01' FROM employees WHERE fname = 'Glory Ann' AND lname = 'Balderas'
UNION ALL
SELECT id, 4, 4, 0, '2025-01-01' FROM employees WHERE fname = 'Glory Ann' AND lname = 'Balderas'
UNION ALL
SELECT id, 5, 4, 0, '2025-01-01' FROM employees WHERE fname = 'Glory Ann' AND lname = 'Balderas'
UNION ALL
SELECT id, 6, NULL, 1, '2025-01-01' FROM employees WHERE fname = 'Glory Ann' AND lname = 'Balderas';

-- Bansil, Kristian David - M-F 7am-4pm
INSERT INTO employee_default_schedules (employee_id, day_of_week, work_schedule_id, is_rest_day, effective_from)
SELECT id, 0, NULL, 1, '2025-01-01' FROM employees WHERE fname = 'Kristian David' AND lname = 'Bansil'
UNION ALL
SELECT id, 1, 4, 0, '2025-01-01' FROM employees WHERE fname = 'Kristian David' AND lname = 'Bansil'
UNION ALL
SELECT id, 2, 4, 0, '2025-01-01' FROM employees WHERE fname = 'Kristian David' AND lname = 'Bansil'
UNION ALL
SELECT id, 3, 4, 0, '2025-01-01' FROM employees WHERE fname = 'Kristian David' AND lname = 'Bansil'
UNION ALL
SELECT id, 4, 4, 0, '2025-01-01' FROM employees WHERE fname = 'Kristian David' AND lname = 'Bansil'
UNION ALL
SELECT id, 5, 4, 0, '2025-01-01' FROM employees WHERE fname = 'Kristian David' AND lname = 'Bansil'
UNION ALL
SELECT id, 6, NULL, 1, '2025-01-01' FROM employees WHERE fname = 'Kristian David' AND lname = 'Bansil';

-- Bautista, Oliva - M-F 8AM-4:30PM
INSERT INTO employee_default_schedules (employee_id, day_of_week, work_schedule_id, is_rest_day, effective_from)
SELECT id, 0, NULL, 1, '2025-01-01' FROM employees WHERE fname = 'Oliva' AND lname = 'Bautista'
UNION ALL
SELECT id, 1, 9, 0, '2025-01-01' FROM employees WHERE fname = 'Oliva' AND lname = 'Bautista'
UNION ALL
SELECT id, 2, 9, 0, '2025-01-01' FROM employees WHERE fname = 'Oliva' AND lname = 'Bautista'
UNION ALL
SELECT id, 3, 9, 0, '2025-01-01' FROM employees WHERE fname = 'Oliva' AND lname = 'Bautista'
UNION ALL
SELECT id, 4, 9, 0, '2025-01-01' FROM employees WHERE fname = 'Oliva' AND lname = 'Bautista'
UNION ALL
SELECT id, 5, 9, 0, '2025-01-01' FROM employees WHERE fname = 'Oliva' AND lname = 'Bautista'
UNION ALL
SELECT id, 6, NULL, 1, '2025-01-01' FROM employees WHERE fname = 'Oliva' AND lname = 'Bautista';

-- Belangel, Karen - M-FRI 8AM-5PM
INSERT INTO employee_default_schedules (employee_id, day_of_week, work_schedule_id, is_rest_day, effective_from)
SELECT id, 0, NULL, 1, '2025-01-01' FROM employees WHERE fname = 'Karen' AND lname = 'Belangel'
UNION ALL
SELECT id, 1, 5, 0, '2025-01-01' FROM employees WHERE fname = 'Karen' AND lname = 'Belangel'
UNION ALL
SELECT id, 2, 5, 0, '2025-01-01' FROM employees WHERE fname = 'Karen' AND lname = 'Belangel'
UNION ALL
SELECT id, 3, 5, 0, '2025-01-01' FROM employees WHERE fname = 'Karen' AND lname = 'Belangel'
UNION ALL
SELECT id, 4, 5, 0, '2025-01-01' FROM employees WHERE fname = 'Karen' AND lname = 'Belangel'
UNION ALL
SELECT id, 5, 5, 0, '2025-01-01' FROM employees WHERE fname = 'Karen' AND lname = 'Belangel'
UNION ALL
SELECT id, 6, NULL, 1, '2025-01-01' FROM employees WHERE fname = 'Karen' AND lname = 'Belangel';

-- Benalla, Renneca Villapaña - M-Fri 7am-4pm
INSERT INTO employee_default_schedules (employee_id, day_of_week, work_schedule_id, is_rest_day, effective_from)
SELECT id, 0, NULL, 1, '2025-01-01' FROM employees WHERE fname = 'Renneca Villapaña' AND lname = 'Benalla'
UNION ALL
SELECT id, 1, 4, 0, '2025-01-01' FROM employees WHERE fname = 'Renneca Villapaña' AND lname = 'Benalla'
UNION ALL
SELECT id, 2, 4, 0, '2025-01-01' FROM employees WHERE fname = 'Renneca Villapaña' AND lname = 'Benalla'
UNION ALL
SELECT id, 3, 4, 0, '2025-01-01' FROM employees WHERE fname = 'Renneca Villapaña' AND lname = 'Benalla'
UNION ALL
SELECT id, 4, 4, 0, '2025-01-01' FROM employees WHERE fname = 'Renneca Villapaña' AND lname = 'Benalla'
UNION ALL
SELECT id, 5, 4, 0, '2025-01-01' FROM employees WHERE fname = 'Renneca Villapaña' AND lname = 'Benalla'
UNION ALL
SELECT id, 6, NULL, 1, '2025-01-01' FROM employees WHERE fname = 'Renneca Villapaña' AND lname = 'Benalla';

-- Bondoc, Francis Eugene Aguhayon - M-Fri 8:30am-4:30pm
INSERT INTO employee_default_schedules (employee_id, day_of_week, work_schedule_id, is_rest_day, effective_from)
SELECT id, 0, NULL, 1, '2025-01-01' FROM employees WHERE fname = 'Francis Eugene Aguhayon' AND lname = 'Bondoc'
UNION ALL
SELECT id, 1, 16, 0, '2025-01-01' FROM employees WHERE fname = 'Francis Eugene Aguhayon' AND lname = 'Bondoc'
UNION ALL
SELECT id, 2, 16, 0, '2025-01-01' FROM employees WHERE fname = 'Francis Eugene Aguhayon' AND lname = 'Bondoc'
UNION ALL
SELECT id, 3, 16, 0, '2025-01-01' FROM employees WHERE fname = 'Francis Eugene Aguhayon' AND lname = 'Bondoc'
UNION ALL
SELECT id, 4, 16, 0, '2025-01-01' FROM employees WHERE fname = 'Francis Eugene Aguhayon' AND lname = 'Bondoc'
UNION ALL
SELECT id, 5, 16, 0, '2025-01-01' FROM employees WHERE fname = 'Francis Eugene Aguhayon' AND lname = 'Bondoc'
UNION ALL
SELECT id, 6, NULL, 1, '2025-01-01' FROM employees WHERE fname = 'Francis Eugene Aguhayon' AND lname = 'Bondoc';

-- Briones, John Michael Comprado - M-Fri 7:00am-4:00pm
INSERT INTO employee_default_schedules (employee_id, day_of_week, work_schedule_id, is_rest_day, effective_from)
SELECT id, 0, NULL, 1, '2025-01-01' FROM employees WHERE fname = 'John Michael Comprado' AND lname = 'Briones'
UNION ALL
SELECT id, 1, 4, 0, '2025-01-01' FROM employees WHERE fname = 'John Michael Comprado' AND lname = 'Briones'
UNION ALL
SELECT id, 2, 4, 0, '2025-01-01' FROM employees WHERE fname = 'John Michael Comprado' AND lname = 'Briones'
UNION ALL
SELECT id, 3, 4, 0, '2025-01-01' FROM employees WHERE fname = 'John Michael Comprado' AND lname = 'Briones'
UNION ALL
SELECT id, 4, 4, 0, '2025-01-01' FROM employees WHERE fname = 'John Michael Comprado' AND lname = 'Briones'
UNION ALL
SELECT id, 5, 4, 0, '2025-01-01' FROM employees WHERE fname = 'John Michael Comprado' AND lname = 'Briones'
UNION ALL
SELECT id, 6, NULL, 1, '2025-01-01' FROM employees WHERE fname = 'John Michael Comprado' AND lname = 'Briones';

-- Cabusao, Precious Zahra Cortez - M-Fri 7:00am-4:00pm
INSERT INTO employee_default_schedules (employee_id, day_of_week, work_schedule_id, is_rest_day, effective_from)
SELECT id, 0, NULL, 1, '2025-01-01' FROM employees WHERE fname = 'Precious Zahra Cortez' AND lname = 'Cabusao'
UNION ALL
SELECT id, 1, 4, 0, '2025-01-01' FROM employees WHERE fname = 'Precious Zahra Cortez' AND lname = 'Cabusao'
UNION ALL
SELECT id, 2, 4, 0, '2025-01-01' FROM employees WHERE fname = 'Precious Zahra Cortez' AND lname = 'Cabusao'
UNION ALL
SELECT id, 3, 4, 0, '2025-01-01' FROM employees WHERE fname = 'Precious Zahra Cortez' AND lname = 'Cabusao'
UNION ALL
SELECT id, 4, 4, 0, '2025-01-01' FROM employees WHERE fname = 'Precious Zahra Cortez' AND lname = 'Cabusao'
UNION ALL
SELECT id, 5, 4, 0, '2025-01-01' FROM employees WHERE fname = 'Precious Zahra Cortez' AND lname = 'Cabusao'
UNION ALL
SELECT id, 6, NULL, 1, '2025-01-01' FROM employees WHERE fname = 'Precious Zahra Cortez' AND lname = 'Cabusao';

-- Camerino, Yris Gaelle Parreñas - M-T; Thu-Fri 7am-6pm | Wed, Sat-Sun OFF
INSERT INTO employee_default_schedules (employee_id, day_of_week, work_schedule_id, is_rest_day, effective_from)
SELECT id, 0, NULL, 1, '2025-01-01' FROM employees WHERE fname = 'Yris Gaelle Parreñas' AND lname = 'Camerino'
UNION ALL
SELECT id, 1, 13, 0, '2025-01-01' FROM employees WHERE fname = 'Yris Gaelle Parreñas' AND lname = 'Camerino'
UNION ALL
SELECT id, 2, 13, 0, '2025-01-01' FROM employees WHERE fname = 'Yris Gaelle Parreñas' AND lname = 'Camerino'
UNION ALL
SELECT id, 3, NULL, 1, '2025-01-01' FROM employees WHERE fname = 'Yris Gaelle Parreñas' AND lname = 'Camerino'
UNION ALL
SELECT id, 4, 13, 0, '2025-01-01' FROM employees WHERE fname = 'Yris Gaelle Parreñas' AND lname = 'Camerino'
UNION ALL
SELECT id, 5, 13, 0, '2025-01-01' FROM employees WHERE fname = 'Yris Gaelle Parreñas' AND lname = 'Camerino'
UNION ALL
SELECT id, 6, NULL, 1, '2025-01-01' FROM employees WHERE fname = 'Yris Gaelle Parreñas' AND lname = 'Camerino';

-- Capati, Allen - Tue-Thu 7am-6pm | Fri 8am-7pm | Sat-Mon OFF
INSERT INTO employee_default_schedules (employee_id, day_of_week, work_schedule_id, is_rest_day, effective_from)
SELECT id, 0, NULL, 1, '2025-01-01' FROM employees WHERE fname = 'Allen' AND lname = 'Capati'
UNION ALL
SELECT id, 1, NULL, 1, '2025-01-01' FROM employees WHERE fname = 'Allen' AND lname = 'Capati'
UNION ALL
SELECT id, 2, 13, 0, '2025-01-01' FROM employees WHERE fname = 'Allen' AND lname = 'Capati' -- Tue 7AM-6PM
UNION ALL
SELECT id, 3, 13, 0, '2025-01-01' FROM employees WHERE fname = 'Allen' AND lname = 'Capati' -- Wed 7AM-6PM
UNION ALL
SELECT id, 4, 13, 0, '2025-01-01' FROM employees WHERE fname = 'Allen' AND lname = 'Capati' -- Thu 7AM-6PM
UNION ALL
SELECT id, 5, 2, 0, '2025-01-01' FROM employees WHERE fname = 'Allen' AND lname = 'Capati' -- Fri 8AM-7PM
UNION ALL
SELECT id, 6, NULL, 1, '2025-01-01' FROM employees WHERE fname = 'Allen' AND lname = 'Capati';

-- Capiral, Gabriel - M-Fri 7AM-4PM
INSERT INTO employee_default_schedules (employee_id, day_of_week, work_schedule_id, is_rest_day, effective_from)
SELECT id, 0, NULL, 1, '2025-01-01' FROM employees WHERE fname = 'Gabriel' AND lname = 'Capiral'
UNION ALL
SELECT id, 1, 4, 0, '2025-01-01' FROM employees WHERE fname = 'Gabriel' AND lname = 'Capiral'
UNION ALL
SELECT id, 2, 4, 0, '2025-01-01' FROM employees WHERE fname = 'Gabriel' AND lname = 'Capiral'
UNION ALL
SELECT id, 3, 4, 0, '2025-01-01' FROM employees WHERE fname = 'Gabriel' AND lname = 'Capiral'
UNION ALL
SELECT id, 4, 4, 0, '2025-01-01' FROM employees WHERE fname = 'Gabriel' AND lname = 'Capiral'
UNION ALL
SELECT id, 5, 4, 0, '2025-01-01' FROM employees WHERE fname = 'Gabriel' AND lname = 'Capiral'
UNION ALL
SELECT id, 6, NULL, 1, '2025-01-01' FROM employees WHERE fname = 'Gabriel' AND lname = 'Capiral';

-- Caraan, Sarah - M-F 8AM-4:30PM
INSERT INTO employee_default_schedules (employee_id, day_of_week, work_schedule_id, is_rest_day, effective_from)
SELECT id, 0, NULL, 1, '2025-01-01' FROM employees WHERE fname = 'Sarah' AND lname = 'Caraan'
UNION ALL
SELECT id, 1, 9, 0, '2025-01-01' FROM employees WHERE fname = 'Sarah' AND lname = 'Caraan'
UNION ALL
SELECT id, 2, 9, 0, '2025-01-01' FROM employees WHERE fname = 'Sarah' AND lname = 'Caraan'
UNION ALL
SELECT id, 3, 9, 0, '2025-01-01' FROM employees WHERE fname = 'Sarah' AND lname = 'Caraan'
UNION ALL
SELECT id, 4, 9, 0, '2025-01-01' FROM employees WHERE fname = 'Sarah' AND lname = 'Caraan'
UNION ALL
SELECT id, 5, 9, 0, '2025-01-01' FROM employees WHERE fname = 'Sarah' AND lname = 'Caraan'
UNION ALL
SELECT id, 6, NULL, 1, '2025-01-01' FROM employees WHERE fname = 'Sarah' AND lname = 'Caraan';

-- Castro, Aizel Santos - M-Th 7am-6pm | Fri-Sun OFF
INSERT INTO employee_default_schedules (employee_id, day_of_week, work_schedule_id, is_rest_day, effective_from)
SELECT id, 0, NULL, 1, '2025-01-01' FROM employees WHERE fname = 'Aizel Santos' AND lname = 'Castro'
UNION ALL
SELECT id, 1, 13, 0, '2025-01-01' FROM employees WHERE fname = 'Aizel Santos' AND lname = 'Castro'
UNION ALL
SELECT id, 2, 13, 0, '2025-01-01' FROM employees WHERE fname = 'Aizel Santos' AND lname = 'Castro'
UNION ALL
SELECT id, 3, 13, 0, '2025-01-01' FROM employees WHERE fname = 'Aizel Santos' AND lname = 'Castro'
UNION ALL
SELECT id, 4, 13, 0, '2025-01-01' FROM employees WHERE fname = 'Aizel Santos' AND lname = 'Castro'
UNION ALL
SELECT id, 5, NULL, 1, '2025-01-01' FROM employees WHERE fname = 'Aizel Santos' AND lname = 'Castro'
UNION ALL
SELECT id, 6, NULL, 1, '2025-01-01' FROM employees WHERE fname = 'Aizel Santos' AND lname = 'Castro';

-- Catalogo, Marnie Perez - Sun-Wed 7am-6pm | Thu-Sat OFF
INSERT INTO employee_default_schedules (employee_id, day_of_week, work_schedule_id, is_rest_day, effective_from)
SELECT id, 0, 13, 0, '2025-01-01' FROM employees WHERE fname = 'Marnie Perez' AND lname = 'Catalogo' -- Sun 7AM-6PM
UNION ALL
SELECT id, 1, 13, 0, '2025-01-01' FROM employees WHERE fname = 'Marnie Perez' AND lname = 'Catalogo' -- Mon 7AM-6PM
UNION ALL
SELECT id, 2, 13, 0, '2025-01-01' FROM employees WHERE fname = 'Marnie Perez' AND lname = 'Catalogo' -- Tue 7AM-6PM
UNION ALL
SELECT id, 3, 13, 0, '2025-01-01' FROM employees WHERE fname = 'Marnie Perez' AND lname = 'Catalogo' -- Wed 7AM-6PM
UNION ALL
SELECT id, 4, NULL, 1, '2025-01-01' FROM employees WHERE fname = 'Marnie Perez' AND lname = 'Catalogo'
UNION ALL
SELECT id, 5, NULL, 1, '2025-01-01' FROM employees WHERE fname = 'Marnie Perez' AND lname = 'Catalogo'
UNION ALL
SELECT id, 6, NULL, 1, '2025-01-01' FROM employees WHERE fname = 'Marnie Perez' AND lname = 'Catalogo';

-- Celeste, Lovelaine - M-Tue 10AM-7PM | Wed 7AM-4PM | Fri 9AM-6PM | Thu, Sat-Sun OFF
INSERT INTO employee_default_schedules (employee_id, day_of_week, work_schedule_id, is_rest_day, effective_from)
SELECT id, 0, NULL, 1, '2025-01-01' FROM employees WHERE fname = 'Lovelaine' AND lname = 'Celeste'
UNION ALL
SELECT id, 1, 7, 0, '2025-01-01' FROM employees WHERE fname = 'Lovelaine' AND lname = 'Celeste' -- Mon 10AM-7PM
UNION ALL
SELECT id, 2, 7, 0, '2025-01-01' FROM employees WHERE fname = 'Lovelaine' AND lname = 'Celeste' -- Tue 10AM-7PM
UNION ALL
SELECT id, 3, 4, 0, '2025-01-01' FROM employees WHERE fname = 'Lovelaine' AND lname = 'Celeste' -- Wed 7AM-4PM
UNION ALL
SELECT id, 4, NULL, 1, '2025-01-01' FROM employees WHERE fname = 'Lovelaine' AND lname = 'Celeste'
UNION ALL
SELECT id, 5, 6, 0, '2025-01-01' FROM employees WHERE fname = 'Lovelaine' AND lname = 'Celeste' -- Fri 9AM-6PM
UNION ALL
SELECT id, 6, NULL, 1, '2025-01-01' FROM employees WHERE fname = 'Lovelaine' AND lname = 'Celeste';

-- Colis, Reymark Bryan Silvano - M-F 6am-3pm
INSERT INTO employee_default_schedules (employee_id, day_of_week, work_schedule_id, is_rest_day, effective_from)
SELECT id, 0, NULL, 1, '2025-01-01' FROM employees WHERE fname = 'Reymark Bryan Silvano' AND lname = 'Colis'
UNION ALL
SELECT id, 1, 8, 0, '2025-01-01' FROM employees WHERE fname = 'Reymark Bryan Silvano' AND lname = 'Colis'
UNION ALL
SELECT id, 2, 8, 0, '2025-01-01' FROM employees WHERE fname = 'Reymark Bryan Silvano' AND lname = 'Colis'
UNION ALL
SELECT id, 3, 8, 0, '2025-01-01' FROM employees WHERE fname = 'Reymark Bryan Silvano' AND lname = 'Colis'
UNION ALL
SELECT id, 4, 8, 0, '2025-01-01' FROM employees WHERE fname = 'Reymark Bryan Silvano' AND lname = 'Colis'
UNION ALL
SELECT id, 5, 8, 0, '2025-01-01' FROM employees WHERE fname = 'Reymark Bryan Silvano' AND lname = 'Colis'
UNION ALL
SELECT id, 6, NULL, 1, '2025-01-01' FROM employees WHERE fname = 'Reymark Bryan Silvano' AND lname = 'Colis';

-- Costelloe, Neil Anthony - Flexi (M-F 7AM-4PM as default)
INSERT INTO employee_default_schedules (employee_id, day_of_week, work_schedule_id, is_rest_day, effective_from)
SELECT id, 0, NULL, 1, '2025-01-01' FROM employees WHERE fname = 'Neil Anthony' AND lname = 'Costelloe'
UNION ALL
SELECT id, 1, 4, 0, '2025-01-01' FROM employees WHERE fname = 'Neil Anthony' AND lname = 'Costelloe'
UNION ALL
SELECT id, 2, 4, 0, '2025-01-01' FROM employees WHERE fname = 'Neil Anthony' AND lname = 'Costelloe'
UNION ALL
SELECT id, 3, 4, 0, '2025-01-01' FROM employees WHERE fname = 'Neil Anthony' AND lname = 'Costelloe'
UNION ALL
SELECT id, 4, 4, 0, '2025-01-01' FROM employees WHERE fname = 'Neil Anthony' AND lname = 'Costelloe'
UNION ALL
SELECT id, 5, 4, 0, '2025-01-01' FROM employees WHERE fname = 'Neil Anthony' AND lname = 'Costelloe'
UNION ALL
SELECT id, 6, NULL, 1, '2025-01-01' FROM employees WHERE fname = 'Neil Anthony' AND lname = 'Costelloe';

-- Crisanto, Elritz Tongson - Tue-Fri 7am-6pm | Sat-Mon OFF
INSERT INTO employee_default_schedules (employee_id, day_of_week, work_schedule_id, is_rest_day, effective_from)
SELECT id, 0, NULL, 1, '2025-01-01' FROM employees WHERE fname = 'Elritz Tongson' AND lname = 'Crisanto'
UNION ALL
SELECT id, 1, NULL, 1, '2025-01-01' FROM employees WHERE fname = 'Elritz Tongson' AND lname = 'Crisanto'
UNION ALL
SELECT id, 2, 13, 0, '2025-01-01' FROM employees WHERE fname = 'Elritz Tongson' AND lname = 'Crisanto'
UNION ALL
SELECT id, 3, 13, 0, '2025-01-01' FROM employees WHERE fname = 'Elritz Tongson' AND lname = 'Crisanto'
UNION ALL
SELECT id, 4, 13, 0, '2025-01-01' FROM employees WHERE fname = 'Elritz Tongson' AND lname = 'Crisanto'
UNION ALL
SELECT id, 5, 13, 0, '2025-01-01' FROM employees WHERE fname = 'Elritz Tongson' AND lname = 'Crisanto'
UNION ALL
SELECT id, 6, NULL, 1, '2025-01-01' FROM employees WHERE fname = 'Elritz Tongson' AND lname = 'Crisanto';

-- Cueto, Ron Paulo - M-Fri 7AM-4PM
INSERT INTO employee_default_schedules (employee_id, day_of_week, work_schedule_id, is_rest_day, effective_from)
SELECT id, 0, NULL, 1, '2025-01-01' FROM employees WHERE fname = 'Ron Paulo' AND lname = 'Cueto'
UNION ALL
SELECT id, 1, 4, 0, '2025-01-01' FROM employees WHERE fname = 'Ron Paulo' AND lname = 'Cueto'
UNION ALL
SELECT id, 2, 4, 0, '2025-01-01' FROM employees WHERE fname = 'Ron Paulo' AND lname = 'Cueto'
UNION ALL
SELECT id, 3, 4, 0, '2025-01-01' FROM employees WHERE fname = 'Ron Paulo' AND lname = 'Cueto'
UNION ALL
SELECT id, 4, 4, 0, '2025-01-01' FROM employees WHERE fname = 'Ron Paulo' AND lname = 'Cueto'
UNION ALL
SELECT id, 5, 4, 0, '2025-01-01' FROM employees WHERE fname = 'Ron Paulo' AND lname = 'Cueto'
UNION ALL
SELECT id, 6, NULL, 1, '2025-01-01' FROM employees WHERE fname = 'Ron Paulo' AND lname = 'Cueto';

-- Continuing with remaining employees...
-- Due to file length, I'll create the rest in a similar pattern
-- The file is getting long - would you like me to continue with all 90+ employees,
-- or would you prefer a PHP script that generates these inserts programmatically?

