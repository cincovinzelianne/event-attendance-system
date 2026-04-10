<?php
/**
 * Backfill Notifications - Add notifications for existing events
 */

require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

echo "🔄 Backfilling notifications for existing events...\n\n";

try {
    // Get all events that don't have notifications yet
    $eventsStmt = $db->query("
        SELECT e.id, e.event_name, e.event_description, e.event_date, e.event_time, e.location
        FROM events e
        WHERE e.is_active = 1
        AND e.id NOT IN (SELECT DISTINCT event_id FROM student_notifications WHERE event_id IS NOT NULL)
        ORDER BY e.event_date DESC
    ");
    
    $events = $eventsStmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($events)) {
        echo "✓ All existing events already have notifications!\n";
    } else {
        echo "Found " . count($events) . " events without notifications\n\n";
        
        // Get all active students
        $studentsStmt = $db->query("SELECT id FROM students WHERE is_active = 1");
        $students = $studentsStmt->fetchAll(PDO::FETCH_COLUMN);
        
        if (empty($students)) {
            echo "❌ No active students found!\n";
            exit(1);
        }
        
        echo "Found " . count($students) . " active students\n";
        echo "Creating notifications...\n\n";
        
        $notifStmt = $db->prepare("
            INSERT INTO student_notifications (student_id, event_id, action_key, title, body) 
            VALUES (?, ?, ?, ?, ?)
        ");
        
        $totalCreated = 0;
        
        foreach ($events as $event) {
            $eventDateTime = date('M d, Y \a\t g:i A', strtotime($event['event_date'] . ' ' . $event['event_time']));
            $notificationTitle = "📅 New Event: " . $event['event_name'];
            $notificationBody = $event['event_description'] . "\n\n📍 Location: " . ($event['location'] ?: 'TBA') . "\n📅 Date & Time: " . $eventDateTime;
            
            foreach ($students as $studentId) {
                $notifStmt->execute([
                    $studentId,
                    $event['id'],
                    'new_event',
                    $notificationTitle,
                    $notificationBody
                ]);
                $totalCreated++;
            }
            
            echo "✓ Created notifications for event: {$event['event_name']}\n";
        }
        
        echo "\n✓ Successfully created $totalCreated notifications!\n";
    }
    
    // Show final count
    $finalCount = $db->query("SELECT COUNT(*) FROM student_notifications")->fetchColumn();
    echo "\n📊 Total notifications in database now: $finalCount\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
