<?php
require_once 'includes/admin_auth.php';
require_once 'includes/notifications.php';

$adminAuth = new AdminAuth();
$currentAdmin = $adminAuth->requireAuth();

// Handle AJAX QR code submission for continuous scanning (no page refresh)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax']) && $_POST['ajax'] === '1' && isset($_POST['qr_data'])) {
    header('Content-Type: application/json');
    
    $qrData = trim($_POST['qr_data']);
    $eventIdParam = isset($_POST['event_id']) ? (int)$_POST['event_id'] : null;
    $timeType = isset($_POST['time_type']) ? $_POST['time_type'] : 'auto'; // 'auto', 'time_in', or 'time_out'
    
    try {
        if (!$eventIdParam) {
            echo json_encode(['success' => false, 'message' => 'ℹ️ Please select an event first before scanning', 'type' => 'info']);
            exit;
        }
        
        $database = new Database();
        $db = $database->getConnection();
        
        // Get student
        $stmt = $db->prepare("SELECT * FROM students WHERE student_id = ? OR qr_code = ?");
        $stmt->execute([$qrData, $qrData]);
        $student = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$student) {
            echo json_encode(['success' => false, 'message' => '✗ Student not found', 'type' => 'error']);
            exit;
        }
        
        // Check if attendance exists
        $existingStmt = $db->prepare("SELECT id, status FROM attendance WHERE student_id = ? AND event_id = ?");
        $existingStmt->execute([$student['id'], $eventIdParam]);
        $existing = $existingStmt->fetch(PDO::FETCH_ASSOC);
        
        // Get event info for message
        $eventStmt = $db->prepare("SELECT event_name FROM events WHERE id = ?");
        $eventStmt->execute([$eventIdParam]);
        $event = $eventStmt->fetch(PDO::FETCH_ASSOC);
        $eventName = $event ? $event['event_name'] : 'Event';
        
        // Determine action based on time_type
        $isTimeIn = false;
        if ($timeType === 'time_in') {
            $isTimeIn = true;
        } elseif ($timeType === 'time_out') {
            $isTimeIn = false;
        } else {
            // Auto mode: first scan = time_in, second scan = time_out
            $isTimeIn = !$existing;
        }
        
        if ($isTimeIn) {
            // Time In: Create new record if doesn't exist
            if (!$existing) {
                $db->prepare("INSERT INTO attendance (student_id, event_id, qr_code_used, status, attendance_time) VALUES (?, ?, ?, 'present', NOW())")
                   ->execute([$student['id'], $eventIdParam, $qrData]);
            } else {
                // Update existing record's time if manual time_in selected
                $db->prepare("UPDATE attendance SET attendance_time = NOW(), qr_code_used = ? WHERE student_id = ? AND event_id = ?")
                   ->execute([$qrData, $student['id'], $eventIdParam]);
            }
            
            notifyStudentAttendanceAction(
                $db,
                (int) $student['id'],
                (int) $eventIdParam,
                'time_in',
                date('Y-m-d H:i:s')
            );
            
            echo json_encode([
                'success' => true,
                'message' => '✓ Check In Recorded!',
                'time' => date('g:i A'),
                'student' => $student['first_name'] . ' ' . $student['last_name'],
                'student_id' => $student['student_id'],
                'email' => $student['email'],
                'course' => $student['course'] ?? 'Not set',
                'phone' => $student['phone'] ?? 'Not provided',
                'year_level' => $student['year_level'] ?? 'Not set',
                'is_active' => $student['is_active'],
                'created_at' => $student['created_at'],
                'event' => $eventName,
                'type' => 'success'
            ]);
        } else {
            // Time Out: Confirm attendance
            if (!$existing) {
                // Create new record if doesn't exist
                $db->prepare("INSERT INTO attendance (student_id, event_id, qr_code_used, status, attendance_time) VALUES (?, ?, ?, 'present', NOW())")
                   ->execute([$student['id'], $eventIdParam, $qrData]);
            } else {
                // Update existing record
                $db->prepare("UPDATE attendance SET status = 'present', qr_code_used = ? WHERE student_id = ? AND event_id = ?")
                   ->execute([$qrData, $student['id'], $eventIdParam]);
            }
            
            notifyStudentAttendanceAction(
                $db,
                (int) $student['id'],
                (int) $eventIdParam,
                'time_out',
                date('Y-m-d H:i:s')
            );
            
            echo json_encode([
                'success' => true,
                'message' => '✓ Check Out Recorded! Status: Present',
                'time' => date('g:i A'),
                'student' => $student['first_name'] . ' ' . $student['last_name'],
                'student_id' => $student['student_id'],
                'email' => $student['email'],
                'course' => $student['course'] ?? 'Not set',
                'phone' => $student['phone'] ?? 'Not provided',
                'year_level' => $student['year_level'] ?? 'Not set',
                'is_active' => $student['is_active'],
                'created_at' => $student['created_at'],
                'event' => $eventName,
                'type' => 'success'
            ]);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage(), 'type' => 'error']);
    }
    exit;
}

$message = '';
$messageType = '';
$scannedStudent = null;
$events = [];
$selectedEventId = null;

