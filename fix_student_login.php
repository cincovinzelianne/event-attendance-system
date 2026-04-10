<?php
/**
 * Fix Student Login - Create Test Student Accounts
 */

require_once 'config/database.php';
require_once 'includes/qr_generator.php';

$status = [
    'db_connected' => false,
    'students_table_exists' => false,
    'existing_students' => [],
    'student_count' => 0
];

try {
    $database = new Database();
    $db = $database->getConnection();
    $status['db_connected'] = true;
    
    // Check if students table exists
    $stmt = $db->query("SHOW TABLES LIKE 'students'");
    $status['students_table_exists'] = $stmt->rowCount() > 0;
    
    // Get existing students
    if ($status['students_table_exists']) {
        $stmt = $db->query("SELECT id, student_id, first_name, last_name, email FROM students");
        $status['existing_students'] = $stmt->fetchAll();
        $status['student_count'] = count($status['existing_students']);
    }
    
} catch (Exception $e) {
    $status['db_connected'] = false;
}

// Handle student creation
$creationResult = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_students'])) {
    $creationResult = [
        'success' => false,
        'created' => [],
        'errors' => []
    ];
    
    try {
        $database = new Database();
        $db = $database->getConnection();
        $qrGenerator = new QRGenerator();
        
        if (!$status['students_table_exists']) {
            $creationResult['errors'][] = "Students table does not exist. Import database.sql first.";
        } else {
            $studentAccounts = [
                [
                    'student_id' => '2021-001',
                    'first_name' => 'Juan',
                    'last_name' => 'Dela Cruz',
                    'email' => 'juan.delacruz@llcc.edu.ph',
                    'password' => 'Student@123',
                    'phone' => '09123456789',
                    'course' => 'BS Information Technology',
                    'year_level' => '2nd Year'
                ],
                [
                    'student_id' => '2021-002',
                    'first_name' => 'Maria',
                    'last_name' => 'Santos',
                    'email' => 'maria.santos@llcc.edu.ph',
                    'password' => 'Student@123',
                    'phone' => '09987654321',
                    'course' => 'BS Business Administration',
                    'year_level' => '3rd Year'
                ],
                [
                    'student_id' => '2021-003',
                    'first_name' => 'Pedro',
                    'last_name' => 'Reyes',
                    'email' => 'pedro.reyes@llcc.edu.ph',
                    'password' => 'Student@123',
                    'phone' => '09111222333',
                    'course' => 'BS Accountancy',
                    'year_level' => '1st Year'
                ],
                [
                    'student_id' => '2021-004',
                    'first_name' => 'Ana',
                    'last_name' => 'Gonzales',
                    'email' => 'ana.gonzales@llcc.edu.ph',
                    'password' => 'Student@123',
                    'phone' => '09444555666',
                    'course' => 'BS Hospitality Management',
                    'year_level' => '2nd Year'
                ]
            ];
            
            foreach ($studentAccounts as $student) {
                try {
                    // Check if student already exists
                    $checkStmt = $db->prepare("SELECT id FROM students WHERE email = ?");
                    $checkStmt->execute([$student['email']]);
                    
                    if ($checkStmt->fetch()) {
                        $creationResult['errors'][] = "Student already exists: " . $student['email'];
                        continue;
                    }
                    
                    // Generate QR code
                    $qrResult = $qrGenerator->generateQRCode(
                        $student['student_id'],
                        $student['first_name'],
                        $student['last_name'],
                        $student['email']
                    );
                    
                    if (isset($qrResult['error'])) {
                        $creationResult['errors'][] = "QR generation error: " . $qrResult['error'];
                        continue;
                    }
                    
                    // Hash password
                    $passwordHash = password_hash($student['password'], PASSWORD_DEFAULT);
                    
                    // Insert student
                    $insertStmt = $db->prepare("
                        INSERT INTO students 
                        (student_id, first_name, last_name, email, password, phone, course, year_level, qr_code, qr_code_path, is_active) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)
                    ");
                    
                    $insertStmt->execute([
                        $student['student_id'],
                        $student['first_name'],
                        $student['last_name'],
                        $student['email'],
                        $passwordHash,
                        $student['phone'],
                        $student['course'],
                        $student['year_level'],
                        $qrResult['qr_code'],
                        $qrResult['qr_url']
                    ]);
                    
                    $creationResult['created'][] = [
                        'student_id' => $student['student_id'],
                        'name' => $student['first_name'] . ' ' . $student['last_name'],
                        'email' => $student['email'],
                        'password' => $student['password'],
                        'course' => $student['course']
                    ];
                    
                } catch (Exception $e) {
                    $creationResult['errors'][] = "Error creating " . $student['email'] . ": " . $e->getMessage();
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
    <title>Fix Student Login - Event Attendance</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container {
            max-width: 1000px;
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
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
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
            font-size: 13px;
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
        .credentials-table {
            background: #f8f9ff;
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid #667eea;
            margin-bottom: 15px;
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
            font-size: 13px;
        }
        .credentials-row:last-child {
            border-bottom: none;
        }
        .credentials-label {
            font-weight: bold;
            color: #667eea;
            min-width: 80px;
        }
        .credentials-value {
            color: #333;
            font-family: 'Courier New', monospace;
        }
        form {
            display: contents;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>👨‍🎓 Fix Student Login</h1>
            <p>Create test student accounts</p>
        </div>

        <div class="content">
            <!-- CREATION RESULT -->
            <?php if ($creationResult): ?>
                <?php if ($creationResult['success']): ?>
                    <div class="alert success">
                        <div class="alert-icon">✓</div>
                        <div class="alert-content">
                            <h3>Student Accounts Created Successfully!</h3>
                            <p><?php echo count($creationResult['created']); ?> student account(s) have been added to the database.</p>
                        </div>
                    </div>

                    <div class="section">
                        <h2>✓ Login Credentials</h2>
                        <?php foreach ($creationResult['created'] as $student): ?>
                            <div class="credentials-table">
                                <h4><?php echo $student['name']; ?></h4>
                                <div class="credentials-row">
                                    <div class="credentials-label">ID:</div>
                                    <div class="credentials-value"><?php echo $student['student_id']; ?></div>
                                </div>
                                <div class="credentials-row">
                                    <div class="credentials-label">Email:</div>
                                    <div class="credentials-value"><?php echo $student['email']; ?></div>
                                </div>
                                <div class="credentials-row">
                                    <div class="credentials-label">Password:</div>
                                    <div class="credentials-value"><?php echo $student['password']; ?></div>
                                </div>
                                <div class="credentials-row">
                                    <div class="credentials-label">Course:</div>
                                    <div class="credentials-value"><?php echo $student['course']; ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="button-group">
                        <a href="signin.php" class="btn btn-primary">🔗 Go to Student Login</a>
                    </div>

                    <div class="alert info" style="margin-top: 20px;">
                        <div class="alert-icon">ℹ️</div>
                        <div class="alert-content">
                            <h3>Next Step</h3>
                            <p>Click the link above to login with any of the student credentials shown above.</p>
                        </div>
                    </div>

                <?php else: ?>
                    <div class="alert error">
                        <div class="alert-icon">✗</div>
                        <div class="alert-content">
                            <h3>Creation Failed</h3>
                            <p>Could not create student accounts. See errors below.</p>
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
                                <h4><span class="status-indicator <?php echo $status['students_table_exists'] ? 'ok' : 'error'; ?>"></span>Students Table</h4>
                                <div class="value"><?php echo $status['students_table_exists'] ? '✓ Exists' : '✗ Missing'; ?></div>
                            </div>
                            <div class="info-box">
                                <h4>Student Count</h4>
                                <div class="value"><?php echo $status['student_count']; ?></div>
                            </div>
                        </div>

                        <?php if (!$status['students_table_exists']): ?>
                            <div class="alert warning">
                                <div class="alert-icon">⚠️</div>
                                <div class="alert-content">
                                    <h3>Students Table Missing</h3>
                                    <p>The students table doesn't exist. You need to import <code>database.sql</code> first.</p>
                                    <div class="button-group" style="margin-top: 10px;">
                                        <a href="setup_database.php" class="btn btn-secondary">Import SQL Files</a>
                                    </div>
                                </div>
                            </div>
                        <?php else: ?>
                            <!-- Show existing students if any -->
                            <?php if (!empty($status['existing_students'])): ?>
                                <div style="margin-top: 20px;">
                                    <h3 style="color: #333; margin-bottom: 10px;">Existing Student Accounts:</h3>
                                    <table>
                                        <thead>
                                            <tr>
                                                <th>Student ID</th>
                                                <th>Name</th>
                                                <th>Email</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($status['existing_students'] as $student): ?>
                                                <tr>
                                                    <td><strong><?php echo $student['student_id']; ?></strong></td>
                                                    <td><?php echo $student['first_name'] . ' ' . $student['last_name']; ?></td>
                                                    <td><?php echo $student['email']; ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="alert warning" style="margin-top: 20px;">
                                    <div class="alert-icon">⚠️</div>
                                    <div class="alert-content">
                                        <h3>No Student Accounts Found</h3>
                                        <p>The students table exists but is empty. Click below to create test student accounts.</p>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <!-- Create Student Accounts -->
                            <div style="margin-top: 30px;">
                                <div class="alert info">
                                    <div class="alert-icon">ℹ️</div>
                                    <div class="alert-content">
                                        <h3>Create Test Student Accounts</h3>
                                        <p>Click the button below to create four test student accounts with QR codes.</p>
                                    </div>
                                </div>

                                <form method="POST">
                                    <button type="submit" name="create_students" value="1" class="btn btn-primary" onclick="return confirm('Create 4 test student accounts? This may take a moment...')">
                                        👥 Create Student Accounts
                                    </button>
                                </form>

                                <div style="margin-top: 20px;">
                                    <h3 style="color: #333; margin-bottom: 15px;">Default Credentials to be Created:</h3>
                                    
                                    <div class="credentials-table">
                                        <h4>Student #1 - Juan Dela Cruz</h4>
                                        <div class="credentials-row">
                                            <div class="credentials-label">Email:</div>
                                            <div class="credentials-value">juan.delacruz@llcc.edu.ph</div>
                                        </div>
                                        <div class="credentials-row">
                                            <div class="credentials-label">Password:</div>
                                            <div class="credentials-value">Student@123</div>
                                        </div>
                                        <div class="credentials-row">
                                            <div class="credentials-label">Course:</div>
                                            <div class="credentials-value">BS Information Technology</div>
                                        </div>
                                    </div>

                                    <div class="credentials-table">
                                        <h4>Student #2 - Maria Santos</h4>
                                        <div class="credentials-row">
                                            <div class="credentials-label">Email:</div>
                                            <div class="credentials-value">maria.santos@llcc.edu.ph</div>
                                        </div>
                                        <div class="credentials-row">
                                            <div class="credentials-label">Password:</div>
                                            <div class="credentials-value">Student@123</div>
                                        </div>
                                        <div class="credentials-row">
                                            <div class="credentials-label">Course:</div>
                                            <div class="credentials-value">BS Business Administration</div>
                                        </div>
                                    </div>

                                    <div class="credentials-table">
                                        <h4>Student #3 - Pedro Reyes</h4>
                                        <div class="credentials-row">
                                            <div class="credentials-label">Email:</div>
                                            <div class="credentials-value">pedro.reyes@llcc.edu.ph</div>
                                        </div>
                                        <div class="credentials-row">
                                            <div class="credentials-label">Password:</div>
                                            <div class="credentials-value">Student@123</div>
                                        </div>
                                        <div class="credentials-row">
                                            <div class="credentials-label">Course:</div>
                                            <div class="credentials-value">BS Accountancy</div>
                                        </div>
                                    </div>

                                    <div class="credentials-table">
                                        <h4>Student #4 - Ana Gonzales</h4>
                                        <div class="credentials-row">
                                            <div class="credentials-label">Email:</div>
                                            <div class="credentials-value">ana.gonzales@llcc.edu.ph</div>
                                        </div>
                                        <div class="credentials-row">
                                            <div class="credentials-label">Password:</div>
                                            <div class="credentials-value">Student@123</div>
                                        </div>
                                        <div class="credentials-row">
                                            <div class="credentials-label">Course:</div>
                                            <div class="credentials-value">BS Hospitality Management</div>
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

                    <div style="background: #f8f9ff; padding: 15px; border-radius: 8px; border-left: 4px solid #667eea;">
                        <h4 style="color: #333; margin-bottom: 10px;">If you still get "Invalid email or password":</h4>
                        <ol style="margin-left: 20px; color: #666; font-size: 13px; line-height: 1.8;">
                            <li><strong>Make sure you imported SQL files:</strong><br/>
                                Go to <a href="setup_database.php" style="color: #667eea;">setup_database.php</a> first if you haven't.
                            </li>
                            <li><strong>Create student accounts:</strong><br/>
                                Use the button above to create test student accounts.
                            </li>
                            <li><strong>Use correct credentials:</strong><br/>
                                Email should be one of the test accounts shown above<br/>
                                Password: <code>Student@123</code>
                            </li>
                            <li><strong>Clear browser cache:</strong><br/>
                                Try a different browser or clear your browser cache.
                            </li>
                        </ol>
                    </div>
                </div>

            <?php endif; ?>
        </div>
    </div>
</body>
</html>
