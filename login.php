<?php
/**
 * Unified Login System
 * Handles both Student and Admin authentication in one page
 */

require_once 'config/database.php';
require_once 'includes/auth.php';
require_once 'includes/admin_auth.php';

$message = '';
$messageType = '';
$loginType = isset($_GET['type']) ? $_GET['type'] : 'student'; // Default to student

// Check if user is already logged in
$auth = new Auth();
$adminAuth = new AdminAuth();

$currentStudent = $auth->getCurrentUser();
$currentAdmin = $adminAuth->getCurrentAdmin();

if ($currentStudent) {
    header('Location: dashboard.php');
    exit;
}

if ($currentAdmin) {
    header('Location: admin_dashboard.php');
    exit;
}

// Handle login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type = isset($_POST['login_type']) ? $_POST['login_type'] : 'student';
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    
    if (empty($email) || empty($password)) {
        $message = 'Please fill in all fields';
        $messageType = 'error';
    } else {
        if ($type === 'admin') {
            // Admin login
            $result = $adminAuth->login($email, $password);
            $message = $result['message'];
            $messageType = $result['success'] ? 'success' : 'error';
            
            if ($result['success']) {
                header('Location: admin_dashboard.php');
                exit;
            }
        } else {
            // Student login
            $result = $auth->login($email, $password);
            $message = $result['message'];
            $messageType = $result['success'] ? 'success' : 'error';
            
            if ($result['success']) {
                header('Location: dashboard.php');
                exit;
            }
        }
    }
}

