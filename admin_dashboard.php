<?php
require_once 'includes/admin_auth.php';
require_once 'config/database.php';

$adminAuth = new AdminAuth();
$currentAdmin = $adminAuth->requireAuth();

// Basic KPI metrics
$totalEvents = 0;
$totalStudents = 0;
$totalAttendance = 0;
$totalQRCodes = 0;
$studentsAllCount = 0;
$eventsAllCount = 0;
$chartLabels = [];
$chartStudentsSeries = [];
$chartEventsSeries = [];
$recentAttendance = [];

try {
    $database = new Database();
    $db = $database->getConnection();

    $totalEvents = (int)$db->query("SELECT COUNT(*) FROM events WHERE is_active = 1")->fetchColumn();
    $totalStudents = (int)$db->query("SELECT COUNT(*) FROM students WHERE is_active = 1")->fetchColumn();
    $totalAttendance = (int)$db->query("SELECT COUNT(*) FROM attendance")->fetchColumn();
    $totalQRCodes = (int)$db->query("SELECT COUNT(*) FROM students WHERE qr_code IS NOT NULL")->fetchColumn();

    $studentsAllCount = (int)$db->query("SELECT COUNT(*) FROM students")->fetchColumn();
    $eventsAllCount = (int)$db->query("SELECT COUNT(*) FROM events")->fetchColumn();

    $stuMap = [];
    $evMap = [];
    $stuStmt = $db->query("
        SELECT DATE_FORMAT(created_at, '%Y-%m') AS ym, COUNT(*) AS c
        FROM students
        WHERE created_at >= DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 5 MONTH), '%Y-%m-01')
        GROUP BY ym
    ");
    foreach ($stuStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $stuMap[$row['ym']] = (int)$row['c'];
    }
    $evStmt = $db->query("
        SELECT DATE_FORMAT(created_at, '%Y-%m') AS ym, COUNT(*) AS c
        FROM events
        WHERE created_at >= DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 5 MONTH), '%Y-%m-01')
        GROUP BY ym
    ");
    foreach ($evStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $evMap[$row['ym']] = (int)$row['c'];
    }

    try {
        $tz = new DateTimeZone(date_default_timezone_get());
    } catch (Exception $e) {
        $tz = new DateTimeZone('UTC');
    }
    $anchor = new DateTime('first day of this month', $tz);
    $anchor->modify('-5 months');
    for ($i = 0; $i < 6; $i++) {
        $m = clone $anchor;
        $m->modify('+' . $i . ' months');
        $ym = $m->format('Y-m');
        $chartLabels[] = $m->format('M Y');
        $chartStudentsSeries[] = $stuMap[$ym] ?? 0;
        $chartEventsSeries[] = $evMap[$ym] ?? 0;
    }

    $recentAttendanceStmt = $db->query("
        SELECT
            a.id,
            s.student_id,
            CONCAT(s.first_name, ' ', s.last_name) AS student_name,
            COALESCE(e.event_name, 'Unknown Event') AS event_name,
            COALESCE(e.venue, '') AS venue,
            a.attendance_time,
            COALESCE(a.status, 'present') AS status
        FROM attendance a
        LEFT JOIN students s ON s.id = a.student_id
        LEFT JOIN events e ON e.id = a.event_id
        ORDER BY a.attendance_time DESC, a.id DESC
        LIMIT 8
    ");
    $recentAttendance = $recentAttendanceStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Fail silently; UI will still render with zeros
}

