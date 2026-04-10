<?php
/**
 * Auto Setup & Fix Database
 * Automatically imports all SQL files in correct order
 */

require_once 'config/database.php';

set_time_limit(60);

$status = [
    'connected' => false,
    'tables_created' => 0,
    'files_imported' => [],
    'errors' => [],
    'success' => false
];

try {
    $database = new Database();
    $db = $database->getConnection();
    $status['connected'] = true;
    
    // SQL files in correct import order
    $sqlFiles = [
        'database.sql',
        'create_admin_tables.sql',
        'create_courses_table.sql',
        'create_event_departments_table.sql',
        'student_notifications.sql',
        'add_attendance_locked_column.sql',
        'add_qr_code_locked_field.sql',
    ];
    
    // Import each file
    foreach ($sqlFiles as $file) {
        $filePath = __DIR__ . '/' . $file;
        
        if (!file_exists($filePath)) {
            $status['errors'][] = "File not found: $file";
            continue;
        }
        
        try {
            $sqlContent = file_get_contents($filePath);
            $statements = array_filter(array_map('trim', explode(';', $sqlContent)));
            
            foreach ($statements as $statement) {
                if (!empty($statement)) {
                    $db->exec($statement);
                }
            }
            
            $status['files_imported'][] = $file;
            
        } catch (Exception $e) {
            $status['errors'][] = "$file: " . $e->getMessage();
        }
    }
    
    // Count tables
    $stmt = $db->query("SELECT COUNT(*) as count FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = '" . DB_NAME . "'");
    $status['tables_created'] = $stmt->fetch()['count'];
    
    $status['success'] = count($status['files_imported']) === count($sqlFiles) && $status['tables_created'] > 0;
    
} catch (Exception $e) {
    $status['errors'][] = "Connection Error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Auto Setup - Event Attendance</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 600px;
            width: 100%;
            padding: 40px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .header h1 {
            color: #333;
            font-size: 24px;
            margin-bottom: 10px;
        }
        .header p {
            color: #666;
            font-size: 14px;
        }
        .status-box {
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
        }
        .status-box.success {
            background: #d4edda;
            border: 2px solid #28a745;
            color: #155724;
        }
        .status-box.error {
            background: #f8d7da;
            border: 2px solid #dc3545;
            color: #721c24;
        }
        .status-box.processing {
            background: #d1ecf1;
            border: 2px solid #17a2b8;
            color: #0c5460;
        }
        .status-box h2 {
            font-size: 18px;
            margin-bottom: 5px;
        }
        .status-box p {
            font-size: 14px;
        }
        .checklist {
            background: #f8f9ff;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .checklist-item {
            display: flex;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }
        .checklist-item:last-child {
            border-bottom: none;
        }
        .checklist-icon {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            font-weight: bold;
            color: white;
            font-size: 16px;
        }
        .checklist-icon.success {
            background: #28a745;
        }
        .checklist-icon.error {
            background: #dc3545;
        }
        .checklist-text {
            flex: 1;
            color: #333;
            font-size: 13px;
        }
        .button-group {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
        .btn {
            flex: 1;
            padding: 12px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            font-size: 13px;
            transition: all 0.3s;
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        .btn-secondary {
            background: #e9ecef;
            color: #333;
        }
        .btn-secondary:hover {
            background: #dee2e6;
        }
        .error-list {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
            padding: 15px;
            border-radius: 5px;
            margin-top: 20px;
        }
        .error-list h3 {
            margin-bottom: 10px;
        }
        .error-list ul {
            margin-left: 20px;
        }
        .error-list li {
            margin: 5px 0;
            font-size: 12px;
        }
        .success-links {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
            padding: 15px;
            border-radius: 5px;
            margin-top: 20px;
        }
        .success-links h3 {
            margin-bottom: 10px;
        }
        .success-links a {
            display: block;
            color: #0c5460;
            text-decoration: none;
            margin: 8px 0;
            padding: 8px;
            background: rgba(255,255,255,0.5);
            border-radius: 3px;
            font-size: 13px;
        }
        .success-links a:hover {
            background: rgba(255,255,255,0.8);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔧 Database Auto Setup</h1>
            <p>Automatically importing all database tables...</p>
        </div>

        <?php if ($status['success']): ?>
            <!-- SUCCESS -->
            <div class="status-box success">
                <h2>✓ Setup Complete!</h2>
                <p>All database tables have been created successfully.</p>
            </div>

            <div class="checklist">
                <div class="checklist-item">
                    <div class="checklist-icon success">✓</div>
                    <div class="checklist-text">
                        <strong>Database Connected:</strong> <?php echo DB_NAME; ?>
                    </div>
                </div>
                <div class="checklist-item">
                    <div class="checklist-icon success">✓</div>
                    <div class="checklist-text">
                        <strong>Files Imported:</strong> <?php echo count($status['files_imported']); ?> SQL files
                    </div>
                </div>
                <div class="checklist-item">
                    <div class="checklist-icon success">✓</div>
                    <div class="checklist-text">
                        <strong>Tables Created:</strong> <?php echo $status['tables_created']; ?> tables
                    </div>
                </div>
            </div>

            <!-- NEXT STEPS -->
            <div class="success-links">
                <h3>🎯 Next Steps:</h3>
                <a href="create_accounts.php">1. Create Test Accounts</a>
                <a href="signin.php">2. Student Login</a>
                <a href="admin_signin.php">3. Admin Login</a>
                <a href="dashboard.php">4. Student Dashboard</a>
            </div>

            <div class="button-group">
                <a class="btn btn-primary" href="create_accounts.php" style="text-decoration: none; display: flex; align-items: center; justify-content: center;">
                    Create Accounts
                </a>
            </div>

        <?php elseif ($status['connected']): ?>
            <!-- PARTIAL SUCCESS -->
            <div class="status-box processing">
                <h2>⚠️ Partial Setup</h2>
                <p><?php echo $status['tables_created']; ?> tables created, but some errors occurred.</p>
            </div>

            <div class="checklist">
                <?php foreach ($status['files_imported'] as $file): ?>
                    <div class="checklist-item">
                        <div class="checklist-icon success">✓</div>
                        <div class="checklist-text"><?php echo $file; ?></div>
                    </div>
                <?php endforeach; ?>
            </div>

            <?php if (!empty($status['errors'])): ?>
                <div class="error-list">
                    <h3>⚠️ Errors:</h3>
                    <ul>
                        <?php foreach ($status['errors'] as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <div class="button-group" style="margin-top: 20px;">
                <button class="btn btn-secondary" onclick="location.reload()">Retry</button>
                <a href="import_sql.php" class="btn btn-primary" style="text-decoration: none;">Manual Import</a>
            </div>

        <?php else: ?>
            <!-- CONNECTION ERROR -->
            <div class="status-box error">
                <h2>✗ Connection Failed</h2>
                <p>Could not connect to the database.</p>
            </div>

            <?php if (!empty($status['errors'])): ?>
                <div class="error-list">
                    <h3>Error Details:</h3>
                    <ul>
                        <?php foreach ($status['errors'] as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <div style="background: #fff3cd; border: 1px solid #ffeaa7; color: #856404; padding: 15px; border-radius: 5px; margin-top: 20px; font-size: 13px;">
                <strong>⚠️ Troubleshooting:</strong>
                <ul style="margin-left: 20px; margin-top: 10px;">
                    <li>Verify XAMPP MySQL service is running</li>
                    <li>Check config/database.php settings</li>
                    <li>Ensure database 'event_attendance' exists</li>
                </ul>
            </div>

            <div class="button-group" style="margin-top: 20px;">
                <button class="btn btn-secondary" onclick="location.reload()">Retry Connection</button>
                <a href="database_diagnostic.php" class="btn btn-primary" style="text-decoration: none;">Diagnostics</a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
