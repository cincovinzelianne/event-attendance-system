<?php
require_once 'includes/auth.php';

$auth = new Auth();
$currentUser = $auth->getCurrentUser();

if (!$currentUser) {
    header('Location: signin.php');
    exit;
}

function h($v) {
    return htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8');
}

$database = new Database();
$db = $database->getConnection();
$studentId = (int) $currentUser['id'];

$hasSnTbl = false;
try {
    $hasSnTbl = (bool) $db->query("SHOW TABLES LIKE 'student_notifications'")->fetch();
} catch (Exception $e) {
    $hasSnTbl = false;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $hasSnTbl) {
    if (isset($_POST['mark_all_read'])) {
        try {
            $u = $db->prepare('UPDATE student_notifications SET read_at = NOW() WHERE student_id = ? AND read_at IS NULL');
            $u->execute([$studentId]);
        } catch (Exception $e) {
            // ignore
        }
        header('Location: notifications.php');
        exit;
    }
    if (isset($_POST['mark_one_id'])) {
        $nid = (int) $_POST['mark_one_id'];
        if ($nid > 0) {
            try {
                $u = $db->prepare('UPDATE student_notifications SET read_at = NOW() WHERE id = ? AND student_id = ? AND read_at IS NULL');
                $u->execute([$nid, $studentId]);
            } catch (Exception $e) {
                // ignore
            }
        }
        header('Location: notifications.php');
        exit;
    }
}

