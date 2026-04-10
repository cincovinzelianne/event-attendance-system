-- Update existing students to have correct profile_completed status
-- Mark students with complete course and year_level as having completed profiles
UPDATE students 
SET profile_completed = 1, profile_completed_at = NOW() 
WHERE course IS NOT NULL AND course != '' AND year_level IS NOT NULL AND year_level != '';

-- Mark students with incomplete course or year_level as having incomplete profiles
UPDATE students 
SET profile_completed = 0 
WHERE course IS NULL OR course = '' OR year_level IS NULL OR year_level = '';






