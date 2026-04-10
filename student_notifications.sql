-- Run once in your MySQL database (e.g. phpMyAdmin or mysql CLI).
-- Stores in-app alerts shown to students when attendance is clocked.

CREATE TABLE IF NOT EXISTS student_notifications (
    id INT(11) NOT NULL AUTO_INCREMENT,
    student_id INT(11) NOT NULL,
    event_id INT(11) NULL DEFAULT NULL,
    action_key VARCHAR(32) NOT NULL,
    title VARCHAR(255) NOT NULL,
    body TEXT NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    read_at DATETIME NULL DEFAULT NULL,
    PRIMARY KEY (id),
    KEY idx_sn_student_created (student_id, created_at),
    KEY idx_sn_event (event_id),
    CONSTRAINT fk_sn_student FOREIGN KEY (student_id) REFERENCES students (id) ON DELETE CASCADE,
    CONSTRAINT fk_sn_event FOREIGN KEY (event_id) REFERENCES events (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
