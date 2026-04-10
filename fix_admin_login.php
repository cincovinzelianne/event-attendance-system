<?php
/**
 * Admin Account Fix - Create Default Admin Accounts
 */

require_once 'config/database.php';

$status = [
    'db_connected' => false,
    'admins_table_exists' => false,
    'existing_admins' => [],
    'admin_sessions_table_exists' => false
];

try {
    $database = new Database();
    $db = $database->getConnection();
    $status['db_connected'] = true;
    
    // Check if admins table exists
    $stmt = $db->query("SHOW TABLES LIKE 'admins'");
    $status['admins_table_exists'] = $stmt->rowCount() > 0;
    
    // Check if admin_sessions table exists
    $stmt = $db->query("SHOW TABLES LIKE 'admin_sessions'");
    $status['admin_sessions_table_exists'] = $stmt->rowCount() > 0;
    
    // Get existing admins
    if ($status['admins_table_exists']) {
        $stmt = $db->query("SELECT id, username, email, full_name FROM admins");
        $status['existing_admins'] = $stmt->fetchAll();
    }
    
} catch (Exception $e) {
    $status['db_connected'] = false;
}

// Handle admin creation
$creationResult = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_admins'])) {
    $creationResult = [
        'success' => false,
        'created' => [],
        'errors' => []
    ];
    
    try {
        $database = new Database();
        $db = $database->getConnection();
        
        if (!$status['admins_table_exists']) {
            $creationResult['errors'][] = "Admins table does not exist. Import create_admin_tables.sql first.";
        } else {
            $adminAccounts = [
                [
                    'username' => 'admin',
                    'email' => 'admin@llcc.edu.ph',
                    'password' => 'Admin@123',
                    'full_name' => 'System Administrator'
                ],
                [
                    'username' => 'admin_staff',
                    'email' => 'staff@llcc.edu.ph',
                    'password' => 'Staff@123',
                    'full_name' => 'Admin Staff'
                ]
            ];
            
            foreach ($adminAccounts as $admin) {
                try {
                    // Check if already exists
                    $checkStmt = $db->prepare("SELECT id FROM admins WHERE username = ? OR email = ?");
                    $checkStmt->execute([$admin['username'], $admin['email']]);
                    
                    if ($checkStmt->fetch()) {
                        $creationResult['errors'][] = "Admin already exists: " . $admin['username'];
                        continue;
                    }
                    
                    // Hash password
                    $passwordHash = password_hash($admin['password'], PASSWORD_DEFAULT);
                    
                    // Insert
                    $insertStmt = $db->prepare("
                        INSERT INTO admins (username, email, password_hash, full_name, role, is_active) 
                        VALUES (?, ?, ?, ?, 'admin', 1)
                    ");
                    
                    $insertStmt->execute([
                        $admin['username'],
                        $admin['email'],
                        $passwordHash,
                        $admin['full_name']
                    ]);
                    
                    $creationResult['created'][] = [
                        'username' => $admin['username'],
                        'email' => $admin['email'],
                        'password' => $admin['password']
                    ];
                    
                } catch (Exception $e) {
                    $creationResult['errors'][] = $e->getMessage();
                }
            }
            
            $creationResult['success'] = count($creationResult['created']) > 0;
        }
    } catch (Exception $e) {
        $creationResult['errors'][] = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fix Admin Login - Event Attendance</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container {
            max-width: 900px;
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
        }
        .content {
            padding: 40px;
        }
        .alert {
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            align-items: flex-start;
            gap: 15px;
        }
        .alert.success {
            background: #d4edda;
            border: 2px solid #28a745;
            color: #155724;
        }
        .alert.error {
            background: #f8d7da;
            border: 2px solid #dc3545;
            color: #721c24;
        }
        .alert.info {
            background: #d1ecf1;
            border: 2px solid #17a2b8;
            color: #0c5460;
        }
        .alert.warning {
            background: #fff3cd;
            border: 2px solid #ffc107;
            color: #856404;
        }
        .alert-icon {
            font-size: 24px;
            flex-shrink: 0;
        }
        .alert-content h3 {
            margin-bottom: 5px;
        }
        .alert-content p {
            font-size: 14px;
            opacity: 0.9;
        }
        .section {
            margin-bottom: 30px;
        }
        .section h2 {
            color: #333;
            font-size: 20px;
            margin-bottom: 15px;
            border-bottom: 2px solid #667eea;
            padding-bottom: 10px;
        }
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        .info-box {
            background: #f8f9ff;
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid #667eea;
        }
        .info-box h4 {
            color: #667eea;
            font-size: 12px;
            text-transform: uppercase;
            margin-bottom: 8px;
        }
        .info-box .value {
            font-size: 16px;
            font-weight: bold;
            color: #333;
        }
        .status-indicator {
            display: inline-block;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            margin-right: 5px;
        }
        .status-indicator.ok {
            background: #28a745;
        }
        .status-indicator.error {
            background: #dc3545;
        }
        .button-group {
            display: flex;
            gap: 10px;
            margin-top: 20px;
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
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        th {
            background: #f0f0f0;
            padding: 12px;
            text-align: left;
            border-bottom: 2px solid #ddd;
            font-weight: bold;
            color: #333;
        }
        td {
            padding: 12px;
            border-bottom: 1px solid #eee;
        }
        tr:hover {
            background: #f8f9ff;
        }
        .code-box {
            background: #f0f0f0;
            padding: 12px;
            border-radius: 5px;
            font-family: 'Courier New', monospace;
            font-size: 13px;
            color: #333;
            margin: 10px 0;
            word-break: break-all;
        }
        .credentials-table {
            background: #f8f9ff;
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid #667eea;
        }
        .credentials-table h4 {
            color: #333;
            margin-bottom: 12px;
        }
        .credentials-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #ddd;
        }
        .credentials-row:last-child {
            border-bottom: none;
        }
        .credentials-label {
            font-weight: bold;
            color: #667eea;
            min-width: 100px;
        }
        .credentials-value {
            color: #333;
            font-family: 'Courier New', monospace;
        }
        .step-box {
            background: #f8f9ff;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 15px;
            border-left: 4px solid #667eea;
        }
        .step-box h4 {
            color: #667eea;
            margin-bottom: 8px;
        }
        .step-box ol {
            margin-left: 20px;
            color: #666;
            font-size: 14px;
            line-height: 1.8;
        }
        .step-box li {
            margin-bottom: 8px;
        }
        form {
            display: contents;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔐 Fix Admin Login</h1>
            <p>Create or repair admin account credentials</p>
        </div>

        <div class="content">
            <!-- CREATION RESULT -->
            <?php if ($creationResult): ?>
                <?php if ($creationResult['success']): ?>
                    <div class="alert success">
                        <div class="alert-icon">✓</div>
                        <div class="alert-content">
                            <h3>Admin Accounts Created Successfully!</h3>
                            <p><?php echo count($creationResult['created']); ?> admin account(s) have been added to the database.</p>
                        </div>
                    </div>

                    <div class="section">
                        <h2>✓ Login Credentials</h2>
                        <?php foreach ($creationResult['created'] as $admin): ?>
                            <div class="credentials-table">
                                <h4><?php echo $admin['username']; ?></h4>
                                <div class="credentials-row">
                                    <div class="credentials-label">Email:</div>
                                    <div class="credentials-value"><?php echo $admin['email']; ?></div>
                                </div>
                                <div class="credentials-row">
                                    <div class="credentials-label">Password:</div>
                                    <div class="credentials-value"><?php echo $admin['password']; ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="button-group">
                        <a href="admin_signin.php" class="btn btn-primary">🔗 Go to Admin Login</a>
                    </div>

                    <div class="alert info" style="margin-top: 20px;">
                        <div class="alert-icon">ℹ️</div>
                        <div class="alert-content">
                            <h3>Next Step</h3>
                            <p>Click the link above to login with your admin credentials. Use the email and password provided above.</p>
                        </div>
                    </div>

                <?php else: ?>
                    <div class="alert error">
                        <div class="alert-icon">✗</div>
                        <div class="alert-content">
                            <h3>Creation Failed</h3>
                            <p>Could not create admin accounts. See errors below.</p>
                        </div>
                    </div>

                    <?php if (!empty($creationResult['errors'])): ?>
                        <div class="section">
                            <h2>Errors</h2>
                            <ul style="margin-left: 20px; color: #666;">
                                <?php foreach ($creationResult['errors'] as $error): ?>
                                    <li style="margin: 5px 0; font-size: 13px;"><?php echo htmlspecialchars($error); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>

            <?php else: ?>
                <!-- INITIAL STATE - Show diagnostics and option to create -->

                <?php if (!$status['db_connected']): ?>
                    <div class="alert error">
                        <div class="alert-icon">✗</div>
                        <div class="alert-content">
                            <h3>Database Connection Failed</h3>
                            <p>Cannot connect to the database. Make sure XAMPP MySQL is running.</p>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Database Status -->
                    <div class="section">
                        <h2>📊 Database Status</h2>
                        <div class="info-grid">
                            <div class="info-box">
                                <h4><span class="status-indicator <?php echo $status['db_connected'] ? 'ok' : 'error'; ?>"></span>Connection</h4>
                                <div class="value"><?php echo $status['db_connected'] ? '✓ Connected' : '✗ Failed'; ?></div>
                            </div>
                            <div class="info-box">
                                <h4><span class="status-indicator <?php echo $status['admins_table_exists'] ? 'ok' : 'error'; ?>"></span>Admins Table</h4>
                                <div class="value"><?php echo $status['admins_table_exists'] ? '✓ Exists' : '✗ Missing'; ?></div>
                            </div>
                            <div class="info-box">
                                <h4><span class="status-indicator <?php echo $status['admin_sessions_table_exists'] ? 'ok' : 'error'; ?>"></span>Sessions Table</h4>
                                <div class="value"><?php echo $status['admin_sessions_table_exists'] ? '✓ Exists' : '✗ Missing'; ?></div>
                            </div>
                            <div class="info-box">
                                <h4>Existing Admins</h4>
                                <div class="value"><?php echo count($status['existing_admins']); ?></div>
                            </div>
                        </div>

                        <?php if (!$status['admins_table_exists']): ?>
                            <div class="alert warning">
                                <div class="alert-icon">⚠️</div>
                                <div class="alert-content">
                                    <h3>Admin Tables Missing</h3>
                                    <p>The admins table doesn't exist. You need to import <code>create_admin_tables.sql</code> first.</p>
                                    <div class="button-group" style="margin-top: 10px;">
                                        <a href="setup_database.php" class="btn btn-secondary">Import SQL Files</a>
                                    </div>
                                </div>
                            </div>
                        <?php else: ?>
                            <!-- Show existing admins if any -->
                            <?php if (!empty($status['existing_admins'])): ?>
                                <div style="margin-top: 20px;">
                                    <h3 style="color: #333; margin-bottom: 10px;">Existing Admin Accounts:</h3>
                                    <table>
                                        <thead>
                                            <tr>
                                                <th>Username</th>
                                                <th>Email</th>
                                                <th>Full Name</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($status['existing_admins'] as $admin): ?>
                                                <tr>
                                                    <td><strong><?php echo $admin['username']; ?></strong></td>
                                                    <td><?php echo $admin['email']; ?></td>
                                                    <td><?php echo $admin['full_name']; ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>

                            <!-- Create Admin Accounts -->
                            <div style="margin-top: 30px;">
                                <div class="alert info">
                                    <div class="alert-icon">ℹ️</div>
                                    <div class="alert-content">
                                        <h3>Create Default Admin Accounts</h3>
                                        <p>Click the button below to create two test admin accounts with default credentials.</p>
                                    </div>
                                </div>

                                <form method="POST">
                                    <button type="submit" name="create_admins" value="1" class="btn btn-primary" onclick="return confirm('Create default admin accounts?')">
                                        👤 Create Admin Accounts
                                    </button>
                                </form>

                                <div class="step-box" style="margin-top: 20px;">
                                    <h4>Default Credentials to be Created:</h4>
                                    <div style="margin-top: 10px;">
                                        <div class="credentials-table">
                                            <h4>Admin #1</h4>
                                            <div class="credentials-row">
                                                <div class="credentials-label">Username:</div>
                                                <div class="credentials-value">admin</div>
                                            </div>
                                            <div class="credentials-row">
                                                <div class="credentials-label">Email:</div>
                                                <div class="credentials-value">admin@llcc.edu.ph</div>
                                            </div>
                                            <div class="credentials-row">
                                                <div class="credentials-label">Password:</div>
                                                <div class="credentials-value">Admin@123</div>
                                            </div>
                                        </div>

                                        <div class="credentials-table" style="margin-top: 10px;">
                                            <h4>Admin #2</h4>
                                            <div class="credentials-row">
                                                <div class="credentials-label">Username:</div>
                                                <div class="credentials-value">admin_staff</div>
                                            </div>
                                            <div class="credentials-row">
                                                <div class="credentials-label">Email:</div>
                                                <div class="credentials-value">staff@llcc.edu.ph</div>
                                            </div>
                                            <div class="credentials-row">
                                                <div class="credentials-label">Password:</div>
                                                <div class="credentials-value">Staff@123</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                <?php endif; ?>

                <!-- Troubleshooting -->
                <div class="section">
                    <h2>🔧 Troubleshooting</h2>

                    <div class="step-box">
                        <h4>If you still get "Invalid email or password":</h4>
                        <ol>
                            <li><strong>Make sure you imported SQL files:</strong><br/>
                                Go to <a href="setup_database.php" style="color: #667eea;">setup_database.php</a> and run the import if you haven't already.
                            </li>
                            <li><strong>Use correct credentials:</strong><br/>
                                Email: <code>admin@llcc.edu.ph</code><br/>
                                Password: <code>Admin@123</code>
                            </li>
                            <li><strong>Clear browser cache and cookies:</strong><br/>
                                Sometimes old session data causes login issues. Try a different browser or clear cache.
                            </li>
                            <li><strong>Check database directly:</strong><br/>
                                Go to <a href="database_diagnostic.php" style="color: #667eea;">database_diagnostic.php</a> to verify tables and data.
                            </li>
                        </ol>
                    </div>
                </div>

            <?php endif; ?>
        </div>
    </div>
</body>
</html>
