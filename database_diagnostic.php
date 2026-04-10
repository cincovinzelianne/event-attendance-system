<?php
/**
 * Database Diagnostic Dashboard - HTML Version
 * Complete analysis of database tables and structure
 */

require_once 'config/database.php';

$requiredTables = [
    'students' => 'Student authentication and profiles',
    'admins' => 'Admin user accounts',
    'admin_sessions' => 'Admin session tracking',
    'events' => 'Event information',
    'attendance' => 'Attendance records',
    'user_sessions' => 'Student session tracking',
    'analytics' => 'Action logging',
    'courses' => 'Course/program information',
    'event_departments' => 'Event categorization',
    'student_notifications' => 'Notification tracking'
];

$dbStatus = [
    'connected' => false,
    'dbExists' => false,
    'tables' => [],
    'existingTables' => [],
    'missingTables' => [],
    'error' => null,
    'tableCounts' => []
];

try {
    $database = new Database();
    $db = $database->getConnection();
    $dbStatus['connected'] = true;
    
    // Check if database exists
    $stmt = $db->query("SELECT DATABASE()");
    $result = $stmt->fetch();
    if ($result['DATABASE()'] === DB_NAME) {
        $dbStatus['dbExists'] = true;
    }
    
    // Get existing tables
    $stmt = $db->query("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = '" . DB_NAME . "'");
    $tables = $stmt->fetchAll();
    $dbStatus['existingTables'] = array_column($tables, 'TABLE_NAME');
    
    // Compare with required tables
    foreach ($requiredTables as $tableName => $description) {
        $exists = in_array($tableName, $dbStatus['existingTables']);
        
        if ($exists) {
            // Get row count
            $countStmt = $db->query("SELECT COUNT(*) as count FROM $tableName");
            $count = $countStmt->fetch()['count'];
            $dbStatus['tableCounts'][$tableName] = $count;
        } else {
            $dbStatus['missingTables'][] = $tableName;
        }
    }
    
} catch (Exception $e) {
    $dbStatus['error'] = $e->getMessage();
}

