-- Adds Floating as a non-accruing employment type.
ALTER TABLE employees
    MODIFY COLUMN Emp_Type
    ENUM('Probationary', 'Floating', 'Regular', 'Old_Regular')
    DEFAULT 'Probationary';
