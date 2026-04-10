<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/database.php';

date_default_timezone_set('Asia/Manila');

function extractSectionName($courseLabel) {
    $courseLabel = trim((string) $courseLabel);
    if ($courseLabel !== '' && preg_match('/\[([^\]]+)\]\s*$/', $courseLabel, $matches)) {
        return trim($matches[1]);
    }
    return 'No Section';
}

function buildCourseLabel(array $course, string $sectionName): string {
    $base = trim($course['course_name'] . (!empty($course['major']) ? ' - ' . $course['major'] : ''));
    return $base . ' [' . $sectionName . ']';
}

function normalizeCourseLabel($courseLabel): string {
    return trim(preg_replace('/\s*\[[^\]]+\]\s*$/', '', trim((string) $courseLabel)));
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
    $stmt = $db->prepare("INSERT INTO students (" . implode(', ', $columns) . ") VALUES ($placeholders)");

    return [$columns, $stmt];
}

function nextStudentIdentity(PDO $db, string $deptCode, string $sectionCode): array {
    static $counter = 0;
    $counter++;

    do {
        $studentId = sprintf('2026-%s%s%04d', $deptCode, $sectionCode, $counter);
        $checkStmt = $db->prepare('SELECT id FROM students WHERE student_id = ? OR email = ? LIMIT 1');
        $email = 'student.' . strtolower(str_replace('-', '', $studentId)) . '@llcc.edu.ph';
        $checkStmt->execute([$studentId, $email]);
        $exists = $checkStmt->fetchColumn();
    } while ($exists);

    return [$studentId, $email];
}

function departmentCode(string $department): string {
    $words = preg_split('/\s+/', preg_replace('/[^A-Z ]/i', ' ', strtoupper($department)));
    $letters = '';
    foreach ($words as $word) {
        if ($word === '' || in_array($word, ['DEPARTMENT', 'OF'], true)) {
            continue;
        }
        $letters .= substr($word, 0, 1);
    }
    return substr($letters ?: 'DP', 0, 3);
}

