<?php
/**
 * Account Creation Tool - Event Attendance System
 * Creates sample admin and student accounts with hashed passwords
 */

require_once 'config/database.php';
require_once 'includes/qr_generator.php';

$message = '';
$messageType = '';
$createdAccounts = [];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $database = new Database();
        $db = $database->getConnection();
        $qrGenerator = new QRGenerator();
        
        // Create Admin Accounts
        if (isset($_POST['create_admins'])) {
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
                $passwordHash = password_hash($admin['password'], PASSWORD_DEFAULT);
                
                $stmt = $db->prepare("
                    INSERT INTO admins (username, email, password_hash, full_name, role) 
                    VALUES (?, ?, ?, ?, 'admin')
                ");
                
                $result = $stmt->execute([
                    $admin['username'],
                    $admin['email'],
                    $passwordHash,
                    $admin['full_name']
                ]);
                
                if ($result) {
                    $createdAccounts[] = [
                        'type' => 'Admin',
                        'username' => $admin['username'],
                        'email' => $admin['email'],
                        'password' => $admin['password'],
                        'note' => 'Use username or email to login'
                    ];
                }
            }
            
            $message = count($createdAccounts) . ' admin account(s) created successfully!';
            $messageType = 'success';
        }
        
        // Create Student Accounts
        if (isset($_POST['create_students'])) {
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
                // Check if student already exists
                $checkStmt = $db->prepare("SELECT id FROM students WHERE email = ?");
                $checkStmt->execute([$student['email']]);
                
                if ($checkStmt->fetch()) {
                    continue; // Skip if already exists
                }
                
                // Generate QR code
                $qrResult = $qrGenerator->generateQRCode(
                    $student['student_id'],
                    $student['first_name'],
                    $student['last_name'],
                    $student['email']
                );
                
                if (isset($qrResult['error'])) {
                    continue;
                }
                
                // Hash password
                $hashedPassword = password_hash($student['password'], PASSWORD_DEFAULT);
                
                // Insert student
                $stmt = $db->prepare("
                    INSERT INTO students 
                    (student_id, first_name, last_name, email, password, phone, course, year_level, qr_code, qr_code_path) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                
                $result = $stmt->execute([
                    $student['student_id'],
                    $student['first_name'],
                    $student['last_name'],
                    $student['email'],
                    $hashedPassword,
                    $student['phone'],
                    $student['course'],
                    $student['year_level'],
                    $qrResult['qr_code'],
                    $qrResult['qr_url']
                ]);
                
                if ($result) {
                    $createdAccounts[] = [
                        'type' => 'Student',
                        'id' => $student['student_id'],
                        'name' => $student['first_name'] . ' ' . $student['last_name'],
                        'email' => $student['email'],
                        'password' => $student['password'],
                        'course' => $student['course']
                    ];
                }
            }
            
            if (!empty($createdAccounts)) {
                $message = count($createdAccounts) . ' student account(s) created successfully!';
                $messageType = 'success';
            }
        }
        
    } catch (Exception $e) {
        $message = 'Error: ' . $e->getMessage();
        $messageType = 'error';
    }
}

