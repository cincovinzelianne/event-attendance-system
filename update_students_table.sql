-- Add profile completion fields to students table
ALTER TABLE students 
ADD COLUMN profile_completed TINYINT(1) DEFAULT 0,
ADD COLUMN profile_completed_at TIMESTAMP NULL;

-- Update existing students to mark them as having incomplete profiles if they don't have course/year_level
UPDATE students 
SET profile_completed = 0 
WHERE course IS NULL OR course = '' OR year_level IS NULL OR year_level = '';

-- Mark students with complete course and year_level as having completed profiles
UPDATE students 
SET profile_completed = 1, profile_completed_at = NOW() 
WHERE course IS NOT NULL AND course != '' AND year_level IS NOT NULL AND year_level != '';






