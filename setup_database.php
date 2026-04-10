<?php
/**
 * Database Setup - Direct Import with Progress Display
 */

require_once 'config/database.php';

// Initialize results
$importResult = null;
$isImporting = false;

// Check if we should run the import
if (isset($_GET['action']) && $_GET['action'] === 'import') {
    $isImporting = true;
    
    set_time_limit(120);
    
    $result = [
        'success' => false,
        'total_files' => 0,
        'imported_files' => [],
        'failed_files' => [],
        'statements_executed' => 0,
        'tables_created' => 0,
        'errors' => [],
        'start_time' => microtime(true)
    ];
    
    try {
        $database = new Database();
        $db = $database->getConnection();
        
        // SQL files in import order
        $sqlFiles = [
            'database.sql',
            'create_admin_tables.sql',
            'create_courses_table.sql',
            'create_event_departments_table.sql',
            'student_notifications.sql',
            'add_attendance_locked_column.sql',
            'add_qr_code_locked_field.sql',
        ];
        
        $result['total_files'] = count($sqlFiles);
        
        foreach ($sqlFiles as $file) {
            $filePath = __DIR__ . '/' . $file;
            
            if (!file_exists($filePath)) {
                $result['failed_files'][] = $file;
                $result['errors'][] = "File not found: $file";
                continue;
            }
            
            try {
                $sqlContent = file_get_contents($filePath);
                $statements = array_filter(array_map('trim', explode(';', $sqlContent)));
                
                foreach ($statements as $statement) {
                    if (!empty($statement)) {
                        try {
                            $db->exec($statement);
                            $result['statements_executed']++;
                        } catch (PDOException $e) {
                            // Ignore "already exists" errors
                            if (strpos($e->getMessage(), 'already exists') === false) {
                                throw $e;
                            }
                        }
                    }
                }
                
                $result['imported_files'][] = $file;
                
            } catch (Exception $e) {
                $result['failed_files'][] = $file;
                $result['errors'][] = "$file: " . $e->getMessage();
            }
        }
        
        // Get table count
        $stmt = $db->query("SELECT COUNT(*) as count FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = '" . DB_NAME . "'");
        $result['tables_created'] = $stmt->fetch()['count'];
        
        $result['success'] = count($result['imported_files']) > 0 && $result['tables_created'] > 0;
        
    } catch (Exception $e) {
        $result['errors'][] = "Error: " . $e->getMessage();
    }
    
    $result['execution_time'] = round(microtime(true) - $result['start_time'], 2);
    $importResult = $result;
}

// Get current database status
$dbStatus = [
    'connected' => false,
    'tables' => [],
    'total_tables' => 0
];