try {
    $database = new Database();
    $db = $database->getConnection();

    $targetPerDepartmentSection = 50;
    $sections = ['Section A', 'Section B', 'Section C', 'Section D', 'Section E'];
    $passwordHash = password_hash('password', PASSWORD_DEFAULT);

    $courses = $db->query("SELECT * FROM courses WHERE is_active = 1 ORDER BY department, course_name, major")->fetchAll(PDO::FETCH_ASSOC);
    $coursesByDepartment = [];
    $courseMap = [];
    foreach ($courses as $course) {
        $coursesByDepartment[$course['department']][] = $course;
        $fullLabel = trim($course['course_name'] . (!empty($course['major']) ? ' - ' . $course['major'] : ''));
        $courseMap[$fullLabel] = $course['department'];
        if (!isset($courseMap[$course['course_name']])) {
            $courseMap[$course['course_name']] = $course['department'];
        }
    }

    $seededStmt = $db->query("
        SELECT s.id, s.student_id, s.course, s.year_level
        FROM students s
        WHERE s.email LIKE '%@llcc.edu.ph'
        ORDER BY s.student_id ASC, s.id ASC
    ");
    $seededStudents = $seededStmt->fetchAll(PDO::FETCH_ASSOC);

    $grouped = [];
    foreach ($seededStudents as $student) {
        $department = $courseMap[normalizeCourseLabel($student['course'] ?? '')] ?? 'Unassigned';
        $section = extractSectionName($student['course'] ?? '');
        if (!isset($grouped[$department])) {
            $grouped[$department] = [];
        }
        if (!isset($grouped[$department][$section])) {
            $grouped[$department][$section] = [];
        }
        $grouped[$department][$section][] = $student;
    }

    $deleteIds = [];
    foreach ($coursesByDepartment as $department => $departmentCourses) {
        foreach ($sections as $sectionName) {
            $rows = $grouped[$department][$sectionName] ?? [];
            if (count($rows) > $targetPerDepartmentSection) {
                $deleteSlice = array_slice($rows, $targetPerDepartmentSection);
                foreach ($deleteSlice as $row) {
                    $deleteIds[] = (int) $row['id'];
                }
                $grouped[$department][$sectionName] = array_slice($rows, 0, $targetPerDepartmentSection);
            }
        }
    }

    [$insertColumns, $insertStmt] = buildStudentInsert($db);

    $db->beginTransaction();

    $created = 0;
    $deleted = 0;

    if ($deleteIds) {
        $chunks = array_chunk($deleteIds, 200);
        foreach ($chunks as $chunk) {
            $in = implode(',', array_fill(0, count($chunk), '?'));
            $delStmt = $db->prepare("DELETE FROM students WHERE id IN ($in)");
            $delStmt->execute($chunk);
            $deleted += $delStmt->rowCount();
        }
    }

    $freshRowsStmt = $db->query("SELECT s.course FROM students s WHERE s.email LIKE '%@llcc.edu.ph'");
    $currentCounts = [];
    foreach ($freshRowsStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $department = $courseMap[normalizeCourseLabel($row['course'] ?? '')] ?? 'Unassigned';
        $section = extractSectionName($row['course'] ?? '');
        if (!isset($currentCounts[$department])) {
            $currentCounts[$department] = [];
        }
        if (!isset($currentCounts[$department][$section])) {
            $currentCounts[$department][$section] = 0;
        }
        $currentCounts[$department][$section]++;
    }

    foreach ($coursesByDepartment as $department => $departmentCourses) {
        $deptCode = departmentCode($department);
        foreach ($sections as $sectionIndex => $sectionName) {
            $currentCount = $currentCounts[$department][$sectionName] ?? 0;
            if ($currentCount >= $targetPerDepartmentSection) {
                continue;
            }

            $need = $targetPerDepartmentSection - $currentCount;
            for ($i = 0; $i < $need; $i++) {
                $course = $departmentCourses[$i % count($departmentCourses)];
                $yearLevels = json_decode($course['year_levels'], true);
                if (!is_array($yearLevels) || empty($yearLevels)) {
                    $yearLevels = ['1st Year', '2nd Year', '3rd Year', '4th Year'];
                }
                $yearLevel = $yearLevels[$i % count($yearLevels)];

                [$studentId, $email] = nextStudentIdentity($db, $deptCode, (string) ($sectionIndex + 1));
                $courseLabel = buildCourseLabel($course, $sectionName);
                $lastName = sprintf(
                    '%s-%s-%02d',
                    preg_replace('/[^A-Z0-9]+/i', '', $course['course_name']),
                    preg_replace('/[^A-Z0-9]+/i', '', $sectionName),
                    $i + 1
                );
                $phone = '09' . str_pad((string) (100000000 + $created + $i + 1), 9, '0', STR_PAD_LEFT);
                $qrCode = 'STU_SEED_' . strtoupper(substr(md5($studentId . $email), 0, 16));

                $rowData = [
                    'student_id' => $studentId,
                    'first_name' => 'LLCC',
                    'last_name' => $lastName,
                    'email' => $email,
                    'password' => $passwordHash,
                    'phone' => $phone,
                    'course' => $courseLabel,
                    'year_level' => $yearLevel,
                    'qr_code' => $qrCode,
                    'qr_code_path' => 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=' . urlencode($qrCode) . '&ecc=H',
                    'profile_completed' => 1,
                    'profile_completed_at' => date('Y-m-d H:i:s'),
                    'qr_code_locked' => 0,
                    'is_active' => 1
                ];

                $values = [];
                foreach ($insertColumns as $columnName) {
                    $values[] = $rowData[$columnName] ?? null;
                }

                $insertStmt->execute($values);
                $created++;
            }
        }
    }

    $db->commit();

    $summaryRows = $db->query("SELECT course FROM students WHERE email LIKE '%@llcc.edu.ph'")->fetchAll(PDO::FETCH_ASSOC);
    $summary = [];
    foreach ($summaryRows as $row) {
        $department = $courseMap[normalizeCourseLabel($row['course'] ?? '')] ?? 'Unassigned';
        $section = extractSectionName($row['course'] ?? '');
        if (!isset($summary[$department])) {
            $summary[$department] = [];
        }
        if (!isset($summary[$department][$section])) {
            $summary[$department][$section] = 0;
        }
        $summary[$department][$section]++;
    }

    echo "Rebalance complete.\n";
    echo "Deleted: {$deleted}\n";
    echo "Created: {$created}\n\n";
    ksort($summary);
    foreach ($summary as $department => $sectionsSummary) {
        ksort($sectionsSummary);
        foreach ($sectionsSummary as $section => $count) {
            echo $department . ' | ' . $section . ' | ' . $count . "\n";
        }
    }
} catch (Throwable $e) {
    if (isset($db) && $db instanceof PDO && $db->inTransaction()) {
        $db->rollBack();
    }
    echo 'Error: ' . $e->getMessage() . "\n";
    exit(1);
}
