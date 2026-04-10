<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/database.php';

date_default_timezone_set('Asia/Manila');

function ensureCoursesTable(PDO $db): void {
    $db->exec("
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
        )
    ");
}

function seedDefaultCourses(PDO $db): void {
    $defaultCourses = [
        ['COLLEGE OF EDUCATION', 'COED', 'EDUCATION MAJOR IN ENGLISH', '["1st Year", "2nd Year", "3rd Year", "4th Year"]'],
        ['COLLEGE OF EDUCATION', 'COED', 'EDUCATION MAJOR IN MATHEMATICS', '["1st Year", "2nd Year", "3rd Year", "4th Year"]'],
        ['COLLEGE OF EDUCATION', 'COED', 'EDUCATION MAJOR IN SCIENCE', '["1st Year", "2nd Year", "3rd Year", "4th Year"]'],
        ['COLLEGE OF TECHNOLOGY', 'BSIT', 'INFORMATION TECHNOLOGY', '["1st Year", "2nd Year", "3rd Year", "4th Year"]'],
        ['COLLEGE OF TECHNOLOGY', 'BSCS', 'COMPUTER SCIENCE', '["1st Year", "2nd Year", "3rd Year", "4th Year"]'],
        ['COLLEGE OF HOSPITALITY AND TOURISM MANAGEMENT', 'BSBA', 'BUSINESS ADMINISTRATION', '["1st Year", "2nd Year", "3rd Year", "4th Year"]'],
        ['COLLEGE OF HOSPITALITY AND TOURISM MANAGEMENT', 'BSBA', 'MARKETING', '["1st Year", "2nd Year", "3rd Year", "4th Year"]'],
        ['COLLEGE OF HOSPITALITY AND TOURISM MANAGEMENT', 'BSN', 'NURSING', '["1st Year", "2nd Year", "3rd Year", "4th Year"]']
    ];

    $insertCourse = $db->prepare("
        INSERT INTO courses (department, course_name, major, year_levels, is_active)
        VALUES (?, ?, ?, ?, 1)
        ON DUPLICATE KEY UPDATE
            year_levels = VALUES(year_levels),
            is_active = 1
    ");

    foreach ($defaultCourses as $courseRow) {
        $insertCourse->execute($courseRow);
    }
}

function buildStudentInsert(PDO $db): array {
    $availableColumns = $db->query("SHOW COLUMNS FROM students")->fetchAll(PDO::FETCH_COLUMN);
    $availableMap = array_fill_keys($availableColumns, true);

    $columns = [
        'student_id',
        'first_name',
        'last_name',
        'email',
        'password',
        'phone',
        'course',
        'year_level',
        'qr_code',
        'qr_code_path'
    ];

    if (isset($availableMap['profile_completed'])) {
        $columns[] = 'profile_completed';
    }
    if (isset($availableMap['profile_completed_at'])) {
        $columns[] = 'profile_completed_at';
    }
    if (isset($availableMap['qr_code_locked'])) {
        $columns[] = 'qr_code_locked';
    }
    if (isset($availableMap['is_active'])) {
        $columns[] = 'is_active';
    }

    $placeholders = implode(', ', array_fill(0, count($columns), '?'));
    $sql = "INSERT IGNORE INTO students (" . implode(', ', $columns) . ") VALUES ($placeholders)";

    return [$columns, $db->prepare($sql)];
}

try {
    $database = new Database();
    $db = $database->getConnection();

    ensureCoursesTable($db);
    seedDefaultCourses($db);

    $coursesStmt = $db->query("SELECT * FROM courses WHERE is_active = 1 ORDER BY department, course_name, major");
    $courses = $coursesStmt->fetchAll(PDO::FETCH_ASSOC);

    [$insertColumns, $insertStudent] = buildStudentInsert($db);

    $sections = ['Section A', 'Section B', 'Section C', 'Section D', 'Section E'];
    $studentsPerSection = 10;
    $passwordHash = password_hash('password', PASSWORD_DEFAULT);
    $created = 0;
    $skipped = 0;

    foreach ($courses as $course) {
        $yearLevels = json_decode($course['year_levels'], true);
        if (!is_array($yearLevels) || empty($yearLevels)) {
            $yearLevels = ['1st Year', '2nd Year', '3rd Year', '4th Year'];
        }

        $baseCourseLabel = trim($course['course_name'] . ($course['major'] ? ' - ' . $course['major'] : ''));

        foreach (array_values($yearLevels) as $yearIndex => $yearLevel) {
            foreach (array_values($sections) as $sectionIndex => $sectionName) {
                for ($studentNumber = 1; $studentNumber <= $studentsPerSection; $studentNumber++) {
                    $studentId = sprintf(
                        '2026-%02d%d%d%02d',
                        (int)$course['id'],
                        $yearIndex + 1,
                        $sectionIndex + 1,
                        $studentNumber
                    );

                    $courseLabel = $baseCourseLabel . ' [' . $sectionName . ']';
                    $email = 'student.' . strtolower(str_replace('-', '', $studentId)) . '@llcc.edu.ph';
                    $firstName = 'LLCC';
                    $lastName = sprintf(
                        '%s-Y%d-%s-%02d',
                        preg_replace('/[^A-Z0-9]+/i', '', $course['course_name']),
                        $yearIndex + 1,
                        preg_replace('/[^A-Z0-9]+/i', '', $sectionName),
                        $studentNumber
                    );
                    $phoneSeed = ((int)$course['id'] * 10000) + (($yearIndex + 1) * 1000) + (($sectionIndex + 1) * 100) + $studentNumber;
                    $phone = '09' . str_pad((string)$phoneSeed, 9, '0', STR_PAD_LEFT);
                    $qrCode = 'STU_SEED_' . strtoupper(substr(md5($studentId . $email), 0, 16));

                    $rowData = [
                        'student_id' => $studentId,
                        'first_name' => $firstName,
                        'last_name' => $lastName,
                        'email' => $email,
                        'password' => $passwordHash,
                        'phone' => $phone,
                        'course' => $courseLabel,
                        'year_level' => $yearLevel,
                        'qr_code' => $qrCode,
                        'qr_code_path' => null,
                        'profile_completed' => 1,
                        'profile_completed_at' => date('Y-m-d H:i:s'),
                        'qr_code_locked' => 0,
                        'is_active' => 1
                    ];

                    $values = [];
                    foreach ($insertColumns as $columnName) {
                        $values[] = $rowData[$columnName] ?? null;
                    }

                    $insertStudent->execute($values);

                    if ($insertStudent->rowCount() > 0) {
                        $created++;
                    } else {
                        $skipped++;
                    }
                }
            }
        }
    }

    $summary = $db->query("
        SELECT COALESCE(c.department, 'Unassigned') AS department_name, COUNT(*) AS total_students
        FROM students s
        LEFT JOIN courses c ON (s.course = c.course_name OR s.course LIKE CONCAT(c.course_name, ' - %'))
        GROUP BY COALESCE(c.department, 'Unassigned')
        ORDER BY department_name
    ")->fetchAll(PDO::FETCH_ASSOC);

    echo "Bulk LLCC student seeding completed.\n";
    echo "Created: {$created}\n";
    echo "Skipped existing: {$skipped}\n";
    echo "Password for all seeded students: password\n";
    echo "Email domain used: @llcc.edu.ph\n\n";
    echo "Department totals:\n";
    foreach ($summary as $row) {
        echo "- {$row['department_name']}: {$row['total_students']}\n";
    }
} catch (Throwable $e) {
    echo 'Error: ' . $e->getMessage() . "\n";
    exit(1);
}
