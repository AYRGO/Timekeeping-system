-- SQL Trigger to automatically move overtime requests when status changes to approved/declined
-- Run this SQL command in your database to create the trigger

DELIMITER //

CREATE TRIGGER move_completed_ot_requests
AFTER UPDATE ON post_ot_requests
FOR EACH ROW
BEGIN
    -- Check if status was changed to approved or declined
    IF NEW.status IN ('approved', 'declined') AND OLD.status = 'pending' THEN
        -- Insert into archive table
        INSERT INTO post2_overtime_requests 
        (id, employee_id, time_log_id, time_in, time_out, ot_duration, ot_type, attachment, reason, status, created_at, approved_at, approved_by, notified)
        VALUES 
        (NEW.id, NEW.employee_id, NEW.time_log_id, NEW.time_in, NEW.time_out, NEW.ot_duration, NEW.ot_type, NEW.attachment, NEW.reason, NEW.status, NEW.created_at, NEW.approved_at, NEW.approved_by, NEW.notified);
        
        -- Delete from original table
        DELETE FROM post_ot_requests WHERE id = NEW.id;
    END IF;
END//

DELIMITER ;

-- To drop the trigger if needed:
-- DROP TRIGGER IF EXISTS move_completed_ot_requests;