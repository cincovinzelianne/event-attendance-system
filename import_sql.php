<?php
/**
 * SQL File Import Manager
 * Shows all available SQL files and allows importing them to database
 */

require_once 'config/database.php';

// Define SQL files in import order with descriptions
$sqlFiles = [
    [
        'file' => 'database.sql',
        'order' => 1,
        'category' => 'CORE TABLES',
        'required' => true,
        'description' => 'Main database tables: students, events, attendance, analytics, sessions',
        'tables' => ['students', 'user_sessions', 'events', 'attendance', 'analytics']
    ],
    [
        'file' => 'create_admin_tables.sql',
        'order' => 2,
        'category' => 'ADMIN INFRASTRUCTURE',
        'required' => true,
        'description' => 'Admin users and session management',
        'tables' => ['admins', 'admin_sessions']
    ],
    [
        'file' => 'create_courses_table.sql',
        'order' => 3,
        'category' => 'COURSES & DEPARTMENTS',
        'required' => true,
        'description' => 'Course and department information with hierarchical structure',
        'tables' => ['courses']
    ],
    [
        'file' => 'create_event_departments_table.sql',
        'order' => 4,
        'category' => 'EVENT MANAGEMENT',
        'required' => false,
        'description' => 'Event department categorization',
        'tables' => ['event_departments']
    ],
    [
        'file' => 'student_notifications.sql',
        'order' => 5,
        'category' => 'NOTIFICATIONS',
        'required' => false,
        'description' => 'Student notification system',
        'tables' => ['student_notifications']
    ],
    [
        'file' => 'add_attendance_locked_column.sql',
        'order' => 6,
        'category' => 'ENHANCEMENTS',
        'required' => false,
        'description' => 'Add attendance sheet locking feature',
        'tables' => []
    ],
    [
        'file' => 'add_qr_code_locked_field.sql',
        'order' => 7,
        'category' => 'ENHANCEMENTS',
        'required' => false,
        'description' => 'Add QR code locking feature for admin control',
        'tables' => []
    ],
    [
        'file' => 'sample_accounts.sql',
        'order' => 8,
        'category' => 'TEST DATA',
        'required' => false,
        'description' => 'Sample admin and student accounts',
        'tables' => []
    ],
];

// Handle import request
$importResult = null;
$importedFile = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['import_file'])) {
    $fileToImport = $_POST['import_file'];
    $filePath = $fileToImport;
    
    if (file_exists($filePath)) {
        try {
            $database = new Database();
            $db = $database->getConnection();
            
            // Read SQL file
            $sqlContent = file_get_contents($filePath);
            
            // Split by semicolon and execute each statement
            $statements = array_filter(array_map('trim', explode(';', $sqlContent)));
            $successCount = 0;
            $errorCount = 0;
            $errors = [];
            
            foreach ($statements as $statement) {
                if (!empty($statement)) {
                    try {
                        $db->exec($statement);
                        $successCount++;
                    } catch (Exception $e) {
                        $errorCount++;
                        $errors[] = $e->getMessage();
                    }
                }
            }
            
            $importResult = [
                'success' => $errorCount === 0,
                'file' => basename($fileToImport),
                'successCount' => $successCount,
                'errorCount' => $errorCount,
                'errors' => $errors
            ];
            $importedFile = basename($fileToImport);
            
        } catch (Exception $e) {
            $importResult = [
                'success' => false,
                'file' => basename($fileToImport),
                'errors' => [$e->getMessage()]
            ];
        }
    }
}

// Get table status
$existingTables = [];
try {
    $database = new Database();
    $db = $database->getConnection();
    $stmt = $db->query("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = '" . DB_NAME . "'");
    $existingTables = array_column($stmt->fetchAll(), 'TABLE_NAME');
} catch (Exception $e) {
    // Connection error
}

