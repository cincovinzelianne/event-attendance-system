<?php
/**
 * Debug Notifications - Check if notifications are in database
 */

require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

try {
    // Check if table exists
    $tableExists = (bool) $db->query("SHOW TABLES LIKE 'student_notifications'")->fetch();
    
    if (!$tableExists) {
        echo "❌ student_notifications table does not exist!\n\n";
        echo "Creating table...\n";
        $db->exec("
            CREATE TABLE IF NOT EXISTS student_notifications (
                id INT(11) NOT NULL AUTO_INCREMENT,
                student_id INT(11) NOT NULL,
                event_id INT(11) NULL DEFAULT NULL,
                action_key VARCHAR(32) NOT NULL,
                title VARCHAR(255) NOT NULL,
                body TEXT NOT NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                read_at DATETIME NULL DEFAULT NULL,
                PRIMARY KEY (id),
                KEY idx_sn_student_created (student_id, created_at),
                KEY idx_sn_event (event_id),
                CONSTRAINT fk_sn_student FOREIGN KEY (student_id) REFERENCES students (id) ON DELETE CASCADE,
                CONSTRAINT fk_sn_event FOREIGN KEY (event_id) REFERENCES events (id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        echo "✓ Table created!\n\n";
    } else {
        echo "✓ student_notifications table exists\n\n";
    }
    
    // Check total notifications
    $totalCount = $db->query("SELECT COUNT(*) FROM student_notifications")->fetchColumn();
    echo "📊 Total notifications in database: $totalCount\n\n";
    
    // Show all notifications
    $notifs = $db->query("
        SELECT sn.id, sn.student_id, s.email, sn.event_id, sn.title, sn.action_key, sn.created_at, sn.read_at
        FROM student_notifications sn
        LEFT JOIN students s ON sn.student_id = s.id
        ORDER BY sn.created_at DESC
        LIMIT 20
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($notifs)) {
        echo "⚠️  No notifications found in the database.\n";
        echo "This means:\n";
        echo "  • No events have been created yet with the new notification system, OR\n";
        echo "  • Events were created before the notification system was implemented\n\n";
        
        echo "📝 To test:\n";
        echo "  1. Go to admin_create_event.php\n";
        echo "  2. Create a new event\n";
        echo "  3. Refresh this page\n";
        echo "  4. Then check the student Notifications page\n";
    } else {
        echo "📋 Notifications Found:\n";
        echo "─────────────────────────────────────────────────\n";
        foreach ($notifs as $n) {
            echo "\nNotification ID: {$n['id']}\n";
            echo "  Student: {$n['email']} (ID: {$n['student_id']})\n";
            echo "  Event ID: {$n['event_id']}\n";
            echo "  Type: {$n['action_key']}\n";
            echo "  Title: {$n['title']}\n";
            echo "  Created: {$n['created_at']}\n";
            echo "  Read: " . ($n['read_at'] ? $n['read_at'] : "Not read") . "\n";
        }
    }
    
    echo "\n─────────────────────────────────────────────────\n";
    echo "\n👥 Active Students:\n";
    $students = $db->query("
        SELECT id, email, first_name, last_name, is_active
        FROM students
        WHERE is_active = 1
        ORDER BY id
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($students as $s) {
        echo "  • {$s['first_name']} {$s['last_name']} ({$s['email']}) - ID: {$s['id']}\n";
    }
    
    echo "\n📅 Events:\n";
    $events = $db->query("
        SELECT id, event_name, event_date, event_time, is_active
        FROM events
        WHERE is_active = 1
        ORDER BY event_date DESC
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($events)) {
        echo "  No active events found\n";
    } else {
        foreach ($events as $e) {
            echo "  • {$e['event_name']} - {$e['event_date']} {$e['event_time']} (ID: {$e['id']})\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
