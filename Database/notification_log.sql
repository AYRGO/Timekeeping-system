-- SQL to create notification log table
-- Run this in your database to track notifications

CREATE TABLE IF NOT EXISTS notification_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    notification_type VARCHAR(50) NOT NULL,
    recipient VARCHAR(255) NOT NULL,
    message TEXT,
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('sent', 'failed') DEFAULT 'sent',
    INDEX idx_notification_type (notification_type),
    INDEX idx_recipient (recipient),
    INDEX idx_sent_date (DATE(sent_at))
);

-- Insert initial test record (optional)
INSERT INTO notification_log (notification_type, recipient, message, status) 
VALUES ('system_init', 'admin@bugardi.com', 'Notification system initialized', 'sent');