if (count($chartLabels) !== 6) {
    $chartLabels = [];
    $chartStudentsSeries = [];
    $chartEventsSeries = [];
    try {
        $tzFill = new DateTimeZone(date_default_timezone_get());
    } catch (Exception $e) {
        $tzFill = new DateTimeZone('UTC');
    }
    $anchorFill = new DateTime('first day of this month', $tzFill);
    $anchorFill->modify('-5 months');
    for ($i = 0; $i < 6; $i++) {
        $m = clone $anchorFill;
        $m->modify('+' . $i . ' months');
        $chartLabels[] = $m->format('M Y');
        $chartStudentsSeries[] = 0;
        $chartEventsSeries[] = 0;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Event Attendance</title>
    <script>
    (function () {
        try {
            var savedTheme = localStorage.getItem('attendtrack-admin-theme');
            if (savedTheme === 'dark') {
                document.documentElement.classList.add('admin-dark');
            }
        } catch (e) {}
    })();
    </script>
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
                    <span class="crumb-current">Dashboard</span>
                </div>
                <h1 class="page-title">Welcome back, <?php echo htmlspecialchars(explode(' ', trim((string)$currentAdmin['full_name']))[0] ?: 'Admin'); ?>!</h1>
            </div>
            <div class="header-right">
                <div class="search-wrapper">
                    <i class="fas fa-magnifying-glass"></i>
                    <input type="text" id="dashboardSearch" placeholder="Search..." aria-label="Search dashboard">
                </div>
                <button class="icon-btn" type="button" aria-label="Notifications">
                    <i class="fas fa-bell"></i>
                    <span class="badge-dot"></span>
                </button>
                <button class="icon-btn theme-toggle" type="button" aria-label="Toggle dark mode" onclick="toggleDarkMode()">
                    <i class="fas fa-moon"></i>
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
                        <a href="admin_signout.php" class="danger"><i class="fas fa-right-from-bracket"></i> Sign out</a>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="admin-main">
            <div class="kpi-grid dashboard-search-targets">
                <div class="kpi-card glass searchable-card" data-search="total events active events events">
                    <div class="kpi-header">
                        <div class="kpi-icon kpi-indigo">
                            <i class="fas fa-calendar-alt"></i>
                        </div>
                        <span class="kpi-label">Total Events</span>
                    </div>
                    <div class="kpi-value" data-target="<?php echo $totalEvents; ?>">0</div>
                    <div class="kpi-footer">
                        <span><i class="fas fa-circle" aria-hidden="true"></i> Active events</span>
                    </div>
                </div>
                
                <div class="kpi-card glass searchable-card" data-search="students total students active student records">
                    <div class="kpi-header">
                        <div class="kpi-icon kpi-violet">
                            <i class="fas fa-users"></i>
                        </div>
                        <span class="kpi-label">Students</span>
                    </div>
                    <div class="kpi-value" data-target="<?php echo $totalStudents; ?>">0</div>
                    <div class="kpi-footer">
                        <span><i class="fas fa-user-graduate"></i> Active records</span>
                    </div>
                </div>
                
                <div class="kpi-card glass searchable-card" data-search="attendance attendance logs total scans">
                    <div class="kpi-header">
                        <div class="kpi-icon kpi-green">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <span class="kpi-label">Attendance</span>
                    </div>
                    <div class="kpi-value" data-target="<?php echo $totalAttendance; ?>">0</div>
                    <div class="kpi-footer">
                        <span><i class="fas fa-clock"></i> Total scans</span>
                    </div>
                </div>
                
                <div class="kpi-card glass searchable-card" data-search="qr codes generated codes qr">
                    <div class="kpi-header">
                        <div class="kpi-icon kpi-sky">
                            <i class="fas fa-qrcode"></i>
                        </div>
                        <span class="kpi-label">QR Codes</span>
                    </div>
                    <div class="kpi-value" data-target="<?php echo $totalQRCodes; ?>">0</div>
                    <div class="kpi-footer">
                        <span><i class="fas fa-layer-group"></i> Generated codes</span>
                    </div>
                </div>
            </div>
            
            <div class="dashboard-layout">
                <section class="panel panel-wide searchable-card" data-search="students overview events overview chart graph monthly overview">
                    <header class="panel-header">
                        <div class="panel-title-wrap">
                            <h2 class="panel-title"><i class="fas fa-chart-column"></i> Students &amp; events overview</h2>
                            <span class="panel-subtitle">Totals and new registrations by month (last 6 months)</span>
                        </div>
                        <button class="pill-btn" type="button" onclick="location.reload()">
                            <i class="fas fa-rotate-right"></i> Refresh
                        </button>
                    </header>
                    <div class="panel-body overview-panel-body">
                        <div class="overview-stats-row">
                            <div class="overview-stat-card">
                                <div class="overview-stat-icon overview-stat-violet">
                                    <i class="fas fa-user-graduate"></i>
                                </div>
                                <div class="overview-stat-text">
                                    <span class="overview-stat-label">Total students</span>
                                    <span class="overview-stat-value"><?php echo number_format($studentsAllCount); ?></span>
                                    <span class="overview-stat-hint">All student accounts</span>
                                </div>
                            </div>
                            <div class="overview-stat-card">
                                <div class="overview-stat-icon overview-stat-indigo">
                                    <i class="fas fa-calendar-days"></i>
                                </div>
                                <div class="overview-stat-text">
                                    <span class="overview-stat-label">Total events</span>
                                    <span class="overview-stat-value"><?php echo number_format($eventsAllCount); ?></span>
                                    <span class="overview-stat-hint">All events (including inactive)</span>
                                </div>
                            </div>
                        </div>
                        <div class="overview-chart-card">
                            <div class="overview-chart-head">
                                <span class="overview-chart-title">New per month</span>
                                <span class="overview-chart-legend">
                                    <span class="lg lg-stu"><i class="fas fa-square"></i> Students</span>
                                    <span class="lg lg-ev"><i class="fas fa-square"></i> Events</span>
                                </span>
                            </div>
                            <div class="overview-chart-wrap">
                                <canvas id="adminOverviewChart" aria-label="Students and events per month"></canvas>
                            </div>
                        </div>
                    </div>
                </section>
                
                <section class="panel panel-side searchable-card" data-search="quick actions create event attendance sheets scan qr manage students">
                    <header class="panel-header">
                        <div class="panel-title-wrap">
                            <h2 class="panel-title"><i class="fas fa-bolt-lightning"></i> Quick Actions</h2>
                            <span class="panel-subtitle">Most common administration tasks</span>
                        </div>
                    </header>
                    <div class="panel-body">
                        <div class="quick-actions-grid">
                            <a href="admin_create_event.php" class="quick-chip chip-purple searchable-link" data-search="create event">
                                <i class="fas fa-plus-circle"></i>
                                <span>Create Event</span>
                            </a>
                            <a href="admin_attendance.php" class="quick-chip chip-indigo searchable-link" data-search="attendance sheets attendance">
                                <i class="fas fa-clipboard-check"></i>
                                <span>Attendance Sheets</span>
                            </a>
                            <a href="admin_qr_scanner.php" class="quick-chip chip-green searchable-link" data-search="scan qr qr scanner">
                                <i class="fas fa-qrcode"></i>
                                <span>Scan QR</span>
                            </a>
                            <a href="admin_students.php" class="quick-chip chip-sky searchable-link" data-search="manage students students">
                                <i class="fas fa-user-graduate"></i>
                                <span>Manage Students</span>
                            </a>
                           
                           
                        </div>
                    </div>
                </section>
            </div>

            <section class="panel panel-full searchable-card" data-search="recent attendance latest attendance logs table records">
                <header class="panel-header">
                    <div class="panel-title-wrap">
                        <h2 class="panel-title"><i class="fas fa-table-list"></i> Recent Attendance</h2>
                        <span class="panel-subtitle">Latest scan records across events</span>
                    </div>
                    <a href="admin_attendance.php" class="panel-link">View All <i class="fas fa-chevron-right"></i></a>
                </header>
                <div class="panel-body">
                    <div class="attendance-table-wrap">
                        <table class="attendance-table">
                            <thead>
                                <tr>
                                    <th>Student ID</th>
                                    <th>Name</th>
                                    <th>Event</th>
                                    <th>Time</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody id="recentAttendanceBody">
                                <?php if (empty($recentAttendance)): ?>
                                    <tr class="attendance-empty">
                                        <td colspan="5">No attendance records yet.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($recentAttendance as $row): ?>
                                        <tr class="attendance-row" data-search="<?php echo htmlspecialchars(strtolower(trim(($row['student_id'] ?? '') . ' ' . ($row['student_name'] ?? '') . ' ' . ($row['event_name'] ?? '') . ' ' . ($row['venue'] ?? '') . ' ' . ($row['status'] ?? '')))); ?>">
                                            <td><?php echo htmlspecialchars($row['student_id'] ?: 'N/A'); ?></td>
                                            <td>
                                                <div class="att-name"><?php echo htmlspecialchars($row['student_name'] ?: 'Unknown Student'); ?></div>
                                                <div class="att-sub"><?php echo htmlspecialchars($row['venue'] ?: 'No venue'); ?></div>
                                            </td>
                                            <td><?php echo htmlspecialchars($row['event_name']); ?></td>
                                            <td><?php echo $row['attendance_time'] ? htmlspecialchars(date('M d, Y g:i A', strtotime($row['attendance_time']))) : 'Not set'; ?></td>
                                            <td>
                                                <span class="status-pill status-<?php echo htmlspecialchars(strtolower($row['status'])); ?>">
                                                    <i class="fas fa-circle"></i>
                                                    <?php echo htmlspecialchars(ucfirst($row['status'])); ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>
        </div>
    </div>
    
    <style>
    /* Admin Layout Styles */
    .admin-body {
        background:
            radial-gradient(circle at top left, rgba(170, 191, 255, 0.42) 0, transparent 28%),
            radial-gradient(circle at top right, rgba(214, 194, 255, 0.28) 0, transparent 26%),
            linear-gradient(180deg, #f3f2ff 0%, #f7f7ff 100%);
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        margin: 0;
        padding: 0;
        color: #0f172a;
    }

    html.admin-dark .admin-body {
        background:
            radial-gradient(circle at top left, rgba(74, 91, 180, 0.24) 0, transparent 24%),
            radial-gradient(circle at top right, rgba(102, 79, 153, 0.18) 0, transparent 26%),
            linear-gradient(180deg, #0f172a 0%, #111827 100%);
        color: #e5eefc;
    }
    
    .admin-content {
        margin-left: 304px;
        min-height: 100vh;
        background: transparent;
        transition: margin-left 0.24s ease;
    }

    body.sidebar-collapsed .admin-content {
        margin-left: 146px;
    }
    
    .admin-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        padding: 16px 22px;
        position: sticky;
        top: 18px;
        z-index: 120;
        backdrop-filter: blur(18px);
        background: rgba(255,255,255,0.78);
        border: 1px solid rgba(218, 224, 255, 0.95);
        box-shadow: 0 18px 42px rgba(129, 140, 248, 0.14);
        border-radius: 22px;
        margin: 18px 22px 0;
    }

    html.admin-dark .admin-header {
        background: rgba(15, 23, 42, 0.82);
        border-color: rgba(71, 85, 105, 0.72);
        box-shadow: 0 18px 42px rgba(2, 6, 23, 0.45);
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
        font-size: 26px;
        font-weight: 700;
        letter-spacing: -0.02em;
        color: #313d74;
    }

    html.admin-dark .breadcrumb,
    html.admin-dark .page-title,
    html.admin-dark .profile-name,
    html.admin-dark .kpi-value,
    html.admin-dark .overview-stat-value,
    html.admin-dark .timeline-title {
        color: #e5eefc;
    }

    html.admin-dark .crumb-current,
    html.admin-dark .panel-title i {
        color: #94a3ff;
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
        padding: 10px 14px;
        border-radius: 12px;
        background: #f6f7ff;
        border: 1px solid #e2e7ff;
        min-width: 260px;
        max-width: 320px;
    }

    html.admin-dark .search-wrapper,
    html.admin-dark .icon-btn,
    html.admin-dark .profile-trigger {
        background: #162033;
        border-color: #273449;
    }
    
    .search-wrapper i{
        color:#8f99c9;
        font-size:13px;
    }
    
    .search-wrapper input{
        border:none;
        outline:none;
        background:transparent;
        width:100%;
        font-size:13px;
        color:#394574;
        font-family:'Inter',sans-serif;
    }

    html.admin-dark .search-wrapper i,
    html.admin-dark .icon-btn,
    html.admin-dark .caret,
    html.admin-dark .profile-role,
    html.admin-dark .search-wrapper input,
    html.admin-dark .search-wrapper input::placeholder,
    html.admin-dark .kpi-label,
    html.admin-dark .kpi-footer,
    html.admin-dark .panel-title,
    html.admin-dark .panel-subtitle,
    html.admin-dark .overview-stat-label,
    html.admin-dark .overview-stat-hint,
    html.admin-dark .overview-chart-title,
    html.admin-dark .overview-chart-legend,
    html.admin-dark .timeline-meta {
        color: #95a3bf;
    }
    
    .search-wrapper input::placeholder{
        color:#9ca3af;
    }
    
    .icon-btn{
        border:none;
        background:#f6f7ff;
        border-radius:999px;
        width:38px;
        height:38px;
        display:inline-flex;
        align-items:center;
        justify-content:center;
        color:#6070a7;
        cursor:pointer;
        transition:all 0.18s ease;
        position:relative;
        border:1px solid #e3e8ff;
    }
    
    .icon-btn:hover{
        background:#edf0ff;
        color:#4f69f6;
        transform:translateY(-1px);
        box-shadow:0 12px 22px rgba(129,140,248,0.18);
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
        padding:6px 12px 6px 6px;
        border-radius:999px;
        border:1px solid #e3e8ff;
        background:#fbfcff;
        cursor:pointer;
        transition:all 0.2s ease;
    }
    
    .profile-trigger:hover{
        background:#ffffff;
        box-shadow:0 10px 24px rgba(15,23,42,0.18);
        transform:translateY(-1px);
    }
    
    .avatar-circle{
        width:34px;
        height:34px;
        border-radius:999px;
        background:linear-gradient(135deg,#5b7cfa,#7f8fff);
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

    html.admin-dark .profile-dropdown {
        background: #111827;
        border-color: #273449;
        box-shadow: 0 18px 40px rgba(2, 6, 23, 0.5);
    }

    html.admin-dark .profile-dropdown a {
        color: #e5eefc;
    }

    html.admin-dark .profile-dropdown a i {
        color: #95a3bf;
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
        padding: 20px 22px 32px;
        max-width: 1240px;
        margin: 0 auto;
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
    
    /* Mobile responsiveness */
    @media (max-width: 768px) {
        .sidebar-toggle {
            display: block;
        }
        
        .admin-content {
            margin-left: 0;
        }
        
        .admin-header {
            padding: 15px 18px;
            margin: 12px 12px 0;
            top: 12px;
        }
        
        .header-content h1 {
            font-size: 24px;
        }
        
        .admin-main {
            padding: 20px;
        }
    }
    
    .dashboard-stats {
        display:none; /* deprecated layout */
    }
    
    .kpi-grid{
        display:grid;
        grid-template-columns:repeat(auto-fit,minmax(190px,1fr));
        gap:14px;
        margin-bottom:18px;
    }
    
    .kpi-card{
        padding:14px 14px 12px;
        border-radius:18px;
        border:1px solid #e4e8ff;
        position:relative;
        overflow:hidden;
        background:linear-gradient(135deg, rgba(255,255,255,0.98), rgba(248,249,255,0.96));
        box-shadow:0 16px 32px rgba(139, 151, 212, 0.12);
        transition:transform 0.16s ease,box-shadow 0.16s ease, border-color 0.16s ease;
    }

    html.admin-dark .kpi-card,
    html.admin-dark .panel,
    html.admin-dark .overview-stat-card,
    html.admin-dark .overview-chart-card {
        background: linear-gradient(180deg, rgba(17, 24, 39, 0.96), rgba(15, 23, 42, 0.96));
        border-color: #273449;
        box-shadow: 0 18px 36px rgba(2, 6, 23, 0.35);
    }
    
    .kpi-card.glass::before{
        content:'';
        position:absolute;
        inset:-40%;
        background:linear-gradient(135deg,rgba(129,140,248,0.16),rgba(236,72,153,0.0));
        opacity:0.75;
        mix-blend-mode:soft-light;
        pointer-events:none;
    }
    
    .kpi-card:hover{
        transform:translateY(-3px);
        border-color:rgba(95,139,255,0.72);
        box-shadow:0 18px 34px rgba(95,139,255,0.18);
    }
    
    .kpi-header{
        display:flex;
        align-items:center;
        justify-content:space-between;
        position:relative;
        z-index:1;
        margin-bottom:8px;
    }
    
    .kpi-icon{
        width:32px;
        height:32px;
        border-radius:999px;
        display:flex;
        align-items:center;
        justify-content:center;
        color:#fff;
        font-size:14px;
        box-shadow:0 10px 25px rgba(15,23,42,0.25);
    }
    
    .kpi-indigo{ background:linear-gradient(135deg,#4f46e5,#6366f1); }
    .kpi-violet{ background:linear-gradient(135deg,#7c3aed,#a855f7); }
    .kpi-green{ background:linear-gradient(135deg,#16a34a,#22c55e); }
    .kpi-sky{ background:linear-gradient(135deg,#0ea5e9,#22d3ee); }
    
    .kpi-label{
        font-size:11px;
        text-transform:uppercase;
        letter-spacing:0.14em;
        color:#6b7280;
        font-weight:700;
    }
    
    .kpi-value{
        position:relative;
        z-index:1;
        font-size:26px;
        font-weight:800;
        letter-spacing:-0.04em;
        color:#0f172a;
        margin-bottom:4px;
    }
    
    .kpi-footer{
        position:relative;
        z-index:1;
        font-size:11px;
        color:#6b7280;
        display:flex;
        align-items:center;
        gap:6px;
    }
    
    .kpi-footer i{
        font-size:7px;
        color:#22c55e;
    }
    
    .dashboard-layout{
        display:grid;
        grid-template-columns: minmax(0,2.1fr) minmax(0,1.1fr);
        gap:18px;
        margin-bottom: 18px;
    }

    .panel-full {
        margin-bottom: 8px;
    }
    
    .panel{
        border-radius:20px;
        background:rgba(255,255,255,0.94);
        border:1px solid #e4e8ff;
        box-shadow:0 18px 36px rgba(139, 151, 212, 0.13);
        display:flex;
        flex-direction:column;
        min-height:0;
    }
    
    .panel-header{
        padding:14px 16px 10px;
        border-bottom:1px solid rgba(148,163,184,0.24);
        display:flex;
        align-items:center;
        justify-content:space-between;
        gap:10px;
    }

    html.admin-dark .panel-header {
        border-bottom-color: rgba(71, 85, 105, 0.45);
    }
    
    .panel-title-wrap{
        display:flex;
        flex-direction:column;
        gap:3px;
    }
    
    .panel-title{
        margin:0;
        font-size:13px;
        font-weight:700;
        text-transform:uppercase;
        letter-spacing:0.12em;
        color:#4b5563;
        display:flex;
        align-items:center;
        gap:8px;
    }
    
    .panel-title i{
        color:#6366f1;
    }
    
    .panel-subtitle{
        font-size:11px;
        color:#9ca3af;
    }

    .panel-link {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        text-decoration: none;
        font-size: 12px;
        font-weight: 700;
        color: #6b7cff;
    }

    html.admin-dark .panel-link {
        color: #9bb0ff;
    }
    
    .panel-body{
        padding:12px 14px 14px;
    }

    .overview-panel-body{
        display:flex;
        flex-direction:column;
        gap:14px;
    }
    .overview-stats-row{
        display:grid;
        grid-template-columns:repeat(2,minmax(0,1fr));
        gap:12px;
    }
    @media (max-width:600px){
        .overview-stats-row{ grid-template-columns:1fr; }
    }
    .overview-stat-card{
        display:flex;
        align-items:flex-start;
        gap:12px;
        padding:14px 14px;
        border-radius:14px;
        border:1px solid rgba(148,163,184,0.28);
        background:linear-gradient(135deg,rgba(255,255,255,0.96),rgba(238,242,255,0.5));
        box-shadow:0 8px 22px rgba(15,23,42,0.06);
    }
    .overview-stat-icon{
        width:44px;
        height:44px;
        border-radius:14px;
        display:flex;
        align-items:center;
        justify-content:center;
        color:#fff;
        font-size:18px;
        flex-shrink:0;
    }
    .overview-stat-violet{ background:linear-gradient(135deg,#6d28d9,#8b5cf6); }
    .overview-stat-indigo{ background:linear-gradient(135deg,#3730a3,#6366f1); }
    .overview-stat-text{
        display:flex;
        flex-direction:column;
        gap:2px;
        min-width:0;
    }
    .overview-stat-label{
        font-size:11px;
        font-weight:700;
        text-transform:uppercase;
        letter-spacing:0.08em;
        color:#6b7280;
    }
    .overview-stat-value{
        font-size:26px;
        font-weight:800;
        letter-spacing:-0.02em;
        color:#0f172a;
        line-height:1.1;
    }
    .overview-stat-hint{
        font-size:11px;
        color:#9ca3af;
        margin-top:2px;
    }
    .overview-chart-card{
        border-radius:14px;
        border:1px solid rgba(148,163,184,0.24);
        background:#fff;
        padding:12px 12px 8px;
        box-shadow:0 10px 28px rgba(15,23,42,0.06);
    }
    .overview-chart-head{
        display:flex;
        align-items:center;
        justify-content:space-between;
        flex-wrap:wrap;
        gap:8px;
        margin-bottom:6px;
        padding:0 4px;
    }
    .overview-chart-title{
        font-size:12px;
        font-weight:700;
        color:#4b5563;
        text-transform:uppercase;
        letter-spacing:0.1em;
    }
    .overview-chart-legend{
        display:flex;
        gap:14px;
        font-size:11px;
        font-weight:600;
        color:#6b7280;
    }
    .overview-chart-legend .lg i{ margin-right:4px; font-size:10px; }
    .lg-stu i{ color:#8b5cf6; }
    .lg-ev i{ color:#6366f1; }
    .overview-chart-wrap{
        position:relative;
        height:min(280px,42vw);
        min-height:220px;
        width:100%;
    }
    .overview-chart-wrap canvas{ max-height:100%; }

    .theme-toggle.active {
        background: linear-gradient(135deg, #4f7cff 0%, #6f7cff 100%);
        color: #fff;
        border-color: transparent;
        box-shadow: 0 14px 28px rgba(95, 124, 255, 0.28);
    }

    html.admin-dark .theme-toggle {
        background: linear-gradient(135deg, #4f7cff 0%, #6f7cff 100%);
        color: #fff;
        border-color: transparent;
    }
    
    .pill-btn{
        border:none;
        border-radius:999px;
        padding:6px 10px;
        font-size:11px;
        font-weight:600;
        display:inline-flex;
        align-items:center;
        gap:6px;
        background:linear-gradient(135deg,#e0e7ff,#f5f3ff);
        color:#4f46e5;
        cursor:pointer;
        transition:all 0.18s ease;
    }
    
    .pill-btn:hover{
        box-shadow:0 12px 28px rgba(129,140,248,0.35);
        transform:translateY(-1px);
    }
    
    .timeline{
        list-style:none;
        margin:0;
        padding:4px 0 0 0;
        position:relative;
    }
    
    .timeline::before{
        content:'';
        position:absolute;
        left:10px;
        top:0;
        bottom:0;
        border-left:1px dashed rgba(148,163,184,0.6);
    }
    
    .timeline-item{
        position:relative;
        padding-left:30px;
        padding-bottom:12px;
    }
    
    .timeline-dot{
        width:12px;
        height:12px;
        border-radius:999px;
        border:2px solid #6366f1;
        background:#eef2ff;
        position:absolute;
        left:4px;
        top:3px;
        box-shadow:0 0 0 4px rgba(129,140,248,0.35);
    }
    
    .timeline-content{
        display:flex;
        flex-direction:column;
        gap:3px;
    }
    
    .timeline-title{
        font-size:13px;
        font-weight:600;
        color:#111827;
    }
    
    .timeline-meta{
        font-size:11px;
        color:#9ca3af;
    }
    
    .timeline-item.empty .timeline-dot{
        border-color:#9ca3af;
        background:#f3f4f6;
        box-shadow:none;
    }
    
    .quick-actions-grid{
        display:grid;
        grid-template-columns:1fr;
        gap:10px;
    }
    
    .quick-chip{
        display:flex;
        align-items:center;
        gap:8px;
        padding:9px 11px;
        border-radius:12px;
        color:#f9fafb;
        text-decoration:none;
        font-size:13px;
        font-weight:600;
        box-shadow:0 14px 32px rgba(15,23,42,0.26);
        transition:transform 0.16s ease, box-shadow 0.16s ease, filter 0.16s ease;
    }
    
    .quick-chip i{
        font-size:14px;
    }
    
    .quick-chip:hover{
        transform:translateY(-2px);
        filter:brightness(1.05);
        box-shadow:0 20px 44px rgba(15,23,42,0.4);
    }
    
    .chip-purple{ background:linear-gradient(135deg,#4c1d95,#7c3aed); }
    .chip-indigo{ background:linear-gradient(135deg,#3730a3,#4f46e5); }
    .chip-green{ background:linear-gradient(135deg,#166534,#22c55e); }
    .chip-sky{ background:linear-gradient(135deg,#0369a1,#0ea5e9); }
    .chip-amber{ background:linear-gradient(135deg,#92400e,#f59e0b); }
    .chip-rose{ background:linear-gradient(135deg,#9f1239,#f97316); }

    .attendance-table-wrap {
        overflow-x: auto;
    }

    .attendance-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        min-width: 760px;
    }

    .attendance-table thead th {
        text-align: left;
        font-size: 12px;
        color: #8a95b8;
        font-weight: 700;
        padding: 12px 14px;
        border-bottom: 1px solid #e8edff;
    }

    .attendance-table tbody td {
        padding: 14px;
        border-bottom: 1px solid #edf1ff;
        font-size: 13px;
        color: #34415f;
        vertical-align: middle;
    }

    .attendance-table tbody tr:last-child td {
        border-bottom: none;
    }

    .att-name {
        font-weight: 700;
        color: #2e3c62;
    }

    .att-sub {
        margin-top: 3px;
        font-size: 11px;
        color: #8d98b9;
    }

    .status-pill {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 7px 12px;
        border-radius: 999px;
        font-size: 12px;
        font-weight: 700;
    }

    .status-pill i {
        font-size: 8px;
    }

    .status-present {
        background: #e5f8ec;
        color: #18884f;
    }

    .status-late {
        background: #fff2d8;
        color: #b7791f;
    }

    .status-absent {
        background: #ffe6e6;
        color: #cf3d3d;
    }

    .attendance-empty td {
        text-align: center;
        color: #8d98b9;
        padding: 28px 14px;
    }

    .search-hidden {
        display: none !important;
    }

    html.admin-dark .attendance-table thead th {
        color: #94a3bf;
        border-bottom-color: #273449;
    }

    html.admin-dark .attendance-table tbody td,
    html.admin-dark .att-name {
        color: #dce6ff;
    }

    html.admin-dark .attendance-table tbody td {
        border-bottom-color: #1f2b3d;
    }

    html.admin-dark .att-sub,
    html.admin-dark .attendance-empty td {
        color: #8ea0bf;
    }

    html.admin-dark .status-present {
        background: rgba(34, 197, 94, 0.16);
        color: #74e0a0;
    }

    html.admin-dark .status-late {
        background: rgba(245, 158, 11, 0.16);
        color: #ffd37b;
    }

    html.admin-dark .status-absent {
        background: rgba(239, 68, 68, 0.16);
        color: #ff9f9f;
    }
    
    @media (max-width: 1024px) {
        .dashboard-layout{
            grid-template-columns: minmax(0,1.5fr) minmax(0,1.1fr);
        }
    }
    
    @media (max-width: 900px) {
        .admin-content{
            margin-left:0;
        }
        body.sidebar-collapsed .admin-content{
            margin-left:0;
        }
        .dashboard-layout{
            grid-template-columns:1fr;
        }
    }
    
    @media (max-width: 768px) {
        .admin-header{
            flex-wrap:wrap;
            padding:14px 16px;
        }
        .header-right{
            width:100%;
            justify-content:flex-end;
        }
        .search-wrapper{
            flex:1;
            min-width:0;
        }
        .admin-main{
            padding:18px 16px 24px;
        }
    }
    </style>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script>
    window.__adminOverviewChart = {
        labels: <?php echo json_encode($chartLabels, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>,
        students: <?php echo json_encode($chartStudentsSeries, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>,
        events: <?php echo json_encode($chartEventsSeries, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>
    };
    </script>

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

    function syncThemeToggle() {
        const toggle = document.querySelector('.theme-toggle');
        const icon = toggle ? toggle.querySelector('i') : null;
        const isDark = document.documentElement.classList.contains('admin-dark');
        if (!toggle || !icon) return;
        toggle.classList.toggle('active', isDark);
        icon.classList.toggle('fa-moon', !isDark);
        icon.classList.toggle('fa-sun', isDark);
    }

    function toggleDarkMode() {
        const root = document.documentElement;
        root.classList.toggle('admin-dark');
        try {
            localStorage.setItem('attendtrack-admin-theme', root.classList.contains('admin-dark') ? 'dark' : 'light');
        } catch (e) {}
        syncThemeToggle();
    }

    function filterDashboard(query) {
        const normalized = (query || '').trim().toLowerCase();
        document.querySelectorAll('.searchable-card').forEach(function(card) {
            const haystack = (card.getAttribute('data-search') || '').toLowerCase();
            card.classList.toggle('search-hidden', normalized !== '' && !haystack.includes(normalized));
        });

        const rows = document.querySelectorAll('.attendance-row');
        let visibleRows = 0;
        rows.forEach(function(row) {
            const haystack = (row.getAttribute('data-search') || '').toLowerCase();
            const hide = normalized !== '' && !haystack.includes(normalized);
            row.classList.toggle('search-hidden', hide);
            if (!hide) visibleRows++;
        });

        const emptyRow = document.querySelector('.attendance-empty');
        if (emptyRow && rows.length > 0) {
            emptyRow.classList.toggle('search-hidden', visibleRows !== 0);
            emptyRow.querySelector('td').textContent = normalized !== '' && visibleRows === 0
                ? 'No matching attendance records found.'
                : 'No attendance records yet.';
        }
    }

    // Animated KPI counters + overview chart
    document.addEventListener('DOMContentLoaded', function () {
        syncThemeToggle();

        const searchInput = document.getElementById('dashboardSearch');
        if (searchInput) {
            searchInput.addEventListener('input', function () {
                filterDashboard(searchInput.value);
            });
        }

        const counters = document.querySelectorAll('.kpi-value[data-target]');
        counters.forEach(counter => {
            const target = parseInt(counter.getAttribute('data-target') || '0', 10);
            let current = 0;
            const duration = 800;
            const start = performance.now();

            function tick(now) {
                const progress = Math.min((now - start) / duration, 1);
                current = Math.floor(progress * target);
                counter.textContent = current.toLocaleString();
                if (progress < 1) requestAnimationFrame(tick);
            }

            requestAnimationFrame(tick);
        });

        const ctx = document.getElementById('adminOverviewChart');
        const pack = window.__adminOverviewChart;
        if (ctx && pack && typeof Chart !== 'undefined') {
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: pack.labels,
                    datasets: [
                        {
                            label: 'Attendance',
                            data: pack.students,
                            borderColor: 'rgba(139, 92, 246, 0.95)',
                            backgroundColor: 'rgba(139, 92, 246, 0.14)',
                            borderWidth: 3,
                            pointRadius: 4,
                            pointHoverRadius: 5,
                            pointBackgroundColor: '#8b5cf6',
                            tension: 0.42,
                            fill: false
                        },
                        {
                            label: 'Total Students',
                            data: pack.events,
                            borderColor: 'rgba(99, 102, 241, 0.95)',
                            backgroundColor: 'rgba(99, 102, 241, 0.14)',
                            borderWidth: 3,
                            pointRadius: 4,
                            pointHoverRadius: 5,
                            pointBackgroundColor: '#6366f1',
                            tension: 0.42,
                            fill: false
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: { mode: 'index', intersect: false },
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: 'rgba(15, 23, 42, 0.92)',
                            padding: 10,
                            cornerRadius: 8
                        }
                    },
                    scales: {
                        x: {
                            grid: { display: false },
                            ticks: { font: { size: 11, weight: '600' }, color: '#64748b' }
                        },
                        y: {
                            beginAtZero: true,
                            ticks: { precision: 0, font: { size: 11 }, color: '#64748b' },
                            grid: { color: 'rgba(148, 163, 184, 0.2)' }
                        }
                    }
                }
            });
        }
    });
    </script>
    
    <script src="assets/js/script.js"></script>
</body>
</html>