$missing = count($dbStatus['missingTables']);
$present = count($requiredTables) - $missing;
$systemReady = $missing === 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Diagnostic - Event Attendance</title>
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
        }
        .header p {
            color: #666;
            font-size: 14px;
        }
        .status-banner {
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
            font-weight: bold;
            font-size: 16px;
        }
        .status-banner.success {
            background: #d4edda;
            color: #155724;
            border: 2px solid #c3e6cb;
        }
        .status-banner.error {
            background: #f8d7da;
            color: #721c24;
            border: 2px solid #f5c6cb;
        }
        .status-banner.warning {
            background: #fff3cd;
            color: #856404;
            border: 2px solid #ffeaa7;
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
            font-size: 18px;
            font-weight: bold;
        }
        .card-body {
            padding: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th {
            background: #f8f9fa;
            padding: 12px;
            text-align: left;
            border-bottom: 2px solid #dee2e6;
            font-weight: bold;
            color: #333;
        }
        td {
            padding: 12px;
            border-bottom: 1px solid #dee2e6;
        }
        tr:hover {
            background: #f8f9ff;
        }
        .status-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }
        .badge-success {
            background: #d4edda;
            color: #155724;
        }
        .badge-danger {
            background: #f8d7da;
            color: #721c24;
        }
        .count-badge {
            display: inline-block;
            background: #667eea;
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        .stat-box {
            background: #f8f9ff;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            border-left: 4px solid #667eea;
        }
        .stat-box h3 {
            color: #667eea;
            font-size: 12px;
            text-transform: uppercase;
            margin-bottom: 10px;
        }
        .stat-box .value {
            font-size: 32px;
            font-weight: bold;
            color: #333;
        }
        .connection-status {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 15px;
        }
        .connection-status .indicator {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 12px;
        }
        .indicator.connected {
            background: #28a745;
        }
        .indicator.disconnected {
            background: #dc3545;
        }
        .action-buttons {
            display: flex;
            gap: 10px;
            margin-top: 20px;
            flex-wrap: wrap;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: bold;
            border: none;
            cursor: pointer;
            transition: all 0.3s;
        }
        .btn-primary {
            background: #667eea;
            color: white;
        }
        .btn-primary:hover {
            background: #764ba2;
            transform: translateY(-2px);
        }
        .error-box {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .warning-box {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        code {
            background: #f0f0f0;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
            color: #d63384;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>🗄️ Database Diagnostic Report</h1>
            <p>Event Attendance System - Table Status Check</p>
            <p style="margin-top: 10px; font-size: 12px; color: #999;">
                Generated: <?php echo date('Y-m-d H:i:s'); ?>
            </p>
        </div>

        <!-- Status Banner -->
        <?php if ($dbStatus['error']): ?>
            <div class="status-banner error">
                ❌ Database Connection Error: <?php echo htmlspecialchars($dbStatus['error']); ?>
            </div>
        <?php elseif ($systemReady): ?>
            <div class="status-banner success">
                ✓ Database is Ready! All required tables exist (<?php echo count($requiredTables); ?> tables)
            </div>
        <?php else: ?>
            <div class="status-banner warning">
                ⚠️ Database Missing Tables: <?php echo $missing; ?> of <?php echo count($requiredTables); ?> tables are missing
            </div>
        <?php endif; ?>

        <!-- Connection Status Card -->
        <div class="card">
            <div class="card-header">📡 Connection Status</div>
            <div class="card-body">
                <div class="connection-status">
                    <div class="indicator <?php echo $dbStatus['connected'] ? 'connected' : 'disconnected'; ?>">
                        <?php echo $dbStatus['connected'] ? '✓' : '✗'; ?>
                    </div>
                    <div>
                        <strong>Database Connection:</strong><br>
                        <span><?php echo $dbStatus['connected'] ? '✓ Connected' : '✗ Failed'; ?></span><br>
                        <span style="color: #666; font-size: 12px;">Host: <?php echo DB_HOST; ?> | Database: <?php echo DB_NAME; ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-box">
                <h3>Total Tables Required</h3>
                <div class="value"><?php echo count($requiredTables); ?></div>
            </div>
            <div class="stat-box">
                <h3>Tables Present</h3>
                <div class="value" style="color: #28a745;"><?php echo $present; ?></div>
            </div>
            <div class="stat-box">
                <h3>Tables Missing</h3>
                <div class="value" style="color: <?php echo $missing > 0 ? '#dc3545' : '#28a745'; ?>;"><?php echo $missing; ?></div>
            </div>
        </div>

        <!-- Table Status -->
        <div class="card">
            <div class="card-header">📋 Table Status</div>
            <div class="card-body">
                <table>
                    <thead>
                        <tr>
                            <th>Table Name</th>
                            <th>Description</th>
                            <th>Status</th>
                            <th>Records</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($requiredTables as $tableName => $description): ?>
                            <?php 
                            $exists = in_array($tableName, $dbStatus['existingTables']);
                            $count = $dbStatus['tableCounts'][$tableName] ?? 0;
                            ?>
                            <tr>
                                <td><strong><?php echo $tableName; ?></strong></td>
                                <td><?php echo $description; ?></td>
                                <td>
                                    <span class="status-badge <?php echo $exists ? 'badge-success' : 'badge-danger'; ?>">
                                        <?php echo $exists ? '✓ Exists' : '✗ Missing'; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($exists): ?>
                                        <span class="count-badge"><?php echo $count; ?></span> records
                                    <?php else: ?>
                                        <span style="color: #999; font-size: 12px;">N/A</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Missing Tables -->
        <?php if (!empty($dbStatus['missingTables'])): ?>
            <div class="card">
                <div class="card-header">✗ Missing Tables Action Required</div>
                <div class="card-body">
                    <div class="warning-box">
                        <strong>⚠️ Warning:</strong> The following tables are missing and must be created for the system to work:
                        <ul style="margin-left: 20px; margin-top: 10px;">
                            <?php foreach ($dbStatus['missingTables'] as $table): ?>
                                <li><code><?php echo $table; ?></code></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>

                    <h3 style="color: #333; margin-top: 20px; margin-bottom: 10px;">📝 How to Fix:</h3>
                    <p style="margin-bottom: 15px; color: #666;">
                        You need to import the SQL files to create these tables. Follow these steps:
                    </p>
                    <ol style="margin-left: 20px; color: #666; line-height: 1.8;">
                        <li>Open <a href="http://localhost/phpmyadmin" style="color: #667eea;">phpMyAdmin</a></li>
                        <li>Select the <code><?php echo DB_NAME; ?></code> database</li>
                        <li>Go to the "Import" tab</li>
                        <li>Import these files in order:
                            <ul style="margin-left: 20px; margin-top: 5px;">
                                <li><code>database.sql</code> - Core tables (students, events, attendance, etc.)</li>
                                <li><code>create_admin_tables.sql</code> - Admin tables</li>
                                <li><code>create_courses_table.sql</code> - Courses</li>
                                <li><code>student_notifications.sql</code> - Notifications</li>
                            </ul>
                        </li>
                        <li>Refresh this page to verify tables are created</li>
                    </ol>

                    <div class="action-buttons" style="margin-top: 20px;">
                        <a href="http://localhost/phpmyadmin" class="btn btn-primary" target="_blank">
                            🔗 Open phpMyAdmin
                        </a>
                        <button class="btn btn-primary" onclick="location.reload()">
                            🔄 Refresh Status
                        </button>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="card">
                <div class="card-header">✓ System Ready</div>
                <div class="card-body">
                    <div style="background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 15px; border-radius: 5px; margin-bottom: 20px;">
                        ✓ All required database tables are present! Your system is ready to use.
                    </div>
                    
                    <h3 style="color: #333; margin-bottom: 10px;">🚀 Next Steps:</h3>
                    <ol style="margin-left: 20px; color: #666; line-height: 1.8;">
                        <li>Create test accounts: <a href="create_accounts.php" style="color: #667eea;">create_accounts.php</a></li>
                        <li>Sign in as student: <a href="signin.php" style="color: #667eea;">signin.php</a></li>
                        <li>Sign in as admin: <a href="admin_signin.php" style="color: #667eea;">admin_signin.php</a></li>
                    </ol>
                </div>
            </div>
        <?php endif; ?>

        <!-- Database Details -->
        <div class="card">
            <div class="card-header">ℹ️ Database Information</div>
            <div class="card-body">
                <table>
                    <tr>
                        <td><strong>Database Name:</strong></td>
                        <td><code><?php echo DB_NAME; ?></code></td>
                    </tr>
                    <tr>
                        <td><strong>Host:</strong></td>
                        <td><code><?php echo DB_HOST; ?></code></td>
                    </tr>
                    <tr>
                        <td><strong>User:</strong></td>
                        <td><code><?php echo DB_USER; ?></code></td>
                    </tr>
                    <tr>
                        <td><strong>Connection Status:</strong></td>
                        <td><?php echo $dbStatus['connected'] ? '✓ Connected' : '✗ Failed'; ?></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
