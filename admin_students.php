<?php
require_once 'includes/admin_auth.php';

$adminAuth = new AdminAuth();
$currentAdmin = $adminAuth->requireAuth();

$message = '';
$messageType = '';

// Handle student actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $studentId = $_POST['student_id'] ?? '';
    
    try {
        $database = new Database();
        $db = $database->getConnection();
        
        switch ($action) {
            case 'toggle_status':
                $stmt = $db->prepare("UPDATE students SET is_active = NOT is_active WHERE id = ?");
                $stmt->execute([$studentId]);
                $message = 'Student status updated successfully!';
                $messageType = 'success';
                break;
                
            case 'delete_student':
                $stmt = $db->prepare("DELETE FROM students WHERE id = ?");
                $stmt->execute([$studentId]);
                $message = 'Student deleted successfully!';
                $messageType = 'success';
                break;
                
            case 'toggle_qr_lock':
                // Get current lock status
                $checkStmt = $db->prepare("SELECT qr_code_locked, student_id, first_name, last_name, email FROM students WHERE id = ?");
                $checkStmt->execute([$studentId]);
                $student = $checkStmt->fetch(PDO::FETCH_ASSOC);
                
                if ($student) {
                    $isLocked = (int)$student['qr_code_locked'];
                    $newLockStatus = $isLocked ? 0 : 1;
                    
                    if ($newLockStatus == 0) {
                        // Unlocking - regenerate QR code
                        require_once 'includes/qr_generator.php';
                        $qrGenerator = new QRGenerator();
                        
                        $qrResult = $qrGenerator->generateQRCode(
                            $student['student_id'],
                            $student['first_name'],
                            $student['last_name'],
                            $student['email']
                        );
                        
                        if (isset($qrResult['error'])) {
                            $message = 'Error regenerating QR code: ' . $qrResult['error'];
                            $messageType = 'error';
                        } else {
                            // Delete old QR code file if exists
                            $oldQrStmt = $db->prepare("SELECT qr_code_path FROM students WHERE id = ?");
                            $oldQrStmt->execute([$studentId]);
                            $oldQr = $oldQrStmt->fetch(PDO::FETCH_ASSOC);
                            if ($oldQr && $oldQr['qr_code_path'] && file_exists($oldQr['qr_code_path'])) {
                                @unlink($oldQr['qr_code_path']);
                            }
                            
                            // Update with new QR code and unlock
                            $updateStmt = $db->prepare("UPDATE students SET qr_code = ?, qr_code_path = ?, qr_code_locked = 0 WHERE id = ?");
                            $updateStmt->execute([$qrResult['qr_code'], $qrResult['qr_url'], $studentId]);
                            $message = 'QR code unlocked and regenerated successfully!';
                            $messageType = 'success';
                        }
                    } else {
                        // Locking - just update the lock status
                        $lockStmt = $db->prepare("UPDATE students SET qr_code_locked = 1 WHERE id = ?");
                        $lockStmt->execute([$studentId]);
                        $message = 'QR code locked successfully!';
                        $messageType = 'success';
                    }
                } else {
                    $message = 'Student not found!';
                    $messageType = 'error';
                }
                break;
                
            case 'lock_all_qr':
                // Lock all QR codes
                $lockAllStmt = $db->prepare("UPDATE students SET qr_code_locked = 1 WHERE is_active = 1");
                $lockAllStmt->execute();
                $affectedRows = $lockAllStmt->rowCount();
                $message = "Successfully locked QR codes for {$affectedRows} student(s)!";
                $messageType = 'success';
                break;
                
            case 'unlock_all_qr':
                // Unlock all QR codes and only refresh missing QR paths/codes.
                // Regenerating every QR synchronously is too slow for large student sets.
                require_once 'includes/qr_generator.php';
                $qrGenerator = new QRGenerator();
                
                // Unlock everyone first.
                $unlockAllStmt = $db->prepare("UPDATE students SET qr_code_locked = 0 WHERE is_active = 1");
                $unlockAllStmt->execute();

                // Only repair students who are missing a QR code value or image path.
                $allStudentsStmt = $db->query("SELECT id, student_id, first_name, last_name, email, qr_code, qr_code_path FROM students WHERE is_active = 1 AND (qr_code IS NULL OR qr_code = '' OR qr_code_path IS NULL OR qr_code_path = '')");
                $allStudents = $allStudentsStmt->fetchAll(PDO::FETCH_ASSOC);
                
                $successCount = 0;
                $errorCount = 0;
                
                foreach ($allStudents as $student) {
                    try {
                        if (!empty($student['qr_code'])) {
                            $updateStmt = $db->prepare("UPDATE students SET qr_code_path = ?, qr_code_locked = 0 WHERE id = ?");
                            $updateStmt->execute([$qrGenerator->getQRCodeImagePath($student['qr_code']), $student['id']]);
                        } else {
                            $qrResult = $qrGenerator->generateQRCode(
                                $student['student_id'],
                                $student['first_name'],
                                $student['last_name'],
                                $student['email']
                            );

                            if (isset($qrResult['error'])) {
                                $errorCount++;
                                continue;
                            }

                            $updateStmt = $db->prepare("UPDATE students SET qr_code = ?, qr_code_path = ?, qr_code_locked = 0 WHERE id = ?");
                            $updateStmt->execute([$qrResult['qr_code'], $qrResult['qr_url'], $student['id']]);
                        }
                        $successCount++;
                    } catch (Exception $e) {
                        $errorCount++;
                    }
                }
                
                if ($errorCount > 0) {
                    $message = "Unlocked QR codes for all active students and repaired {$successCount} missing QR records. {$errorCount} error(s) occurred.";
                    $messageType = $successCount > 0 ? 'success' : 'error';
                } else {
                    $message = "Successfully unlocked all QR codes. Repaired {$successCount} missing QR record(s).";
                    $messageType = 'success';
                }
                break;
        }
    } catch (Exception $e) {
        $message = 'Error: ' . $e->getMessage();
        $messageType = 'error';
    }
}

function extractSectionFromCourseLabel($courseLabel) {
    $courseLabel = trim((string)$courseLabel);
    if ($courseLabel !== '' && preg_match('/\[([^\]]+)\]\s*$/', $courseLabel, $matches)) {
        return trim($matches[1]);
    }
    return 'No Section';
}

