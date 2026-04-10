<?php
/**
 * QUICK START: Event Attendance System Setup Guide
 * File: setup_guide.php
 * 
 * This file provides step-by-step instructions to get the system running
 */
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EventAttendance - Setup Guide</title>
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
        .header h1 { font-size: 28px; margin-bottom: 5px; }
        .header p { opacity: 0.9; }
        .content {
            padding: 30px;
        }
        .section {
            margin: 30px 0;
            padding: 20px;
            border-left: 4px solid #667eea;
            background: #f8f9ff;
            border-radius: 5px;
        }
        .section h2 {
            color: #667eea;
            margin-bottom: 15px;
            font-size: 20px;
        }
        .step {
            margin: 15px 0;
            padding: 12px;
            background: white;
            border-radius: 5px;
            border-left: 3px solid #764ba2;
        }
        .step-number {
            display: inline-block;
            background: #667eea;
            color: white;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            text-align: center;
            line-height: 30px;
            margin-right: 10px;
            font-weight: bold;
        }
        code {
            background: #f0f0f0;
            padding: 3px 8px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
            color: #d63384;
        }
        .check { color: #28a745; font-weight: bold; }
        .warning { color: #ffc107; font-weight: bold; }
        .error { color: #dc3545; font-weight: bold; }
        .link { color: #667eea; text-decoration: none; }
        .link:hover { text-decoration: underline; }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        th, td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background: #f0f0f0;
            font-weight: bold;
        }
        .status-pass { color: #28a745; }
        .status-fail { color: #dc3545; }
        .status-warn { color: #ffc107; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎓 Event Attendance System</h1>
            <p>LLCC - Lapu-Lapu City College</p>
            <p style="margin-top: 10px; font-size: 14px;">Setup & Troubleshooting Guide</p>
        </div>

        <div class="content">
            <!-- SECTION 1: Prerequisites -->
            <div class="section">
                <h2>📋 Step 1: Prerequisites Check</h2>
                <div class="step">
                    <span class="step-number">1</span>
                    <strong>XAMPP Installation</strong><br>
                    Ensure you have XAMPP installed with Apache and MySQL services.
                </div>
                <div class="step">
                    <span class="step-number">2</span>
                    <strong>Required Services</strong><br>
                    ✓ Apache 2.4+ (Web Server)<br>
                    ✓ MySQL 5.7+ (Database)<br>
                    ✓ PHP 7.4+
                </div>
            </div>

            <!-- SECTION 2: Start Services -->
            <div class="section">
                <h2>🚀 Step 2: Start XAMPP Services</h2>
                <div class="step">
                    <span class="step-number">1</span>
                    <strong>Start XAMPP Control Panel</strong><br>
                    Open XAMPP Control Panel and click "Start" for:
                    <ul style="margin-left: 40px; margin-top: 10px;">
                        <li>Apache</li>
                        <li>MySQL</li>
                    </ul>
                </div>
                <div class="step">
                    <span class="step-number">2</span>
                    <strong>Verify Services Running</strong><br>
                    <code>http://localhost</code> should show XAMPP dashboard<br>
                    <code>http://localhost/phpmyadmin</code> should open phpMyAdmin
                </div>
            </div>

            <!-- SECTION 3: Database Setup -->
            <div class="section">
                <h2>🗄️ Step 3: Database Setup</h2>
                <div class="step">
                    <span class="step-number">1</span>
                    <strong>Access phpMyAdmin</strong><br>
                    Go to: <code>http://localhost/phpmyadmin</code>
                </div>
                <div class="step">
                    <span class="step-number">2</span>
                    <strong>Create Database</strong><br>
                    Create a new database named: <code>event_attendance</code>
                </div>
                <div class="step">
                    <span class="step-number">3</span>
                    <strong>Import Schema</strong><br>
                    Import the SQL files in this order:
                    <ul style="margin-left: 40px; margin-top: 10px;">
                        <li><code>database.sql</code> - Core tables</li>
                        <li><code>create_admin_tables.sql</code> - Admin infrastructure</li>
                        <li><code>create_courses_table.sql</code> - Courses/Departments</li>
                        <li><code>student_notifications.sql</code> - Notifications</li>
                    </ul>
                </div>
            </div>

            <!-- SECTION 4: System Check -->
            <div class="section">
                <h2>✅ Step 4: System Health Check</h2>
                <div class="step">
                    <span class="step-number">1</span>
                    <strong>Run System Check</strong><br>
                    Visit: <code><a href="system_check.php" class="link">http://localhost/EventAttendance/system_check.php</a></code><br>
                    This will verify all PHP extensions and configuration
                </div>
                <div class="step">
                    <span class="step-number">2</span>
                    <strong>Verify All Checks Pass</strong><br>
                    All checks should show ✓ (green)
                </div>
            </div>

            <!-- SECTION 5: Access Application -->
            <div class="section">
                <h2>🌐 Step 5: Access Application</h2>
                <div class="step">
                    <span class="step-number">1</span>
                    <strong>Student Portal</strong><br>
                    <a href="signin.php" class="link">http://localhost/EventAttendance/signin.php</a><br>
                    Register or Sign In with student account
                </div>
                <div class="step">
                    <span class="step-number">2</span>
                    <strong>Admin Portal</strong><br>
                    <a href="admin_signin.php" class="link">http://localhost/EventAttendance/admin_signin.php</a><br>
                    Register admin account, then sign in
                </div>
                <div class="step">
                    <span class="step-number">3</span>
                    <strong>Student Dashboard</strong><br>
                    After login: <a href="dashboard.php" class="link">http://localhost/EventAttendance/dashboard.php</a>
                </div>
            </div>

            <!-- SECTION 6: Troubleshooting -->
            <div class="section">
                <h2>🔧 Step 6: Troubleshooting Common Issues</h2>
                
                <div class="step">
                    <strong class="error">❌ "Database connection failed"</strong><br>
                    <ul style="margin-left: 40px; margin-top: 10px;">
                        <li>✓ Verify MySQL is running in XAMPP</li>
                        <li>✓ Check database name: <code>event_attendance</code></li>
                        <li>✓ Verify <code>config/database.php</code> settings</li>
                    </ul>
                </div>

                <div class="step">
                    <strong class="error">❌ "Table doesn't exist"</strong><br>
                    <ul style="margin-left: 40px; margin-top: 10px;">
                        <li>✓ Import SQL files (database.sql, create_admin_tables.sql)</li>
                        <li>✓ Check phpMyAdmin for table creation</li>
                    </ul>
                </div>

                <div class="step">
                    <strong class="error">❌ "GD extension not loaded"</strong><br>
                    <ul style="margin-left: 40px; margin-top: 10px;">
                        <li>✓ Edit PHP config to enable GD</li>
                        <li>✓ In XAMPP folder: php/php.ini</li>
                        <li>✓ Find and uncomment: <code>;extension=gd</code> → <code>extension=gd</code></li>
                        <li>✓ Restart Apache</li>
                    </ul>
                </div>

                <div class="step">
                    <strong class="error">❌ "QR codes not generating"</strong><br>
                    <ul style="margin-left: 40px; margin-top: 10px;">
                        <li>✓ Check <code>assets/qr_codes/</code> is writable</li>
                        <li>✓ Verify GD extension is loaded (see above)</li>
                        <li>✓ Check internet connection (QR APIs may need it)</li>
                    </ul>
                </div>

                <div class="step">
                    <strong class="error">❌ Admin registration/login not working</strong><br>
                    <ul style="margin-left: 40px; margin-top: 10px;">
                        <li>✓ Verify <code>create_admin_tables.sql</code> was imported</li>
                        <li>✓ Check phpMyAdmin for <code>admins</code> and <code>admin_sessions</code> tables</li>
                    </ul>
                </div>
            </div>

            <!-- SECTION 7: System Requirements -->
            <div class="section">
                <h2>💻 System Requirements Summary</h2>
                <table>
                    <tr>
                        <th>Component</th>
                        <th>Minimum Version</th>
                        <th>Status</th>
                    </tr>
                    <tr>
                        <td>PHP</td>
                        <td>7.4.0</td>
                        <td><span class="status-pass">✓ Check via system_check.php</span></td>
                    </tr>
                    <tr>
                        <td>MySQL</td>
                        <td>5.7</td>
                        <td><span class="status-pass">✓ Running in XAMPP</span></td>
                    </tr>
                    <tr>
                        <td>Apache</td>
                        <td>2.4</td>
                        <td><span class="status-pass">✓ Running in XAMPP</span></td>
                    </tr>
                    <tr>
                        <td>PDO Extension</td>
                        <td>Any</td>
                        <td><span class="status-pass">✓ Check via system_check.php</span></td>
                    </tr>
                    <tr>
                        <td>GD Extension</td>
                        <td>Any</td>
                        <td><span class="status-pass">✓ Required for QR</span></td>
                    </tr>
                    <tr>
                        <td>cURL Extension</td>
                        <td>Any</td>
                        <td><span class="status-pass">✓ Optional but recommended</span></td>
                    </tr>
                </table>
            </div>

            <!-- SECTION 8: Feature Overview -->
            <div class="section">
                <h2>🎯 Key Features</h2>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 15px;">
                    <div>
                        <h3 style="color: #667eea; margin-bottom: 10px;">👨‍🎓 Student Features</h3>
                        <ul>
                            <li>Registration & Login</li>
                            <li>Profile Management</li>
                            <li>QR Code Display</li>
                            <li>Attendance Tracking</li>
                            <li>Event Calendar</li>
                            <li>Notifications</li>
                        </ul>
                    </div>
                    <div>
                        <h3 style="color: #667eea; margin-bottom: 10px;">👨‍💼 Admin Features</h3>
                        <ul>
                            <li>Event Management</li>
                            <li>QR Scanner</li>
                            <li>Attendance Sheet</li>
                            <li>Student Management</li>
                            <li>Analytics Dashboard</li>
                            <li>Google Calendar Integration</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- SECTION 9: Contact/Support -->
            <div class="section">
                <h2>📞 Support & Documentation</h2>
                <div class="step">
                    <strong>View Detailed Status Report</strong><br>
                    <a href="SYSTEM_STATUS_REPORT.md" class="link">SYSTEM_STATUS_REPORT.md</a><br>
                    Complete technical analysis of system state
                </div>
            </div>
        </div>
    </div>
</body>
</html>
