-- Add notified column to post_ot_requests table to track email notifications

ALTER TABLE `post_ot_requests` 
ADD COLUMN `notified` TINYINT(1) NOT NULL DEFAULT 0 
COMMENT 'Email notification status: 0=not sent, 1=sent';

-- Optional: Set existing approved/rejected records as already notified to prevent spam
-- Uncomment the following line if you want to mark existing records as notified:
-- UPDATE `post_ot_requests` SET `notified` = 1 WHERE `status` IN ('Approved', 'Rejected');

-- Verify the column was added successfully
DESCRIBE `post_ot_requests`;
