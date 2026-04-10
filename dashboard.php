<?php
require_once 'includes/auth.php';

$auth = new Auth();
$currentUser = $auth->getCurrentUser();

if (!$currentUser) {
    header('Location: signin.php');
    exit;
}

function h($v){ return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8'); }

// Force refresh user data from database to get latest profile status
try {
    $database = new Database();
    $db = $database->getConnection();
    $stmt = $db->prepare("SELECT * FROM students WHERE id = ?");
    $stmt->execute([$currentUser['id']]);
    $freshUserData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($freshUserData) {
        // Update session with fresh data
        $_SESSION['user'] = $freshUserData;
        $currentUser = $freshUserData;
    }
} catch (Exception $e) {
    // If database error, continue with session data
}

// Check if profile is incomplete
$profileIncomplete = false;
$profileCompleted = !empty($currentUser['profile_completed']) || (!empty($currentUser['course']) && !empty($currentUser['year_level']));
$hasCourse = !empty($currentUser['course']);
$hasYearLevel = !empty($currentUser['year_level']);

// Debug: Uncomment the line below to see profile status in browser console
// echo "<script>console.log('Profile Status:', {profileCompleted: $profileCompleted, hasCourse: $hasCourse, hasYearLevel: $hasYearLevel});</script>";

if (!$hasCourse || !$hasYearLevel || !$profileCompleted) {
    $profileIncomplete = true;
}

/**
 * Eligibility check (same intent as admin_attendance/admin_qr_scanner):
 * - If event has no filters, any active student can attend
 * - If event_departments has rows for event, student must belong to those departments (via courses table mapping)
 * - If event_courses has rows for event, student course must match one of those course_names
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

// Load upcoming events for this student (today + future)
$studentEvents = [];
$attendanceByEventId = [];
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
                LIMIT 30
            ");
        } else {
            $stmtEvents = $db->prepare("
                SELECT id, event_name, event_date, event_time, location
                FROM events
                WHERE is_active = 1 AND event_date >= :today
                ORDER BY event_date ASC, event_time ASC
                LIMIT 30
            ");
        }
        $stmtEvents->execute([':today' => $today]);
        $upcoming = $stmtEvents->fetchAll(PDO::FETCH_ASSOC);

        // Filter by eligibility
        foreach ($upcoming as $ev) {
            $ev['attendance_locked'] = isset($ev['attendance_locked']) ? (int)$ev['attendance_locked'] : 0;
            if (isStudentEligibleForEventByCourse($db, $currentUser['course'], (int)$ev['id'])) {
                $studentEvents[] = $ev;
            }
            if (count($studentEvents) >= 6) break; // keep it tight for mobile
        }

        // Fetch attendance states for these events (single query)
        if ($studentEvents) {
            $eventIds = array_map(fn($e) => (int)$e['id'], $studentEvents);
            $in = implode(',', array_fill(0, count($eventIds), '?'));
            $stmtAtt = $db->prepare("SELECT event_id, time_in, time_out, time_in_2, time_out_2 FROM attendance WHERE student_id = ? AND event_id IN ($in)");
            $stmtAtt->execute(array_merge([(int)$currentUser['id']], $eventIds));
            foreach ($stmtAtt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $attendanceByEventId[(int)$row['event_id']] = $row;
            }
        }
    }
} catch (Exception $e) {
    // ignore (dashboard should still load)
}

$attendanceAlerts = [];
try {
    if (isset($db)) {
        $hasSnTbl = $db->query("SHOW TABLES LIKE 'student_notifications'")->fetch();
        if ($hasSnTbl) {
            $snStmt = $db->prepare(
                'SELECT id, title, body, created_at, read_at FROM student_notifications WHERE student_id = ? ORDER BY created_at DESC LIMIT 12'
            );
            $snStmt->execute([(int) $currentUser['id']]);
            $attendanceAlerts = $snStmt->fetchAll(PDO::FETCH_ASSOC);
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
    <title>Dashboard - Event Attendance</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        :root{
            --bg: #F5F7FB;
            --card: #ffffff;
            --text: #0f172a;
            --muted: #64748b;
            --border: rgba(15, 23, 42, 0.08);
            --shadow: 0 10px 30px rgba(15, 23, 42, 0.08);
            --shadow-sm: 0 6px 16px rgba(15, 23, 42, 0.08);
            --grad: linear-gradient(135deg, #3b82f6 0%, #8b5cf6 100%);
            --grad-soft: linear-gradient(135deg, rgba(59,130,246,0.10) 0%, rgba(139,92,246,0.10) 100%);
            --radius: 18px;
        }

        /* Override assets/css/style.css (auth layout: flex + padding centers content vertically) */
        body{
            margin: 0;
            padding: 0;
            padding-left: env(safe-area-inset-left, 0px);
            padding-right: env(safe-area-inset-right, 0px);
            padding-top: env(safe-area-inset-top, 0px);
            display: block;
            align-items: unset;
            justify-content: unset;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: var(--bg) !important;
            color: var(--text);
            min-height: 100vh;
        }

        .dash-shell{
            width: 100%;
            max-width: 420px; /* iPhone 390 + comfortable gutters */
            margin: 0 auto;
            padding: 12px 16px 16px;
        }

        .card{
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            box-shadow: var(--shadow-sm);
        }

        .welcome-card{
            padding: 18px;
            background: var(--grad);
            color: #fff;
            border: none;
            box-shadow: 0 18px 40px rgba(59,130,246,0.22);
        }

        .welcome-top{
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 12px;
        }

        .welcome-title{
            margin: 0;
            font-size: 20px;
            font-weight: 700;
            letter-spacing: -0.02em;
            line-height: 1.2;
        }

        .welcome-sub{
            margin: 6px 0 0;
            font-size: 13px;
            opacity: 0.92;
            line-height: 1.35;
        }

        .welcome-pill{
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 9px 12px;
            border-radius: 999px;
            background: rgba(255,255,255,0.18);
            border: 1px solid rgba(255,255,255,0.20);
            font-size: 12px;
            font-weight: 600;
            white-space: nowrap;
        }

        .section{
            margin-top: 14px;
        }

        .section-head{
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin: 0 2px 10px;
        }

        .section-title{
            margin: 0;
            font-size: 14px;
            color: var(--muted);
            font-weight: 700;
            letter-spacing: 0.02em;
            text-transform: uppercase;
        }

        .info-card{
            padding: 14px;
        }

        .info-row{
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
            padding: 12px 8px;
            border-bottom: 1px solid rgba(15, 23, 42, 0.06);
        }

        .info-row:last-child{
            border-bottom: none;
        }

        .info-left{
            display: flex;
            align-items: center;
            gap: 10px;
            min-width: 0;
        }

        .info-icon{
            width: 34px;
            height: 34px;
            border-radius: 12px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: var(--grad-soft);
            color: #6d28d9;
            flex-shrink: 0;
        }

        .info-label{
            font-size: 13px;
            color: var(--muted);
            font-weight: 600;
            white-space: nowrap;
        }

        .info-value{
            font-size: 13px;
            font-weight: 700;
            color: var(--text);
            text-align: right;
            word-break: break-word;
        }

        .events-card{
            padding: 14px;
        }

        .event-item{
            padding: 12px;
            border-radius: 16px;
            border: 1px solid rgba(15, 23, 42, 0.08);
            background: #fff;
            box-shadow: 0 8px 18px rgba(15, 23, 42, 0.06);
            margin-top: 10px;
        }

        .event-item:first-child{ margin-top: 0; }

        .event-top{
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 10px;
        }

        .event-title{
            margin: 0;
            font-size: 15px;
            font-weight: 800;
            letter-spacing: -0.01em;
        }

        .event-meta{
            margin-top: 8px;
            display: grid;
            gap: 6px;
        }

        .event-meta-row{
            display: flex;
            align-items: center;
            gap: 8px;
            color: var(--muted);
            font-size: 13px;
            font-weight: 600;
        }

        .event-meta-row i{
            width: 16px;
            text-align: center;
            color: #7c3aed;
        }

        .pill{
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 7px 10px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 800;
            white-space: nowrap;
        }

        .pill-today{
            background: linear-gradient(135deg, rgba(16,185,129,0.16), rgba(34,197,94,0.12));
            color: #047857;
            border: 1px solid rgba(16,185,129,0.25);
        }

        .pill-upcoming{
            background: var(--grad-soft);
            color: #5b21b6;
            border: 1px solid rgba(139,92,246,0.25);
        }

        .pill-locked{
            background: linear-gradient(135deg, rgba(239,68,68,0.16), rgba(244,63,94,0.12));
            color: #b91c1c;
            border: 1px solid rgba(239,68,68,0.28);
        }

        .event-att{
            margin-top: 10px;
            padding-top: 10px;
            border-top: 1px dashed rgba(15, 23, 42, 0.16);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            color: var(--muted);
            font-size: 12px;
            font-weight: 700;
        }

        .link-btn{
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 12px;
            border-radius: 14px;
            text-decoration: none;
            font-weight: 800;
            font-size: 13px;
            background: rgba(255,255,255,0.18);
            border: 1px solid rgba(255,255,255,0.22);
            color: #fff;
            white-space: nowrap;
        }

        /* Success Notification Styles */
        .success-notification {
            background: linear-gradient(135deg, #d4edda, #c3e6cb);
            border: 2px solid #28a745;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(40, 167, 69, 0.2);
        }
        
        .success-notification .notification-icon {
            color: #155724;
            font-size: 32px;
            flex-shrink: 0;
        }
        
        .success-notification .notification-text h3 {
            margin: 0 0 8px 0;
            color: #155724;
            font-size: 20px;
            font-weight: 700;
        }
        
        .success-notification .notification-text p {
            margin: 0;
            color: #155724;
            font-size: 16px;
            line-height: 1.5;
        }
        
        /* Profile Notification Styles */
        .profile-notification {
            background: linear-gradient(135deg, #fef3c7, #fde68a);
            border: 2px solid #f59e0b;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(245, 158, 11, 0.2);
        }
        
        .notification-content {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .notification-icon {
            color: #d97706;
            font-size: 32px;
            flex-shrink: 0;
        }
        
        .notification-text {
            flex: 1;
        }
        
        .notification-text h3 {
            margin: 0 0 8px 0;
            color: #92400e;
            font-size: 20px;
            font-weight: 700;
        }
        
        .notification-text p {
            margin: 0;
            color: #92400e;
            font-size: 16px;
            line-height: 1.5;
        }
        
        .notification-action {
            flex-shrink: 0;
        }
        
        .btn-warning {
            background: linear-gradient(135deg, #f59e0b, #d97706);
            color: white;
            padding: 12px 24px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(245, 158, 11, 0.3);
        }
        
        .btn-warning:hover {
            background: linear-gradient(135deg, #d97706, #b45309);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(245, 158, 11, 0.4);
        }
        
        @media (max-width: 768px) {
            .notification-content {
                flex-direction: column;
                text-align: center;
                gap: 15px;
            }
            
            .notification-icon {
                font-size: 24px;
            }
            
            .dash-shell { padding: 16px 14px 14px; }
        }

        .alerts-card { padding: 14px; }
        .alert-item {
            display: flex;
            gap: 12px;
            align-items: flex-start;
            padding: 12px;
            border-radius: 14px;
            border: 1px solid rgba(16, 185, 129, 0.22);
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.08) 0%, rgba(59, 130, 246, 0.06) 100%);
            margin-top: 10px;
        }
        .alert-item:first-child { margin-top: 0; }
        .alert-item.unread {
            border-color: rgba(59, 130, 246, 0.28);
            box-shadow: 0 6px 16px rgba(59, 130, 246, 0.08);
        }
        .alert-item-icon {
            width: 36px;
            height: 36px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(255, 255, 255, 0.85);
            color: #059669;
            flex-shrink: 0;
        }
        .alert-item-text h3 {
            margin: 0 0 4px;
            font-size: 14px;
            font-weight: 800;
            color: var(--text);
        }
        .alert-item-text p {
            margin: 0;
            font-size: 13px;
            color: var(--muted);
            line-height: 1.45;
            font-weight: 600;
        }
        .alert-item-meta {
            margin-top: 6px;
            font-size: 11px;
            color: var(--muted);
            font-weight: 600;
            opacity: 0.85;
        }
    </style>
</head>
<body>
    <div class="dash-shell">
        <?php if (isset($_GET['profile_completed']) && $_GET['profile_completed'] == '1'): ?>
        <div class="success-notification">
            <div class="notification-content">
                <div class="notification-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="notification-text">
                    <h3>Profile Completed Successfully!</h3>
                    <p>Your academic information has been set and you can now access all features.</p>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if ($profileIncomplete): ?>
        <div class="profile-notification">
            <div class="notification-content">
                <div class="notification-icon">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div class="notification-text">
                    <h3>Complete Your Profile</h3>
                    <p>You need to complete your academic information to access all features. This can only be set once.</p>
                </div>
                <div class="notification-action">
                    <a href="student_profile_completion.php" class="btn btn-warning">
                        <i class="fas fa-user-edit"></i> Complete Profile
                    </a>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="card welcome-card">
            <div class="welcome-top">
                <div>
                    <h1 class="welcome-title">Hi, <?= h($currentUser['first_name']) ?>.</h1>
                    <p class="welcome-sub">Welcome back — here are your upcoming events and profile.</p>
                </div>
                <a class="link-btn desktop-buttons" href="signout.php" title="Sign out">
                    <i class="fas fa-right-from-bracket"></i>
                    <span>Sign out</span>
                </a>
                <span class="welcome-pill" aria-label="Today">
                    <i class="fas fa-calendar-day"></i>
                    <?= h(date('M d')) ?>
                </span>
            </div>
        </div>

        <?php if ($attendanceAlerts): ?>
        <div class="section" style="margin-top:16px;">
            <div class="section-head">
                <h2 class="section-title">Attendance updates</h2>
                <a href="notifications.php" style="font-size:12px;font-weight:800;color:#2563eb;text-decoration:none;">See all</a>
            </div>
            <div class="card alerts-card">
                <?php foreach ($attendanceAlerts as $al): ?>
                    <?php
                        $unread = empty($al['read_at']);
                        $when = !empty($al['created_at']) ? strtotime($al['created_at']) : false;
                        $whenStr = $when ? date('M j, g:i A', $when) : '';
                    ?>
                    <div class="alert-item<?= $unread ? ' unread' : '' ?>">
                        <div class="alert-item-icon" aria-hidden="true">
                            <i class="fas fa-circle-check"></i>
                        </div>
                        <div class="alert-item-text">
                            <h3><?= h($al['title']) ?></h3>
                            <p><?= h($al['body']) ?></p>
                            <?php if ($whenStr !== ''): ?>
                                <div class="alert-item-meta"><?= h($whenStr) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <div class="section" style="margin-top:16px;">
            <div class="section-head">
                <h2 class="section-title">My Events</h2>
            </div>
            <div class="card events-card">
                <?php if ($profileIncomplete): ?>
                    <div class="event-item" style="background: var(--grad-soft); border: 1px solid rgba(139,92,246,0.18); box-shadow: none;">
                        <div class="event-top">
                            <h3 class="event-title">Complete your profile to see events</h3>
                            <span class="pill pill-upcoming"><i class="fas fa-user-check"></i> Action</span>
                        </div>
                        <div class="event-meta">
                            <div class="event-meta-row">
                                <i class="fas fa-circle-info"></i>
                                <span>Set your course and year level once to unlock event access.</span>
                            </div>
                        </div>
                        <div class="event-att">
                            <span>Go to Profile Completion</span>
                            <a href="student_profile_completion.php" style="text-decoration:none; font-weight:900; color:#5b21b6;">Open</a>
                        </div>
                    </div>
                <?php elseif (!$studentEvents): ?>
                    <div class="event-item" style="background: #fff; border-style: dashed; box-shadow: none;">
                        <div class="event-top">
                            <h3 class="event-title">No upcoming events found</h3>
                            <span class="pill pill-upcoming"><i class="fas fa-clock"></i> Stay tuned</span>
                        </div>
                        <div class="event-meta">
                            <div class="event-meta-row">
                                <i class="fas fa-bell"></i>
                                <span>When an event matches your course/department, it will appear here.</span>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <?php foreach ($studentEvents as $ev): ?>
                        <?php
                            $isToday = ($ev['event_date'] === date('Y-m-d'));
                            $locked = !empty($ev['attendance_locked']);
                            $pillClass = $locked ? 'pill-locked' : ($isToday ? 'pill-today' : 'pill-upcoming');
                            $pillText = $locked ? 'Locked' : ($isToday ? 'Today' : 'Upcoming');
                            $pillIcon = $locked ? 'fa-lock' : ($isToday ? 'fa-bolt' : 'fa-calendar');
                            $att = $attendanceByEventId[(int)$ev['id']] ?? null;
                            $attLines = [];
                            if ($att) {
                                if (!empty($att['time_in'])) {
                                    $attLines[] = 'Time In 1: ' . date('g:i A', strtotime($att['time_in']));
                                }
                                if (!empty($att['time_out'])) {
                                    $attLines[] = 'Time Out 1: ' . date('g:i A', strtotime($att['time_out']));
                                }
                                if (!empty($att['time_in_2'])) {
                                    $attLines[] = 'Time In 2: ' . date('g:i A', strtotime($att['time_in_2']));
                                }
                                if (!empty($att['time_out_2'])) {
                                    $attLines[] = 'Time Out 2: ' . date('g:i A', strtotime($att['time_out_2']));
                                }
                            }
                        ?>
                        <div class="event-item">
                            <div class="event-top">
                                <h3 class="event-title"><?= h($ev['event_name']) ?></h3>
                                <span class="pill <?= $pillClass ?>"><i class="fas <?= h($pillIcon) ?>"></i> <?= h($pillText) ?></span>
                            </div>
                            <div class="event-meta">
                                <div class="event-meta-row">
                                    <i class="fas fa-calendar-day"></i>
                                    <span><?= h(date('M d, Y', strtotime($ev['event_date']))) ?></span>
                                </div>
                                <div class="event-meta-row">
                                    <i class="fas fa-clock"></i>
                                    <span><?= h(date('g:i A', strtotime($ev['event_time']))) ?></span>
                                </div>
                                <div class="event-meta-row">
                                    <i class="fas fa-location-dot"></i>
                                    <span><?= h($ev['location'] ?: 'TBA') ?></span>
                                </div>
                            </div>
                            <div class="event-att">
                                <span>
                                    <i class="fas fa-qrcode" style="margin-right:8px;"></i>
                                    <?php if (!$attLines): ?>
                                        <?= h('No scans yet') ?>
                                    <?php else: ?>
                                        <?php foreach ($attLines as $idx => $line): ?>
                                            <?= $idx > 0 ? '<br>' : '' ?><?= h($line) ?>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </span>
                                <a href="qr_code.php" style="text-decoration:none; font-weight:900; color:#5b21b6;">Open QR</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="section">
            <div class="section-head">
                <h2 class="section-title">Student Information</h2>
            </div>
            <div class="card info-card">
                <div class="info-row">
                    <div class="info-left">
                        <span class="info-icon"><i class="fas fa-id-card"></i></span>
                        <span class="info-label">Student ID</span>
                    </div>
                    <div class="info-value"><?= h($currentUser['student_id']) ?></div>
                </div>

                <div class="info-row">
                    <div class="info-left">
                        <span class="info-icon"><i class="fas fa-user"></i></span>
                        <span class="info-label">Full Name</span>
                    </div>
                    <div class="info-value"><?= h($currentUser['first_name'] . ' ' . $currentUser['last_name']) ?></div>
                </div>

                <div class="info-row">
                    <div class="info-left">
                        <span class="info-icon"><i class="fas fa-envelope"></i></span>
                        <span class="info-label">Email</span>
                    </div>
                    <div class="info-value"><?= h($currentUser['email']) ?></div>
                </div>

                <div class="info-row">
                    <div class="info-left">
                        <span class="info-icon"><i class="fas fa-graduation-cap"></i></span>
                        <span class="info-label">Course</span>
                    </div>
                    <div class="info-value"><?= h($currentUser['course'] ?: 'Not set') ?></div>
                </div>

                <div class="info-row">
                    <div class="info-left">
                        <span class="info-icon"><i class="fas fa-layer-group"></i></span>
                        <span class="info-label">Year Level</span>
                    </div>
                    <div class="info-value"><?= h($currentUser['year_level'] ?: 'Not set') ?></div>
                </div>

                <div class="info-row">
                    <div class="info-left">
                        <span class="info-icon"><i class="fas fa-phone"></i></span>
                        <span class="info-label">Phone</span>
                    </div>
                    <div class="info-value"><?= h($currentUser['phone'] ?: 'Not set') ?></div>
                </div>
            </div>
        </div>
    </div>
    
    <?php include 'includes/bottom_navbar.php'; ?>
</body>
</html>
