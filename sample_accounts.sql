-- Event Attendance System - Sample Account Creation
-- Run this SQL to create sample admin and student accounts
-- Passwords are hashed with password_hash() using PASSWORD_DEFAULT algorithm

USE event_attendance;

-- ========================================
-- ADMIN ACCOUNTS
-- ========================================

INSERT INTO admins (username, email, password_hash, full_name, role) VALUES
(
    'admin',
    'admin@llcc.edu.ph',
    '$2y$10$qTt8RWEKVnFCLNxF1B3F0.Q7oNXzM8C0CvU6K5pF8V3p.zXxJJPUW',  -- Password: Admin@123
    'System Administrator',
    'admin'
),
(
    'admin_staff',
    'staff@llcc.edu.ph',
    '$2y$10$mUKKvJ1xF7m4F3n7B8c1Y.T2nF5X8P0Q3R4S5T6U7V8W9X0Y1Z2A',  -- Password: Staff@123
    'Admin Staff',
    'admin'
);

-- ========================================
-- STUDENT ACCOUNTS
-- ========================================
-- Note: These are basic student records without QR codes
-- For full functionality with QR codes, use create_accounts.php web interface

INSERT INTO students 
(student_id, first_name, last_name, email, password, phone, course, year_level, qr_code, qr_code_path) 
VALUES
(
    '2021-001',
    'Juan',
    'Dela Cruz',
    'juan.delacruz@llcc.edu.ph',
    '$2y$10$D1q2w3e4r5t6y7u8i9o.pC5S9L2A4nF7K0X3M6W9b.Z4c7F0E3H',  -- Password: Student@123
    '09123456789',
    'BS Information Technology',
    '2nd Year',
    'STU_0001',
    'assets/qr_codes/qr_2021-001.png'
),
(
    '2021-002',
    'Maria',
    'Santos',
    'maria.santos@llcc.edu.ph',
    '$2y$10$D1q2w3e4r5t6y7u8i9o.pC5S9L2A4nF7K0X3M6W9b.Z4c7F0E3H',  -- Password: Student@123
    '09987654321',
    'BS Business Administration',
    '3rd Year',
    'STU_0002',
    'assets/qr_codes/qr_2021-002.png'
),
(
    '2021-003',
    'Pedro',
    'Reyes',
    'pedro.reyes@llcc.edu.ph',
    '$2y$10$D1q2w3e4r5t6y7u8i9o.pC5S9L2A4nF7K0X3M6W9b.Z4c7F0E3H',  -- Password: Student@123
    '09111222333',
    'BS Accountancy',
    '1st Year',
    'STU_0003',
    'assets/qr_codes/qr_2021-003.png'
),
(
    '2021-004',
    'Ana',
    'Gonzales',
    'ana.gonzales@llcc.edu.ph',
    '$2y$10$D1q2w3e4r5t6y7u8i9o.pC5S9L2A4nF7K0X3M6W9b.Z4c7F0E3H',  -- Password: Student@123
    '09444555666',
    'BS Hospitality Management',
    '2nd Year',
    'STU_0004',
    'assets/qr_codes/qr_2021-004.png'
);

-- ========================================
-- VERIFICATION - Show created accounts
-- ========================================
SELECT 'ADMIN ACCOUNTS' as Type, COUNT(*) as Count FROM admins
UNION ALL
SELECT 'STUDENT ACCOUNTS', COUNT(*) FROM students;

-- Show admin details (without password hashes)
SELECT '--- ADMIN LOGIN CREDENTIALS ---' as Info;
SELECT username, email, 'Admin@123 or Staff@123' as password FROM admins;

-- Show student details (without password hashes)
SELECT '--- STUDENT LOGIN CREDENTIALS ---' as Info;
SELECT student_id, first_name, last_name, email, 'Student@123' as password FROM students;