// Count existing tables
$allTables = [];
foreach ($sqlFiles as $file) {
    foreach ($file['tables'] as $table) {
        $allTables[] = $table;
    }
}
$tablesImported = count(array_intersect($allTables, $existingTables));
$totalTables = count(array_unique($allTables));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SQL Import Manager - Event Attendance</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        .header {
            background: white;
            padding: 30px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        .header h1 {
            color: #333;
            margin-bottom: 10px;
            font-size: 28px;
        }
        .header p {
            color: #666;
            font-size: 14px;
        }
        .progress-bar {
            width: 100%;
            height: 30px;
            background: #e9ecef;
            border-radius: 15px;
            overflow: hidden;
            margin-top: 15px;
            display: flex;
            align-items: center;
        }
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 12px;
            font-weight: bold;
            width: <?php echo ($tablesImported / max($totalTables, 1)) * 100; ?>%;
            transition: width 0.3s;
        }
        .progress-text {
            position: absolute;
            width: 100%;
            text-align: center;
            color: #333;
            font-weight: bold;
            font-size: 12px;
        }
        .card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            overflow: hidden;
        }
        .card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            font-weight: bold;
            font-size: 16px;
        }
        .card-body {
            padding: 20px;
        }
        .sql-file-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 15px;
            border-bottom: 1px solid #eee;
            margin-bottom: 10px;
            background: #f8f9ff;
            border-radius: 5px;
            gap: 15px;
        }
        .sql-file-row:last-child {
            margin-bottom: 0;
            border-bottom: none;
        }
        .file-info {
            flex: 1;
        }
        .file-info .filename {
            font-weight: bold;
            color: #333;
            font-size: 14px;
            margin-bottom: 3px;
        }
        .file-info .description {
            color: #666;
            font-size: 12px;
        }
        .file-category {
            display: inline-block;
            background: #667eea;
            color: white;
            padding: 4px 10px;
            border-radius: 15px;
            font-size: 11px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .file-category.required {
            background: #dc3545;
        }
        .file-status {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .status-indicator {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: white;
            font-size: 14px;
        }
        .status-indicator.imported {
            background: #28a745;
        }
        .status-indicator.pending {
            background: #ffc107;
        }
        .import-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 8px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            font-size: 12px;
            transition: all 0.3s;
        }
        .import-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        .import-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        .alert {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            display: none;
        }
        .alert.show { display: block; }
        .alert.success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        .alert.error {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
        .alert strong {
            display: block;
            margin-bottom: 5px;
        }
        .alert ul {
            margin-left: 20px;
            margin-top: 5px;
        }
        .quick-import {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        .quick-import-btn {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            font-size: 13px;
            transition: all 0.3s;
        }
        .quick-import-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(40, 167, 69, 0.4);
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        .stat-box {
            background: #f8f9ff;
            padding: 15px;
            border-radius: 5px;
            text-align: center;
            border-left: 4px solid #667eea;
        }
        .stat-box h4 {
            color: #667eea;
            font-size: 11px;
            text-transform: uppercase;
            margin-bottom: 5px;
        }
        .stat-box .value {
            font-size: 24px;
            font-weight: bold;
            color: #333;
        }
        .divider {
            height: 1px;
            background: #eee;
            margin: 20px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
        }
        th {
            background: #f0f0f0;
            padding: 10px;
            text-align: left;
            font-weight: bold;
            border-bottom: 2px solid #ddd;
        }
        td {
            padding: 10px;
            border-bottom: 1px solid #eee;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>📊 SQL Import Manager</h1>
            <p>Event Attendance System - Database Setup Tool</p>
            
            <div class="stats-grid" style="margin-top: 20px;">
                <div class="stat-box">
                    <h4>Total SQL Files</h4>
                    <div class="value"><?php echo count($sqlFiles); ?></div>
                </div>
                <div class="stat-box">
                    <h4>Tables Status</h4>
                    <div class="value"><?php echo $tablesImported; ?>/<?php echo $totalTables; ?></div>
                </div>
                <div class="stat-box">
                    <h4>Progress</h4>
                    <div class="value"><?php echo round(($tablesImported / max($totalTables, 1)) * 100); ?>%</div>
                </div>
            </div>

            <div class="progress-bar">
                <div class="progress-fill"></div>
                <div class="progress-text"><?php echo $tablesImported; ?>/<?php echo $totalTables; ?> tables created</div>
            </div>
        </div>

        <!-- Import Result Alert -->
        <?php if ($importResult): ?>
            <div class="alert show <?php echo $importResult['success'] ? 'success' : 'error'; ?>">
                <strong><?php echo $importResult['success'] ? '✓ Import Successful!' : '✗ Import Failed'; ?></strong>
                File: <strong><?php echo htmlspecialchars($importResult['file']); ?></strong><br>
                <?php if ($importResult['success']): ?>
                    Executed <?php echo $importResult['successCount']; ?> SQL statements successfully.<br>
                    <em style="font-size: 12px; opacity: 0.9;">Page will refresh in 3 seconds...</em>
                    <script>setTimeout(() => location.reload(), 3000);</script>
                <?php else: ?>
                    Errors encountered:
                    <ul>
                        <?php foreach ($importResult['errors'] as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- Quick Import All -->
        <div class="card">
            <div class="card-header">⚡ Quick Setup</div>
            <div class="card-body">
                <p style="color: #666; margin-bottom: 15px; font-size: 13px;">
                    Click to import all required foundation tables (Core, Admin, Courses). Optional tables can be imported individually below.
                </p>
                <div style="display: flex; gap: 10px;">
                    <form method="POST" style="display: flex; gap: 10px;">
                        <input type="hidden" name="import_file" value="database.sql">
                        <button type="submit" class="quick-import-btn" onclick="return confirm('Import database.sql?')">
                            📦 1. Core Tables
                        </button>
                    </form>
                    <form method="POST" style="display: flex; gap: 10px;">
                        <input type="hidden" name="import_file" value="create_admin_tables.sql">
                        <button type="submit" class="quick-import-btn" onclick="return confirm('Import create_admin_tables.sql?')">
                            👨‍💼 2. Admin Tables
                        </button>
                    </form>
                    <form method="POST" style="display: flex; gap: 10px;">
                        <input type="hidden" name="import_file" value="create_courses_table.sql">
                        <button type="submit" class="quick-import-btn" onclick="return confirm('Import create_courses_table.sql?')">
                            📚 3. Courses
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- All SQL Files -->
        <div class="card">
            <div class="card-header">📁 Available SQL Files</div>
            <div class="card-body">
                <?php 
                // Group by category
                $byCategory = [];
                foreach ($sqlFiles as $file) {
                    $cat = $file['category'];
                    if (!isset($byCategory[$cat])) {
                        $byCategory[$cat] = [];
                    }
                    $byCategory[$cat][] = $file;
                }
                
                foreach ($byCategory as $category => $files):
                ?>
                    <div style="margin-bottom: 25px;">
                        <h3 style="color: #667eea; margin-bottom: 15px; font-size: 14px; text-transform: uppercase; opacity: 0.8;">
                            <?php echo $category; ?>
                        </h3>
                        
                        <?php foreach ($files as $file):
                            $fileExists = file_exists($file['file']);
                            $imported = false;
                            
                            // Check if tables from this file are already imported
                            if (!empty($file['tables'])) {
                                $imported = !empty(array_intersect($file['tables'], $existingTables));
                            }
                        ?>
                            <div class="sql-file-row">
                                <div style="flex-basis: 60px;">
                                    <div class="file-category <?php echo $file['required'] ? 'required' : ''; ?>">
                                        <?php echo $file['required'] ? 'REQUIRED' : 'OPTIONAL'; ?>
                                    </div>
                                </div>
                                
                                <div class="file-info">
                                    <div class="filename"><?php echo $file['file']; ?></div>
                                    <div class="description"><?php echo $file['description']; ?></div>
                                    <?php if (!empty($file['tables'])): ?>
                                        <div style="font-size: 11px; color: #999; margin-top: 3px;">
                                            Tables: <code><?php echo implode(', ', $file['tables']); ?></code>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="file-status">
                                    <div class="status-indicator <?php echo $imported ? 'imported' : 'pending'; ?>">
                                        <?php echo $imported ? '✓' : '○'; ?>
                                    </div>
                                    
                                    <?php if ($fileExists): ?>
                                        <form method="POST" style="display: contents;">
                                            <input type="hidden" name="import_file" value="<?php echo $file['file']; ?>">
                                            <button type="submit" class="import-btn" onclick="return confirm('Import <?php echo $file['file']; ?>?')">
                                                <?php echo $imported ? 'REIMPORT' : 'IMPORT'; ?>
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <span style="color: #999; font-size: 12px;">File not found</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Implementation Guide -->
        <div class="card">
            <div class="card-header">📖 Recommended Import Order</div>
            <div class="card-body">
                <table>
                    <thead>
                        <tr>
                            <th>Order</th>
                            <th>File</th>
                            <th>Purpose</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($sqlFiles as $file):
                            $imported = false;
                            if (!empty($file['tables'])) {
                                $imported = !empty(array_intersect($file['tables'], $existingTables));
                            }
                        ?>
                            <tr>
                                <td>#<?php echo $file['order']; ?></td>
                                <td><strong><?php echo $file['file']; ?></strong></td>
                                <td><?php echo $file['description']; ?></td>
                                <td>
                                    <span style="color: <?php echo $imported ? '#28a745' : '#ffc107'; ?>; font-weight: bold;">
                                        <?php echo $imported ? '✓ Imported' : '○ Pending'; ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Next Steps -->
        <div class="card">
            <div class="card-header">🎯 Next Steps</div>
            <div class="card-body">
                <p style="color: #666; margin-bottom: 15px;">After importing all SQL files:</p>
                <ol style="margin-left: 20px; color: #666; line-height: 1.8;">
                    <li>All database tables will be created and ready</li>
                    <li>Create test accounts: <a href="create_accounts.php" style="color: #667eea; font-weight: bold;">create_accounts.php</a></li>
                    <li>Sign in as student: <a href="signin.php" style="color: #667eea; font-weight: bold;">signin.php</a></li>
                    <li>Sign in as admin: <a href="admin_signin.php" style="color: #667eea; font-weight: bold;">admin_signin.php</a></li>
                    <li>View system health: <a href="system_check.php" style="color: #667eea; font-weight: bold;">system_check.php</a></li>
                </ol>
            </div>
        </div>
    </div>
</body>
</html>
