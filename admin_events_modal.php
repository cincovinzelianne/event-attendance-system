<?php
require_once 'includes/admin_auth.php';
require_once 'config/database.php';

$adminAuth = new AdminAuth();
$currentAdmin = $adminAuth->requireAuth();

try {
    $database = new Database();
    $db = $database->getConnection();

    $stmt = $db->prepare("SELECT id, event_name, event_description, event_date, event_time, location, is_active, created_at FROM events ORDER BY event_date DESC, event_time DESC");
    $stmt->execute();
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    http_response_code(500);
    echo '<div class="events-modal-error">Failed to load events.</div>';
    exit;
}

function h($str) {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

?>
<div class="events-modal-header">
    <h3><i class="fas fa-calendar-alt"></i> All Events</h3>
</div>
<div class="events-modal-body">
    <?php if (!$events): ?>
        <div class="empty-state">
            <i class="fas fa-inbox"></i>
            <p>No events found.</p>
        </div>
    <?php else: ?>
        <div class="events-table-wrapper">
            <table class="events-table">
                <thead>
                    <tr>
                        <th>Event</th>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Location</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($events as $ev): ?>
                        <tr>
                            <td>
                                <div class="event-title"><?= h($ev['event_name']) ?></div>
                                <?php if (!empty($ev['event_description'])): ?>
                                    <div class="event-desc"><?= h(mb_strimwidth($ev['event_description'], 0, 120, '…', 'UTF-8')) ?></div>
                                <?php endif; ?>
                            </td>
                            <td><?= h(date('M d, Y', strtotime($ev['event_date']))) ?></td>
                            <td><?= h(date('g:i A', strtotime($ev['event_time']))) ?></td>
                            <td><?= h($ev['location']) ?></td>
                            <td>
                                <?php if ((int)$ev['is_active'] === 1): ?>
                                    <span class="status-badge active">Active</span>
                                <?php else: ?>
                                    <span class="status-badge inactive">Inactive</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
<style>
    .events-modal-header { display:flex; align-items:center; justify-content:space-between; padding: 16px 20px; border-bottom: 1px solid #e2e8f0; }
    .events-modal-header h3 { margin:0; font-size:18px; color:#2d3748; display:flex; align-items:center; gap:10px; }
    .events-table-wrapper { max-height: 60vh; overflow:auto; }
    .events-table { width:100%; border-collapse: collapse; }
    .events-table th, .events-table td { text-align:left; padding:12px 14px; border-bottom: 1px solid #edf2f7; font-size:14px; }
    .events-table thead th { position:sticky; top:0; background:#f8fafc; z-index:1; }
    .event-title { font-weight:600; color:#2d3748; }
    .event-desc { color:#718096; font-size:12px; margin-top:4px; }
    .status-badge { display:inline-block; padding:4px 10px; border-radius:999px; font-size:12px; font-weight:600; }
    .status-badge.active { background:#e6fffa; color:#0f766e; }
    .status-badge.inactive { background:#fff5f5; color:#c53030; }
    .empty-state { padding: 30px; text-align:center; color:#718096; }
    .empty-state i { font-size:24px; margin-bottom:8px; color:#a0aec0; }
</style>