// Handle AJAX: Get event details
if (isset($_GET['action']) && $_GET['action'] === 'get_event_details' && isset($_GET['event_id'])) {
    header('Content-Type: application/json');
    $eventId = (int)$_GET['event_id'];
    
    try {
        // Get event details
        $eventStmt = $db->prepare("
            SELECT id, event_name, event_description, event_date, event_time, location
            FROM events
            WHERE id = ? AND is_active = 1
        ");
        $eventStmt->execute([$eventId]);
        $event = $eventStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$event) {
            echo json_encode(['success' => false, 'error' => 'Event not found']);
            exit;
        }
        
        // Get attendance info for this student and event
        $attendanceStmt = $db->prepare("
            SELECT attendance_time, status
            FROM attendance
            WHERE student_id = ? AND event_id = ?
        ");
        $attendanceStmt->execute([$studentId, $eventId]);
        $attendance = $attendanceStmt->fetch(PDO::FETCH_ASSOC);
        
        // Format event date and time
        $eventDateTime = date('M d, Y \a\t g:i A', strtotime($event['event_date'] . ' ' . $event['event_time']));
        
        echo json_encode([
            'success' => true,
            'event' => [
                'id' => $event['id'],
                'name' => $event['event_name'],
                'description' => $event['event_description'],
                'date' => $event['event_date'],
                'time' => $event['event_time'],
                'dateFormatted' => $eventDateTime,
                'location' => $event['location'] ?: 'TBA'
            ],
            'attendance' => $attendance ? [
                'attendanceTime' => $attendance['attendance_time'] ? date('M d, Y g:i A', strtotime($attendance['attendance_time'])) : null,
                'status' => $attendance['status']
            ] : null
        ]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

$items = [];
$unreadCount = 0;
if ($hasSnTbl) {
    try {
        $stmt = $db->prepare(
            'SELECT id, action_key, title, body, event_id, created_at, read_at
             FROM student_notifications
             WHERE student_id = ?
             ORDER BY created_at DESC
             LIMIT 80'
        );
        $stmt->execute([$studentId]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($items as $row) {
            if (empty($row['read_at'])) {
                $unreadCount++;
            }
        }
    } catch (Exception $e) {
        $items = [];
    }
}

function notification_visual($actionKey) {
    $key = (string) $actionKey;
    if ($key === 'new_event') {
        return [
            'icon' => 'fa-calendar-check',
            'modifier' => ' notif--new-event',
            'icon_color' => '#8b5cf6',
        ];
    }
    if ($key === 'time_in' || $key === 'time_in_2') {
        return [
            'icon' => 'fa-arrow-right-to-bracket',
            'modifier' => ' notif--in',
            'icon_color' => '#059669',
        ];
    }
    if ($key === 'time_out' || $key === 'time_out_2') {
        return [
            'icon' => 'fa-arrow-right-from-bracket',
            'modifier' => ' notif--out',
            'icon_color' => '#2563eb',
        ];
    }
    return [
        'icon' => 'fa-bell',
        'modifier' => '',
        'icon_color' => '#7c3aed',
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications - Event Attendance</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        :root {
            --bg: #F5F7FB;
            --card: #ffffff;
            --text: #0f172a;
            --muted: #64748b;
            --border: rgba(15, 23, 42, 0.08);
            --shadow-sm: 0 6px 16px rgba(15, 23, 42, 0.08);
            --grad: linear-gradient(135deg, #3b82f6 0%, #8b5cf6 100%);
            --radius: 18px;
        }
        /* Override assets/css/style.css (auth layout: flex + padding centers content vertically) */
        body {
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
        .dash-shell {
            width: 100%;
            max-width: 420px;
            margin: 0 auto;
            padding: 12px 16px 16px;
        }
        .page-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 14px;
        }
        .header-actions {
            display: flex;
            flex-direction: column;
            gap: 8px;
            align-items: flex-end;
        }
        .page-header h1 {
            margin: 0;
            font-size: 22px;
            font-weight: 800;
            letter-spacing: -0.02em;
        }
        .page-header p {
            margin: 6px 0 0;
            font-size: 13px;
            color: var(--muted);
            font-weight: 600;
            line-height: 1.4;
        }
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 12px;
            border-radius: 14px;
            text-decoration: none;
            color: #1e40af;
            font-weight: 700;
            font-size: 13px;
            background: rgba(59, 130, 246, 0.1);
            border: 1px solid rgba(59, 130, 246, 0.2);
            white-space: nowrap;
        }
        .back-link:active { transform: scale(0.98); }
        .signout-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 12px;
            border-radius: 14px;
            text-decoration: none;
            color: #b91c1c;
            font-weight: 700;
            font-size: 13px;
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.2);
            white-space: nowrap;
        }
        .signout-link:active { transform: scale(0.98); }
        .section-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin: 0 2px 10px;
            gap: 10px;
            flex-wrap: wrap;
        }
        .section-title {
            margin: 0;
            font-size: 14px;
            color: var(--muted);
            font-weight: 700;
            letter-spacing: 0.02em;
            text-transform: uppercase;
        }
        .mark-read-btn {
            border: none;
            cursor: pointer;
            font-family: inherit;
            font-size: 12px;
            font-weight: 700;
            color: #1d4ed8;
            background: rgba(59, 130, 246, 0.12);
            padding: 8px 12px;
            border-radius: 999px;
            border: 1px solid rgba(59, 130, 246, 0.22);
        }
        .mark-read-btn:disabled {
            opacity: 0.45;
            cursor: not-allowed;
        }
        .card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            box-shadow: var(--shadow-sm);
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
        .alert-item.notif--out {
            border-color: rgba(37, 99, 235, 0.22);
            background: linear-gradient(135deg, rgba(37, 99, 235, 0.08) 0%, rgba(139, 92, 246, 0.06) 100%);
        }
        .alert-item.notif--new-event {
            border-color: rgba(139, 92, 246, 0.22);
            background: linear-gradient(135deg, rgba(139, 92, 246, 0.08) 0%, rgba(168, 85, 247, 0.06) 100%);
        }
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
            flex-shrink: 0;
        }
        .alert-item-body { flex: 1; min-width: 0; }
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
        .unread-dot {
            display: inline-block;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #3b82f6;
            margin-left: 6px;
            vertical-align: middle;
        }
        .alert-actions {
            flex-shrink: 0;
        }
        .dismiss-one {
            border: none;
            background: rgba(15, 23, 42, 0.06);
            color: var(--muted);
            width: 34px;
            height: 34px;
            border-radius: 10px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .dismiss-one:active { transform: scale(0.96); }
        .empty-state {
            text-align: center;
            padding: 36px 20px;
            color: var(--muted);
        }
        .empty-state i {
            font-size: 44px;
            opacity: 0.35;
            margin-bottom: 12px;
            color: #64748b;
        }
        .empty-state h2 {
            margin: 0 0 8px;
            font-size: 16px;
            font-weight: 800;
            color: var(--text);
        }
        .empty-state p {
            margin: 0;
            font-size: 13px;
            font-weight: 600;
            line-height: 1.5;
        }
        .no-table-banner {
            padding: 14px;
            border-radius: 14px;
            background: #fff7ed;
            border: 1px solid rgba(245, 158, 11, 0.35);
            color: #9a3412;
            font-size: 13px;
            font-weight: 600;
            line-height: 1.45;
        }
        @media (max-width: 768px) {
            .dash-shell { padding: 16px 14px 14px; }
        }
        
        /* Event Details Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(15, 23, 42, 0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
            padding: 16px;
            animation: fadeIn 0.2s ease;
        }
        
        .modal.active {
            display: flex;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        .modal-content {
            background: var(--card);
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(15, 23, 42, 0.18);
            width: 100%;
            max-width: 480px;
            max-height: 85vh;
            overflow-y: auto;
            animation: slideUp 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
        }
        
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 24px;
            border-bottom: 1px solid var(--border);
            position: sticky;
            top: 0;
            background: var(--card);
        }
        
        .modal-header h2 {
            margin: 0;
            font-size: 20px;
            font-weight: 800;
            color: var(--text);
        }
        
        .modal-close {
            background: none;
            border: none;
            font-size: 24px;
            color: var(--muted);
            cursor: pointer;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 10px;
            transition: all 0.2s ease;
        }
        
        .modal-close:hover {
            background: rgba(15, 23, 42, 0.06);
            color: var(--text);
        }
        
        .modal-body {
            padding: 20px 24px 24px;
        }
        
        .modal-loader {
            text-align: center;
            padding: 40px 20px;
            color: var(--muted);
        }
        
        .modal-loader i {
            font-size: 32px;
            margin-bottom: 16px;
            display: block;
            color: #8b5cf6;
        }
        
        .modal-loader p {
            margin: 0;
            font-weight: 600;
        }
        
        .event-detail-section {
            margin-bottom: 24px;
        }
        
        .event-detail-section:last-child {
            margin-bottom: 0;
        }
        
        .event-detail-title {
            font-size: 12px;
            color: var(--muted);
            font-weight: 700;
            letter-spacing: 0.02em;
            text-transform: uppercase;
            margin-bottom: 12px;
        }
        
        .event-detail-content {
            background: rgba(139, 92, 246, 0.06);
            padding: 16px;
            border-radius: 14px;
            border: 1px solid rgba(139, 92, 246, 0.15);
        }
        
        .event-name {
            margin: 0 0 8px;
            font-size: 18px;
            font-weight: 800;
            color: var(--text);
        }
        
        .event-info-row {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 0;
            font-size: 14px;
            color: var(--muted);
            font-weight: 600;
        }
        
        .event-icon {
            width: 24px;
            text-align: center;
            color: #8b5cf6;
        }
        
        .attendance-section {
            background: rgba(59, 130, 246, 0.06);
            padding: 16px;
            border-radius: 14px;
            border: 1px solid rgba(59, 130, 246, 0.15);
        }
        
        .attendance-title {
            font-size: 14px;
            font-weight: 800;
            color: var(--text);
            margin: 0 0 14px;
        }
        
        .attendance-item {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid rgba(59, 130, 246, 0.1);
            font-size: 13px;
        }
        
        .attendance-item:last-child {
            border-bottom: none;
        }
        
        .attendance-label {
            color: var(--muted);
            font-weight: 600;
        }
        
        .attendance-value {
            color: var(--text);
            font-weight: 700;
        }
        
        .attendance-value.na {
            color: #9ca3af;
            font-style: italic;
        }
        
        .status-badge-modal {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
            text-transform: capitalize;
        }
        
        .status-badge-modal.present {
            background: linear-gradient(135deg, rgba(34, 197, 94, 0.2), rgba(74, 222, 128, 0.1));
            color: #16a34a;
        }
        
        .status-badge-modal.late {
            background: linear-gradient(135deg, rgba(239, 68, 68, 0.2), rgba(252, 165, 165, 0.1));
            color: #dc2626;
        }
        
        .status-badge-modal.absent {
            background: linear-gradient(135deg, rgba(107, 114, 128, 0.2), rgba(156, 163, 175, 0.1));
            color: #374151;
        }
    </style>
</head>
<body>
    <div class="dash-shell">
        <div class="page-header">
            <div>
                <h1>Notifications</h1>
                <p>Get notified when new events are created and when you time in or time out at events.</p>
            </div>
            <div class="header-actions">
                <a class="back-link" href="dashboard.php"><i class="fas fa-chevron-left"></i> Home</a>
                <a class="signout-link" href="signout.php"><i class="fas fa-right-from-bracket"></i> Sign out</a>
            </div>
        </div>

        <?php if (!$hasSnTbl): ?>
            <div class="no-table-banner">
                Notifications are not set up yet. Ask your administrator to run <code>student_notifications.sql</code> on the database.
            </div>
        <?php elseif (!$items): ?>
            <div class="card">
                <div class="empty-state">
                    <div><i class="fas fa-bell-slash" aria-hidden="true"></i></div>
                    <h2>No notifications yet</h2>
                    <p>When new events are created or when you time in/out at an event, notifications will appear here.</p>
                </div>
            </div>
        <?php else: ?>
            <div class="section-head">
                <h2 class="section-title">
                    Recent
                    <?php if ($unreadCount > 0): ?>
                        <span class="unread-dot" title="<?= (int) $unreadCount ?> unread"></span>
                    <?php endif; ?>
                </h2>
                <?php if ($unreadCount > 0): ?>
                    <form method="post" action="">
                        <button type="submit" name="mark_all_read" value="1" class="mark-read-btn">Mark all read</button>
                    </form>
                <?php endif; ?>
            </div>
            <div class="card alerts-card">
                <?php foreach ($items as $al): ?>
                    <?php
                    $unread = empty($al['read_at']);
                    $when = !empty($al['created_at']) ? strtotime($al['created_at']) : false;
                    $whenStr = $when ? date('M j, Y · g:i A', $when) : '';
                    $vis = notification_visual($al['action_key'] ?? '');
                    ?>
                    <div class="alert-item<?= h($vis['modifier']) ?><?= $unread ? ' unread' : '' ?>" <?php if ($al['event_id']): ?>onclick="showEventDetails(<?= (int) $al['event_id'] ?>)" role="button" tabindex="0" data-event-id="<?= (int) $al['event_id'] ?>" style="cursor: pointer;"<?php endif; ?>>
                        <div class="alert-item-icon" style="color:<?= h($vis['icon_color']) ?>;" aria-hidden="true">
                            <i class="fas <?= h($vis['icon']) ?>"></i>
                        </div>
                        <div class="alert-item-body alert-item-text">
                            <h3><?= h($al['title']) ?><?php if ($unread): ?><span class="unread-dot" title="Unread"></span><?php endif; ?></h3>
                            <p><?= h($al['body']) ?></p>
                            <?php if ($whenStr !== ''): ?>
                                <div class="alert-item-meta"><?= h($whenStr) ?></div>
                            <?php endif; ?>
                        </div>
                        <?php if ($unread): ?>
                            <div class="alert-actions">
                                <form method="post" action="">
                                    <input type="hidden" name="mark_one_id" value="<?= (int) $al['id'] ?>">
                                    <button type="submit" class="dismiss-one" title="Mark as read" aria-label="Mark as read">
                                        <i class="fas fa-check"></i>
                                    </button>
                                </form>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Event Details Modal -->
    <div id="eventModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Event Details</h2>
                <button class="modal-close" onclick="closeEventModal()">✕</button>
            </div>
            <div class="modal-body" id="modalBody">
                <div class="modal-loader">
                    <i class="fas fa-spinner fa-spin"></i>
                    <p>Loading event details...</p>
                </div>
            </div>
        </div>
    </div>

    <script>
        function showEventDetails(eventId) {
            const modal = document.getElementById('eventModal');
            const modalBody = document.getElementById('modalBody');
            
            // Show loading state
            modal.classList.add('active');
            modalBody.innerHTML = `
                <div class="modal-loader">
                    <i class="fas fa-spinner fa-spin"></i>
                    <p>Loading event details...</p>
                </div>
            `;
            
            // Fetch event details via AJAX
            fetch(`?action=get_event_details&event_id=${eventId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const event = data.event;
                        const attendance = data.attendance;
                        
                        // Build HTML for event details
                        let html = `
                            <div class="event-detail-section">
                                <div class="event-detail-title">Event Information</div>
                                <div class="event-detail-content">
                                    <h3 class="event-name">${escapeHtml(event.name)}</h3>
                        `;
                        
                        if (event.description) {
                            html += `<p style="margin: 8px 0; color: var(--muted); font-weight: 600;">${escapeHtml(event.description)}</p>`;
                        }
                        
                        if (event.location) {
                            html += `
                                <div class="event-info-row">
                                    <div class="event-icon"><i class="fas fa-map-pin"></i></div>
                                    <div>${escapeHtml(event.location)}</div>
                                </div>
                            `;
                        }
                        
                        html += `
                                <div class="event-info-row">
                                    <div class="event-icon"><i class="fas fa-calendar-alt"></i></div>
                                    <div>${escapeHtml(event.dateFormatted)}</div>
                                </div>
                                </div>
                            </div>
                        `;
                        
                        // Attendance information
                        html += `
                            <div class="event-detail-section">
                                <div class="event-detail-title">Your Attendance</div>
                                <div class="attendance-section">
                                    <h4 class="attendance-title">Time Records</h4>
                        `;
                        
                        if (attendance) {
                            html += `
                                <div class="attendance-item">
                                    <span class="attendance-label">Recorded At</span>
                                    <span class="attendance-value">${attendance.attendanceTime || '—'}</span>
                                </div>
                                <div class="attendance-item" style="border-bottom: none; margin-top: 12px; padding-top: 12px; border-top: 1px solid rgba(59, 130, 246, 0.1);">
                                    <span class="attendance-label">Status</span>
                                    <span class="status-badge-modal ${attendance.status}">${attendance.status}</span>
                                </div>
                            `;
                        } else {
                            html += `
                                <p style="color: var(--muted); text-align: center; margin: 12px 0; font-weight: 600;">No attendance record yet</p>
                            `;
                        }
                        
                        html += `
                                </div>
                            </div>
                        `;
                        
                        modalBody.innerHTML = html;
                    } else {
                        modalBody.innerHTML = `
                            <div class="modal-loader" style="color: #dc2626;">
                                <i class="fas fa-exclamation-circle" style="color: #dc2626;"></i>
                                <p>Error loading event details</p>
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    modalBody.innerHTML = `
                        <div class="modal-loader" style="color: #dc2626;">
                            <i class="fas fa-exclamation-circle" style="color: #dc2626;"></i>
                            <p>Failed to load event details</p>
                        </div>
                    `;
                });
        }
        
        function closeEventModal() {
            const modal = document.getElementById('eventModal');
            modal.classList.remove('active');
        }
        
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        // Close modal when clicking outside
        document.getElementById('eventModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeEventModal();
            }
        });
        
        // Close modal with Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeEventModal();
            }
        });
    </script>

    <?php include 'includes/bottom_navbar.php'; ?>
</body>
</html>
