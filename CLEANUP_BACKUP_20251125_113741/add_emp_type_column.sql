-- Add Emp_Type column to employees table
-- This column will store employee employment type (Regular or Probationary)

ALTER TABLE `employees` 
ADD COLUMN `Emp_Type` ENUM('Regular', 'Probationary') NOT NULL DEFAULT 'Probationary' 
AFTER `status`;