try {
    $database = new Database();
    $db = $database->getConnection();
    $dbStatus['connected'] = true;
    
    $stmt = $db->query("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = '" . DB_NAME . "'");
    $dbStatus['tables'] = array_column($stmt->fetchAll(), 'TABLE_NAME');
    $dbStatus['total_tables'] = count($dbStatus['tables']);
} catch (Exception $e) {
    // Connection failed
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Setup - Event Attendance</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
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
        .status-card {
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
        }
        .status-card.success {
            background: #d4edda;
            border: 2px solid #28a745;
            color: #155724;
        }
        .status-card.error {
            background: #f8d7da;
            border: 2px solid #dc3545;
            color: #721c24;
        }
        .status-card.info {
            background: #d1ecf1;
            border: 2px solid #17a2b8;
            color: #0c5460;
        }
        .status-card h2 {
            font-size: 20px;
            margin-bottom: 8px;
        }
        .status-card p {
            font-size: 14px;
            opacity: 0.9;
        }
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin: 20px 0;
        }
        .info-box {
            background: #f8f9ff;
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid #667eea;
        }
        .info-box h3 {
            color: #667eea;
            font-size: 12px;
            text-transform: uppercase;
            margin-bottom: 8px;
        }
        .info-box .value {
            font-size: 24px;
            font-weight: bold;
            color: #333;
        }
        .button-group {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: bold;
            font-size: 14px;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
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
        .files-list {
            margin: 20px 0;
        }
        .file-item {
            display: flex;
            align-items: center;
            padding: 12px;
            background: #f8f9ff;
            margin: 8px 0;
            border-radius: 5px;
            border-left: 3px solid #667eea;
        }
        .file-item.success {
            border-left-color: #28a745;
            background: #d4edda;
        }
        .file-item.error {
            border-left-color: #dc3545;
            background: #f8d7da;
        }
        .file-icon {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 12px;
            font-weight: bold;
            color: white;
            font-size: 14px;
        }
        .file-icon.success {
            background: #28a745;
        }
        .file-icon.error {
            background: #dc3545;
        }
        .file-name {
            font-weight: bold;
            color: #333;
            font-size: 13px;
        }
        .next-steps {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
            padding: 20px;
            border-radius: 8px;
            margin-top: 20px;
        }
        .next-steps h3 {
            margin-bottom: 15px;
        }
        .next-steps ol {
            margin-left: 20px;
            line-height: 1.8;
        }
        .next-steps a {
            color: #0c5460;
            text-decoration: none;
            font-weight: bold;
        }
        .next-steps a:hover {
            text-decoration: underline;
        }
        .loading {
            text-align: center;
            padding: 40px;
        }
        .spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #667eea;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto 15px;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .table-count {
            display: flex;
            gap: 20px;
            margin: 20px 0;
        }
        .table-item {
            flex: 1;
            padding: 15px;
            background: #f0f0f0;
            border-radius: 5px;
            text-align: center;
        }
        .table-item strong {
            display: block;
            color: #667eea;
            font-size: 12px;
            text-transform: uppercase;
            margin-bottom: 5px;
        }
        .table-item .count {
            font-size: 20px;
            font-weight: bold;
            color: #333;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🗄️ Database Setup</h1>
            <p>Event Attendance System - Automatic Import</p>
        </div>

        <div class="content">
            <?php if ($importResult === null): ?>
                <!-- INITIAL STATE - Show option to import -->
                <div class="status-card info">
                    <h2>Ready to Setup Database</h2>
                    <p>This will import all SQL files and create all necessary tables.</p>
                </div>

                <div class="info-grid">
                    <div class="info-box">
                        <h3>Current Tables</h3>
                        <div class="value"><?php echo $dbStatus['total_tables']; ?></div>
                    </div>
                    <div class="info-box">
                        <h3>Status</h3>
                        <div class="value" style="color: <?php echo $dbStatus['connected'] ? '#28a745' : '#dc3545'; ?>;">
                            <?php echo $dbStatus['connected'] ? '✓ Ready' : '✗ Error'; ?>
                        </div>
                    </div>
                </div>

                <?php if ($dbStatus['connected']): ?>
                    <div class="button-group">
                        <a href="?action=import" class="btn btn-primary" onclick="return confirm('Start database import? This will create all necessary tables.')">
                            🚀 Start Import
                        </a>
                    </div>

                    <?php if ($dbStatus['total_tables'] > 0): ?>
                        <div style="background: #fff3cd; border: 1px solid #ffeaa7; color: #856404; padding: 15px; border-radius: 8px; margin-top: 20px;">
                            <strong>ℹ️ Database Already Has Tables</strong>
                            <p style="margin-top: 8px; font-size: 13px;">
                                Your database already contains <?php echo $dbStatus['total_tables']; ?> table(s). 
                                Running import again will add any missing tables and not affect existing data.
                            </p>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div style="background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 15px; border-radius: 8px; margin-top: 20px;">
                        <strong>✗ Database Connection Error</strong>
                        <p style="margin-top: 8px; font-size: 13px;">
                            Could not connect to the database. Please verify that:
                        </p>
                        <ul style="margin-left: 20px; margin-top: 8px; font-size: 13px;">
                            <li>XAMPP MySQL service is running</li>
                            <li>Database 'event_attendance' exists</li>
                            <li>config/database.php settings are correct</li>
                        </ul>
                    </div>
                <?php endif; ?>

            <?php elseif ($isImporting && $importResult): ?>
                <!-- IMPORT RESULTS -->
                <?php if ($importResult['success']): ?>
                    <!-- SUCCESS -->
                    <div class="status-card success">
                        <h2>✓ Database Import Successful!</h2>
                        <p>All SQL files have been imported and tables created.</p>
                    </div>

                    <div class="info-grid">
                        <div class="info-box">
                            <h3>Files Imported</h3>
                            <div class="value"><?php echo count($importResult['imported_files']); ?>/<?php echo $importResult['total_files']; ?></div>
                        </div>
                        <div class="info-box">
                            <h3>Tables Created</h3>
                            <div class="value"><?php echo $importResult['tables_created']; ?></div>
                        </div>
                    </div>

                    <h3 style="margin-top: 25px; margin-bottom: 15px; color: #333;">📁 Imported Files:</h3>
                    <div class="files-list">
                        <?php foreach ($importResult['imported_files'] as $file): ?>
                            <div class="file-item success">
                                <div class="file-icon success">✓</div>
                                <div class="file-name"><?php echo $file; ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="next-steps">
                        <h3>🎯 Next Steps:</h3>
                        <ol>
                            <li><a href="create_accounts.php">Create Test Accounts</a> (Admin & Student accounts)</li>
                            <li><a href="signin.php">Student Login</a> (juan.delacruz@llcc.edu.ph / Student@123)</li>
                            <li><a href="admin_signin.php">Admin Login</a> (admin@llcc.edu.ph / Admin@123)</li>
                            <li><a href="dashboard.php">Access Student Dashboard</a></li>
                        </ol>
                    </div>

                <?php else: ?>
                    <!-- PARTIAL SUCCESS / ERROR -->
                    <div class="status-card error">
                        <h2><?php echo count($importResult['imported_files']) > 0 ? '⚠️ Partial Import' : '✗ Import Failed'; ?></h2>
                        <p><?php echo count($importResult['imported_files']); ?> file(s) imported, <?php echo count($importResult['failed_files']); ?> failed.</p>
                    </div>

                    <?php if (count($importResult['imported_files']) > 0): ?>
                        <h3 style="margin-top: 20px; margin-bottom: 15px; color: #333;">✓ Successful:</h3>
                        <div class="files-list">
                            <?php foreach ($importResult['imported_files'] as $file): ?>
                                <div class="file-item success">
                                    <div class="file-icon success">✓</div>
                                    <div class="file-name"><?php echo $file; ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <?php if (count($importResult['failed_files']) > 0): ?>
                        <h3 style="margin-top: 20px; margin-bottom: 15px; color: #333;">✗ Failed:</h3>
                        <div class="files-list">
                            <?php foreach ($importResult['failed_files'] as $file): ?>
                                <div class="file-item error">
                                    <div class="file-icon error">✗</div>
                                    <div class="file-name"><?php echo $file; ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($importResult['errors'])): ?>
                        <div style="background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 15px; border-radius: 8px; margin-top: 20px;">
                            <strong>Errors:</strong>
                            <ul style="margin-left: 20px; margin-top: 10px; font-size: 13px;">
                                <?php foreach ($importResult['errors'] as $error): ?>
                                    <li><?php echo htmlspecialchars($error); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <div class="button-group" style="margin-top: 20px;">
                        <a href="?action=import" class="btn btn-primary" onclick="return confirm('Retry import?')">
                            🔄 Retry
                        </a>
                        <a href="import_sql.php" class="btn btn-secondary">
                            📋 Manual Import
                        </a>
                    </div>
                <?php endif; ?>

                <div style="background: #e7f3ff; border: 1px solid #b3d9ff; color: #003366; padding: 12px; border-radius: 5px; margin-top: 20px; font-size: 12px;">
                    <strong>ℹ️ Execution Time:</strong> <?php echo $importResult['execution_time']; ?>s | 
                    <strong>Statements:</strong> <?php echo $importResult['statements_executed']; ?> |
                    <strong>Tables:</strong> <?php echo $importResult['tables_created']; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
