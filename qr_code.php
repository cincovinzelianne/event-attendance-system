<?php
require_once 'includes/auth.php';
require_once 'config/database.php';

$auth = new Auth();
$currentUser = $auth->getCurrentUser();

if (!$currentUser) {
    header('Location: signin.php');
    exit;
}

function h($v){ return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8'); }

// Basic profile completeness flags (used to decide when to show events)
$profileIncomplete = false;
$profileCompleted = !empty($currentUser['profile_completed']) || (!empty($currentUser['course']) && !empty($currentUser['year_level']));
$hasCourse = !empty($currentUser['course']);
$hasYearLevel = !empty($currentUser['year_level']);
if (!$hasCourse || !$hasYearLevel || !$profileCompleted) {
    $profileIncomplete = true;
}

// Check if QR code is locked
try {
    $database = new Database();
    $db = $database->getConnection();
    $lockStmt = $db->prepare("SELECT qr_code_locked FROM students WHERE id = ?");
    $lockStmt->execute([$currentUser['id']]);
    $lockStatus = $lockStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($lockStatus && isset($lockStatus['qr_code_locked']) && $lockStatus['qr_code_locked'] == 1) {
        // QR code is locked, show error message
        $qrData = null;
        $qrLocked = true;
    } else {
        $qrData = $auth->getQRCodeData($currentUser['student_id']);
        $qrLocked = false;
    }
} catch (Exception $e) {
    $qrData = $auth->getQRCodeData($currentUser['student_id']);
    $qrLocked = false;
}

/**
 * Determine if current student's course is eligible for a given event.
 * Same logic as admin screens (departments + courses pivots), but simplified for read-only use.
 */
function isStudentEligibleForEventByCourse($db, $studentCourseStr, $eventId) {
    $studentCourseStr = trim((string)$studentCourseStr);
    if ($studentCourseStr === '') return false;

    // Get selected departments for event (priority filtering)
    $selectedDepartments = [];
    try {
        $hasDeptPivot = $db->query("SHOW TABLES LIKE 'event_departments'")->fetch();
        if ($hasDeptPivot) {
            $hasDeptEventId = $db->query("SHOW COLUMNS FROM event_departments LIKE 'event_id'")->fetch();
            $hasDeptName = $db->query("SHOW COLUMNS FROM event_departments LIKE 'department'")->fetch();
            if ($hasDeptEventId && $hasDeptName) {
                $deptStmt = $db->prepare("SELECT department FROM event_departments WHERE event_id = ?");
                $deptStmt->execute([(int)$eventId]);
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
                $courseIdsStmt->execute([(int)$eventId]);
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

    // If no filters are set, all students are eligible
    if (empty($selectedDepartments) && empty($courseFilterNames)) {
        return true;
    }

    // If departments are set, student course must map to a course under those departments
    if (!empty($selectedDepartments)) {
        try {
            $in = implode(',', array_fill(0, count($selectedDepartments), '?'));
            $sql = "SELECT COUNT(*) FROM courses c
                    WHERE (c.course_name = ? OR ? LIKE CONCAT(c.course_name, ' - %'))
                    AND c.department IN ($in)";
            $params = array_merge([$studentCourseStr, $studentCourseStr], $selectedDepartments);
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $deptOk = (int)$stmt->fetchColumn() > 0;
            if (!$deptOk) return false;
        } catch (Exception $e) {
            // If we can't map department, fall back to course-only check
        }

        // If courses are also selected, further filter by those courses
        if (!empty($courseFilterNames)) {
            foreach ($courseFilterNames as $courseName) {
                if ($studentCourseStr === $courseName || str_starts_with($studentCourseStr, $courseName . ' - ')) {
                    return true;
                }
            }
            return false;
        }

        return true;
    }

    // If only courses are selected, check by course names
    foreach ($courseFilterNames as $courseName) {
        if ($studentCourseStr === $courseName || str_starts_with($studentCourseStr, $courseName . ' - ')) {
            return true;
        }
    }
    return false;
}

// Load upcoming events for this student (today + future) to show on QR screen
$qrStudentEvents = [];
try {
    if (isset($db) && !$profileIncomplete && !empty($currentUser['course'])) {
        $today = date('Y-m-d');
        // Include lock status if column exists
        $hasLockCol = $db->query("SHOW COLUMNS FROM events LIKE 'attendance_locked'")->fetch();
        if ($hasLockCol) {
            $stmtEvents = $db->prepare("
                SELECT id, event_name, event_date, event_time, location, COALESCE(attendance_locked, 0) as attendance_locked
                FROM events
                WHERE is_active = 1 AND event_date >= :today
                ORDER BY event_date ASC, event_time ASC
                LIMIT 20
            ");
        } else {
            $stmtEvents = $db->prepare("
                SELECT id, event_name, event_date, event_time, location
                FROM events
                WHERE is_active = 1 AND event_date >= :today
                ORDER BY event_date ASC, event_time ASC
                LIMIT 20
            ");
        }
        $stmtEvents->execute([':today' => $today]);
        $upcoming = $stmtEvents->fetchAll(PDO::FETCH_ASSOC);

        foreach ($upcoming as $ev) {
            $ev['attendance_locked'] = isset($ev['attendance_locked']) ? (int)$ev['attendance_locked'] : 0;
            if (isStudentEligibleForEventByCourse($db, $currentUser['course'], (int)$ev['id'])) {
                $qrStudentEvents[] = $ev;
            }
            if (count($qrStudentEvents) >= 4) break; // compact list on QR screen
        }
    }
} catch (Exception $e) {
    // ignore
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My QR Code - Event Attendance</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body{
            margin:0;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background:#F5F7FB;
            color:#0f172a;
            min-height:100vh;
        }

        .qr-container {
            max-width: 420px;
            margin: 0 auto;
            padding: 18px 16px 16px;
        }
        
        .qr-card {
            background: white;
            border-radius: 20px;
            padding: 28px 22px;
            box-shadow: 0 16px 36px rgba(15, 23, 42, 0.14);
            text-align: center;
            margin-bottom: 30px;
        }
        
        .qr-header {
            margin-bottom: 30px;
        }
        
        .qr-header h1 {
            font-size: 2.5rem;
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 10px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .qr-header p {
            color: #718096;
            font-size: 1.1rem;
        }
        
        .qr-code-display {
            background: #f7fafc;
            border-radius: 15px;
            padding: 30px;
            margin: 30px 0;
            border: 2px dashed #e2e8f0;
        }
        
        .qr-image {
            max-width: 300px;
            width: 100%;
            height: auto;
            border-radius: 10px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        
        .qr-info {
            background: #f7fafc;
            border-radius: 15px;
            padding: 25px;
            margin: 20px 0;
        }
        
        .qr-info h3 {
            color: #2d3748;
            margin-bottom: 15px;
            font-size: 1.3rem;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }
        
        .info-item {
            background: white;
            padding: 15px;
            border-radius: 10px;
            border-left: 4px solid #667eea;
        }
        
        .info-label {
            font-weight: 500;
            color: #4a5568;
            font-size: 0.9rem;
        }
        
        .info-value {
            color: #2d3748;
            font-weight: 600;
            margin-top: 5px;
        }
        
        .qr-actions {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 30px;
            flex-wrap: wrap;
        }
        
        .btn {
            padding: 12px 25px;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            cursor: pointer;
            font-family: 'Inter', sans-serif;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }
        
        .btn-secondary {
            background: #e2e8f0;
            color: #4a5568;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
        }
        
        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }
        
        .back-link:hover {
            color: #5a67d8;
        }
        
        .header-links {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .signout-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 14px;
            border-radius: 10px;
            text-decoration: none;
            color: #b91c1c;
            font-weight: 600;
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.2);
            transition: all 0.3s ease;
        }

        .signout-link:hover {
            background: rgba(239, 68, 68, 0.16);
            transform: translateY(-1px);
        }
        
        .qr-code-text {
            background: #f7fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 15px;
            font-family: 'Courier New', monospace;
            font-size: 0.9rem;
            word-break: break-all;
            margin-top: 15px;
        }

        /* Compact events list under QR card */
        .events-card {
            background: #ffffff;
            border-radius: 18px;
            padding: 16px 14px;
            box-shadow: 0 10px 26px rgba(15, 23, 42, 0.10);
            margin-bottom:16px;
            border:1px solid rgba(15,23,42,0.06);
        }

        .events-header{
            display:flex;
            align-items:center;
            justify-content:space-between;
            margin-bottom:8px;
        }

        .events-title{
            font-size:13px;
            text-transform:uppercase;
            letter-spacing:0.06em;
            color:#64748b;
            font-weight:700;
        }

        .event-pill{
            font-size:11px;
            padding:6px 9px;
            border-radius:999px;
            background:linear-gradient(135deg, rgba(59,130,246,0.10), rgba(139,92,246,0.10));
            color:#4f46e5;
            font-weight:600;
        }

        .event-row{
            padding:10px 6px;
            border-radius:14px;
            display:flex;
            align-items:flex-start;
            gap:10px;
        }

        .event-row + .event-row{
            margin-top:4px;
        }

        .event-date-dot{
            width:28px;
            height:28px;
            border-radius:999px;
            background:linear-gradient(135deg,#3b82f6,#8b5cf6);
            color:#fff;
            font-size:11px;
            display:flex;
            flex-direction:column;
            align-items:center;
            justify-content:center;
            flex-shrink:0;
        }

        .event-main{
            flex:1;
        }

        .event-name{
            font-size:13px;
            font-weight:700;
            margin:0 0 3px;
        }

        .event-meta-line{
            font-size:11px;
            color:#64748b;
            display:flex;
            align-items:center;
            gap:6px;
        }

        .event-meta-line i{
            font-size:11px;
            color:#7c3aed;
        }

        .lock-badge{
            font-size:11px;
            padding:5px 8px;
            border-radius:999px;
            background:rgba(239,68,68,0.12);
            color:#b91c1c;
            font-weight:600;
        }
        
        @media (max-width: 768px) {
            .qr-container {
                padding: 16px 14px 14px;
            }
            
            .qr-card {
                padding: 25px 20px;
            }
            
            .qr-header h1 {
                font-size: 2rem;
            }
            
            .qr-actions {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="qr-container">
        <div class="header-links">
            <a href="dashboard.php" class="back-link">← Back to Dashboard</a>
            <a href="signout.php" class="signout-link"><i class="fas fa-right-from-bracket"></i> Sign out</a>
        </div>
        
        <div class="qr-card">
            <div class="qr-header">
                <h1>My QR Code</h1>
                <p>Use this QR code for event attendance and identification</p>
            </div>
            
            <?php if ($qrLocked): ?>
                <div class="qr-code-display">
                    <div style="text-align: center; padding: 40px 20px;">
                        <i class="fas fa-lock" style="font-size: 64px; color: #e53e3e; margin-bottom: 20px;"></i>
                        <h2 style="color: #e53e3e; margin-bottom: 15px;">QR Code Locked</h2>
                        <p style="color: #718096; font-size: 1.1rem; line-height: 1.6;">
                            Your QR code has been locked by an administrator.<br>
                            Please contact the administration office to unlock your QR code.
                        </p>
                    </div>
                </div>
            <?php elseif ($qrData && $qrData['qr_code_path']): ?>
                <div class="qr-code-display">
                    <img src="<?php echo htmlspecialchars($qrData['qr_code_path']); ?>" 
                         alt="QR Code for <?php echo htmlspecialchars($currentUser['first_name'] . ' ' . $currentUser['last_name']); ?>" 
                         class="qr-image">
                    
                    <div class="qr-code-text">
                        <strong>QR Code:</strong> <?php echo htmlspecialchars($qrData['qr_code']); ?>
                    </div>
                </div>
                
                <div class="qr-info">
                    <h3>QR Code Information</h3>
                    <div class="info-grid">
                        <div class="info-item">
                            <div class="info-label">Student Name</div>
                            <div class="info-value"><?php echo htmlspecialchars($currentUser['first_name'] . ' ' . $currentUser['last_name']); ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Student ID</div>
                            <div class="info-value"><?php echo htmlspecialchars($currentUser['student_id']); ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Email</div>
                            <div class="info-value"><?php echo htmlspecialchars($currentUser['email']); ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Course</div>
                            <div class="info-value"><?php echo htmlspecialchars($currentUser['course'] ?: 'Not specified'); ?></div>
                        </div>
                    </div>
                </div>
                
                <div class="qr-actions">
                    <button onclick="downloadQR()" class="btn btn-primary">Download QR Code</button>
                    <button onclick="printQR()" class="btn btn-secondary">Print QR Code</button>
                    <button onclick="shareQR()" class="btn btn-secondary">Share QR Code</button>
                </div>
            <?php else: ?>
                <div class="qr-code-display">
                    <p style="color: #e53e3e; font-weight: 500;">QR Code not found. Please contact support.</p>
                </div>
            <?php endif; ?>
        </div>

        <div class="events-card">
            <div class="events-header">
                <span class="events-title">My Events</span>
                <span class="event-pill"><i class="fas fa-calendar-day" style="margin-right:4px;"></i><?= h(date('M d')) ?></span>
            </div>

            <?php if ($profileIncomplete): ?>
                <div class="event-row" style="background:rgba(251,191,36,0.06);">
                    <div class="event-main">
                        <p class="event-name">Complete your profile to see events</p>
                        <div class="event-meta-line">
                            <i class="fas fa-circle-info"></i>
                            <span>Set your course and year level to unlock event list.</span>
                        </div>
                    </div>
                </div>
            <?php elseif (!$qrStudentEvents): ?>
                <div class="event-row">
                    <div class="event-main">
                        <p class="event-name">No upcoming events yet</p>
                        <div class="event-meta-line">
                            <i class="fas fa-bell"></i>
                            <span>When an event matches your course, it will appear here.</span>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($qrStudentEvents as $ev): ?>
                    <?php
                        $isToday = ($ev['event_date'] === date('Y-m-d'));
                        $locked = !empty($ev['attendance_locked']);
                    ?>
                    <div class="event-row">
                        <div class="event-date-dot">
                            <span><?= h(date('M', strtotime($ev['event_date']))) ?></span>
                            <span><?= h(date('d', strtotime($ev['event_date']))) ?></span>
                        </div>
                        <div class="event-main">
                            <p class="event-name"><?= h($ev['event_name']) ?></p>
                            <div class="event-meta-line">
                                <i class="fas fa-clock"></i>
                                <span><?= h(date('g:i A', strtotime($ev['event_time']))) ?></span>
                            </div>
                            <div class="event-meta-line">
                                <i class="fas fa-location-dot"></i>
                                <span><?= h($ev['location'] ?: 'TBA') ?></span>
                            </div>
                        </div>
                        <?php if ($locked): ?>
                            <span class="lock-badge"><i class="fas fa-lock" style="margin-right:4px;"></i>Locked</span>
                        <?php elseif ($isToday): ?>
                            <span class="lock-badge" style="background:rgba(16,185,129,0.12);color:#047857;"><i class="fas fa-bolt" style="margin-right:4px;"></i>Today</span>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        function downloadQR() {
            const qrImage = document.querySelector('.qr-image');
            if (qrImage) {
                const link = document.createElement('a');
                link.href = qrImage.src;
                link.download = 'qr_code_<?php echo $currentUser['student_id']; ?>.png';
                link.click();
            }
        }
        
        function printQR() {
            const qrCard = document.querySelector('.qr-card');
            if (qrCard) {
                const printWindow = window.open('', '_blank');
                printWindow.document.write(`
                    <html>
                        <head>
                            <title>QR Code - <?php echo $currentUser['first_name'] . ' ' . $currentUser['last_name']; ?></title>
                            <style>
                                body { font-family: 'Roboto', sans-serif; text-align: center; padding: 20px; }
                                .qr-image { max-width: 300px; width: 100%; }
                                .qr-info { margin-top: 20px; }
                            </style>
                        </head>
                        <body>
                            ${qrCard.innerHTML}
                        </body>
                    </html>
                `);
                printWindow.document.close();
                printWindow.print();
            }
        }
        
        function shareQR() {
            if (navigator.share) {
                navigator.share({
                    title: 'My QR Code - Event Attendance',
                    text: 'Check out my QR code for event attendance',
                    url: window.location.href
                });
            } else {
                // Fallback for browsers that don't support Web Share API
                const qrCode = '<?php echo $qrData['qr_code'] ?? ''; ?>';
                navigator.clipboard.writeText(`My QR Code: ${qrCode}\nURL: ${window.location.href}`);
                alert('QR Code information copied to clipboard!');
            }
        }
    </script>
    
    <?php include 'includes/bottom_navbar.php'; ?>
</body>
</html>