function stripSectionFromCourseLabel($courseLabel) {
    $courseLabel = trim((string)$courseLabel);
    return trim(preg_replace('/\s*\[[^\]]+\]\s*$/', '', $courseLabel));
}

// Get search and filter parameters
$search = $_GET['search'] ?? '';
$course = $_GET['course'] ?? '';
$department = $_GET['department'] ?? '';
$section = $_GET['section'] ?? '';
$year_level = $_GET['year_level'] ?? '';
$status = $_GET['status'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 20;
$offset = ($page - 1) * $limit;

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $normalizedStudentCourse = "TRIM(SUBSTRING_INDEX(COALESCE(s.course, ''), ' [', 1))";
    $fullCourseLabel = "TRIM(CONCAT(c.course_name, CASE WHEN c.major IS NOT NULL AND c.major != '' THEN CONCAT(' - ', c.major) ELSE '' END))";
    $studentCourseJoin = "($normalizedStudentCourse = $fullCourseLabel OR $normalizedStudentCourse = c.course_name)";

    // Build query conditions
    $whereConditions = [];
    $params = [];
    
    if (!empty($search)) {
        $whereConditions[] = "(s.first_name LIKE ? OR s.last_name LIKE ? OR s.student_id LIKE ? OR s.email LIKE ?)";
        $searchTerm = "%$search%";
        $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
    }
    
    if (!empty($course)) {
        $whereConditions[] = "(s.course = ? OR s.course LIKE ?)";
        $params[] = $course;
        $params[] = $course . ' [%';
    }

    if (!empty($department)) {
        $whereConditions[] = "c.department = ?";
        $params[] = $department;
    }

    if (!empty($section)) {
        $whereConditions[] = "s.course LIKE ?";
        $params[] = '%[' . $section . ']';
    }
    
    if (!empty($year_level)) {
        $whereConditions[] = "s.year_level = ?";
        $params[] = $year_level;
    }
    
    if ($status !== '') {
        $whereConditions[] = "s.is_active = ?";
        $params[] = (int)$status;
    }
    
    $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
    
    // Get total count
    $countQuery = "SELECT COUNT(*) as total
                   FROM students s
                   LEFT JOIN courses c ON $studentCourseJoin
                   $whereClause";
    $countStmt = $db->prepare($countQuery);
    $countStmt->execute($params);
    $totalRecords = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
    $totalPages = ceil($totalRecords / $limit);
    
    // Get students with pagination
    $query = "SELECT s.*, c.department AS department_name, c.course_name, c.major
              FROM students s
              LEFT JOIN courses c ON $studentCourseJoin
              $whereClause
              ORDER BY c.department, s.year_level, s.course, s.last_name, s.first_name
              LIMIT $limit OFFSET $offset";
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($students as &$student) {
        $student['section_name'] = extractSectionFromCourseLabel($student['course'] ?? '');
        $student['course_display'] = stripSectionFromCourseLabel($student['course'] ?? '');
        $student['department_name'] = $student['department_name'] ?: 'Unassigned';
    }
    unset($student);
    
    // Get courses from the new courses table
    $coursesStmt = $db->query("SELECT * FROM courses WHERE is_active = 1 ORDER BY department, course_name, major");
    $courses = $coursesStmt->fetchAll(PDO::FETCH_ASSOC);
    $departments = array_values(array_unique(array_filter(array_map(static function ($courseRow) {
        return $courseRow['department'] ?? '';
    }, $courses))));
    
    // Get unique year levels from courses
    $yearLevels = [];
    foreach ($courses as $course) {
        $levels = json_decode($course['year_levels'], true);
        if (is_array($levels)) {
            $yearLevels = array_merge($yearLevels, $levels);
        }
    }
    $yearLevels = array_unique($yearLevels);
    sort($yearLevels);

    $sectionCoursesStmt = $db->query("SELECT DISTINCT course FROM students WHERE course IS NOT NULL AND course != '' ORDER BY course");
    $sectionOptions = [];
    foreach ($sectionCoursesStmt->fetchAll(PDO::FETCH_COLUMN) as $courseLabel) {
        $sectionLabel = extractSectionFromCourseLabel($courseLabel);
        if ($sectionLabel !== 'No Section') {
            $sectionOptions[$sectionLabel] = true;
        }
    }
    $sectionOptions = array_keys($sectionOptions);
    sort($sectionOptions);

    $browserQuery = "
        SELECT COALESCE(c.department, 'Unassigned') AS department_name, s.course, COUNT(*) AS total_students
        FROM students s
        LEFT JOIN courses c ON $studentCourseJoin
        GROUP BY COALESCE(c.department, 'Unassigned'), s.course
        ORDER BY department_name, s.course
    ";
    $browserRows = $db->query($browserQuery)->fetchAll(PDO::FETCH_ASSOC);
    $studentBrowser = [];
    foreach ($browserRows as $browserRow) {
        $deptName = $browserRow['department_name'] ?: 'Unassigned';
        $sectionName = extractSectionFromCourseLabel($browserRow['course'] ?? '');
        if (!isset($studentBrowser[$deptName])) {
            $studentBrowser[$deptName] = [
                'total' => 0,
                'sections' => []
            ];
        }
        if (!isset($studentBrowser[$deptName]['sections'][$sectionName])) {
            $studentBrowser[$deptName]['sections'][$sectionName] = 0;
        }
        $studentBrowser[$deptName]['sections'][$sectionName] += (int)$browserRow['total_students'];
        $studentBrowser[$deptName]['total'] += (int)$browserRow['total_students'];
    }
    
} catch (Exception $e) {
    $students = [];
    $totalRecords = 0;
    $totalPages = 0;
    $courses = [];
    $departments = [];
    $sectionOptions = [];
    $studentBrowser = [];
    $yearLevels = [];
    $message = 'Error loading students: ' . $e->getMessage();
    $messageType = 'error';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Students Management - Admin Panel</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="admin-body">
    <!-- Include Admin Sidebar -->
    <?php include 'includes/admin_sidebar.php'; ?>
    
    <!-- Mobile Sidebar Toggle -->
    <button class="sidebar-toggle" onclick="toggleSidebar()">
        <i class="fas fa-bars"></i>
    </button>
    
    <!-- Main Content -->
    <div class="admin-content">
        <div class="admin-header">
            <div class="header-left">
                <div class="breadcrumb">
                    <span class="crumb-root"><i class="fas fa-house"></i> Home</span>
                    <span class="crumb-separator">/</span>
                    <span class="crumb-current">Students</span>
                </div>
                <h1 class="page-title"><i class="fas fa-user-graduate"></i> Students</h1>
            </div>
            <div class="header-right">
                <div class="search-wrapper">
                    <i class="fas fa-magnifying-glass"></i>
                    <input type="text" id="searchHeader" placeholder="Search students, IDs, emails..." />
                </div>
                <button class="icon-btn" type="button" aria-label="Notifications">
                    <i class="fas fa-bell"></i>
                    <span class="badge-dot"></span>
                </button>
                <button class="icon-btn collapse-toggle" type="button" aria-label="Collapse sidebar" onclick="toggleSidebarCollapse()">
                    <i class="fas fa-chevron-left"></i>
                </button>
                <div class="profile-menu">
                    <button class="profile-trigger" type="button" onclick="toggleProfileMenu()">
                        <div class="avatar-circle">
                            <span><?php echo strtoupper(substr($currentAdmin['full_name'], 0, 1)); ?></span>
                        </div>
                        <div class="profile-text">
                            <span class="profile-name"><?php echo htmlspecialchars($currentAdmin['full_name']); ?></span>
                            <span class="profile-role">Administrator</span>
                        </div>
                        <i class="fas fa-chevron-down caret"></i>
                    </button>
                    <div class="profile-dropdown" id="profileDropdown">
                        <a href="admin_profile.php"><i class="fas fa-user"></i> Profile</a>
                        <a href="admin_settings.php"><i class="fas fa-gear"></i> Settings</a>
                        <a href="admin_signout.php" class="danger"><i class="fas fa-right-from-bracket"></i> Sign out</a>
                    </div>
                </div>
                <a href="admin_add_student.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Add Student
                </a>
            </div>
        </div>
        
        <div class="admin-main">
            <?php if ($message): ?>
                <div class="message <?php echo $messageType; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>
            
            <!-- Filters and Search -->
            <div class="filters-container">
                <form method="GET" class="filters-form">
                    <div class="filter-row">
                        <div class="filter-group">
                            <label for="search">Search Students</label>
                            <input type="text" id="search" name="search" placeholder="Search by name, ID, or email..." 
                                   value="<?php echo htmlspecialchars($search); ?>">
                        </div>

                        <div class="filter-group">
                            <label for="department">Department</label>
                            <select id="department" name="department">
                                <option value="">All Departments</option>
                                <?php foreach ($departments as $departmentOption): ?>
                                    <option value="<?php echo htmlspecialchars($departmentOption); ?>"
                                            <?php echo $department === $departmentOption ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($departmentOption); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <label for="course">Course</label>
                            <select id="course" name="course">
                                <option value="">All Courses</option>
                                <?php 
                                $currentDepartment = '';
                                foreach ($courses as $courseOption): 
                                    if ($currentDepartment !== $courseOption['department']):
                                        if ($currentDepartment !== ''):
                                            echo '</optgroup>';
                                        endif;
                                        echo '<optgroup label="' . htmlspecialchars($courseOption['department']) . '">';
                                        $currentDepartment = $courseOption['department'];
                                    endif;
                                    
                                    $courseValue = $courseOption['course_name'];
                                    if ($courseOption['major']) {
                                        $courseValue .= ' - ' . $courseOption['major'];
                                    }
                                    
                                    $displayText = $courseOption['course_name'];
                                    if ($courseOption['major']) {
                                        $displayText .= ' - ' . $courseOption['major'];
                                    }
                                ?>
                                    <option value="<?php echo htmlspecialchars($courseValue); ?>" 
                                            <?php echo $course === $courseValue ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($displayText); ?>
                                    </option>
                                <?php endforeach; 
                                if ($currentDepartment !== ''):
                                    echo '</optgroup>';
                                endif;
                                ?>
                            </select>
                        </div>

                        <div class="filter-group">
                            <label for="section">Section</label>
                            <select id="section" name="section">
                                <option value="">All Sections</option>
                                <?php foreach ($sectionOptions as $sectionOption): ?>
                                    <option value="<?php echo htmlspecialchars($sectionOption); ?>"
                                            <?php echo $section === $sectionOption ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($sectionOption); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <label for="year_level">Year Level</label>
                            <select id="year_level" name="year_level">
                                <option value="">All Year Levels</option>
                                <?php foreach ($yearLevels as $yearOption): ?>
                                    <option value="<?php echo htmlspecialchars($yearOption); ?>" 
                                            <?php echo $year_level === $yearOption ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($yearOption); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <label for="status">Status</label>
                            <select id="status" name="status">
                                <option value="">All Status</option>
                                <option value="1" <?php echo $status === '1' ? 'selected' : ''; ?>>Active</option>
                                <option value="0" <?php echo $status === '0' ? 'selected' : ''; ?>>Inactive</option>
                            </select>
                        </div>
                        
                        <div class="filter-actions">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search"></i> Filter
                            </button>
                            <a href="admin_students.php" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Clear
                            </a>
                        </div>
                    </div>
                </form>
            </div>

            <div class="browser-panel">
                <div class="browser-header">
                    <div>
                        <h3>Browse By Department and Section</h3>
                        <p>Quickly jump to a student group by department, then section.</p>
                    </div>
                    <a href="admin_students.php" class="browser-reset">Show all students</a>
                </div>
                <div class="browser-grid">
                    <?php if (empty($studentBrowser)): ?>
                        <div class="browser-empty">No grouped student data is available yet.</div>
                    <?php else: ?>
                        <?php foreach ($studentBrowser as $departmentName => $browserInfo): ?>
                            <div class="browser-card">
                                <div class="browser-card-head">
                                    <h4><?php echo htmlspecialchars($departmentName); ?></h4>
                                    <span><?php echo (int)$browserInfo['total']; ?> students</span>
                                </div>
                                <div class="browser-chip-list">
                                    <?php foreach ($browserInfo['sections'] as $sectionName => $sectionTotal): ?>
                                        <a class="browser-chip <?php echo ($department === $departmentName && $section === $sectionName) ? 'active' : ''; ?>"
                                           href="?<?php echo htmlspecialchars(http_build_query(array_merge($_GET, ['department' => $departmentName, 'section' => $sectionName, 'page' => 1]))); ?>">
                                            <span><?php echo htmlspecialchars($sectionName); ?></span>
                                            <strong><?php echo (int)$sectionTotal; ?></strong>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Students Table -->
            <div class="table-container">
                <div class="table-header">
                    <h3>Students List (<?php echo $totalRecords; ?> total)</h3>
                    <div class="table-actions">
                        <button class="btn btn-warning" onclick="lockAllQR()" title="Lock all QR codes">
                            <i class="fas fa-lock"></i> Lock All QR
                        </button>
                        <button class="btn btn-success" onclick="unlockAllQR()" title="Unlock and regenerate all QR codes">
                            <i class="fas fa-unlock"></i> Unlock All QR
                        </button>
                        <button class="btn btn-secondary" onclick="exportStudents()">
                            <i class="fas fa-download"></i> Export
                        </button>
                    </div>
                </div>
                
                <div class="table-responsive">
                    <table class="students-table">
                        <thead>
                            <tr>
                                <th>Student ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Department</th>
                                <th>Phone</th>
                                <th>Course</th>
                                <th>Year Level</th>
                                <th>Section</th>
                                <th>QR Code</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($students)): ?>
                                <tr>
                                    <td colspan="11" class="no-data">
                                        <i class="fas fa-user-slash"></i>
                                        <p>No students found</p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($students as $student): ?>
                                    <tr>
                                        <td>
                                            <span class="student-id"><?php echo htmlspecialchars($student['student_id']); ?></span>
                                        </td>
                                        <td>
                                            <div class="student-info">
                                                <div class="student-name">
                                                    <?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="student-email"><?php echo htmlspecialchars($student['email']); ?></span>
                                        </td>
                                        <td>
                                            <span class="student-department"><?php echo htmlspecialchars($student['department_name'] ?: 'Unassigned'); ?></span>
                                        </td>
                                        <td>
                                            <span class="student-phone"><?php echo htmlspecialchars($student['phone'] ?: 'N/A'); ?></span>
                                        </td>
                                        <td>
                                            <span class="student-course"><?php echo htmlspecialchars($student['course_display'] ?: 'N/A'); ?></span>
                                        </td>
                                        <td>
                                            <span class="student-year"><?php echo htmlspecialchars($student['year_level'] ?: 'N/A'); ?></span>
                                        </td>
                                        <td>
                                            <span class="student-section"><?php echo htmlspecialchars($student['section_name'] ?: 'No Section'); ?></span>
                                        </td>
                                        <td>
                                            <?php 
                                            $isLocked = isset($student['qr_code_locked']) && $student['qr_code_locked'] == 1;
                                            if ($isLocked): ?>
                                                <span class="qr-locked" title="QR Code is locked">
                                                    <i class="fas fa-lock"></i> Locked
                                                </span>
                                            <?php elseif ($student['qr_code_path']): ?>
                                                <button onclick="showQRModal('<?php echo htmlspecialchars($student['qr_code_path']); ?>', '<?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?>')" 
                                                        class="qr-link">
                                                    <i class="fas fa-qrcode"></i> View QR
                                                </button>
                                            <?php else: ?>
                                                <span class="no-qr">No QR Code</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="status-badge <?php echo $student['is_active'] ? 'active' : 'inactive'; ?>">
                                                <?php echo $student['is_active'] ? 'Active' : 'Inactive'; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <button class="btn-action view" onclick="viewStudent(<?php echo $student['id']; ?>)" 
                                                        title="View Details">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button class="btn-action toggle" 
                                                        onclick="toggleStudentStatus(<?php echo $student['id']; ?>, <?php echo $student['is_active']; ?>)" 
                                                        title="<?php echo $student['is_active'] ? 'Deactivate' : 'Activate'; ?>">
                                                    <i class="fas fa-<?php echo $student['is_active'] ? 'ban' : 'check'; ?>"></i>
                                                </button>
                                                <?php 
                                                $isQrLocked = isset($student['qr_code_locked']) && $student['qr_code_locked'] == 1;
                                                $qrLockClass = $isQrLocked ? 'locked' : 'unlocked';
                                                ?>
                                                <button class="btn-action qr-lock <?php echo $qrLockClass; ?>" 
                                                        onclick="toggleQRLock(<?php echo $student['id']; ?>, <?php echo $isQrLocked ? '1' : '0'; ?>)" 
                                                        title="<?php echo $isQrLocked ? 'Unlock QR Code' : 'Lock QR Code'; ?>">
                                                    <i class="fas fa-<?php echo $isQrLocked ? 'unlock' : 'lock'; ?>"></i>
                                                </button>
                                                <button class="btn-action delete" onclick="deleteStudent(<?php echo $student['id']; ?>)" 
                                                        title="Delete Student">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>" 
                               class="pagination-btn">
                                <i class="fas fa-chevron-left"></i> Previous
                            </a>
                        <?php endif; ?>
                        
                        <div class="pagination-info">
                            Page <?php echo $page; ?> of <?php echo $totalPages; ?>
                        </div>
                        
                        <?php if ($page < $totalPages): ?>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>" 
                               class="pagination-btn">
                                Next <i class="fas fa-chevron-right"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <style>
    /* Admin Layout Styles */
    .admin-body {
        background: radial-gradient(circle at top left, #eef2ff 0, #f6f8fc 40%, #f6f7fb 100%);
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        margin: 0;
        padding: 0;
        overflow-x: hidden;
        color: #0f172a;
    }
    
    .admin-content {
        margin-left: 280px;
        min-height: 100vh;
        background: transparent;
        transition: margin-left 0.24s ease;
        width: calc(100% - 280px);
    }
    
    .admin-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        padding: 18px 28px;
        position: sticky;
        top: 0;
        z-index: 120;
        backdrop-filter: blur(20px);
        background: linear-gradient(135deg, rgba(248,250,252,0.92), rgba(238,242,255,0.96));
        border-bottom: 1px solid rgba(148,163,184,0.25);
        box-shadow: 0 16px 40px rgba(15,23,42,0.14);
    }
    
    .header-left {
        display: flex;
        flex-direction: column;
        gap: 6px;
    }
    
    .breadcrumb {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: 0.12em;
        color: #94a3b8;
        font-weight: 600;
    }
    
    .crumb-root i{
        margin-right:4px;
    }
    
    .crumb-current{
        color:#4f46e5;
    }
    
    .page-title {
        margin: 0;
        font-family: 'Poppins', system-ui, sans-serif;
        font-size: 22px;
        font-weight: 700;
        letter-spacing: -0.02em;
        color: #0f172a;
        display:flex;
        align-items:center;
        gap:8px;
    }
    
    .page-title i{
        color:#6366f1;
    }
    
    .header-right {
        display: flex;
        align-items: center;
        gap: 12px;
    }
    
    .search-wrapper {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 8px 12px;
        border-radius: 999px;
        background: rgba(15,23,42,0.03);
        border: 1px solid rgba(148,163,184,0.4);
        min-width: 260px;
        max-width: 320px;
    }
    
    .search-wrapper i{
        color:#94a3b8;
        font-size:13px;
    }
    
    .search-wrapper input{
        border:none;
        outline:none;
        background:transparent;
        width:100%;
        font-size:12px;
        color:#0f172a;
        font-family:'Inter',sans-serif;
    }
    
    .search-wrapper input::placeholder{
        color:#9ca3af;
    }
    
    .icon-btn{
        border:none;
        background:rgba(15,23,42,0.04);
        border-radius:999px;
        width:34px;
        height:34px;
        display:inline-flex;
        align-items:center;
        justify-content:center;
        color:#4b5563;
        cursor:pointer;
        transition:all 0.18s ease;
        position:relative;
    }
    
    .icon-btn:hover{
        background:rgba(129,140,248,0.14);
        color:#4f46e5;
        transform:translateY(-1px);
        box-shadow:0 8px 18px rgba(129,140,248,0.28);
    }
    
    .badge-dot{
        position:absolute;
        top:6px;
        right:7px;
        width:7px;
        height:7px;
        border-radius:999px;
        background:#ef4444;
        box-shadow:0 0 0 4px rgba(248,113,113,0.5);
    }
    
    .collapse-toggle i{
        font-size:12px;
    }
    
    .profile-menu{
        position:relative;
    }
    
    .profile-trigger{
        display:flex;
        align-items:center;
        gap:8px;
        padding:6px 10px 6px 6px;
        border-radius:999px;
        border:1px solid rgba(148,163,184,0.4);
        background:rgba(248,250,252,0.7);
        cursor:pointer;
        transition:all 0.2s ease;
    }
    
    .profile-trigger:hover{
        background:#ffffff;
        box-shadow:0 10px 24px rgba(15,23,42,0.18);
        transform:translateY(-1px);
    }
    
    .avatar-circle{
        width:30px;
        height:30px;
        border-radius:999px;
        background:linear-gradient(135deg,#4f46e5,#7c3aed);
        display:flex;
        align-items:center;
        justify-content:center;
        color:#fff;
        font-weight:700;
        font-size:14px;
    }
    
    .profile-text{
        display:flex;
        flex-direction:column;
        align-items:flex-start;
    }
    
    .profile-name{
        font-size:12px;
        font-weight:600;
        color:#0f172a;
    }
    
    .profile-role{
        font-size:11px;
        color:#9ca3af;
    }
    
    .caret{
        font-size:10px;
        color:#9ca3af;
    }
    
    .profile-dropdown{
        position:absolute;
        right:0;
        top:110%;
        background:#ffffff;
        border-radius:14px;
        box-shadow:0 18px 40px rgba(15,23,42,0.28);
        padding:6px 6px;
        min-width:180px;
        display:none;
        z-index:150;
        border:1px solid rgba(148,163,184,0.35);
    }
    
    .profile-dropdown a{
        display:flex;
        align-items:center;
        gap:8px;
        padding:8px 10px;
        border-radius:10px;
        font-size:13px;
        color:#111827;
        text-decoration:none;
        transition:background 0.16s ease,color 0.16s ease;
    }
    
    .profile-dropdown a i{
        width:16px;
        text-align:center;
        color:#6b7280;
    }
    
    .profile-dropdown a:hover{
        background:rgba(129,140,248,0.12);
        color:#4f46e5;
    }
    
    .profile-dropdown a.danger:hover{
        background:rgba(239,68,68,0.12);
        color:#b91c1c;
    }
    
    .admin-main {
        padding: 22px 28px 32px;
        max-width: 1400px;
        margin: 0 auto;
        width: 100%;
    }
    /* Filters */
    .filters-container {
        background: #f9fafb;
        border-radius: 12px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        margin-bottom: 30px;
        overflow: hidden;
    }
    
    .filters-form {
        padding: 25px;
    }
    
    .filter-row {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 20px;
        align-items: end;
        width: 100%;
    }
    
    .filter-group {
        display: flex;
        flex-direction: column;
        gap: 8px;
        min-width: 0;
    }
    
    .filter-group label {
        font-weight: 600;
        color: #2d3748;
        font-size: 14px;
    }
    
    .filter-group input,
    .filter-group select {
        padding: 12px 16px;
        border: 2px solid #e2e8f0;
        border-radius: 8px;
        font-size: 14px;
        font-family: 'Inter', sans-serif;
        transition: all 0.3s ease;
        background: white;
    }
    
    .filter-group input:focus,
    .filter-group select:focus {
        outline: none;
        border-color: #667eea;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }
    
    .filter-actions {
        display: flex;
        gap: 10px;
        align-items: end;
        flex-shrink: 0;
        flex-wrap: wrap;
        justify-content: flex-end;
        grid-column: 1 / -1;
    }

    .browser-panel {
        background: linear-gradient(135deg, #fff7ed, #ffffff);
        border: 1px solid #fed7aa;
        border-radius: 18px;
        box-shadow: 0 10px 30px rgba(251, 146, 60, 0.12);
        padding: 22px;
        margin-bottom: 24px;
    }

    .browser-header {
        display: flex;
        justify-content: space-between;
        gap: 16px;
        align-items: flex-start;
        margin-bottom: 16px;
    }

    .browser-header h3 {
        margin: 0 0 4px;
        font-size: 20px;
        color: #9a3412;
    }

    .browser-header p {
        margin: 0;
        color: #7c2d12;
        font-size: 14px;
    }

    .browser-reset {
        color: #9a3412;
        text-decoration: none;
        font-weight: 600;
        white-space: nowrap;
    }

    .browser-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 16px;
    }

    .browser-card {
        background: rgba(255, 255, 255, 0.82);
        border: 1px solid #fdba74;
        border-radius: 16px;
        padding: 16px;
    }

    .browser-card-head {
        display: flex;
        justify-content: space-between;
        gap: 12px;
        align-items: center;
        margin-bottom: 12px;
    }

    .browser-card-head h4 {
        margin: 0;
        font-size: 15px;
        color: #7c2d12;
    }

    .browser-card-head span {
        font-size: 12px;
        font-weight: 700;
        color: #c2410c;
        background: #ffedd5;
        border-radius: 999px;
        padding: 6px 10px;
    }

    .browser-chip-list {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
    }

    .browser-chip {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 9px 12px;
        border-radius: 999px;
        text-decoration: none;
        background: #fff;
        border: 1px solid #fdba74;
        color: #9a3412;
        font-size: 13px;
        transition: all 0.2s ease;
    }

    .browser-chip strong {
        color: #ea580c;
    }

    .browser-chip:hover,
    .browser-chip.active {
        background: #ea580c;
        border-color: #ea580c;
        color: #fff;
        transform: translateY(-1px);
    }

    .browser-chip:hover strong,
    .browser-chip.active strong {
        color: #fff7ed;
    }

    .browser-empty {
        padding: 18px;
        border-radius: 14px;
        background: #fff;
        color: #9a3412;
        text-align: center;
    }
    
    /* Table Container */
    .table-container {
        background: #f9fafb;
        border-radius: 12px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        overflow: hidden;
    }
    
    .table-header {
        padding: 25px;
        border-bottom: 1px solid #f1f3f4;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .table-header h3 {
        margin: 0;
        color: #2d3748;
        font-size: 20px;
        font-weight: 600;
    }
    
    .table-actions {
        display: flex;
        gap: 10px;
    }
    
    .table-responsive {
        overflow-x: auto;
        width: 100%;
        max-width: 100%;
    }
    
    .students-table {
        width: 100%;
        min-width: 1280px;
        border-collapse: collapse;
        table-layout: fixed;
    }
    
    .students-table th {
        background: #f8f9fa;
        padding: 15px 12px;
        text-align: left;
        font-weight: 600;
        color: #2d3748;
        border-bottom: 2px solid #e2e8f0;
        white-space: nowrap;
        font-size: 13px;
    }
    
    .students-table th:nth-child(1) { width: 10%; }
    .students-table th:nth-child(2) { width: 14%; }
    .students-table th:nth-child(3) { width: 17%; }
    .students-table th:nth-child(4) { width: 14%; }
    .students-table th:nth-child(5) { width: 10%; }
    .students-table th:nth-child(6) { width: 14%; }
    .students-table th:nth-child(7) { width: 8%; }
    .students-table th:nth-child(8) { width: 9%; }
    .students-table th:nth-child(9) { width: 8%; }
    .students-table th:nth-child(10) { width: 8%; }
    .students-table th:nth-child(11) { width: 12%; }
    
    .students-table td {
        padding: 12px;
        border-bottom: 1px solid #f1f3f4;
        vertical-align: middle;
        font-size: 13px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    
    .students-table tr:hover {
        background: #f8f9fa;
    }
    
    .student-id {
        font-weight: 600;
        color: #667eea;
        font-family: 'Courier New', monospace;
    }
    
    .student-info {
        display: flex;
        flex-direction: column;
    }
    
    .student-name {
        font-weight: 600;
        color: #2d3748;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 150px;
        display: block;
    }
    
    .student-email {
        color: #718096;
        font-size: 12px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 180px;
        display: block;
    }
    
    .student-phone,
    .student-department,
    .student-course,
    .student-year,
    .student-section {
        color: #4a5568;
        font-size: 12px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .student-section {
        font-weight: 600;
        color: #9a3412;
    }
    
    .qr-link {
        color: #667eea;
        text-decoration: none;
        font-weight: 500;
        display: flex;
        align-items: center;
        gap: 5px;
        transition: color 0.3s ease;
        background: none;
        border: none;
        cursor: pointer;
        font-size: inherit;
        font-family: inherit;
        padding: 0;
    }
    
    .qr-link:hover {
        color: #5a6fd8;
    }
    
    .no-qr {
        color: #a0aec0;
        font-style: italic;
    }
    
    .qr-locked {
        color: #e53e3e;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 5px;
        font-size: 12px;
    }
    
    .btn-action.qr-lock {
        border-radius: 8px;
    }
    
    .btn-action.qr-lock.unlocked {
        background: #dcfce7;
        color: #166534;
    }
    
    .btn-action.qr-lock.unlocked:hover {
        background: #bbf7d0;
    }
    
    .btn-action.qr-lock.locked {
        background: #fee2e2;
        color: #b91c1c;
    }
    
    .btn-action.qr-lock.locked:hover {
        background: #fecaca;
    }
    
    .status-badge {
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .status-badge.active {
        background: #d4edda;
        color: #155724;
    }
    
    .status-badge.inactive {
        background: #f8d7da;
        color: #721c24;
    }
    
    .action-buttons {
        display: flex;
        gap: 4px;
        flex-wrap: nowrap;
        justify-content: center;
    }
    
    .btn-action {
        width: 28px;
        height: 28px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 11px;
        transition: all 0.3s ease;
        flex-shrink: 0;
    }
    
    .btn-action.view {
        background: #e3f2fd;
        color: #1976d2;
    }
    
    .btn-action.view:hover {
        background: #bbdefb;
    }
    
    .btn-action.edit {
        background: #fff3e0;
        color: #f57c00;
    }
    
    .btn-action.edit:hover {
        background: #ffe0b2;
    }
    
    .btn-action.toggle {
        background: #f3e5f5;
        color: #7b1fa2;
    }
    
    .btn-action.toggle:hover {
        background: #e1bee7;
    }
    
    .btn-action.delete {
        background: #ffebee;
        color: #d32f2f;
    }
    
    .btn-action.delete:hover {
        background: #ffcdd2;
    }
    
    .no-data {
        text-align: center;
        padding: 40px;
        color: #a0aec0;
    }
    
    .no-data i {
        font-size: 48px;
        margin-bottom: 15px;
        display: block;
    }
    
    .no-data p {
        margin: 0;
        font-size: 16px;
        font-weight: 500;
    }
    
    /* Pagination */
    .pagination {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 20px 25px;
        border-top: 1px solid #f1f3f4;
    }
    
    .pagination-btn {
        padding: 10px 20px;
        background: #667eea;
        color: white;
        text-decoration: none;
        border-radius: 6px;
        font-weight: 500;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .pagination-btn:hover {
        background: #5a6fd8;
        transform: translateY(-1px);
    }
    
    .pagination-info {
        color: #718096;
        font-weight: 500;
    }
    
    /* Buttons */
    .btn {
        padding: 12px 24px;
        border: none;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: all 0.3s ease;
        font-family: 'Inter', sans-serif;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .btn-primary {
        background: linear-gradient(135deg, #667eea, #764ba2);
        color: white;
        box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
    }
    
    .btn-primary:hover {
        background: linear-gradient(135deg, #5a6fd8, #6a4190);
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
    }
    
    .btn-secondary {
        background: #6c757d;
        color: white;
        box-shadow: 0 4px 15px rgba(108, 117, 125, 0.3);
    }
    
    .btn-secondary:hover {
        background: #5a6268;
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(108, 117, 125, 0.4);
    }
    
    .btn-warning {
        background: linear-gradient(135deg, #f59e0b, #d97706);
        color: white;
        box-shadow: 0 4px 15px rgba(245, 158, 11, 0.3);
    }
    
    .btn-warning:hover {
        background: linear-gradient(135deg, #d97706, #b45309);
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(245, 158, 11, 0.4);
    }
    
    .btn-success {
        background: linear-gradient(135deg, #10b981, #059669);
        color: white;
        box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);
    }
    
    .btn-success:hover {
        background: linear-gradient(135deg, #059669, #047857);
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(16, 185, 129, 0.4);
    }
    
    .message {
        padding: 16px 20px;
        border-radius: 8px;
        margin-bottom: 25px;
        font-weight: 500;
        border-left: 4px solid;
    }
    
    .message.success {
        background: #d4edda;
        color: #155724;
        border-left-color: #28a745;
    }
    
    .message.error {
        background: #f8d7da;
        color: #721c24;
        border-left-color: #dc3545;
    }
    
    /* QR Code Modal Styles */
    .qr-modal {
        display: none;
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
        backdrop-filter: blur(5px);
        animation: fadeIn 0.3s ease;
    }
    
    .qr-modal.show {
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .qr-modal-content {
        background: white;
        border-radius: 16px;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        max-width: 500px;
        width: 90%;
        max-height: 90vh;
        overflow: hidden;
        animation: slideIn 0.3s ease;
        position: relative;
    }
    
    .qr-modal-header {
        padding: 20px 25px;
        border-bottom: 1px solid #e2e8f0;
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: #f8f9fa;
    }
    
    .qr-modal-header h3 {
        margin: 0;
        color: #2d3748;
        font-size: 20px;
        font-weight: 600;
    }
    
    .qr-modal-close {
        background: none;
        border: none;
        font-size: 20px;
        color: #718096;
        cursor: pointer;
        padding: 8px;
        border-radius: 6px;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        width: 36px;
        height: 36px;
    }
    
    .qr-modal-close:hover {
        background: #e2e8f0;
        color: #2d3748;
    }
    
    .qr-modal-body {
        padding: 25px;
        text-align: center;
    }
    
    .qr-image-container {
        margin-bottom: 25px;
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: 200px;
        background: #f8f9fa;
        border-radius: 12px;
        padding: 20px;
    }
    
    .qr-image {
        max-width: 100%;
        max-height: 300px;
        border-radius: 8px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
    }
    
    .qr-modal-actions {
        display: flex;
        gap: 15px;
        justify-content: center;
        flex-wrap: wrap;
    }
    
    .qr-modal-actions .btn {
        flex: 1;
        min-width: 140px;
        justify-content: center;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }
    
    @keyframes slideIn {
        from { 
            opacity: 0;
            transform: scale(0.9) translateY(-20px);
        }
        to { 
            opacity: 1;
            transform: scale(1) translateY(0);
        }
    }
    
    /* Mobile Sidebar Toggle */
    .sidebar-toggle {
        display: none;
        position: fixed;
        top: 20px;
        left: 20px;
        z-index: 1001;
        background: linear-gradient(135deg, #667eea, #764ba2);
        color: white;
        border: none;
        padding: 12px 15px;
        border-radius: 8px;
        cursor: pointer;
        font-size: 18px;
        box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        transition: all 0.3s ease;
    }
    
    .sidebar-toggle:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
    }
    
    /* Mobile Responsiveness */
    @media (max-width: 768px) {
        .sidebar-toggle {
            display: block;
        }
        
        .admin-content {
            margin-left: 0;
            width: 100%;
        }
        
        .admin-header {
            padding: 15px 20px;
        }
        
        .header-content h1 {
            font-size: 24px;
        }
        
        .admin-main {
            padding: 20px;
        }
        
        .filter-row {
            grid-template-columns: 1fr;
            gap: 15px;
        }
        
        .filter-actions {
            justify-content: stretch;
        }
        
        .filter-actions .btn {
            flex: 1;
            justify-content: center;
        }
        
        .table-header {
            flex-direction: column;
            gap: 15px;
            align-items: stretch;
        }
        
        .students-table {
            font-size: 12px;
            min-width: 800px;
        }
        
        .students-table th,
        .students-table td {
            padding: 8px 6px;
        }
        
        .action-buttons {
            flex-wrap: wrap;
            gap: 3px;
        }
        
        .btn-action {
            width: 24px;
            height: 24px;
            font-size: 10px;
        }
        
        .student-name {
            max-width: 120px;
        }
        
        .student-email {
            max-width: 140px;
        }
        
        /* QR Modal Mobile Styles */
        .qr-modal-content {
            width: 95%;
            max-width: 400px;
            margin: 20px;
        }
        
        .qr-modal-header {
            padding: 15px 20px;
        }
        
        .qr-modal-header h3 {
            font-size: 18px;
        }
        
        .qr-modal-body {
            padding: 20px;
        }
        
        .qr-image-container {
            min-height: 150px;
            padding: 15px;
        }
        
        .qr-modal-actions {
            flex-direction: column;
            gap: 10px;
        }
        
        .qr-modal-actions .btn {
            min-width: auto;
            width: 100%;
        }
    }
    
    @media (max-width: 480px) {
        .admin-main {
            padding: 15px;
        }
        
        .filters-form {
            padding: 20px;
        }
        
        .table-header {
            padding: 20px;
        }
        
        .students-table th,
        .students-table td {
            padding: 8px 12px;
        }
    }
    </style>
    
    <script>
    // Mobile sidebar toggle function
    function toggleSidebar() {
        const sidebar = document.querySelector('.admin-sidebar');
        sidebar.classList.toggle('open');
    }
    
    // Close sidebar when clicking outside on mobile
    document.addEventListener('click', function(e) {
        const sidebar = document.querySelector('.admin-sidebar');
        const toggle = document.querySelector('.sidebar-toggle');
        
        if (window.innerWidth <= 768 && 
            !sidebar.contains(e.target) && 
            !toggle.contains(e.target) && 
            sidebar.classList.contains('open')) {
            sidebar.classList.remove('open');
        }
    });

    function toggleSidebarCollapse() {
        const body = document.body;
        const sidebar = document.querySelector('.admin-sidebar');
        const chevron = document.querySelector('.collapse-toggle i');
        body.classList.toggle('sidebar-collapsed');
        if (sidebar) sidebar.classList.toggle('collapsed');
        if (chevron) {
            chevron.classList.toggle('fa-chevron-left');
            chevron.classList.toggle('fa-chevron-right');
        }
    }

    function toggleProfileMenu() {
        const dropdown = document.getElementById('profileDropdown');
        if (dropdown) {
            dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
        }
    }

    document.addEventListener('click', function(e) {
        const dropdown = document.getElementById('profileDropdown');
        const trigger = document.querySelector('.profile-trigger');
        if (!dropdown || !trigger) return;
        if (!dropdown.contains(e.target) && !trigger.contains(e.target)) {
            dropdown.style.display = 'none';
        }
    });
    
    // Student management functions
    function viewStudent(id) {
        // Implement view student details
        alert('View student details for ID: ' + id);
    }
    
    function editStudent(id) {
        // Implement edit student
        window.location.href = 'admin_edit_student.php?id=' + id;
    }
    
    function toggleStudentStatus(id, currentStatus) {
        if (confirm('Are you sure you want to ' + (currentStatus ? 'deactivate' : 'activate') + ' this student?')) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="action" value="toggle_status">
                <input type="hidden" name="student_id" value="${id}">
            `;
            document.body.appendChild(form);
            form.submit();
        }
    }
    
    function deleteStudent(id) {
        if (confirm('Are you sure you want to delete this student? This action cannot be undone.')) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="action" value="delete_student">
                <input type="hidden" name="student_id" value="${id}">
            `;
            document.body.appendChild(form);
            form.submit();
        }
    }
    
    function toggleQRLock(id, isLocked) {
        const action = isLocked ? 'unlock' : 'lock';
        const confirmMsg = isLocked 
            ? 'Are you sure you want to unlock this QR code? A new QR code will be generated.'
            : 'Are you sure you want to lock this QR code? The student will not be able to view it.';
        
        if (confirm(confirmMsg)) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="action" value="toggle_qr_lock">
                <input type="hidden" name="student_id" value="${id}">
            `;
            document.body.appendChild(form);
            form.submit();
        }
    }
    
    function lockAllQR() {
        if (confirm('Are you sure you want to lock ALL QR codes? All students will not be able to view their QR codes.')) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="action" value="lock_all_qr">
            `;
            document.body.appendChild(form);
            form.submit();
        }
    }
    
    function unlockAllQR() {
        if (confirm('Are you sure you want to unlock ALL QR codes? This will regenerate new QR codes for all students. This may take a few moments.')) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="action" value="unlock_all_qr">
            `;
            document.body.appendChild(form);
            form.submit();
        }
    }
    
    function exportStudents() {
        // Implement export functionality
        alert('Export functionality will be implemented');
    }
    
    // QR Modal functions
    let currentQRImage = '';
    
    function showQRModal(qrImagePath, studentName) {
        currentQRImage = qrImagePath;
        document.getElementById('qrModalTitle').textContent = `${studentName}'s QR Code`;
        document.getElementById('qrModalImage').src = qrImagePath;
        document.getElementById('qrModal').classList.add('show');
        document.body.style.overflow = 'hidden'; // Prevent background scrolling
    }
    
    function closeQRModal() {
        document.getElementById('qrModal').classList.remove('show');
        document.body.style.overflow = 'auto'; // Restore scrolling
        // Clear the image source after animation
        setTimeout(() => {
            document.getElementById('qrModalImage').src = '';
        }, 300);
    }
    
    function downloadQRCode() {
        if (currentQRImage) {
            const link = document.createElement('a');
            link.href = currentQRImage;
            link.download = `qr_code_${Date.now()}.png`;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }
    }
    
    function printQRCode() {
        if (currentQRImage) {
            const printWindow = window.open('', '_blank');
            printWindow.document.write(`
                <html>
                    <head>
                        <title>QR Code Print</title>
                        <style>
                            body { 
                                text-align: center; 
                                font-family: Arial, sans-serif;
                                padding: 20px;
                            }
                            img { 
                                max-width: 100%; 
                                height: auto;
                                border: 2px solid #333;
                            }
                            .print-title {
                                margin-bottom: 20px;
                                font-size: 18px;
                                font-weight: bold;
                            }
                        </style>
                    </head>
                    <body>
                        <div class="print-title">Student QR Code</div>
                        <img src="${currentQRImage}" alt="QR Code">
                    </body>
                </html>
            `);
            printWindow.document.close();
            printWindow.print();
        }
    }
    
    // Close modal when clicking outside
    document.addEventListener('click', function(e) {
        const modal = document.getElementById('qrModal');
        if (e.target === modal) {
            closeQRModal();
        }
    });
    
    // Close modal with Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeQRModal();
        }
    });
    </script>
    
    <!-- QR Code Modal -->
    <div id="qrModal" class="qr-modal">
        <div class="qr-modal-content">
            <div class="qr-modal-header">
                <h3 id="qrModalTitle">QR Code</h3>
                <button class="qr-modal-close" onclick="closeQRModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="qr-modal-body">
                <div class="qr-image-container">
                    <img id="qrModalImage" src="" alt="QR Code" class="qr-image">
                </div>
                <div class="qr-modal-actions">
                    <button onclick="downloadQRCode()" class="btn btn-primary">
                        <i class="fas fa-download"></i> Download QR Code
                    </button>
                    <button onclick="printQRCode()" class="btn btn-secondary">
                        <i class="fas fa-print"></i> Print QR Code
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="assets/js/script.js"></script>
</body>
</html>
