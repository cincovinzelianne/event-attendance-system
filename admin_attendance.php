<?php
require_once 'includes/admin_auth.php';
require_once 'config/database.php';
require_once 'includes/notifications.php';

$adminAuth = new AdminAuth();
$currentAdmin = $adminAuth->requireAuth();

function h($v){ return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8'); }

try {
    $database = new Database();
    $db = $database->getConnection();
} catch (Exception $e) {
    http_response_code(500);
    echo 'DB Error';
    exit;
}

// AJAX: Handle lock/unlock attendance
if (isset($_GET['action']) && $_GET['action'] === 'toggle_lock' && isset($_GET['event_id'])) {
    $eventId = (int)$_GET['event_id'];
    try {
        // Check if attendance_locked column exists, if not create it
        $checkCol = $db->query("SHOW COLUMNS FROM events LIKE 'attendance_locked'")->fetch();
        if (!$checkCol) {
            $db->exec("ALTER TABLE events ADD COLUMN attendance_locked TINYINT(1) DEFAULT 0");
        }
        
        // Toggle lock status
        $currentLock = $db->prepare("SELECT attendance_locked FROM events WHERE id = ?");
        $currentLock->execute([$eventId]);
        $lockStatus = $currentLock->fetchColumn();
        $newStatus = $lockStatus ? 0 : 1;
        
        $update = $db->prepare("UPDATE events SET attendance_locked = ? WHERE id = ?");
        $update->execute([$newStatus, $eventId]);
        
        echo json_encode(['success' => true, 'locked' => (bool)$newStatus]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// AJAX: Handle reset attendance
if (isset($_GET['action']) && $_GET['action'] === 'reset_attendance' && isset($_GET['event_id'])) {
    header('Content-Type: application/json');
    $eventId = (int)$_GET['event_id'];
    try {
        // Delete all attendance records for this event
        $delete = $db->prepare("DELETE FROM attendance WHERE event_id = ?");
        $delete->execute([$eventId]);
        
        echo json_encode(['success' => true, 'message' => 'All attendance records have been reset for this event.']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// AJAX: Handle attendance edit
if (isset($_POST['action']) && $_POST['action'] === 'update_attendance' && isset($_POST['event_id'])) {
    $eventId = (int)$_POST['event_id'];
    $studentId = isset($_POST['student_id']) ? (int)$_POST['student_id'] : null;
    $field = isset($_POST['field']) ? $_POST['field'] : '';
    $value = isset($_POST['value']) ? trim($_POST['value']) : '';
    
    try {
        // Check if attendance is locked
        $checkCol = $db->query("SHOW COLUMNS FROM events LIKE 'attendance_locked'")->fetch();
        if ($checkCol) {
            $lockCheck = $db->prepare("SELECT attendance_locked FROM events WHERE id = ?");
            $lockCheck->execute([$eventId]);
            if ($lockCheck->fetchColumn()) {
                echo json_encode(['success' => false, 'error' => 'Attendance sheet is locked. Unlock it first to make edits.']);
                exit;
            }
        }
        
        if (!$studentId || !in_array($field, ['attendance_time'])) {
            echo json_encode(['success' => false, 'error' => 'Invalid parameters']);
            exit;
        }
        
        // Ensure an attendance row exists for this student+event.
        // Many students won't have a row until they scan QR; manual edits should create it.
        $existsStmt = $db->prepare("SELECT id FROM attendance WHERE student_id = ? AND event_id = ? LIMIT 1");
        $existsStmt->execute([$studentId, $eventId]);
        $attId = $existsStmt->fetchColumn();
        if (!$attId) {
            $insStmt = $db->prepare("INSERT INTO attendance (student_id, event_id, status) VALUES (?, ?, 'present')");
            $insStmt->execute([$studentId, $eventId]);
        }

        $timeValue = null;
        if ($value !== '') {
            // Accept time-only (HH:MM or HH:MM:SS) and preserve existing date part.
            // If there is no existing value, fall back to the event date.
            if (preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $value)) {
                $currentStmt = $db->prepare("SELECT {$field} FROM attendance WHERE student_id = ? AND event_id = ? LIMIT 1");
                $currentStmt->execute([$studentId, $eventId]);
                $currentVal = $currentStmt->fetchColumn();

                $baseDate = null;
                if (!empty($currentVal)) {
                    $baseDate = date('Y-m-d', strtotime($currentVal));
                } else {
                    $evDateStmt = $db->prepare("SELECT event_date FROM events WHERE id = ? LIMIT 1");
                    $evDateStmt->execute([$eventId]);
                    $evDate = $evDateStmt->fetchColumn();
                    $baseDate = $evDate ? date('Y-m-d', strtotime($evDate)) : date('Y-m-d');
                }

                // Normalize seconds
                $timePart = strlen($value) === 5 ? ($value . ':00') : $value;
                $timeValue = $baseDate . ' ' . $timePart;
            } else {
                // Backward-compatible: allow full datetime strings too
                $ts = strtotime($value);
                if ($ts === false) {
                    echo json_encode(['success' => false, 'error' => 'Invalid time format']);
                    exit;
                }
                $timeValue = date('Y-m-d H:i:s', $ts);
            }
        }

        // Update attendance field
        $updateSql = "UPDATE attendance SET {$field} = ? WHERE student_id = ? AND event_id = ?";
        $updateStmt = $db->prepare($updateSql);
        $updateStmt->execute([$timeValue, $studentId, $eventId]);

        if ($timeValue !== null) {
            notifyStudentAttendanceAction($db, $studentId, $eventId, $field, $timeValue);
        }
        
        echo json_encode(['success' => true, 'message' => 'Attendance updated successfully']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// AJAX: load attendance sheet for an event
if (isset($_GET['ajax']) && isset($_GET['event_id'])) {
    $eventId = (int)$_GET['event_id'];

    // Fetch event
    try {
        $checkCol = $db->query("SHOW COLUMNS FROM events LIKE 'attendance_locked'")->fetch();
        if ($checkCol) {
            $eventStmt = $db->prepare("SELECT id, event_name, event_date, event_time, COALESCE(attendance_locked, 0) as attendance_locked FROM events WHERE id = ?");
        } else {
    $eventStmt = $db->prepare("SELECT id, event_name, event_date, event_time FROM events WHERE id = ?");
        }
    } catch (Exception $e) {
        $eventStmt = $db->prepare("SELECT id, event_name, event_date, event_time FROM events WHERE id = ?");
    }
    
    $eventStmt->execute([$eventId]);
    $event = $eventStmt->fetch(PDO::FETCH_ASSOC);
    if (!$event) {
        http_response_code(404);
        echo '<div class="sheet-error">Event not found.</div>';
        exit;
    }

    $isLocked = isset($event['attendance_locked']) && $event['attendance_locked'] == 1;
    $editMode = isset($_GET['edit']) && $_GET['edit'] == '1';

    // Check if event date has passed (only check date, not time - events stay open for entire day)
    $eventDate = $event['event_date'];
    $today = date('Y-m-d');
    $isPast = $eventDate < $today;
    
    // Only show closed message if date is past AND not manually locked (lock is handled separately in header)
    if ($isPast && !$editMode) {
        ob_start();
        ?>
        <div class="sheet-closed">
            <div class="closed-icon">
                <i class="fas fa-lock"></i>
            </div>
            <h3><?= h($event['event_name']) ?></h3>
            <div class="closed-message">
                <p><strong>Attendance is already closed.</strong></p>
                <p class="closed-date">Event Date: <?= h(date('M d, Y', strtotime($event['event_date']))) ?> at <?= h(date('g:i A', strtotime($event['event_time']))) ?></p>
            </div>
        </div>
        <style>
            .sheet-closed { text-align:center; padding:40px 20px; background:#fff; border-radius:10px; }
            .closed-icon { font-size:48px; color:#e53e3e; margin-bottom:16px; }
            .sheet-closed h3 { color:#2d3748; margin:0 0 16px 0; }
            .closed-message { color:#4a5568; }
            .closed-message p { margin:8px 0; }
            .closed-message strong { color:#e53e3e; font-size:18px; }
            .closed-date { color:#718096; font-size:14px; }
        </style>
        <?php
        echo ob_get_clean();
        exit;
    }

    // Get selected departments for event (priority filtering)
    $selectedDepartments = [];
    try {
        $hasDeptPivot = $db->query("SHOW TABLES LIKE 'event_departments'")->fetch();
        if ($hasDeptPivot) {
            $hasDeptEventId = $db->query("SHOW COLUMNS FROM event_departments LIKE 'event_id'")->fetch();
            $hasDeptName = $db->query("SHOW COLUMNS FROM event_departments LIKE 'department'")->fetch();
            if ($hasDeptEventId && $hasDeptName) {
                $deptStmt = $db->prepare("SELECT department FROM event_departments WHERE event_id = ?");
                $deptStmt->execute([$eventId]);
                $selectedDepartments = $deptStmt->fetchAll(PDO::FETCH_COLUMN);
            }
        }
    } catch (Exception $e) { /* ignore */ }

    // Get invited courses for event if pivot exists (secondary filtering)
    $courseFilterNames = [];
    try {
        $hasPivot = $db->query("SHOW TABLES LIKE 'event_courses'")->fetch();
        if ($hasPivot) {
            $hasEventId = $db->query("SHOW COLUMNS FROM event_courses LIKE 'event_id'")->fetch();
            $hasCourseId = $db->query("SHOW COLUMNS FROM event_courses LIKE 'course_id'")->fetch();
            if ($hasEventId && $hasCourseId) {
                $courseIdsStmt = $db->prepare("SELECT course_id FROM event_courses WHERE event_id = ?");
                $courseIdsStmt->execute([$eventId]);
                $courseIds = $courseIdsStmt->fetchAll(PDO::FETCH_COLUMN);
                if ($courseIds) {
                    $in = implode(',', array_map('intval', $courseIds));
                    $names = $db->query("SELECT id, course_name FROM courses WHERE id IN ($in)")->fetchAll(PDO::FETCH_KEY_PAIR);
                    foreach ($courseIds as $cid) {
                        if (isset($names[$cid])) { $courseFilterNames[] = $names[$cid]; }
                    }
                }
            }
        }
    } catch (Exception $e) { /* ignore */ }

    // Build query for students, grouped by department-course-major and year_level
    // Priority: Filter by departments if selected, otherwise filter by courses if selected
    // Note: students.course may contain "course_name" or "course_name - major", so we need flexible matching
    $params = [];
    $where = 's.is_active = 1';
    
    if (!empty($selectedDepartments)) {
        // Filter by selected departments
        // Use a subquery to get course names that belong to selected departments
        $in = implode(',', array_fill(0, count($selectedDepartments), '?'));
        $where .= " AND EXISTS (
            SELECT 1 FROM courses c2 
            WHERE (c2.course_name = s.course OR s.course LIKE CONCAT(c2.course_name, ' - %'))
            AND c2.department IN ($in)
        )";
        $params = array_merge($params, $selectedDepartments);
        
        // If courses are also selected, further filter by those courses
        if (!empty($courseFilterNames)) {
            $courseConditions = [];
            foreach ($courseFilterNames as $courseName) {
                $courseConditions[] = "(s.course = ? OR s.course LIKE ?)";
                $params[] = $courseName;
                $params[] = $courseName . ' - %';
            }
            $where .= " AND (" . implode(' OR ', $courseConditions) . ")";
        }
    } elseif (!empty($courseFilterNames)) {
        // Fallback to course filtering if no departments selected
        $courseConditions = [];
        foreach ($courseFilterNames as $courseName) {
            $courseConditions[] = "(s.course = ? OR s.course LIKE ?)";
            $params[] = $courseName;
            $params[] = $courseName . ' - %';
        }
        $where .= " AND (" . implode(' OR ', $courseConditions) . ")";
    }

    $sql = "SELECT s.id AS student_pk, s.student_id, s.first_name, s.last_name, s.course AS student_course, s.year_level,
                   c.department, c.course_name, c.major,
                   a.status, a.attendance_time
            FROM students s
            LEFT JOIN courses c ON (c.course_name = s.course OR s.course LIKE CONCAT(c.course_name, ' - %'))
            LEFT JOIN attendance a ON a.student_id = s.id AND a.event_id = ?
            WHERE $where
            ORDER BY c.department, c.course_name, c.major, s.year_level, s.last_name, s.first_name";
    array_unshift($params, $eventId);

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fallback: if no rows matched (e.g., course names don't align or no active flag),
    // load all students to ensure the sheet shows data and can be verified.
    // But still respect department filtering if set
    if (!$rows) {
        $fallbackWhere = 's.is_active = 1';
        $fallbackParams = [];
        
        if (!empty($selectedDepartments)) {
            $in = implode(',', array_fill(0, count($selectedDepartments), '?'));
            $fallbackWhere .= " AND EXISTS (
                SELECT 1 FROM courses c2 
                WHERE (c2.course_name = s.course OR s.course LIKE CONCAT(c2.course_name, ' - %'))
                AND c2.department IN ($in)
            )";
            $fallbackParams = array_merge($fallbackParams, $selectedDepartments);
        }
        
        $fallbackSql = "SELECT s.id AS student_pk, s.student_id, s.first_name, s.last_name, s.course AS student_course, s.year_level,
                               c.department, c.course_name, c.major,
                               a.status, a.attendance_time
                        FROM students s
                        LEFT JOIN courses c ON (c.course_name = s.course OR s.course LIKE CONCAT(c.course_name, ' - %'))
                        LEFT JOIN attendance a ON a.student_id = s.id AND a.event_id = ?
                        WHERE $fallbackWhere
                        ORDER BY c.department, c.course_name, c.major, s.year_level, s.last_name, s.first_name";
        array_unshift($fallbackParams, $eventId);
        $fallbackStmt = $db->prepare($fallbackSql);
        $fallbackStmt->execute($fallbackParams);
        $rows = $fallbackStmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Final fallback: Show students who have scanned for this event, plus all active students
        if (!$rows) {
            $finalFallbackSql = "SELECT s.id AS student_pk, s.student_id, s.first_name, s.last_name, s.course AS student_course, s.year_level,
                               c.department, c.course_name, c.major,
                               a.status, a.attendance_time
                        FROM students s
                        LEFT JOIN courses c ON (c.course_name = s.course OR s.course LIKE CONCAT(c.course_name, ' - %'))
                        LEFT JOIN attendance a ON a.student_id = s.id AND a.event_id = ?
                        ORDER BY a.attendance_time DESC, c.department, c.course_name, c.major, s.year_level, s.last_name, s.first_name";
            $finalFallbackStmt = $db->prepare($finalFallbackSql);
            $finalFallbackStmt->execute([$eventId]);
            $rows = $finalFallbackStmt->fetchAll(PDO::FETCH_ASSOC);
        }
    }

    // Render grouped table
    ob_start();
    ?>
    <div class="sheet-header">
        <div class="sheet-title">
            <h3><?= h($event['event_name']) ?></h3>
            <div class="sheet-meta">
                <span><i class="fas fa-calendar"></i> <?= h(date('M d, Y', strtotime($event['event_date']))) ?></span>
                <span><i class="fas fa-clock"></i> <?= h(date('g:i A', strtotime($event['event_time']))) ?></span>
                <?php if ($isLocked): ?>
                    <span class="lock-status locked"><i class="fas fa-lock"></i> Locked</span>
                <?php else: ?>
                    <span class="lock-status unlocked"><i class="fas fa-unlock"></i> Unlocked</span>
                <?php endif; ?>
            </div>
        </div>
        <div class="sheet-actions">
            <button class="btn btn-toggle-lock" onclick="toggleAttendanceLock(<?= $eventId ?>, <?= $isLocked ? 'true' : 'false' ?>)">
                <i class="fas <?= $isLocked ? 'fa-unlock' : 'fa-lock' ?>"></i> 
                <?= $isLocked ? 'Unlock' : 'Lock' ?> Attendance
            </button>
            <button class="btn btn-danger" onclick="resetAllAttendance(<?= $eventId ?>, '<?= h($event['event_name']) ?>')">
                <i class="fas fa-redo"></i> Reset All Attendance
            </button>
            <button class="btn btn-success" onclick="exportToPDF('<?= h($event['event_name']) ?>')">
                <i class="fas fa-file-pdf"></i> Export to PDF
            </button>
            <?php if (!$isLocked): ?>
            <button class="btn btn-edit" id="edit-mode-btn" onclick="toggleEditMode()">
                <i class="fas fa-edit"></i> <span id="edit-btn-text">Edit</span>
            </button>
            <?php if ($editMode): ?>
            <button class="btn btn-save" id="save-changes-btn" type="button" onclick="savePendingAttendanceChanges()" disabled>
                <i class="fas fa-save"></i> <span id="save-btn-text">Save</span>
            </button>
            <span class="save-status" id="save-status" aria-live="polite"></span>
            <?php endif; ?>
            <?php endif; ?>
            <button class="btn btn-secondary" onclick="window.print()"><i class="fas fa-print"></i> Print</button>
        </div>
    </div>
    
    <!-- Filters Section -->
    <div class="sheet-filters">
        <div class="filter-group">
            <label for="filter-department"><i class="fas fa-filter"></i> Filter by Department:</label>
            <select id="filter-department" class="filter-select" onchange="handleYearLevelChange()">
                <option value="">All Departments</option>
            </select>
        </div>
        <div class="filter-group">
            <label for="filter-year-level"><i class="fas fa-graduation-cap"></i> Choose Year Level:</label>
            <select id="filter-year-level" class="filter-select" onchange="handleYearLevelChange()">
                <option value="">Select Year Level</option>
            </select>
        </div>
        <div class="filter-group">
            <label for="filter-section"><i class="fas fa-layer-group"></i> Choose Section:</label>
            <select id="filter-section" class="filter-select" onchange="applyFilters()">
                <option value="">All Sections</option>
            </select>
        </div>
        <button class="btn btn-secondary" onclick="clearFilters()"><i class="fas fa-redo"></i> Clear Filters</button>
    </div>
    <div id="filter-guidance" class="filter-guidance">
        Choose a year level first, then narrow the sheet by section if needed.
    </div>
    
    <div class="attendance-sheet">
        <table class="sheet-table">
            <thead>
                <tr>
                    <th style="width:140px;">Student ID</th>
                    <th>Name</th>
                    <th style="width:220px;">Department - Course</th>
                    <th style="width:120px;">Year Level</th>
                    <th style="width:120px;">Section</th>
                    <th style="width:140px;">Check In Time</th>
                    <th style="width:110px;">Status</th>
                    <th style="width:160px;">Signature</th>
                </tr>
            </thead>
            <tbody>
            <?php
            $currentGroup = '';
            $currentYearLevel = '';
            foreach ($rows as $r) {
                $group = trim(($r['department'] ?: 'Unknown Dept') . ' — ' . ($r['course_name'] ?: $r['student_course'] ?: 'Unknown Course') . (!empty($r['major']) ? (' (' . $r['major'] . ')') : ''));
                $yearLevel = trim((string)($r['year_level'] ?: 'N/A'));
                $section = 'N/A';
                $studentCourse = trim((string)($r['student_course'] ?: ''));
                if ($studentCourse !== '' && preg_match('/\[([^\]]+)\]\s*$/', $studentCourse, $sectionMatch)) {
                    $section = trim($sectionMatch[1]);
                } elseif ($studentCourse !== '' && strpos($studentCourse, ' - ') !== false) {
                    $courseParts = explode(' - ', $studentCourse, 2);
                    $sectionSuffix = trim($courseParts[1] ?? '');
                    if ($sectionSuffix !== '') {
                        $section = $sectionSuffix;
                    }
                }

                if ($group !== $currentGroup) {
                    $currentGroup = $group; $currentYearLevel = '';
                    echo '<tr class="group-row"><td colspan="8">' . h($group) . '</td></tr>';
                }
                if ($yearLevel !== $currentYearLevel) {
                    $currentYearLevel = $yearLevel;
                    echo '<tr class="section-row"><td colspan="8">Year Level: ' . h($yearLevel) . '</td></tr>';
                }

                $status = $r['status'] ?: '';
                
                // Calculate lateness
                $timeInClass = '';
                if ($r['attendance_time']) {
                    $eventDateTime = strtotime($event['event_date'] . ' ' . $event['event_time']);
                    $timeInDateTime = strtotime($r['attendance_time']);
                    $minutesLate = ($timeInDateTime - $eventDateTime) / 60; // Difference in minutes
                    
                    if ($minutesLate >= 31) {
                        $timeInClass = 'time-late-severe'; // 31+ minutes late - red
                    } elseif ($minutesLate >= 15) {
                        $timeInClass = 'time-late-moderate'; // 15-30 minutes late - light red
                    }
                }
                
                // Format time for editing
                $timeInValue = $r['attendance_time'] ? date('H:i', strtotime($r['attendance_time'])) : '';
                ?>
                <tr class="student-row"
                    data-department="<?= h(trim((string)($r['department'] ?: 'Unknown Dept'))) ?>"
                    data-year-level="<?= h($yearLevel) ?>"
                    data-section="<?= h($section) ?>">
                    <td><?= h($r['student_id']) ?></td>
                    <td><?= h($r['last_name'] . ', ' . $r['first_name']) ?></td>
                    <td><?= h($group) ?></td>
                    <td><?= h($yearLevel) ?></td>
                    <td><?= h($section) ?></td>
                    
                    <td class="write-box time-in-cell <?= $timeInClass ?> <?= $editMode ? 'editable' : '' ?>" 
                        data-student-id="<?= $r['student_pk'] ?>" 
                        data-field="attendance_time"
                        data-event-id="<?= $eventId ?>">
                        <?php if ($editMode): ?>
                            <input type="time" 
                                   class="attendance-input" 
                                   value="<?= h($timeInValue) ?>"
                                   data-original="<?= h($timeInValue) ?>">
                        <?php else: ?>
                            <?= $r['attendance_time'] ? h(date('g:i A', strtotime($r['attendance_time']))) : '' ?>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?= $status ? h($status) : '' ?>
                    </td>
                    <td class="write-box"></td>
                </tr>
                <?php
            }
            if (!$rows) {
                echo '<tr><td colspan="7" style="text-align:center; color:#718096; padding:24px;">No students found for this event.</td></tr>';
            }
            ?>
            </tbody>
        </table>
    </div>
    <style>
        .sheet-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:12px; flex-wrap: wrap; gap: 12px; }
        .sheet-title h3 { margin:0; }
        .sheet-meta { display:flex; gap:14px; color:#4a5568; font-size:14px; flex-wrap: wrap; }
        .lock-status { padding: 4px 12px; border-radius: 12px; font-size: 12px; font-weight: 600; display: inline-flex; align-items: center; gap: 6px; }
        .lock-status.locked { background: #fee2e2; color: #991b1b; }
        .lock-status.unlocked { background: #d1fae5; color: #065f46; }
        .sheet-actions { display: flex; gap: 10px; flex-wrap: wrap; }
        .btn { padding: 10px 20px; border: none; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; transition: all 0.3s ease; }
        .btn-success {
        background: linear-gradient(135deg, #10b981, #059669);
        color: white;
        box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
    }
    
    .btn-success:hover {
        background: linear-gradient(135deg, #059669, #047857);
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(16, 185, 129, 0.4);
    }
    
    .sheet-filters {
        background: linear-gradient(135deg, #f0f9ff, #e0f2fe);
        border: 2px solid #0ea5e9;
        border-radius: 10px;
        padding: 20px;
        margin-bottom: 20px;
        display: flex;
        gap: 20px;
        flex-wrap: wrap;
        align-items: flex-end;
    }
    
    .filter-group {
        display: flex;
        flex-direction: column;
        gap: 8px;
        flex: 1;
        min-width: 200px;
    }
    
    .filter-group label {
        font-weight: 600;
        color: #0c4a6e;
        font-size: 14px;
    }
    
    .filter-select {
        padding: 10px 14px;
        border: 2px solid #0ea5e9;
        border-radius: 8px;
        font-size: 14px;
        background: white;
        color: #1e293b;
        cursor: pointer;
        transition: all 0.3s;
    }
    
    .filter-select:hover {
        border-color: #0284c7;
        box-shadow: 0 2px 8px rgba(2, 132, 199, 0.2);
    }
    
    .filter-select:focus {
        outline: none;
        border-color: #0284c7;
        box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.1);
    }
    
    .filter-guidance {
        margin: -8px 0 16px;
        padding: 12px 14px;
        border-radius: 10px;
        font-size: 14px;
        font-weight: 500;
    }
    
    .filter-guidance.is-muted {
        background: #eff6ff;
        color: #1d4ed8;
    }
    
    .filter-guidance.is-warning {
        background: #fff7ed;
        color: #c2410c;
    }
    
    .btn-danger {
        background: linear-gradient(135deg, #ef4444, #dc2626);
        color: white;
        box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
    }
    
    .btn-danger:hover {
        background: linear-gradient(135deg, #dc2626, #b91c1c);
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(239, 68, 68, 0.4);
    }
    
    .btn-toggle-lock { background: linear-gradient(135deg, #f59e0b, #d97706); color: white; }
        .btn-toggle-lock:hover { background: linear-gradient(135deg, #d97706, #b45309); transform: translateY(-2px); }
        .btn-edit { background: linear-gradient(135deg, #8b5cf6, #7c3aed); color: white; }
        .btn-edit:hover { background: linear-gradient(135deg, #7c3aed, #6d28d9); transform: translateY(-2px); }
        .btn-edit.active { background: linear-gradient(135deg, #10b981, #059669); }
        .btn-save { background: linear-gradient(135deg, #10b981, #059669); color: white; }
        .btn-save:hover { background: linear-gradient(135deg, #059669, #047857); transform: translateY(-2px); }
        .btn-save:disabled { opacity: 0.55; cursor: not-allowed; transform: none; }
        .save-status { font-size: 12px; font-weight: 700; color: #065f46; align-self: center; }
        .btn-secondary { background: #6b7280; color: white; }
        .btn-secondary:hover { background: #4b5563; transform: translateY(-2px); }
        .sheet-table { width:100%; border-collapse: collapse; background:#fff; }
        .sheet-table th, .sheet-table td { border:1px solid #e2e8f0; padding:8px 10px; font-size:14px; }
        .group-row td { background:#f7fafc; font-weight:600; color:#2d3748; }
        .section-row td { background:#edf2f7; font-weight:600; color:#2d3748; }
        .att-status { display:none; }
        .write-box { background:#fff; }
        .write-box.editable { background: #fef3c7; }
        .attendance-input { width: 100%; border: 2px solid #8b5cf6; border-radius: 4px; padding: 6px; font-size: 13px; font-family: 'Inter', sans-serif; }
        .attendance-input:focus { outline: none; border-color: #7c3aed; box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.1); }
        .attendance-input.pending { border-color: #f59e0b !important; box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.12); }
        .attendance-input.saved { border-color: #10b981 !important; }
        .box-input { width:100%; border:none; outline:none; font-size:14px; padding:4px; }
        .box-input::placeholder { color:#cbd5e0; }
        
        /* Time In Late Arrival Styles */
        .time-in-cell {
            position: relative;
        }
        
        .time-in-cell.time-late-moderate {
            background: linear-gradient(135deg, #fee2e2, #fecaca) !important;
            color: #991b1b;
            font-weight: 600;
        }
        
        .time-in-cell.time-late-moderate::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 4px;
            background: #f87171;
        }
        
        .time-in-cell.time-late-severe {
            background: linear-gradient(135deg, #fee2e2, #fca5a5) !important;
            color: #7f1d1d;
            font-weight: 700;
        }
        
        .time-in-cell.time-late-severe::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 4px;
            background: #ef4444;
        }
        
        /* Print Styles */
        @media print { 
            .box-input { border:none; } 
            .time-in-cell.time-late-moderate,
            .time-in-cell.time-late-severe {
                background: #fee2e2 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
        
        @media print { 
            .admin-sidebar, .admin-header, .btn, .event-grid { display:none !important; } 
            body { background:#fff; } 
            .admin-content { margin:0; } 
        }
    </style>
    <?php
    echo ob_get_clean();
    exit;
}

// Fetch events for initial list (check if attendance_locked column exists)
try {
    $checkCol = $db->query("SHOW COLUMNS FROM events LIKE 'attendance_locked'")->fetch();
    if ($checkCol) {
        $events = $db->query("SELECT id, event_name, event_date, event_time, location, COALESCE(attendance_locked, 0) as attendance_locked FROM events WHERE is_active = 1 ORDER BY event_date DESC, event_time DESC")
                     ->fetchAll(PDO::FETCH_ASSOC);
    } else {
$events = $db->query("SELECT id, event_name, event_date, event_time, location FROM events WHERE is_active = 1 ORDER BY event_date DESC, event_time DESC")
             ->fetchAll(PDO::FETCH_ASSOC);
        // Add default lock status
        foreach ($events as &$ev) {
            $ev['attendance_locked'] = 0;
        }
    }
} catch (Exception $e) {
    $events = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance - Admin Panel</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.31/jspdf.plugin.autotable.min.js"></script>
</head>
<body class="admin-body">
    <?php include 'includes/admin_sidebar.php'; ?>

    <div class="admin-content">
        <div class="admin-header">
            <div class="header-left">
                <div class="breadcrumb">
                    <span class="crumb-root"><i class="fas fa-house"></i> Home</span>
                    <span class="crumb-separator">/</span>
                    <span class="crumb-current">Attendance</span>
                </div>
                <h1 class="page-title-heading">
                    <i class="fas fa-clipboard-check"></i>
                    <span>Attendance</span>
                </h1>
            </div>
            <div class="header-right">
                <div class="search-wrapper">
                    <i class="fas fa-magnifying-glass"></i>
                    <input type="text" placeholder="Search events, students, or sheets..." />
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
                            <span><?php echo strtoupper(substr($currentAdmin['full_name'] ?? 'A', 0, 1)); ?></span>
                        </div>
                        <div class="profile-text">
                            <span class="profile-name"><?php echo htmlspecialchars($currentAdmin['full_name'] ?? 'Admin'); ?></span>
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
            </div>
        </div>
        <div class="admin-main">
            <div class="event-grid">
                <?php if (!$events): ?>
                    <div class="empty-state">
                        <i class="fas fa-calendar-times"></i>
                        <h3>No Events Available</h3>
                        <p>There are no events to display at this time.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($events as $ev): 
                        $evDate = $ev['event_date'];
                        $today = date('Y-m-d');
                        $isPast = $evDate < $today; // Only check date, not time - events stay open for the entire day
                        $isToday = $evDate == $today;
                        $isLocked = isset($ev['attendance_locked']) && $ev['attendance_locked'] == 1;
                        $statusClosed = $isPast || $isLocked;
                    ?>
                        <div class="event-card <?= $statusClosed ? 'event-closed' : 'event-open' ?>" data-event-id="<?= (int)$ev['id'] ?>">
                            <div class="event-status-badge <?= $statusClosed ? 'status-closed' : 'status-open' ?>">
                                <i class="fas <?= $statusClosed ? 'fa-lock' : 'fa-unlock' ?>"></i>
                                <span><?= $statusClosed ? ($isLocked ? 'Locked' : 'Closed') : ($isToday ? 'Today' : 'Open') ?></span>
                            </div>
                            <div class="event-content">
                                <h3 class="event-title"><?= h($ev['event_name']) ?></h3>
                                <div class="event-details">
                                    <div class="event-detail-item">
                                        <i class="fas fa-calendar-alt"></i>
                                        <span><?= h(date('M d, Y', strtotime($ev['event_date']))) ?></span>
                                    </div>
                                    <div class="event-detail-item">
                                        <i class="fas fa-clock"></i>
                                        <span><?= h(date('g:i A', strtotime($ev['event_time']))) ?></span>
                                    </div>
                                    <?php if (!empty($ev['location'])): ?>
                                    <div class="event-detail-item">
                                        <i class="fas fa-map-marker-alt"></i>
                                        <span><?= h($ev['location']) ?></span>
                                    </div>
                            <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div id="sheet-container" class="sheet-container">
                <div class="placeholder">
                    <i class="fas fa-hand-pointer"></i>
                    <p>Select an event to load its attendance sheet.</p>
                </div>
            </div>
        </div>
    </div>

    <style>
        /* Modern Typography & Base Styles */
        * {
            box-sizing: border-box;
        }
        
        .admin-body {
            background: radial-gradient(circle at top left, #eef2ff 0, #f6f8fc 40%, #f6f7fb 100%);
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            margin: 0;
            padding: 0;
            color: #1f2933;
        }
        
        /* Admin Content Area */
        .admin-content {
            margin-left: 280px;
            min-height: 100vh;
            background: transparent;
            transition: margin-left 0.25s ease;
        }
        
        /* Header Section - SaaS style */
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
            background: linear-gradient(135deg, rgba(248,250,252,0.94), rgba(238,242,255,0.96));
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
        
        .crumb-root i {
            margin-right: 4px;
        }
        
        .crumb-current {
            color: #4f46e5;
        }
        
        .page-title-heading {
            margin: 0;
            font-family: 'Poppins', system-ui, sans-serif;
            font-size: 22px;
            font-weight: 700;
            letter-spacing: -0.02em;
            color: #0f172a;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .page-title-heading i {
            color: #8b5cf6;
            font-size: 20px;
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
        
        .search-wrapper i {
            color: #94a3b8;
            font-size: 13px;
        }
        
        .search-wrapper input {
            border: none;
            outline: none;
            background: transparent;
            width: 100%;
            font-size: 12px;
            color: #0f172a;
            font-family: 'Inter', sans-serif;
        }
        
        .search-wrapper input::placeholder {
            color: #9ca3af;
        }
        
        .icon-btn {
            border: none;
            background: rgba(15,23,42,0.04);
            border-radius: 999px;
            width: 34px;
            height: 34px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #4b5563;
            cursor: pointer;
            transition: all 0.18s ease;
            position: relative;
        }
        
        .icon-btn:hover {
            background: rgba(129,140,248,0.14);
            color: #4f46e5;
            transform: translateY(-1px);
            box-shadow: 0 8px 18px rgba(129,140,248,0.28);
        }
        
        .badge-dot {
            position: absolute;
            top: 6px;
            right: 7px;
            width: 7px;
            height: 7px;
            border-radius: 999px;
            background: #ef4444;
            box-shadow: 0 0 0 4px rgba(248,113,113,0.5);
        }
        
        .collapse-toggle i {
            font-size: 12px;
        }
        
        .profile-menu {
            position: relative;
        }
        
        .profile-trigger {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 6px 10px 6px 6px;
            border-radius: 999px;
            border: 1px solid rgba(148,163,184,0.4);
            background: rgba(248,250,252,0.7);
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .profile-trigger:hover {
            background: #ffffff;
            box-shadow: 0 10px 24px rgba(15,23,42,0.18);
            transform: translateY(-1px);
        }
        
        .avatar-circle {
            width: 30px;
            height: 30px;
            border-radius: 999px;
            background: linear-gradient(135deg,#4f46e5,#7c3aed);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-weight: 700;
            font-size: 14px;
        }
        
        .profile-text {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
        }
        
        .profile-name {
            font-size: 12px;
            font-weight: 600;
            color: #0f172a;
        }
        
        .profile-role {
            font-size: 11px;
            color: #9ca3af;
        }
        
        .caret {
            font-size: 10px;
            color: #9ca3af;
        }
        
        .profile-dropdown {
            position: absolute;
            right: 0;
            top: 110%;
            background: #ffffff;
            border-radius: 14px;
            box-shadow: 0 18px 40px rgba(15,23,42,0.28);
            padding: 6px 6px;
            min-width: 180px;
            display: none;
            z-index: 150;
            border: 1px solid rgba(148,163,184,0.35);
        }
        
        .profile-dropdown a {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 10px;
            border-radius: 10px;
            font-size: 13px;
            color: #111827;
            text-decoration: none;
            transition: background 0.16s ease,color 0.16s ease;
        }
        
        .profile-dropdown a i {
            width: 16px;
            text-align: center;
            color: #6b7280;
        }
        
        .profile-dropdown a:hover {
            background: rgba(129,140,248,0.12);
            color: #4f46e5;
        }
        
        .profile-dropdown a.danger:hover {
            background: rgba(239,68,68,0.12);
            color: #b91c1c;
        }
        
        /* Main Content Area */
        .admin-main {
            padding: 24px 28px 32px;
            max-width: 1400px;
            margin: 0 auto;
        }
        
        /* Event Grid - 3x3 Layout */
        .event-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            grid-template-rows: repeat(3, auto);
            gap: 24px;
            margin-bottom: 32px;
        }
        
        /* Ensure grid doesn't exceed 9 items - wrap to new row */
        .event-grid > *:nth-child(n+10) {
            grid-column: 1 / -1;
        }
        
        /* Event Cards */
        .event-card {
            background: #ffffff;
            border-radius: 16px;
            padding: 24px;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            border: 1px solid #e8eaf0;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
            overflow: hidden;
        }
        
        .event-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #8b5cf6, #a78bfa);
            transform: scaleX(0);
            transition: transform 0.3s ease;
        }
        
        .event-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 24px rgba(139, 92, 246, 0.15);
            border-color: #c4b5fd;
        }
        
        .event-card:hover::before {
            transform: scaleX(1);
        }
        
        /* Open Events - Light Purple/White */
        .event-card.event-open {
            background: linear-gradient(135deg, #ffffff 0%, #faf5ff 100%);
        }
        
        /* Closed Events - Light Red/Beige */
        .event-card.event-closed {
            background: linear-gradient(135deg, #fff5f5 0%, #fef5e7 100%);
            opacity: 0.9;
        }
        
        .event-card.event-closed:hover {
            opacity: 1;
        }
        
        /* Status Badge */
        .event-status-badge {
            position: absolute;
            top: 16px;
            right: 16px;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }
        
        .event-status-badge i {
            font-size: 10px;
        }
        
        .status-open {
            background: linear-gradient(135deg, #10b981, #059669);
            color: #ffffff;
        }
        
        .status-closed {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: #ffffff;
        }
        
        /* Event Content */
        .event-content {
            padding-top: 8px;
        }
        
        .event-title {
            font-family: 'Poppins', sans-serif;
            font-size: 20px;
            font-weight: 600;
            color: #2d3748;
            margin: 0 0 20px 0;
            line-height: 1.4;
            padding-right: 80px;
        }
        
        .event-details {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        
        .event-detail-item {
            display: flex;
            align-items: center;
            gap: 10px;
            color: #4a5568;
            font-size: 14px;
            font-weight: 400;
        }
        
        .event-detail-item i {
            color: #8b5cf6;
            width: 16px;
            text-align: center;
            font-size: 14px;
        }
        
        .event-detail-item span {
            flex: 1;
        }
        
        /* Empty State */
        .empty-state {
            grid-column: 1 / -1;
            text-align: center;
            padding: 80px 20px;
            color: #718096;
        }
        
        .empty-state i {
            font-size: 64px;
            color: #cbd5e0;
            margin-bottom: 20px;
        }
        
        .empty-state h3 {
            font-family: 'Poppins', sans-serif;
            font-size: 24px;
            font-weight: 600;
            color: #4a5568;
            margin: 0 0 8px 0;
        }
        
        .empty-state p {
            font-size: 16px;
            color: #718096;
            margin: 0;
        }
        
        /* Sheet Container */
        .sheet-container {
            background: #ffffff;
            border: 1px solid #e8eaf0;
            border-radius: 16px;
            padding: 32px;
            min-height: 300px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
        }
        
        .placeholder {
            color: #718096;
            text-align: center;
            padding: 60px 20px;
        }
        
        .placeholder i {
            font-size: 48px;
            color: #cbd5e0;
            margin-bottom: 16px;
            display: block;
        }
        
        .placeholder p {
            font-size: 16px;
            color: #718096;
            margin: 0;
        }
        
        /* Responsive Design */
        @media (max-width: 1200px) {
            .admin-content {
                margin-left: 0;
            }
            
            .admin-main {
                padding: 20px 20px 26px;
            }
            
            .event-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 20px;
            }
        }
        
        @media (max-width: 900px) {
            .admin-content {
                margin-left: 0;
            }
        }
        
        @media (max-width: 768px) {
            .admin-header {
                padding: 14px 16px;
                flex-wrap: wrap;
            }
            
            .header-right {
                width: 100%;
                justify-content: flex-end;
            }
            
            .search-wrapper {
                flex: 1;
                min-width: 0;
            }
            
            .admin-main {
                padding: 18px 16px 24px;
            }
            
            .event-grid {
                grid-template-columns: 1fr;
                gap: 16px;
            }
            
            .event-title {
                font-size: 18px;
                padding-right: 0;
            }
            
            .event-status-badge {
                position: static;
                display: inline-flex;
                margin-bottom: 16px;
            }
        }
        
        @media (max-width: 480px) {
            .admin-main {
                padding: 16px 14px 20px;
            }
        }
    </style>

    <script>
    let editMode = false;
    let currentEventId = null;
    
    // Simple click handler function
    window.loadAttendanceSheet = function(eventId) {
        console.log('loadAttendanceSheet called with ID:', eventId);
        const container = document.getElementById('sheet-container');
        
        if (!eventId) {
            alert('Error: No event ID');
            return;
        }
        
        currentEventId = eventId;
        editMode = false;
        
        // Remove active state from all cards
        document.querySelectorAll('.event-card').forEach(c => c.classList.remove('active'));
        // Add active state to clicked card
        const activeCard = document.querySelector('[data-event-id="' + eventId + '"]');
        if (activeCard) {
            activeCard.classList.add('active');
        }
        
        // Show loading state
        container.innerHTML = '<div class="placeholder"><i class="fas fa-spinner fa-spin"></i><p>Loading attendance sheet…</p></div>';
        
        // Fetch attendance sheet
        fetch('admin_attendance.php?ajax=1&event_id=' + encodeURIComponent(eventId), { credentials: 'same-origin' })
            .then(r => r.text())
            .then(html => { 
                container.innerHTML = html;
                container.scrollIntoView({ behavior: 'smooth', block: 'start' });
                attachEditListeners();
                initializeFilters();
            })
            .catch(err => { 
                console.error('Fetch error:', err);
                container.innerHTML = '<div class="placeholder"><i class="fas fa-exclamation-circle"></i><p>Failed to load attendance sheet. Please try again.</p></div>'; 
            });
    };
    
    // Attach direct onclick handlers to cards
    document.addEventListener('DOMContentLoaded', function(){
        console.log('DOMContentLoaded fired');
        const cards = document.querySelectorAll('.event-card');
        console.log('Found ' + cards.length + ' cards');
        
        cards.forEach(card => {
            const id = card.getAttribute('data-event-id');
            console.log('Attaching handler to card:', id);
            card.onclick = function(e) {
                e.preventDefault();
                e.stopPropagation();
                loadAttendanceSheet(id);
                return false;
            };
        });
    });
    
    // Toggle attendance lock
    function toggleAttendanceLock(eventId, isLocked) {
        if (!confirm(isLocked ? 'Are you sure you want to unlock this attendance sheet?' : 'Are you sure you want to lock this attendance sheet? Locked sheets cannot be edited.')) {
            return;
        }
        
        // Exit edit mode if locking
        if (!isLocked) {
            editMode = false;
        }
        
        fetch('admin_attendance.php?action=toggle_lock&event_id=' + encodeURIComponent(eventId), { credentials: 'same-origin' })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    // Reload the attendance sheet
                    const container = document.getElementById('sheet-container');
                    container.innerHTML = '<div class="placeholder"><i class="fas fa-spinner fa-spin"></i><p>Reloading attendance sheet…</p></div>';
                    fetch('admin_attendance.php?ajax=1&event_id=' + encodeURIComponent(eventId) + (editMode && !data.locked ? '&edit=1' : ''), { credentials: 'same-origin' })
                        .then(r => r.text())
                        .then(html => { 
                            container.innerHTML = html;
                            attachEditListeners();
                            
                            // Update edit button if it exists
                            const editBtn = document.getElementById('edit-mode-btn');
                            const editBtnText = document.getElementById('edit-btn-text');
                            if (editBtn) {
                                if (data.locked) {
                                    editBtn.style.display = 'none';
                                } else {
                                    editBtn.style.display = 'inline-flex';
                                    if (editMode) {
                                        editBtn.classList.add('active');
                                        if (editBtnText) editBtnText.textContent = 'Exit Edit';
                                    } else {
                                        editBtn.classList.remove('active');
                                        if (editBtnText) editBtnText.textContent = 'Edit';
                                    }
                                }
                            }
                        });
                } else {
                    alert('Error: ' + (data.error || 'Failed to toggle lock status'));
                }
            })
            .catch(() => alert('Failed to toggle lock status'));
    }
    
    // Reset all attendance for an event
    function resetAllAttendance(eventId, eventName) {
        if (!confirm(`⚠️ Are you sure you want to RESET ALL attendance records for "${eventName}"?\n\nThis action cannot be undone. All check-in times and attendance status will be permanently deleted.`)) {
            return;
        }
        
        if (!confirm('🔴 CONFIRM RESET:\n\nThis will permanently delete ALL attendance records for this event. Click OK to proceed.')) {
            return;
        }
        
        const resetUrl = 'admin_attendance.php?action=reset_attendance&event_id=' + encodeURIComponent(eventId);
        console.log('Resetting attendance with URL:', resetUrl);
        
        fetch(resetUrl, { credentials: 'same-origin' })
            .then(r => {
                console.log('Reset response status:', r.status);
                return r.json();
            })
            .then(data => {
                console.log('Reset response data:', data);
                if (data.success) {
                    alert('✓ Attendance has been reset successfully.\n\nAll records for this event have been cleared.');
                    // Reload the attendance sheet
                    const container = document.getElementById('sheet-container');
                    container.innerHTML = '<div class="placeholder"><i class="fas fa-spinner fa-spin"></i><p>Reloading attendance sheet…</p></div>';
                    fetch('admin_attendance.php?ajax=1&event_id=' + encodeURIComponent(eventId), { credentials: 'same-origin' })
                        .then(r => r.text())
                        .then(html => { 
                            container.innerHTML = html;
                            attachEditListeners();
                            initializeFilters();
                            initializeFilters();
                        });
                } else {
                    alert('Error: ' + (data.error || 'Failed to reset attendance'));
                }
            })
            .catch(err => {
                console.error('Reset error:', err);
                alert('Failed to reset attendance: ' + err.message);
            });
    }
    
    // Toggle edit mode
    function toggleEditMode() {
        if (!currentEventId) {
            alert('Please select an event first');
            return;
        }
        
        editMode = !editMode;
        const container = document.getElementById('sheet-container');
        const editBtn = document.getElementById('edit-mode-btn');
        const editBtnText = document.getElementById('edit-btn-text');
        
        container.innerHTML = '<div class="placeholder"><i class="fas fa-spinner fa-spin"></i><p>Loading attendance sheet…</p></div>';
        
        fetch('admin_attendance.php?ajax=1&event_id=' + encodeURIComponent(currentEventId) + (editMode ? '&edit=1' : ''), { credentials: 'same-origin' })
            .then(r => r.text())
            .then(html => { 
                container.innerHTML = html;
                attachEditListeners();
                
                // Update edit button state
                if (editBtn) {
                    if (editMode) {
                        editBtn.classList.add('active');
                        if (editBtnText) editBtnText.textContent = 'Exit Edit';
                    } else {
                        editBtn.classList.remove('active');
                        if (editBtnText) editBtnText.textContent = 'Edit';
                    }
                }
            })
            .catch(() => alert('Failed to toggle edit mode'));
    }
    
    function setSaveStatus(message, type) {
        const el = document.getElementById('save-status');
        if (!el) return;
        el.textContent = message || '';
        if (type === 'error') {
            el.style.color = '#b91c1c';
        } else if (type === 'saving') {
            el.style.color = '#7c3aed';
        } else {
            el.style.color = '#065f46';
        }
    }

    function updateSaveButtonState() {
        const btn = document.getElementById('save-changes-btn');
        if (!btn) return;
        const pending = document.querySelectorAll('.attendance-input.pending');
        btn.disabled = pending.length === 0;
    }

    async function savePendingAttendanceChanges() {
        const btn = document.getElementById('save-changes-btn');
        const saveBtnText = document.getElementById('save-btn-text');
        const pendingInputs = Array.from(document.querySelectorAll('.attendance-input.pending'));
        if (!pendingInputs.length) {
            setSaveStatus('No changes to save.', 'info');
            updateSaveButtonState();
            return;
        }

        if (btn) btn.disabled = true;
        if (saveBtnText) saveBtnText.textContent = 'Saving...';
        setSaveStatus('Saving changes...', 'saving');

        let failed = 0;
        for (const input of pendingInputs) {
            const cell = input.closest('.write-box');
            if (!cell) continue;
            const studentId = cell.getAttribute('data-student-id');
            const field = cell.getAttribute('data-field');
            const eventId = cell.getAttribute('data-event-id');
            const value = input.value;

            const formData = new FormData();
            formData.append('action', 'update_attendance');
            formData.append('event_id', eventId);
            formData.append('student_id', studentId);
            formData.append('field', field);
            formData.append('value', value);

            try {
                const resp = await fetch('admin_attendance.php', {
                    method: 'POST',
                    credentials: 'same-origin',
                    body: formData
                });
                const data = await resp.json();
                if (data && data.success) {
                    input.classList.remove('pending');
                    input.classList.add('saved');
                    input.setAttribute('data-original', value);
                    setTimeout(() => input.classList.remove('saved'), 1200);
                } else {
                    failed++;
                }
            } catch (e) {
                failed++;
            }
        }

        if (saveBtnText) saveBtnText.textContent = 'Save';

        if (failed > 0) {
            setSaveStatus(`${failed} change(s) failed to save. Try again.`, 'error');
        } else {
            setSaveStatus('Saved.', 'success');
            setTimeout(() => setSaveStatus('', 'success'), 1500);
        }
        updateSaveButtonState();
    }
    
    // Initialize and populate filters
    function initializeFilters() {
        const table = document.querySelector('.sheet-table tbody');
        if (!table) return;
        
        const rows = table.querySelectorAll('tbody tr');
        const departments = new Set();
        const sections = new Set();
        
        rows.forEach(row => {
            const cells = row.querySelectorAll('td');
            if (cells.length >= 4) {
                const deptCourse = cells[2].textContent.trim();
                const section = cells[3].textContent.trim();
                
                if (deptCourse && !deptCourse.includes('Section:') && !deptCourse.includes('Dept')) {
                    const dept = deptCourse.split('—')[0].trim();
                    if (dept) departments.add(dept);
                }
                if (section && !section.includes('Unknown') && section.length > 0) {
                    sections.add(section);
                }
            }
        });
        
        // Populate department dropdown
        const deptSelect = document.getElementById('filter-department');
        if (deptSelect) {
            Array.from(departments).sort().forEach(dept => {
                const option = document.createElement('option');
                option.value = dept;
                option.textContent = dept;
                deptSelect.appendChild(option);
            });
        }
        
        // Populate section dropdown
        const sectionSelect = document.getElementById('filter-section');
        if (sectionSelect) {
            Array.from(sections).sort().forEach(section => {
                const option = document.createElement('option');
                option.value = section;
                option.textContent = section;
                sectionSelect.appendChild(option);
            });
        }
    }
    
    // Apply filters to attendance table
    function applyFilters() {
        const deptFilter = document.getElementById('filter-department').value;
        const sectionFilter = document.getElementById('filter-section').value;
        const table = document.querySelector('.sheet-table tbody');
        
        if (!table) return;
        
        const rows = table.querySelectorAll('tbody tr');
        let visibleCount = 0;
        
        rows.forEach(row => {
            const cells = row.querySelectorAll('td');
            if (cells.length < 4) {
                row.style.display = row.textContent.includes('Unknown Dept') || row.textContent.includes('Section:') ? 'table-row' : 'none';
                return;
            }
            
            const deptCourse = cells[2].textContent.trim();
            const section = cells[3].textContent.trim();
            
            let showRow = true;
            
            // Hide department/section header rows
            if (deptCourse.includes('Unknown Dept') || deptCourse.includes('—') === false || section.length === 0) {
                row.style.display = 'table-row';
                return;
            }
            
            // Apply department filter
            if (deptFilter) {
                const dept = deptCourse.split('—')[0].trim();
                showRow = showRow && dept === deptFilter;
            }
            
            // Apply section filter
            if (sectionFilter) {
                showRow = showRow && section === sectionFilter;
            }
            
            row.style.display = showRow ? 'table-row' : 'none';
            if (showRow) visibleCount++;
        });
        
        // Show message if no results
        if (visibleCount === 0) {
            const emptyRow = document.createElement('tr');
            emptyRow.innerHTML = '<td colspan="7" style="text-align:center; padding:20px; color:#718096;">No results match the selected filters.</td>';
            table.appendChild(emptyRow);
        }
    }
    
    // Clear all filters
    function clearFilters() {
        document.getElementById('filter-department').value = '';
        document.getElementById('filter-section').value = '';
        const table = document.querySelector('.sheet-table tbody');
        if (!table) return;
        
        table.querySelectorAll('tbody tr').forEach(row => {
            row.style.display = 'table-row';
        });
    }
    
    function populateSelectOptions(select, values, placeholder) {
        if (!select) return;
        const selectedValue = select.value;
        select.innerHTML = '';

        const defaultOption = document.createElement('option');
        defaultOption.value = '';
        defaultOption.textContent = placeholder;
        select.appendChild(defaultOption);

        values.forEach(value => {
            const option = document.createElement('option');
            option.value = value;
            option.textContent = value;
            select.appendChild(option);
        });

        if (selectedValue && values.includes(selectedValue)) {
            select.value = selectedValue;
        }
    }

    function populateSectionOptions() {
        const table = document.querySelector('.sheet-table tbody');
        const deptFilter = document.getElementById('filter-department')?.value || '';
        const yearLevelFilter = document.getElementById('filter-year-level')?.value || '';
        const sectionSelect = document.getElementById('filter-section');
        if (!table || !sectionSelect) return;

        const sections = new Set();
        table.querySelectorAll('.student-row').forEach(row => {
            const department = (row.dataset.department || '').trim();
            const yearLevel = (row.dataset.yearLevel || '').trim();
            const section = (row.dataset.section || '').trim();

            if (deptFilter && department !== deptFilter) return;
            if (yearLevelFilter && yearLevel !== yearLevelFilter) return;
            if (section) sections.add(section);
        });

        populateSelectOptions(sectionSelect, Array.from(sections).sort(), 'All Sections');
    }

    function updateFilterGuidance(message, tone = 'info') {
        const guidance = document.getElementById('filter-guidance');
        if (!guidance) return;

        guidance.textContent = message;
        guidance.classList.remove('is-muted', 'is-warning');
        guidance.classList.add(tone === 'warning' ? 'is-warning' : 'is-muted');
    }

    function handleYearLevelChange() {
        populateSectionOptions();
        applyFilters();
    }

    // Override filter helpers so attendance stays narrowed to the selected year level/section.
    function initializeFilters() {
        const table = document.querySelector('.sheet-table tbody');
        if (!table) return;

        const studentRows = Array.from(table.querySelectorAll('.student-row'));
        const departments = new Set();
        const yearLevels = new Set();

        studentRows.forEach(row => {
            const department = (row.dataset.department || '').trim();
            const yearLevel = (row.dataset.yearLevel || '').trim();

            if (department) departments.add(department);
            if (yearLevel) yearLevels.add(yearLevel);
        });

        populateSelectOptions(document.getElementById('filter-department'), Array.from(departments).sort(), 'All Departments');
        populateSelectOptions(document.getElementById('filter-year-level'), Array.from(yearLevels).sort(), 'Select Year Level');
        populateSectionOptions();
        applyFilters();
    }

    function applyFilters() {
        const table = document.querySelector('.sheet-table tbody');
        if (!table) return;

        const deptFilter = document.getElementById('filter-department')?.value || '';
        const yearLevelFilter = document.getElementById('filter-year-level')?.value || '';
        const sectionFilter = document.getElementById('filter-section')?.value || '';
        const allRows = Array.from(table.querySelectorAll('tr'));
        let visibleCount = 0;
        let currentGroupRow = null;
        let currentSectionRow = null;

        const existingEmptyRow = document.getElementById('filter-empty-row');
        if (existingEmptyRow) {
            existingEmptyRow.remove();
        }

        allRows.forEach(row => {
            if (row.classList.contains('group-row')) {
                currentGroupRow = row;
                currentSectionRow = null;
                row.style.display = 'none';
                return;
            }

            if (row.classList.contains('section-row')) {
                currentSectionRow = row;
                row.style.display = 'none';
                return;
            }

            if (!row.classList.contains('student-row')) {
                row.style.display = 'none';
                return;
            }

            const department = (row.dataset.department || '').trim();
            const yearLevel = (row.dataset.yearLevel || '').trim();
            const section = (row.dataset.section || '').trim();

            let showRow = Boolean(yearLevelFilter);
            if (deptFilter) showRow = showRow && department === deptFilter;
            if (yearLevelFilter) showRow = showRow && yearLevel === yearLevelFilter;
            if (sectionFilter) showRow = showRow && section === sectionFilter;

            row.style.display = showRow ? 'table-row' : 'none';

            if (showRow) {
                visibleCount++;
                if (currentGroupRow) currentGroupRow.style.display = 'table-row';
                if (currentSectionRow) currentSectionRow.style.display = 'table-row';
            }
        });

        if (!yearLevelFilter) {
            updateFilterGuidance('Choose a year level to view attendance for this event.', 'warning');
            return;
        }

        if (visibleCount === 0) {
            const emptyRow = document.createElement('tr');
            emptyRow.id = 'filter-empty-row';
            emptyRow.innerHTML = '<td colspan="8" style="text-align:center; padding:20px; color:#718096;">No attendance records match the selected year level and section.</td>';
            table.appendChild(emptyRow);
            updateFilterGuidance('No students matched that year level/section combination.', 'warning');
            return;
        }

        updateFilterGuidance('Showing attendance for the selected year level and section.', 'info');
    }

    function clearFilters() {
        const departmentSelect = document.getElementById('filter-department');
        const yearLevelSelect = document.getElementById('filter-year-level');
        const sectionSelect = document.getElementById('filter-section');

        if (departmentSelect) departmentSelect.value = '';
        if (yearLevelSelect) yearLevelSelect.value = '';
        populateSectionOptions();
        if (sectionSelect) sectionSelect.value = '';

        applyFilters();
    }

    // Export attendance to PDF
function exportToPDF(eventName) {
        const { jsPDF } = window.jspdf;
        const doc = new jsPDF();
        
        // Add title
        doc.setFontSize(16);
        doc.text(eventName + ' - Attendance Report', 14, 20);
        
        // Add date
        doc.setFontSize(10);
        doc.text('Generated on: ' + new Date().toLocaleString(), 14, 30);
        
        // Get table data
        const table = document.querySelector('.sheet-table');
        if (!table) {
            alert('No attendance sheet is loaded.');
            return;
        }
        
        const rows = [];
        const headers = ['Student ID', 'Name', 'Department - Course', 'Year Level', 'Section', 'Check In Time', 'Status', 'Signature'];
        
        // Get visible rows only
        const tableBody = table.querySelector('tbody');
        tableBody.querySelectorAll('tr').forEach(row => {
            if (row.style.display === 'none') {
                return;
            }
            
            const cells = row.querySelectorAll('td');
            // Skip header rows (department/year-level rows)
            if (cells.length === 8 && cells[0].textContent.match(/^\d{4}-\d{3,4}$/)) {
                rows.push([
                    cells[0].textContent.trim(),
                    cells[1].textContent.trim(),
                    cells[2].textContent.trim(),
                    cells[3].textContent.trim(),
                    cells[4].textContent.trim(),
                    cells[5].textContent.trim(),
                    cells[6].textContent.trim(),
                    ''
                ]);
            }
        });
        
        // Add table to PDF
        doc.autoTable({
            head: [headers],
            body: rows,
            startY: 40,
            styles: { fontSize: 9 },
            headStyles: { fillColor: [41, 128, 185], textColor: [255, 255, 255], fontStyle: 'bold' },
            alternateRowStyles: { fillColor: [245, 245, 245] },
            margin: { top: 10, right: 14, bottom: 14, left: 14 }
        });
        
        // Save PDF
        doc.save(eventName + ' - Attendance ' + new Date().getTime() + '.pdf');
        alert('Attendance report exported to PDF successfully!');
    }

    // Attach edit listeners to input fields
    function attachEditListeners() {
        const inputs = document.querySelectorAll('.attendance-input');
        inputs.forEach(input => {
            const handler = function() {
                const original = this.getAttribute('data-original') || '';
                if ((this.value || '') !== original) {
                    this.classList.add('pending');
                } else {
                    this.classList.remove('pending');
                }
                updateSaveButtonState();
            };
            input.addEventListener('input', handler);
            input.addEventListener('change', handler);
        });
        updateSaveButtonState();
    }

    // Shared header helpers from admin_dashboard design
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
    </script>
    
    <style>
        .event-card.active {
            border-color: #8b5cf6;
            box-shadow: 0 8px 24px rgba(139, 92, 246, 0.2);
        }
        
        .event-card.active::before {
            transform: scaleX(1);
        }
    </style>
</body>
</html>