// Load active events for selection (all active events, not just today)
try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Check if attendance_locked column exists
    $checkCol = $db->query("SHOW COLUMNS FROM events LIKE 'attendance_locked'")->fetch();
    
    // Query to get all active events (include lock status if column exists)
    if ($checkCol) {
        $stmtEvents = $db->prepare("
            SELECT id, event_name, event_date, event_time, COALESCE(attendance_locked, 0) as attendance_locked 
            FROM events 
            WHERE is_active = 1 
            ORDER BY event_date DESC, event_time ASC
        ");
    } else {
        $stmtEvents = $db->prepare("
            SELECT id, event_name, event_date, event_time 
            FROM events 
            WHERE is_active = 1 
            ORDER BY event_date DESC, event_time ASC
        ");
    }
    $stmtEvents->execute();
    $events = $stmtEvents->fetchAll(PDO::FETCH_ASSOC);
    
    if ($events) {
        $selectedEventId = isset($_POST['event_id']) ? (int)$_POST['event_id'] : (int)$events[0]['id'];
    }
} catch (Exception $e) {
    // ignore
}

// Function to check if student is eligible for event (same logic as attendance sheet)
function isStudentEligibleForEvent($db, $studentId, $eventId) {
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

    // If no filters are set, all active students are eligible
    if (empty($selectedDepartments) && empty($courseFilterNames)) {
        return true;
    }

    // Build query to check if student matches event criteria
    $params = [];
    $where = 's.id = ? AND s.is_active = 1';
    $params[] = $studentId;
    
    if (!empty($selectedDepartments)) {
        // Filter by selected departments
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

    $checkSql = "SELECT COUNT(*) FROM students s WHERE $where";
    $checkStmt = $db->prepare($checkSql);
    $checkStmt->execute($params);
    $count = $checkStmt->fetchColumn();
    
    return $count > 0;
}

// Handle QR code scan result
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['qr_data'])) {
    $qrData = trim($_POST['qr_data']);
    $eventIdParam = isset($_POST['event_id']) ? (int)$_POST['event_id'] : ($selectedEventId ?? null);
    
    // VALIDATION: Event must be selected
    if (!$eventIdParam) {
        $message = 'ℹ️ Please select an event first before scanning';
        $messageType = 'info';
    } else {
        try {
            if (!isset($db)) {
                $database = new Database();
                $db = $database->getConnection();
        }
        
        // Extract student ID from QR data (assuming QR contains student_id)
        $studentId = $qrData;
        
        // Get student data
        $stmt = $db->prepare("SELECT * FROM students WHERE student_id = ? OR qr_code = ?");
        $stmt->execute([$studentId, $qrData]);
        $scannedStudent = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($scannedStudent) {
            // Check if event is locked
            $isLocked = false;
            if ($eventIdParam) {
                try {
                    $checkCol = $db->query("SHOW COLUMNS FROM events LIKE 'attendance_locked'")->fetch();
                    if ($checkCol) {
                        $lockStmt = $db->prepare("SELECT COALESCE(attendance_locked, 0) as attendance_locked FROM events WHERE id = ?");
                        $lockStmt->execute([$eventIdParam]);
                        $lockResult = $lockStmt->fetch(PDO::FETCH_ASSOC);
                        $isLocked = isset($lockResult['attendance_locked']) && $lockResult['attendance_locked'] == 1;
                    }
                } catch (Exception $e) {
                    // Column doesn't exist or error, assume not locked
                    $isLocked = false;
                }
            }
            
            if ($isLocked) {
                $message = 'Attendance sheet is locked. Please unlock the attendance sheet in the Attendance Dashboard to record attendance.';
                $messageType = 'error';
                $scannedStudent = null; // Don't display student data if locked
            } else {
                // Check if student is eligible for the event
                $isEligible = true;
                if ($eventIdParam) {
                    $isEligible = isStudentEligibleForEvent($db, $scannedStudent['id'], $eventIdParam);
                }
                
                if (!$isEligible) {
                    $message = 'This student is not invited to this event. Only students from the selected courses/departments can attend.';
                    $messageType = 'error';
                    $scannedStudent = null; // Don't display student data if not eligible
                } else {
                    // If an event is selected and student is eligible, record attendance automatically
                    if ($eventIdParam) {
                        // Check if attendance record exists
                        $existingStmt = $db->prepare("SELECT id, status FROM attendance WHERE student_id = ? AND event_id = ?");
                        $existingStmt->execute([$scannedStudent['id'], $eventIdParam]);
                        $existing = $existingStmt->fetch(PDO::FETCH_ASSOC);
                        
                        if (!$existing) {
                            // First scan: mark as present (Time In)
                            $db->prepare("INSERT INTO attendance (student_id, event_id, qr_code_used, status, attendance_time) VALUES (?, ?, ?, 'present', NOW())")
                               ->execute([$scannedStudent['id'], $eventIdParam, $qrData]);
                            $message = '✓ Check In Recorded! Time In: ' . date('g:i A');
                            $messageType = 'success';
                            $clockAction = 'time_in';
                        } else {
                            // Second scan: mark as present (Time Out) - update status to confirm presence
                            $db->prepare("UPDATE attendance SET status = 'present', qr_code_used = ? WHERE student_id = ? AND event_id = ?")
                               ->execute([$qrData, $scannedStudent['id'], $eventIdParam]);
                            $message = '✓ Check Out Recorded! Status: Present';
                            $messageType = 'success';
                            $clockAction = 'time_out';
                        }
                        
                        notifyStudentAttendanceAction(
                            $db,
                            (int) $scannedStudent['id'],
                            (int) $eventIdParam,
                            $clockAction,
                            date('Y-m-d H:i:s')
                        );
                    } else {
                        $message = 'ℹ️ Please select an event first before scanning';
                        $messageType = 'info';
                    }
                }
            }
        } else {
            $message = '✗ Student not found';
            $messageType = 'error';
        }
        
    } catch (Exception $e) {
        $message = 'Error: ' . $e->getMessage();
        $messageType = 'error';
    }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Code Scanner - Admin Panel</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="https://unpkg.com/html5-qrcode"></script>
</head>
<body>
    <!-- Include Admin Sidebar -->
    <?php include 'includes/admin_sidebar.php'; ?>
    
    <!-- Mobile Sidebar Toggle -->
    <button class="sidebar-toggle" onclick="toggleSidebar()">
        <i class="fas fa-bars"></i>
    </button>
    
    <!-- Main Content -->
    <div class="admin-content">
        <div class="admin-header">
            <div class="header-content">
                <div class="page-title-section">
                    <h1 class="page-title">
                        <i class="fas fa-qrcode"></i>
                        <span>QR Code Scanner</span>
                    </h1>
                    <div class="title-divider"></div>
                </div>
                <div class="header-actions">
                    <button class="btn btn-secondary" onclick="toggleScanner()">
                        <i class="fas fa-camera"></i> Toggle Scanner
                    </button>
                </div>
            </div>
        </div>
        
        <div class="admin-main">
            <?php if ($message): ?>
                <div class="message <?php echo $messageType; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>
            
            <!-- Scanner Section -->
            <div class="scanner-container">
                <div class="scanner-section">
                    <div class="scanner-header">
                        <div class="scanner-header-content">
                            <div class="scanner-icon-wrapper">
                                <i class="fas fa-camera"></i>
                            </div>
                            <div>
                                <h3>QR Code Scanner</h3>
                                <p>Point your camera at a student's QR code to scan their information</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="scanner-wrapper">
                        <?php if ($events): ?>
                        <div class="event-selector">
                            <label for="event_id" class="event-label">
                                <i class="fas fa-calendar-day"></i>
                                <span>Select an Event:</span>
                            </label>
                            <div class="event-select-wrapper">
                                <select id="event_id" name="event_id" class="event-select">
                                    <?php foreach ($events as $ev): ?>
                                        <?php 
                                        $isSel = ($selectedEventId == (int)$ev['id']) ? 'selected' : '';
                                        $isLocked = isset($ev['attendance_locked']) && $ev['attendance_locked'] == 1;
                                        $lockText = $isLocked ? ' [LOCKED]' : '';
                                        ?>
                                        <option value="<?php echo (int)$ev['id']; ?>" <?php echo $isSel; ?>>
                                            <?php echo htmlspecialchars($ev['event_name'] . ' — ' . date('g:i A', strtotime($ev['event_time'])) . $lockText); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <a class="btn btn-attendance" href="admin_attendance.php" target="_blank">
                                    <i class="fas fa-clipboard-check"></i> View Attendance
                                </a>
                            </div>
                            <div class="today-indicator">
                                <i class="fas fa-info-circle"></i>
                                <span>Showing all active events</span>
                            </div>
                            <?php 
                            // Show lock warning if selected event is locked
                            if ($selectedEventId) {
                                foreach ($events as $ev) {
                                    if ((int)$ev['id'] == $selectedEventId && isset($ev['attendance_locked']) && $ev['attendance_locked'] == 1) {
                                        echo '<div class="lock-warning" style="margin-top: 12px; padding: 12px 16px; background: linear-gradient(135deg, #fff5f5, #fed7d7); border: 2px solid #fc8181; border-radius: 8px; display: flex; align-items: center; gap: 10px; color: #c53030; font-weight: 600; font-size: 14px;"><i class="fas fa-lock"></i><span>This attendance sheet is locked. Unlock it in the Attendance Dashboard to record attendance.</span></div>';
                                        break;
                                    }
                                }
                            }
                            ?>
                        </div>
                        <?php else: ?>
                        <div class="no-events-warning">
                            <i class="fas fa-calendar-times"></i>
                            <div>
                                <strong>No events scheduled for today</strong>
                                <span>There are no active events scheduled for <?php echo date('M d, Y'); ?>. Please create an event or select a different date.</span>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <div class="qr-reader-container">
                            <div id="qr-reader" class="qr-reader"></div>
                            <div id="qr-reader-results" class="qr-results">
                                <i class="fas fa-camera"></i>
                                <span>Click "Start Scanner" to begin scanning QR codes.</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="scanner-controls">
                        <button id="start-scanner" class="btn btn-primary">
                            <i class="fas fa-play"></i> Start Scanner
                        </button>
                        <button id="stop-scanner" class="btn btn-secondary" disabled>
                            <i class="fas fa-stop"></i> Stop Scanner
                        </button>
                        <button id="switch-camera" class="btn btn-info">
                            <i class="fas fa-sync"></i> Switch Camera
                        </button>
                    </div>
                    
                    <div class="time-selector" style="margin-top: 20px; padding: 16px; background: #f3f4f6; border-radius: 12px; border: 2px solid #e5e7eb;">
                        <div style="margin-bottom: 12px;">
                            <p style="margin: 0 0 12px 0; color: #374151; font-weight: 600; font-size: 14px;">
                                <i class="fas fa-clock"></i> Scan Mode:
                            </p>
                        </div>
                        <div style="display: flex; gap: 12px; flex-wrap: wrap;">
                            <button id="mode-auto" class="btn btn-mode active" onclick="setTimeMode('auto')" style="flex: 1; min-width: 140px;">
                                <i class="fas fa-robot"></i> Auto (In→Out)
                            </button>
                            <button id="mode-time-in" class="btn btn-mode" onclick="setTimeMode('time_in')" style="flex: 1; min-width: 140px;">
                                <i class="fas fa-sign-in-alt"></i> Check In
                            </button>
                            <button id="mode-time-out" class="btn btn-mode" onclick="setTimeMode('time_out')" style="flex: 1; min-width: 140px;">
                                <i class="fas fa-sign-out-alt"></i> Check Out
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Student Data Display -->
                <div id="student-data-container" class="student-data-container" style="display: none;">
                    <div class="student-data-header">
                        <div class="student-header-content">
                            <div class="student-header-icon">
                                <i class="fas fa-user-graduate"></i>
                            </div>
                            <div>
                                <h3>Scanned Student Data</h3>
                                <span class="scan-time">
                                    <i class="fas fa-clock"></i>
                                    Last scan: <span id="scan-time-display">---</span>
                                </span>
                            </div>
                        </div>
                    </div>
                    <!-- Student card will be inserted here via AJAX -->
                </div>
            </div>
        </div>
    </div>
    
    <style>
    /* Modern Typography & Base Styles */
    * {
        box-sizing: border-box;
    }
    
    body {
        background: #f5f7fa;
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        margin: 0;
        padding: 0;
        overflow-x: hidden;
        color: #2d3748;
    }
    
    .admin-content {
        margin-left: 280px;
        min-height: 100vh;
        background: #f5f7fa;
        transition: margin-left 0.3s ease;
        width: calc(100% - 280px);
    }
    
    .admin-header {
        background: #ffffff;
        padding: 32px 40px;
        border-bottom: 1px solid #e8eaf0;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
        position: sticky;
        top: 0;
        z-index: 100;
    }
    
    .header-content {
        display: flex;
        justify-content: space-between;
        align-items: center;
        max-width: 1400px;
        margin: 0 auto;
    }
    
    .page-title-section {
        display: flex;
        flex-direction: column;
        gap: 12px;
        flex: 1;
    }
    
    .page-title {
        font-family: 'Poppins', sans-serif;
        font-size: 32px;
        font-weight: 700;
        color: #2d3748;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 12px;
    }
    
    .page-title i {
        color: #8b5cf6;
        font-size: 28px;
    }
    
    .title-divider {
        width: 60px;
        height: 4px;
        background: linear-gradient(90deg, #8b5cf6, #a78bfa);
        border-radius: 2px;
    }
    
    .header-actions {
        display: flex;
        gap: 15px;
        align-items: center;
    }
    
    .admin-main {
        padding: 40px;
        max-width: 1400px;
        margin: 0 auto;
        width: 100%;
    }
    
    /* Scanner Container */
    .scanner-container {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 32px;
        margin-bottom: 32px;
    }
    
    .scanner-section {
        background: #ffffff;
        border-radius: 16px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
        overflow: hidden;
        border: 1px solid #e8eaf0;
    }
    
    .scanner-header {
        padding: 28px 32px;
        border-bottom: 1px solid #e8eaf0;
        background: linear-gradient(135deg, #8b5cf6 0%, #a78bfa 100%);
        color: white;
    }
    
    .scanner-header-content {
        display: flex;
        align-items: center;
        gap: 16px;
    }
    
    .scanner-icon-wrapper {
        width: 56px;
        height: 56px;
        background: rgba(255, 255, 255, 0.2);
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
        flex-shrink: 0;
    }
    
    .scanner-header h3 {
        margin: 0 0 6px 0;
        font-family: 'Poppins', sans-serif;
        font-size: 22px;
        font-weight: 600;
    }
    
    .scanner-header p {
        margin: 0;
        opacity: 0.95;
        font-size: 14px;
        font-weight: 400;
    }
    
    .scanner-wrapper {
        padding: 32px;
    }
    
    .event-selector {
        margin-bottom: 24px;
        padding: 20px;
        background: linear-gradient(135deg, #faf5ff 0%, #ffffff 100%);
        border-radius: 12px;
        border: 1px solid #e8eaf0;
    }
    
    .event-label {
        display: flex;
        align-items: center;
        gap: 8px;
        font-weight: 600;
        color: #2d3748;
        margin-bottom: 12px;
        font-size: 14px;
    }
    
    .event-label i {
        color: #8b5cf6;
    }
    
    .event-select-wrapper {
        display: flex;
        gap: 12px;
        align-items: center;
    }
    
    .event-select {
        flex: 1;
        padding: 12px 16px;
        border: 2px solid #e8eaf0;
        border-radius: 10px;
        font-size: 14px;
        font-weight: 500;
        color: #2d3748;
        background: white;
        transition: all 0.3s ease;
        font-family: 'Inter', sans-serif;
    }
    
    .event-select:focus {
        outline: none;
        border-color: #8b5cf6;
        box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.1);
    }
    
    .btn-attendance {
        padding: 12px 20px;
        background: linear-gradient(135deg, #667eea, #764ba2);
        color: white;
        border: none;
        border-radius: 10px;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: all 0.3s ease;
        white-space: nowrap;
    }
    
    .btn-attendance:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
    }
    
    .today-indicator {
        margin-top: 12px;
        padding: 10px 16px;
        background: linear-gradient(135deg, #e0e7ff, #f3e8ff);
        border-radius: 8px;
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 13px;
        color: #6b21a8;
        font-weight: 500;
    }
    
    .today-indicator i {
        color: #8b5cf6;
        font-size: 14px;
    }
    
    .no-events-warning {
        padding: 20px;
        background: linear-gradient(135deg, #fff5f5, #fef5e7);
        border: 2px solid #fed7d7;
        border-radius: 12px;
        display: flex;
        align-items: flex-start;
        gap: 16px;
        margin-bottom: 24px;
    }
    
    .no-events-warning i {
        font-size: 24px;
        color: #c53030;
        flex-shrink: 0;
        margin-top: 2px;
    }
    
    .no-events-warning div {
        display: flex;
        flex-direction: column;
        gap: 6px;
    }
    
    .no-events-warning strong {
        color: #c53030;
        font-size: 16px;
        font-weight: 600;
    }
    
    .no-events-warning span {
        color: #9c4221;
        font-size: 14px;
        line-height: 1.5;
    }
    
    .qr-reader-container {
        display: flex;
        flex-direction: column;
        gap: 16px;
    }
    
    .qr-reader {
        width: 100%;
        max-width: 450px;
        margin: 0 auto;
        border-radius: 12px;
        overflow: hidden;
        border: 2px solid #e8eaf0;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
    }
    
    .qr-results {
        padding: 20px;
        background: linear-gradient(135deg, #f7fafc 0%, #ffffff 100%);
        border-radius: 12px;
        border: 2px dashed #cbd5e0;
        min-height: 80px;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 12px;
        color: #718096;
        font-size: 14px;
        text-align: center;
    }
    
    .qr-results i {
        font-size: 20px;
        color: #8b5cf6;
    }
    
    .scanner-controls {
        padding: 24px 32px;
        border-top: 1px solid #e8eaf0;
        background: #faf5ff;
        display: flex;
        gap: 12px;
        flex-wrap: wrap;
    }
    
    /* Student Data Container */
    .student-data-container {
        background: #ffffff;
        border-radius: 16px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
        overflow: hidden;
        border: 1px solid #e8eaf0;
    }
    
    .student-data-header {
        padding: 28px 32px;
        border-bottom: 1px solid #e8eaf0;
        background: linear-gradient(135deg, #faf5ff 0%, #ffffff 100%);
    }
    
    .student-header-content {
        display: flex;
        align-items: center;
        gap: 16px;
    }
    
    .student-header-icon {
        width: 56px;
        height: 56px;
        background: linear-gradient(135deg, #8b5cf6, #a78bfa);
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 24px;
        flex-shrink: 0;
    }
    
    .student-data-header h3 {
        margin: 0 0 6px 0;
        font-family: 'Poppins', sans-serif;
        color: #2d3748;
        font-size: 22px;
        font-weight: 600;
    }
    
    .scan-time {
        color: #718096;
        font-size: 13px;
        display: flex;
        align-items: center;
        gap: 6px;
    }
    
    .scan-time i {
        font-size: 12px;
    }
    
    .student-data-card {
        padding: 32px;
        display: flex;
        gap: 24px;
        align-items: flex-start;
    }
    
    .student-avatar {
        width: 100px;
        height: 100px;
        background: linear-gradient(135deg, #8b5cf6, #a78bfa);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 40px;
        flex-shrink: 0;
        box-shadow: 0 4px 12px rgba(139, 92, 246, 0.3);
    }
    
    .student-info {
        flex: 1;
    }
    
    .student-basic-info {
        margin-bottom: 24px;
        padding-bottom: 24px;
        border-bottom: 1px solid #e8eaf0;
    }
    
    .student-basic-info h4 {
        margin: 0 0 12px 0;
        font-family: 'Poppins', sans-serif;
        color: #2d3748;
        font-size: 26px;
        font-weight: 600;
    }
    
    .student-id {
        color: #8b5cf6;
        font-weight: 600;
        font-size: 16px;
        margin: 0 0 8px 0;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .student-id i {
        font-size: 14px;
    }
    
    .student-email {
        color: #718096;
        font-size: 15px;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .student-email i {
        font-size: 14px;
    }
    
    .student-details {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
        margin-bottom: 24px;
    }
    
    .detail-row {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }
    
    .detail-label {
        font-weight: 600;
        color: #4a5568;
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        display: flex;
        align-items: center;
        gap: 6px;
    }
    
    .detail-label i {
        color: #8b5cf6;
        font-size: 12px;
    }
    
    .detail-value {
        color: #2d3748;
        font-size: 15px;
        font-weight: 500;
    }
    
    .status-badge {
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }
    
    .status-badge.active {
        background: linear-gradient(135deg, #d4edda, #c3e6cb);
        color: #155724;
    }
    
    .status-badge.inactive {
        background: linear-gradient(135deg, #f8d7da, #f5c6cb);
        color: #721c24;
    }
    
    .status-badge.completed {
        background: linear-gradient(135deg, #d1ecf1, #bee5eb);
        color: #0c5460;
    }
    
    .status-badge.incomplete {
        background: linear-gradient(135deg, #fff3cd, #ffeaa7);
        color: #856404;
    }
    
    .student-actions {
        display: flex;
        flex-direction: column;
        gap: 12px;
        flex-shrink: 0;
        min-width: 160px;
    }
    
    .btn-action {
        padding: 12px 20px;
        border: none;
        border-radius: 10px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        font-size: 14px;
        font-weight: 600;
        transition: all 0.3s ease;
        text-decoration: none;
        font-family: 'Inter', sans-serif;
    }
    
    .btn-action.view {
        background: linear-gradient(135deg, #e3f2fd, #bbdefb);
        color: #1976d2;
    }
    
    .btn-action.view:hover {
        background: linear-gradient(135deg, #bbdefb, #90caf9);
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(25, 118, 210, 0.2);
    }
    
    .btn-action.edit {
        background: linear-gradient(135deg, #fff3e0, #ffe0b2);
        color: #f57c00;
    }
    
    .btn-action.edit:hover {
        background: linear-gradient(135deg, #ffe0b2, #ffcc80);
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(245, 124, 0, 0.2);
    }
    
    .btn-action.qr {
        background: linear-gradient(135deg, #f3e5f5, #e1bee7);
        color: #7b1fa2;
    }
    
    .btn-action.qr:hover {
        background: linear-gradient(135deg, #e1bee7, #ce93d8);
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(123, 31, 162, 0.2);
    }
    
    /* Buttons */
    .btn {
        padding: 12px 24px;
        border: none;
        border-radius: 10px;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        font-family: 'Inter', sans-serif;
    }
    
    .btn-primary {
        background: linear-gradient(135deg, #8b5cf6, #a78bfa);
        color: white;
        box-shadow: 0 4px 12px rgba(139, 92, 246, 0.3);
    }
    
    .btn-primary:hover:not(:disabled) {
        background: linear-gradient(135deg, #7c3aed, #9333ea);
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(139, 92, 246, 0.4);
    }
    
    .btn-primary:disabled {
        opacity: 0.6;
        cursor: not-allowed;
    }
    
    .btn-secondary {
        background: linear-gradient(135deg, #6b7280, #4b5563);
        color: white;
        box-shadow: 0 4px 12px rgba(107, 114, 128, 0.3);
    }
    
    .btn-secondary:hover:not(:disabled) {
        background: linear-gradient(135deg, #4b5563, #374151);
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(107, 114, 128, 0.4);
    }
    
    .btn-secondary:disabled {
        opacity: 0.6;
        cursor: not-allowed;
    }
    
    .btn-info {
        background: linear-gradient(135deg, #06b6d4, #0891b2);
        color: white;
        box-shadow: 0 4px 12px rgba(6, 182, 212, 0.3);
    }
    
    .btn-info:hover {
        background: linear-gradient(135deg, #0891b2, #0e7490);
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(6, 182, 212, 0.4);
    }
    
    .btn-mode {
        background: linear-gradient(135deg, #e5e7eb, #d1d5db);
        color: #374151;
        border: 2px solid #d1d5db;
        font-weight: 600;
        font-size: 13px;
        padding: 10px 14px;
        transition: all 0.3s ease;
    }
    
    .btn-mode:hover {
        background: linear-gradient(135deg, #d1d5db, #9ca3af);
        border-color: #9ca3af;
        color: #1f2937;
    }
    
    .btn-mode.active {
        background: linear-gradient(135deg, #8b5cf6, #7c3aed);
        color: white;
        border-color: #7c3aed;
        box-shadow: 0 4px 12px rgba(139, 92, 246, 0.4);
    }
    
    .btn-mode.active:hover {
        background: linear-gradient(135deg, #7c3aed, #6d28d9);
        border-color: #6d28d9;
        box-shadow: 0 6px 16px rgba(139, 92, 246, 0.5);
    }
    
    .message {
        padding: 16px 20px;
        border-radius: 12px;
        margin-bottom: 24px;
        font-weight: 500;
        border-left: 4px solid;
        display: flex;
        align-items: center;
        gap: 12px;
    }
    
    .message.success {
        background: linear-gradient(135deg, #d4edda, #c3e6cb);
        color: #155724;
        border-left-color: #28a745;
    }
    
    .message.error {
        background: linear-gradient(135deg, #f8d7da, #f5c6cb);
        color: #721c24;
        border-left-color: #dc3545;
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
    @media (max-width: 1200px) {
        .admin-content {
            margin-left: 0;
            width: 100%;
        }
        
        .scanner-container {
            grid-template-columns: 1fr;
        }
    }
    
    @media (max-width: 768px) {
        .sidebar-toggle {
            display: block;
        }
        
        .admin-header {
            padding: 24px 20px;
        }
        
        .page-title {
            font-size: 24px;
        }
        
        .admin-main {
            padding: 24px;
        }
        
        .scanner-container {
            gap: 24px;
        }
        
        .scanner-wrapper {
            padding: 20px;
        }
        
        .event-select-wrapper {
            flex-direction: column;
        }
        
        .btn-attendance {
            width: 100%;
            justify-content: center;
        }
        
        .student-data-card {
            flex-direction: column;
            text-align: center;
            padding: 24px;
        }
        
        .student-details {
            grid-template-columns: 1fr;
        }
        
        .student-actions {
            flex-direction: row;
            justify-content: center;
            flex-wrap: wrap;
            min-width: auto;
        }
        
        .scanner-controls {
            flex-direction: column;
            padding: 20px;
        }
        
        .scanner-controls .btn {
            width: 100%;
            justify-content: center;
        }
    }
    
    @media (max-width: 480px) {
        .admin-header {
            padding: 20px 16px;
        }
        
        .page-title {
            font-size: 20px;
        }
        
        .admin-main {
            padding: 16px;
        }
        
        .scanner-wrapper {
            padding: 16px;
        }
        
        .student-data-card {
            padding: 20px;
        }
        
        .student-avatar {
            width: 80px;
            height: 80px;
            font-size: 32px;
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
    
    // QR Scanner functionality
    let html5QrcodeScanner = null;
    let isScanning = false;
    let currentCameraId = null;
    let isProcessingQR = false;  // Debounce flag to prevent rapid scans
    let currentTimeMode = 'auto'; // 'auto', 'time_in', or 'time_out'
    
    // Initialize scanner
    function initScanner() {
        if (html5QrcodeScanner) {
            return;
        }
        
        html5QrcodeScanner = new Html5QrcodeScanner(
            "qr-reader",
            {
                fps: 10,
                qrbox: { width: 250, height: 250 },
                aspectRatio: 1.0
            },
            false
        );
    }
    
    // Start scanner
    function startScanner() {
        if (isScanning) return;
        
        const eventSelect = document.getElementById('event_id');
        if (!eventSelect || !eventSelect.value) {
            alert('Please select an event first before starting the scanner');
            return;
        }
        
        initScanner();
        
        html5QrcodeScanner.render(
            function(decodedText, decodedResult) {
                // Handle successful scan - CONTINUE SCANNING (don't stop)
                // Debounce: prevent rapid successive scans
                if (isProcessingQR) {
                    console.log('QR Code buffered (processing previous scan):', decodedText);
                    return;
                }
                
                console.log('QR Code scanned:', decodedText);
                const resultsEl = document.getElementById('qr-reader-results');
                
                // Show processing message
                resultsEl.innerHTML = 
                    '<div style="display: flex; align-items: center; gap: 12px; color: #3b82f6; font-weight: 600;"><i class="fas fa-spinner fa-spin" style="font-size: 20px;"></i><div>Processing...</div></div>';
                
                // Lock to prevent rapid scans
                isProcessingQR = true;
                
                // Submit the scanned data via AJAX (continuous scanning)
                submitQRDataAjax(decodedText);
            },
            function(error) {
                // Handle scan error (optional)
                console.log('Scan error:', error);
            }
        );
        
        isScanning = true;
        document.getElementById('start-scanner').disabled = true;
        document.getElementById('stop-scanner').disabled = false;
    }
    
    // Stop scanner
    function stopScanner() {
        if (!isScanning) return;
        
        if (html5QrcodeScanner) {
            html5QrcodeScanner.clear();
        }
        
        isScanning = false;
        document.getElementById('start-scanner').disabled = false;
        document.getElementById('stop-scanner').disabled = true;
        document.getElementById('qr-reader-results').innerHTML = 
            '<i class="fas fa-camera" style="font-size: 20px; color: #8b5cf6;"></i><span>Scanner stopped. Click "Start Scanner" to begin scanning.</span>';
    }
    
    // Toggle scanner
    function toggleScanner() {
        if (isScanning) {
            stopScanner();
        } else {
            startScanner();
        }
    }
    
    // Switch camera
    function switchCamera() {
        if (!isScanning) return;
        
        stopScanner();
        setTimeout(() => {
            startScanner();
        }, 500);
    }
    
    // Set time mode (auto, time_in, or time_out)
    function setTimeMode(mode) {
        currentTimeMode = mode;
        
        // Update button states
        document.getElementById('mode-auto').classList.remove('active');
        document.getElementById('mode-time-in').classList.remove('active');
        document.getElementById('mode-time-out').classList.remove('active');
        
        if (mode === 'auto') {
            document.getElementById('mode-auto').classList.add('active');
        } else if (mode === 'time_in') {
            document.getElementById('mode-time-in').classList.add('active');
        } else if (mode === 'time_out') {
            document.getElementById('mode-time-out').classList.add('active');
        }
    }
    
    // Submit QR data to server via AJAX for continuous scanning
    function submitQRDataAjax(qrData) {
        const eventSelect = document.getElementById('event_id');
        const eventId = eventSelect ? eventSelect.value : '';
        
        // Use fetch for AJAX request with time_type parameter
        fetch(window.location.href, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `ajax=1&qr_data=${encodeURIComponent(qrData)}&event_id=${encodeURIComponent(eventId)}&time_type=${encodeURIComponent(currentTimeMode)}`
        })
        .then(response => response.json())
        .then(data => {
            const resultsEl = document.getElementById('qr-reader-results');
            const studentCardEl = document.getElementById('student-data-container');
            
            if (data.success) {
                // Show success message with student name
                const messageHtml = `
                    <div style="display: flex; align-items: center; gap: 12px; color: #10b981; font-weight: 600;">
                        <i class="fas fa-check-circle" style="font-size: 24px;"></i>
                        <div>
                            <div>${data.message}</div>
                            <div style="font-size: 12px; color: #059669; margin-top: 2px;">${data.student} @ ${data.time}</div>
                        </div>
                    </div>
                `;
                resultsEl.innerHTML = messageHtml;
                
                // Update student data card dynamically
                if (studentCardEl && data.student_id) {
                    // Show container
                    studentCardEl.style.display = 'block';
                    
                    // Update timestamp
                    const now = new Date();
                    const timeStr = now.toLocaleString('en-US', { month: 'short', day: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true });
                    document.getElementById('scan-time-display').textContent = timeStr;
                    
                    const cardHtml = `
                        <div class="student-data-card">
                            <div class="student-avatar">
                                <i class="fas fa-user"></i>
                            </div>
                            
                            <div class="student-info">
                                <div class="student-basic-info">
                                    <h4>${escapeHtml(data.student)}</h4>
                                    <p class="student-id">
                                        <i class="fas fa-id-card"></i>
                                        ${escapeHtml(data.student_id)}
                                    </p>
                                    <p class="student-email">
                                        <i class="fas fa-envelope"></i>
                                        ${escapeHtml(data.email)}
                                    </p>
                                </div>
                                
                                <div class="student-details">
                                    <div class="detail-row">
                                        <span class="detail-label">
                                            <i class="fas fa-phone"></i> Phone:
                                        </span>
                                        <span class="detail-value">${escapeHtml(data.phone || 'Not provided')}</span>
                                    </div>
                                    
                                    <div class="detail-row">
                                        <span class="detail-label">
                                            <i class="fas fa-graduation-cap"></i> Course:
                                        </span>
                                        <span class="detail-value">${escapeHtml(data.course || 'Not set')}</span>
                                    </div>
                                    
                                    <div class="detail-row">
                                        <span class="detail-label">
                                            <i class="fas fa-layer-group"></i> Year Level:
                                        </span>
                                        <span class="detail-value">${escapeHtml(data.year_level || 'Not set')}</span>
                                    </div>
                                    
                                    <div class="detail-row">
                                        <span class="detail-label">
                                            <i class="fas fa-toggle-on"></i> Status:
                                        </span>
                                        <span class="status-badge ${data.is_active ? 'active' : 'inactive'}">
                                            <i class="fas ${data.is_active ? 'fa-check-circle' : 'fa-times-circle'}"></i>
                                            ${data.is_active ? 'Active' : 'Inactive'}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                    studentCardEl.innerHTML += cardHtml;
                }
                
                // Keep scanning - don't stop or refresh
                setTimeout(() => {
                    resultsEl.innerHTML = '<i class="fas fa-camera" style="font-size: 20px; color: #8b5cf6;"></i><span>Ready for next scan...</span>';
                    isProcessingQR = false;  // UNLOCK debounce after delay
                }, 2000);
            } else {
                // Show error message
                const errorHtml = `
                    <div style="display: flex; align-items: center; gap: 12px; color: #dc2626; font-weight: 600;">
                        <i class="fas fa-exclamation-circle" style="font-size: 20px;"></i>
                        <div>${data.message}</div>
                    </div>
                `;
                resultsEl.innerHTML = errorHtml;
                
                // Clear after 2 seconds
                setTimeout(() => {
                    resultsEl.innerHTML = '<i class="fas fa-camera" style="font-size: 20px; color: #8b5cf6;"></i><span>Ready for next scan...</span>';
                    isProcessingQR = false;  // UNLOCK debounce
                }, 2000);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            const resultsEl = document.getElementById('qr-reader-results');
            resultsEl.innerHTML = '<div style="color: #dc2626; font-weight: 600;"><i class="fas fa-exclamation-circle"></i> Network error. Retrying...</div>';
            
            // Retry after delay
            setTimeout(() => {
                resultsEl.innerHTML = '<i class="fas fa-camera" style="font-size: 20px; color: #8b5cf6;"></i><span>Ready for next scan...</span>';
                isProcessingQR = false;  // UNLOCK debounce
            }, 2000);
        });
    }
    
    // Helper function to escape HTML
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    // Legacy function for backward compatibility (kept but not used)
    function submitQRData(qrData) {
        // Now using submitQRDataAjax for continuous scanning
        submitQRDataAjax(qrData);
    }
    
    // Student action functions
    function viewStudentDetails(id) {
        window.open(`admin_student_details.php?id=${id}`, '_blank');
    }
    
    function editStudent(id) {
        window.location.href = `admin_edit_student.php?id=${id}`;
    }
    
    function viewQRCode(id) {
        window.open(`admin_view_qr.php?id=${id}`, '_blank');
    }
    
    // Event listeners
    document.getElementById('start-scanner').addEventListener('click', startScanner);
    document.getElementById('stop-scanner').addEventListener('click', stopScanner);
    document.getElementById('switch-camera').addEventListener('click', switchCamera);
    
    // Initialize on page load
    document.addEventListener('DOMContentLoaded', function() {
        const resultsEl = document.getElementById('qr-reader-results');
        if (resultsEl) {
            resultsEl.innerHTML = 
                '<i class="fas fa-camera" style="font-size: 20px; color: #8b5cf6;"></i><span>Click "Start Scanner" to begin scanning QR codes.</span>';
        }
    });
    </script>
    
    <script src="assets/js/script.js"></script>
</body>
</html>

