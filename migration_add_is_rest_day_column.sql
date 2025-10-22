-- Add is_rest_day column to schedule_change_requests table
-- This column helps differentiate between regular schedule changes and rest day requests

ALTER TABLE schedule_change_requests 
ADD COLUMN IF NOT EXISTS is_rest_day TINYINT(1) DEFAULT 0 
COMMENT '1 if this is a rest day request, 0 if regular schedule change';

-- Add is_rest_day column to post_schedule_change_requests table
ALTER TABLE post_schedule_change_requests 
ADD COLUMN IF NOT EXISTS is_rest_day TINYINT(1) DEFAULT 0 
COMMENT '1 if this is a rest day request, 0 if regular schedule change';

-- Add index for better query performance
CREATE INDEX IF NOT EXISTS idx_is_rest_day ON schedule_change_requests(is_rest_day);
CREATE INDEX IF NOT EXISTS idx_is_rest_day_post ON post_schedule_change_requests(is_rest_day);

-- Update existing NULL work_schedule_id records to be marked as rest days
UPDATE schedule_change_requests 
SET is_rest_day = 1 
WHERE work_schedule_id IS NULL;

UPDATE post_schedule_change_requests 
SET is_rest_day = 1 
WHERE work_schedule_id IS NULL;
