-- Create courses table for hierarchical course management
CREATE TABLE IF NOT EXISTS courses (
    id INT(11) NOT NULL AUTO_INCREMENT,
    department VARCHAR(100) NOT NULL,
    course_name VARCHAR(100) NOT NULL,
    major VARCHAR(100) DEFAULT NULL,
    year_levels JSON NOT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY unique_course (department, course_name, major)
);

-- Insert sample data
INSERT INTO courses (department, course_name, major, year_levels) VALUES
('COLLEGE OF EDUCATION', 'COED', 'EDUCATION MAJOR IN ENGLISH', '["1st Year", "2nd Year", "3rd Year", "4th Year"]'),
('COLLEGE OF EDUCATION', 'COED', 'EDUCATION MAJOR IN MATHEMATICS', '["1st Year", "2nd Year", "3rd Year", "4th Year"]'),
('COLLEGE OF EDUCATION', 'COED', 'EDUCATION MAJOR IN SCIENCE', '["1st Year", "2nd Year", "3rd Year", "4th Year"]'),
('COLLEGE OF TECHNOLOGY', 'BSIT', 'INFORMATION TECHNOLOGY', '["1st Year", "2nd Year", "3rd Year", "4th Year"]'),
('COLLEGE OF TECHNOLOGY', 'BSCS', 'COMPUTER SCIENCE', '["1st Year", "2nd Year", "3rd Year", "4th Year"]'),
('COLLEGE OF HOSPITALITY AND TOURISM MANAGEMENT', 'BSBA', 'BUSINESS ADMINISTRATION', '["1st Year", "2nd Year", "3rd Year", "4th Year"]'),
('COLLEGE OF HOSPITALITY AND TOURISM MANAGEMENT', 'BSBA', 'MARKETING', '["1st Year", "2nd Year", "3rd Year", "4th Year"]'),
('COLLEGE OF HOSPITALITY AND TOURISM MANAGEMENT', 'BSN', 'NURSING', '["1st Year", "2nd Year", "3rd Year", "4th Year"]');



