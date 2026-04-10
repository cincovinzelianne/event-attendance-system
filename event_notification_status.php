<?php
/**
 * Event Notification System - Setup & Verification
 * Ensures all tables exist and shows notification status
 */

require_once 'config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Create notifications table if not exists
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
    
    // Get statistics
    $statsStmt = $db->query("
        SELECT 
            (SELECT COUNT(*) FROM student_notifications) as total_notifications,
            (SELECT COUNT(*) FROM student_notifications WHERE read_at IS NULL) as unread_notifications,
            (SELECT COUNT(*) FROM students WHERE is_active = 1) as total_students,
            (SELECT COUNT(*) FROM events WHERE is_active = 1) as total_events
    ");
    
    $stats = $statsStmt->fetch(PDO::FETCH_ASSOC);
    $success = true;
    $message = "✓ Notification system is active and working!";
    
} catch (Exception $e) {
    $success = false;
    $message = "Error: " . $e->getMessage();
    $stats = null;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event Notification System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
            max-width: 600px;
            width: 100%;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px;
            text-align: center;
        }
        .header h1 {
            font-size: 28px;
            margin-bottom: 10px;
        }
        .header p {
            opacity: 0.9;
            font-size: 14px;
        }
        .content {
            padding: 40px;
        }
        .alert {
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            display: flex;
            gap: 15px;
            align-items: flex-start;
        }
        .alert.success {
            background: #d4edda;
            border: 2px solid #28a745;
            color: #155724;
        }
        .alert.error {
            background: #f8d7da;
            border: 2px solid #f5c6cb;
            color: #721c24;
        }
        .alert-icon {
            font-size: 24px;
            flex-shrink: 0;
        }
        .alert h3 {
            margin-bottom: 5px;
            font-size: 16px;
        }
        .stat-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: #f8f9ff;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            transition: all 0.3s;
        }
        .stat-card:hover {
            border-color: #667eea;
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.2);
        }
        .stat-number {
            font-size: 32px;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 8px;
        }
        .stat-label {
            font-size: 13px;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .feature-list {
            background: #f0f4ff;
            border-left: 4px solid #667eea;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 25px;
        }
        .feature-list h4 {
            color: #333;
            margin-bottom: 12px;
            font-size: 15px;
        }
        .feature-item {
            display: flex;
            gap: 12px;
            margin-bottom: 10px;
            font-size: 13px;
            color: #555;
        }
        .feature-item i {
            color: #667eea;
            flex-shrink: 0;
            width: 20px;
            text-align: center;
        }
        .button-group {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 25px;
        }
        .btn {
            flex: 1;
            padding: 12px 20px;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-weight: bold;
            font-size: 14px;
            transition: all 0.3s;
            text-decoration: none;
            text-align: center;
            min-width: 140px;
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
        }
        .btn-secondary {
            background: #f0f0f0;
            color: #333;
        }
        .btn-secondary:hover {
            background: #e0e0e0;
        }
        .info-box {
            background: #fffbf0;
            border: 2px solid #fdo0b83;
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 25px;
            font-size: 13px;
            color: #664d03;
            line-height: 1.6;
        }
        .info-box strong {
            color: #664d03;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔔 Event Notification System</h1>
            <p>When admins create events, all students are automatically notified</p>
        </div>

        <div class="content">
            <?php if ($success): ?>
                <div class="alert success">
                    <div class="alert-icon">✓</div>
                    <div>
                        <h3>System Active</h3>
                        <p><?php echo htmlspecialchars($message); ?></p>
                    </div>
                </div>

                <?php if ($stats): ?>
                <div class="stat-grid">
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $stats['total_notifications']; ?></div>
                        <div class="stat-label">Total Notifications</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number" style="color: #f59e0b;"><?php echo $stats['unread_notifications']; ?></div>
                        <div class="stat-label">Unread Notifications</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number" style="color: #10b981;"><?php echo $stats['total_students']; ?></div>
                        <div class="stat-label">Active Students</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number" style="color: #8b5cf6;"><?php echo $stats['total_events']; ?></div>
                        <div class="stat-label">Active Events</div>
                    </div>
                </div>
                <?php endif; ?>

                <div class="feature-list">
                    <h4>📋 How It Works</h4>
                    <div class="feature-item">
                        <i class="fas fa-check"></i>
                        <span>Admin creates a new event</span>
                    </div>
                    <div class="feature-item">
                        <i class="fas fa-check"></i>
                        <span>System automatically fetches all active students</span>
                    </div>
                    <div class="feature-item">
                        <i class="fas fa-check"></i>
                        <span>Creates notifications with event details</span>
                    </div>
                    <div class="feature-item">
                        <i class="fas fa-check"></i>
                        <span>Students see notifications in their notifications page</span>
                    </div>
                    <div class="feature-item">
                        <i class="fas fa-check"></i>
                        <span>Notifications include event name, description, date & location</span>
                    </div>
                </div>

                <div class="info-box">
                    <strong>💡 Tip:</strong> After admin creates an event in the admin panel, all active students will immediately receive a notification. They can view it in their <strong>Notifications</strong> tab.
                </div>

                <div class="button-group">
                    <a href="admin_create_event.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Create Event
                    </a>
                    <a href="notifications.php" class="btn btn-secondary">
                        <i class="fas fa-bell"></i> View Notifications
                    </a>
                </div>

            <?php else: ?>
                <div class="alert error">
                    <div class="alert-icon">✕</div>
                    <div>
                        <h3>Error</h3>
                        <p><?php echo htmlspecialchars($message); ?></p>
                    </div>
                </div>

                <div class="button-group">
                    <a href="dashboard.php" class="btn btn-primary">
                        Go to Dashboard
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