// Get current account counts
try {
    $database = new Database();
    $db = $database->getConnection();
    
    $adminStmt = $db->query("SELECT COUNT(*) as count FROM admins");
    $adminCount = $adminStmt->fetch()['count'];
    
    $studentStmt = $db->query("SELECT COUNT(*) as count FROM students");
    $studentCount = $studentStmt->fetch()['count'];
} catch (Exception $e) {
    $adminCount = 0;
    $studentCount = 0;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Test Accounts - Event Attendance</title>
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
            border-radius: 10px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .header h1 { font-size: 24px; margin-bottom: 10px; }
        .header p { opacity: 0.9; font-size: 14px; }
        .content {
            padding: 30px;
        }
        .stats {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            padding: 20px;
            background: #f8f9ff;
            border-left: 4px solid #667eea;
            border-radius: 5px;
        }
        .stat-card h3 {
            color: #667eea;
            font-size: 12px;
            text-transform: uppercase;
            margin-bottom: 10px;
        }
        .stat-card .number {
            font-size: 32px;
            font-weight: bold;
            color: #333;
        }
        .action-section {
            margin: 30px 0;
            padding: 20px;
            border: 2px dashed #ddd;
            border-radius: 5px;
            background: #fafafa;
        }
        .action-section h2 {
            color: #667eea;
            margin-bottom: 15px;
            font-size: 18px;
        }
        .action-section p {
            color: #666;
            margin-bottom: 15px;
            font-size: 14px;
        }
        button {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            font-weight: bold;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        button:active {
            transform: translateY(0);
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
        .accounts-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        .accounts-table th {
            background: #667eea;
            color: white;
            padding: 12px;
            text-align: left;
            font-size: 12px;
            text-transform: uppercase;
        }
        .accounts-table td {
            padding: 12px;
            border-bottom: 1px solid #ddd;
            font-size: 13px;
        }
        .accounts-table tr:hover {
            background: #f8f9ff;
        }
        .credential-box {
            background: #f0f0f0;
            padding: 8px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
            color: #d63384;
            font-size: 12px;
            word-break: break-all;
        }
        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 3px;
            font-size: 11px;
            font-weight: bold;
        }
        .badge.admin {
            background: #667eea;
            color: white;
        }
        .badge.student {
            background: #764ba2;
            color: white;
        }
        .divider {
            height: 1px;
            background: #ddd;
            margin: 30px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎓 Create Test Accounts</h1>
            <p>Event Attendance System - LLCC</p>
        </div>

        <div class="content">
            <!-- Alert Message -->
            <?php if ($message): ?>
                <div class="alert show <?php echo $messageType; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <!-- Current Stats -->
            <div class="stats">
                <div class="stat-card">
                    <h3>👨‍💼 Admin Accounts</h3>
                    <div class="number"><?php echo $adminCount; ?></div>
                </div>
                <div class="stat-card">
                    <h3>👨‍🎓 Student Accounts</h3>
                    <div class="number"><?php echo $studentCount; ?></div>
                </div>
            </div>

            <!-- Create Admin Accounts -->
            <form method="POST" onsubmit="return confirm('Create 2 admin accounts?')">
                <div class="action-section">
                    <h2>👨‍💼 Create Admin Accounts</h2>
                    <p>This will create 2 sample admin accounts for testing administrative features.</p>
                    <button type="submit" name="create_admins">Create Admin Accounts</button>
                </div>
            </form>

            <div class="divider"></div>

            <!-- Create Student Accounts -->
            <form method="POST" onsubmit="return confirm('Create 4 student accounts with QR codes?')">
                <div class="action-section">
                    <h2>👨‍🎓 Create Student Accounts</h2>
                    <p>This will create 4 sample student accounts with unique QR codes for attendance tracking.</p>
                    <button type="submit" name="create_students">Create Student Accounts</button>
                </div>
            </form>

            <!-- Display Created Accounts -->
            <?php if (!empty($createdAccounts)): ?>
                <div class="divider"></div>
                <h2 style="color: #667eea; margin-bottom: 20px;">✅ Created Accounts</h2>
                <table class="accounts-table">
                    <thead>
                        <tr>
                            <th>Type</th>
                            <th>Username / ID</th>
                            <th>Name / Email</th>
                            <th>Credentials</th>
                            <th>Additional Info</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($createdAccounts as $account): ?>
                            <tr>
                                <td>
                                    <span class="badge <?php echo strtolower($account['type']); ?>">
                                        <?php echo $account['type']; ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="credential-box">
                                        <?php echo htmlspecialchars($account['username'] ?? $account['id'] ?? ''); ?>
                                    </span>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($account['name'] ?? $account['email']); ?></strong><br>
                                    <span style="color: #666; font-size: 12px;">
                                        <?php echo htmlspecialchars($account['email'] ?? ''); ?>
                                    </span>
                                </td>
                                <td>
                                    <strong>Email:</strong><br>
                                    <span class="credential-box"><?php echo htmlspecialchars($account['email']); ?></span><br><br>
                                    <strong>Password:</strong><br>
                                    <span class="credential-box"><?php echo htmlspecialchars($account['password']); ?></span>
                                </td>
                                <td>
                                    <?php if ($account['type'] === 'Admin'): ?>
                                        Login at: <a href="admin_signin.php" style="color: #667eea;">admin_signin.php</a>
                                    <?php else: ?>
                                        <strong>Course:</strong> <?php echo htmlspecialchars($account['course']); ?><br>
                                        Login at: <a href="signin.php" style="color: #667eea;">signin.php</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <!-- Login Instructions -->
                <div style="margin-top: 30px; padding: 20px; background: #f0f7ff; border-left: 4px solid #667eea; border-radius: 5px;">
                    <h3 style="color: #667eea; margin-bottom: 10px;">🔐 Login Instructions</h3>
                    <div style="color: #333; line-height: 1.8;">
                        <p><strong>For Admin Accounts:</strong></p>
                        <ul style="margin-left: 20px; margin-bottom: 15px;">
                            <li>Go to <a href="admin_signin.php" style="color: #667eea;">admin_signin.php</a></li>
                            <li>Use username or email from the table above</li>
                            <li>Use the password provided</li>
                        </ul>
                        <p><strong>For Student Accounts:</strong></p>
                        <ul style="margin-left: 20px;">
                            <li>Go to <a href="signin.php" style="color: #667eea;">signin.php</a></li>
                            <li>Use email as username</li>
                            <li>Use the password provided</li>
                        </ul>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Account Details Section -->
            <div style="margin-top: 30px; padding: 20px; background: #f9f9f9; border-radius: 5px;">
                <h3 style="color: #667eea; margin-bottom: 15px;">📋 Predefined Account Details</h3>
                
                <h4 style="margin-top: 20px; color: #333;">Admin Accounts:</h4>
                <table style="width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 13px;">
                    <tr style="background: #f0f0f0;">
                        <th style="padding: 8px; text-align: left; border: 1px solid #ddd;">Username</th>
                        <th style="padding: 8px; text-align: left; border: 1px solid #ddd;">Email</th>
                        <th style="padding: 8px; text-align: left; border: 1px solid #ddd;">Password</th>
                        <th style="padding: 8px; text-align: left; border: 1px solid #ddd;">Role</th>
                    </tr>
                    <tr>
                        <td style="padding: 8px; border: 1px solid #ddd;"><code>admin</code></td>
                        <td style="padding: 8px; border: 1px solid #ddd;"><code>admin@llcc.edu.ph</code></td>
                        <td style="padding: 8px; border: 1px solid #ddd;"><code>Admin@123</code></td>
                        <td style="padding: 8px; border: 1px solid #ddd;"><span class="badge admin">ADMIN</span></td>
                    </tr>
                    <tr>
                        <td style="padding: 8px; border: 1px solid #ddd;"><code>admin_staff</code></td>
                        <td style="padding: 8px; border: 1px solid #ddd;"><code>staff@llcc.edu.ph</code></td>
                        <td style="padding: 8px; border: 1px solid #ddd;"><code>Staff@123</code></td>
                        <td style="padding: 8px; border: 1px solid #ddd;"><span class="badge admin">ADMIN</span></td>
                    </tr>
                </table>

                <h4 style="margin-top: 20px; color: #333;">Student Accounts Preview:</h4>
                <table style="width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 13px;">
                    <tr style="background: #f0f0f0;">
                        <th style="padding: 8px; text-align: left; border: 1px solid #ddd;">Student ID</th>
                        <th style="padding: 8px; text-align: left; border: 1px solid #ddd;">Name</th>
                        <th style="padding: 8px; text-align: left; border: 1px solid #ddd;">Email</th>
                        <th style="padding: 8px; text-align: left; border: 1px solid #ddd;">Course</th>
                    </tr>
                    <tr>
                        <td style="padding: 8px; border: 1px solid #ddd;"><code>2021-001</code></td>
                        <td style="padding: 8px; border: 1px solid #ddd;">Juan Dela Cruz</td>
                        <td style="padding: 8px; border: 1px solid #ddd;"><code>juan.delacruz@llcc.edu.ph</code></td>
                        <td style="padding: 8px; border: 1px solid #ddd;">BS Information Technology</td>
                    </tr>
                    <tr>
                        <td style="padding: 8px; border: 1px solid #ddd;"><code>2021-002</code></td>
                        <td style="padding: 8px; border: 1px solid #ddd;">Maria Santos</td>
                        <td style="padding: 8px; border: 1px solid #ddd;"><code>maria.santos@llcc.edu.ph</code></td>
                        <td style="padding: 8px; border: 1px solid #ddd;">BS Business Administration</td>
                    </tr>
                    <tr>
                        <td style="padding: 8px; border: 1px solid #ddd;"><code>2021-003</code></td>
                        <td style="padding: 8px; border: 1px solid #ddd;">Pedro Reyes</td>
                        <td style="padding: 8px; border: 1px solid #ddd;"><code>pedro.reyes@llcc.edu.ph</code></td>
                        <td style="padding: 8px; border: 1px solid #ddd;">BS Accountancy</td>
                    </tr>
                    <tr>
                        <td style="padding: 8px; border: 1px solid #ddd;"><code>2021-004</code></td>
                        <td style="padding: 8px; border: 1px solid #ddd;">Ana Gonzales</td>
                        <td style="padding: 8px; border: 1px solid #ddd;"><code>ana.gonzales@llcc.edu.ph</code></td>
                        <td style="padding: 8px; border: 1px solid #ddd;">BS Hospitality Management</td>
                    </tr>
                </table>
                <p style="font-size: 13px; color: #666; margin-top: 10px;">
                    <strong>Password for all students:</strong> <code>Student@123</code>
                </p>
            </div>
        </div>
    </div>
</body>
</html>
