-- Create event_departments pivot table for filtering attendance by department
-- This table stores which departments are selected for each event
-- Only students from selected departments will appear in the attendance sheet

CREATE TABLE IF NOT EXISTS event_departments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_id INT NOT NULL,
    department VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_event_department (event_id, department),
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Add index for better query performance
CREATE INDEX idx_event_departments_event_id ON event_departments(event_id);
CREATE INDEX idx_event_departments_department ON event_departments(department);