// Check for registration success message
if (isset($_GET['registered']) && $_GET['registered'] == '1') {
    $message = 'Registration successful! Please sign in with your credentials.';
    $messageType = 'success';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Lapu-Lapu City College</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Roboto', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .auth-container {
            width: 100%;
            max-width: 500px;
        }
        
        .auth-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
        }
        
        .auth-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px 30px;
            text-align: center;
        }
        
        .auth-logo {
            width: 100px;
            height: 100px;
            object-fit: contain;
            margin-bottom: 15px;
        }
        
        .auth-header h1 {
            font-size: 24px;
            margin-bottom: 5px;
            font-weight: 700;
        }
        
        .auth-header p {
            opacity: 0.9;
            font-size: 14px;
        }
        
        .auth-body {
            padding: 40px 30px;
        }
        
        .login-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 30px;
            background: #f8f9ff;
            padding: 10px;
            border-radius: 10px;
        }
        
        .login-tab {
            flex: 1;
            padding: 12px;
            border: 2px solid transparent;
            background: transparent;
            cursor: pointer;
            border-radius: 8px;
            font-weight: bold;
            font-size: 14px;
            transition: all 0.3s;
            color: #666;
        }
        
        .login-tab.active {
            background: white;
            color: #667eea;
            border-color: #667eea;
            box-shadow: 0 2px 8px rgba(102, 126, 234, 0.2);
        }
        
        .login-tab:hover {
            color: #667eea;
        }
        
        .message {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .message.error {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
        
        .message.success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        
        .auth-form {
            display: none;
        }
        
        .auth-form.active {
            display: block;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
            font-size: 14px;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.3s;
            font-family: 'Roboto', sans-serif;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .form-group input::placeholder {
            color: #999;
        }
        
        .form-options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            font-size: 13px;
        }
        
        .checkbox-container {
            display: flex;
            align-items: center;
            cursor: pointer;
            color: #666;
            user-select: none;
        }
        
        .checkbox-container input[type="checkbox"] {
            width: auto;
            margin-right: 6px;
            cursor: pointer;
        }
        
        .forgot-password {
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
        }
        
        .forgot-password:hover {
            text-decoration: underline;
        }
        
        .btn {
            width: 100%;
            padding: 14px;
            border: none;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            font-family: 'Roboto', sans-serif;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);
        }
        
        .btn-primary:active {
            transform: translateY(0);
        }
        
        .auth-footer {
            text-align: center;
            margin-top: 20px;
            color: #666;
            font-size: 13px;
        }
        
        .auth-footer a {
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
        }
        
        .auth-footer a:hover {
            text-decoration: underline;
        }
        
        .divider {
            text-align: center;
            margin: 20px 0;
            color: #999;
            font-size: 12px;
        }
        
        @media (max-width: 600px) {
            .auth-header {
                padding: 30px 20px;
            }
            
            .auth-body {
                padding: 20px;
            }
            
            .auth-header h1 {
                font-size: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <img src="assets/images/llcc-logo.png" alt="Lapu-Lapu City College" class="auth-logo">
                <h1>Lapu-Lapu City College</h1>
                <p>Event Attendance System</p>
            </div>
            
            <div class="auth-body">
                <!-- Login Type Tabs -->
                <div class="login-tabs">
                    <button type="button" class="login-tab active" onclick="switchLoginType('student')">
                        👨‍🎓 Student
                    </button>
                    <button type="button" class="login-tab" onclick="switchLoginType('admin')">
                        👨‍💼 Admin
                    </button>
                </div>
                
                <!-- Message Display -->
                <?php if ($message): ?>
                    <div class="message <?php echo $messageType; ?>">
                        <span><?php echo htmlspecialchars($message); ?></span>
                    </div>
                <?php endif; ?>
                
                <!-- Student Login Form -->
                <form id="student-form" class="auth-form active" method="POST" action="">
                    <input type="hidden" name="login_type" value="student">
                    
                    <div class="form-group">
                        <label for="student-email">Email Address</label>
                        <input type="email" id="student-email" name="email" required 
                               placeholder="example@llcc.edu.ph"
                               value="<?php echo isset($_POST['email']) && $_POST['login_type'] === 'student' ? htmlspecialchars($_POST['email']) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="student-password">Password</label>
                        <input type="password" id="student-password" name="password" required 
                               placeholder="Enter your password">
                    </div>
                    
                    <div class="form-options">
                        <label class="checkbox-container">
                            <input type="checkbox" name="remember_me">
                            <span>Remember me</span>
                        </label>
                        <a href="#" class="forgot-password">Forgot password?</a>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Sign In</button>
                    
                    <div class="auth-footer">
                        <p>Don't have an account? <a href="signup.php">Create one here</a></p>
                    </div>
                </form>
                
                <!-- Admin Login Form -->
                <form id="admin-form" class="auth-form" method="POST" action="">
                    <input type="hidden" name="login_type" value="admin">
                    
                    <div class="form-group">
                        <label for="admin-email">Username or Email</label>
                        <input type="text" id="admin-email" name="email" required 
                               placeholder="admin or admin@llcc.edu.ph"
                               value="<?php echo isset($_POST['email']) && $_POST['login_type'] === 'admin' ? htmlspecialchars($_POST['email']) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="admin-password">Password</label>
                        <input type="password" id="admin-password" name="password" required 
                               placeholder="Enter your password">
                    </div>
                    
                    <div class="form-options">
                        <label class="checkbox-container">
                            <input type="checkbox" name="remember_me">
                            <span>Remember me</span>
                        </label>
                        <a href="#" class="forgot-password">Forgot password?</a>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Sign In</button>
                    
                    <div class="auth-footer">
                        <p><a href="fix_admin_login.php">Need admin account? Create one here</a></p>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script>
        function switchLoginType(type) {
            // Hide all forms
            document.getElementById('student-form').classList.remove('active');
            document.getElementById('admin-form').classList.remove('active');
            
            // Remove active class from all tabs
            document.querySelectorAll('.login-tab').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Show selected form
            if (type === 'admin') {
                document.getElementById('admin-form').classList.add('active');
                document.querySelectorAll('.login-tab')[1].classList.add('active');
            } else {
                document.getElementById('student-form').classList.add('active');
                document.querySelectorAll('.login-tab')[0].classList.add('active');
            }
        }
    </script>
</body>
</html>
