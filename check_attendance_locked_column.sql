-- Check if attendance_locked column exists in events table
-- This query will show you if the column exists and its properties

USE event_attendance;

-- Check if column exists
SELECT 
    COLUMN_NAME,
    DATA_TYPE,
    COLUMN_DEFAULT,
    IS_NULLABLE,
    COLUMN_COMMENT
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = 'event_attendance'
AND TABLE_NAME = 'events'
AND COLUMN_NAME = 'attendance_locked';

-- If the query returns a row, the column exists
-- If it returns empty, the column doesn't exist and you can add it using the procedure below
