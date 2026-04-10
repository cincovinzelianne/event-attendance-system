-- SQL to add attendance_locked column to events table (SAFE VERSION)
-- This column allows administrators to lock/unlock attendance sheets
-- This script checks if the column exists before adding it to avoid errors

USE event_attendance;

-- Method 1: Using a stored procedure (recommended - handles check automatically)
DELIMITER $$

DROP PROCEDURE IF EXISTS AddAttendanceLockedColumn$$

CREATE PROCEDURE AddAttendanceLockedColumn()
BEGIN
    DECLARE column_exists INT DEFAULT 0;
    
    -- Check if the column exists
    SELECT COUNT(*) INTO column_exists
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'events'
    AND COLUMN_NAME = 'attendance_locked';
    
    -- Add column only if it doesn't exist
    IF column_exists = 0 THEN
        ALTER TABLE events 
        ADD COLUMN attendance_locked TINYINT(1) DEFAULT 0 
        COMMENT 'Lock status for attendance sheet: 0 = unlocked, 1 = locked';
        
        SELECT 'Column attendance_locked added successfully!' AS Result;
    ELSE
        SELECT 'Column attendance_locked already exists. No changes made.' AS Result;
    END IF;
END$$

DELIMITER ;

-- Execute the procedure
CALL AddAttendanceLockedColumn();

-- Drop the procedure after use
DROP PROCEDURE IF EXISTS AddAttendanceLockedColumn;

-- Optional: Add an index for better query performance (only if it doesn't exist)
SET @index_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.STATISTICS 
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'events' 
    AND INDEX_NAME = 'idx_attendance_locked'
);

SET @sql = IF(@index_exists = 0,
    'CREATE INDEX idx_attendance_locked ON events(attendance_locked)',
    'SELECT "Index idx_attendance_locked already exists. No changes made." AS Result'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ============================================
-- Method 2: Manual check (if you prefer)
-- ============================================
-- First, run this query to check if column exists:
-- SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS 
-- WHERE TABLE_SCHEMA = 'event_attendance' 
-- AND TABLE_NAME = 'events' 
-- AND COLUMN_NAME = 'attendance_locked';
--
-- If the query returns empty (no rows), then run:
-- ALTER TABLE events 
-- ADD COLUMN attendance_locked TINYINT(1) DEFAULT 0 
-- COMMENT 'Lock status for attendance sheet: 0 = unlocked, 1 = locked';

-- Verify the column was added
-- SELECT COLUMN_NAME, DATA_TYPE, COLUMN_DEFAULT, COLUMN_COMMENT 
-- FROM INFORMATION_SCHEMA.COLUMNS 
-- WHERE TABLE_SCHEMA = 'event_attendance' 
-- AND TABLE_NAME = 'events' 
-- AND COLUMN_NAME = 'attendance_locked';
