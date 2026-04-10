<?php
require_once 'config/database.php';

class AdminAuth {
    private $db;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }
    
    public function register($username, $email, $password, $fullName, $role = 'admin') {
        try {
            // Check if username or email already exists
            $stmt = $this->db->prepare("SELECT id FROM admins WHERE username = ? OR email = ?");
            $stmt->execute([$username, $email]);
            
            if ($stmt->rowCount() > 0) {
                return ['success' => false, 'message' => 'Username or email already exists'];
            }
            
            // Hash password
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert new admin
            $stmt = $this->db->prepare("INSERT INTO admins (username, email, password_hash, full_name, role) VALUES (?, ?, ?, ?, ?)");
            $result = $stmt->execute([$username, $email, $passwordHash, $fullName, $role]);
            
            if ($result) {
                return ['success' => true, 'message' => 'Admin registered successfully'];
            } else {
                return ['success' => false, 'message' => 'Registration failed'];
            }
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }
    
    public function login($username, $password) {
        try {
            // Find admin by username or email
            $stmt = $this->db->prepare("SELECT id, username, email, password_hash, full_name, role, is_active FROM admins WHERE (username = ? OR email = ?) AND is_active = 1");
            $stmt->execute([$username, $username]);
            $admin = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$admin) {
                return ['success' => false, 'message' => 'Invalid credentials'];
            }
            
            // Verify password
            if (!password_verify($password, $admin['password_hash'])) {
                return ['success' => false, 'message' => 'Invalid credentials'];
            }
            
            // Create session
            $sessionToken = bin2hex(random_bytes(32));
            $expiresAt = date('Y-m-d H:i:s', strtotime('+24 hours'));
            
            // Deactivate old sessions
            $stmt = $this->db->prepare("UPDATE admin_sessions SET is_active = 0 WHERE admin_id = ?");
            $stmt->execute([$admin['id']]);
            
            // Create new session
            $stmt = $this->db->prepare("INSERT INTO admin_sessions (admin_id, session_token, expires_at) VALUES (?, ?, ?)");
            $stmt->execute([$admin['id'], $sessionToken, $expiresAt]);
            
            // Set session cookie
            setcookie('admin_session', $sessionToken, time() + (24 * 60 * 60), '/', '', false, true);
            
            return [
                'success' => true, 
                'message' => 'Login successful',
                'admin' => [
                    'id' => $admin['id'],
                    'username' => $admin['username'],
                    'email' => $admin['email'],
                    'full_name' => $admin['full_name'],
                    'role' => $admin['role']
                ]
            ];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }
    
    public function getCurrentAdmin() {
        if (!isset($_COOKIE['admin_session'])) {
            return null;
        }
        
        try {
            $sessionToken = $_COOKIE['admin_session'];
            $stmt = $this->db->prepare("
                SELECT a.id, a.username, a.email, a.full_name, a.role 
                FROM admins a 
                JOIN admin_sessions s ON a.id = s.admin_id 
                WHERE s.session_token = ? AND s.is_active = 1 AND s.expires_at > NOW() AND a.is_active = 1
            ");
            $stmt->execute([$sessionToken]);
            $admin = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $admin;
        } catch (Exception $e) {
            return null;
        }
    }
    
    public function logout() {
        if (isset($_COOKIE['admin_session'])) {
            try {
                $sessionToken = $_COOKIE['admin_session'];
                $stmt = $this->db->prepare("UPDATE admin_sessions SET is_active = 0 WHERE session_token = ?");
                $stmt->execute([$sessionToken]);
            } catch (Exception $e) {
                // Log error but don't fail logout
            }
        }
        
        setcookie('admin_session', '', time() - 3600, '/');
    }
    
    public function requireAuth() {
        $admin = $this->getCurrentAdmin();
        if (!$admin) {
            header('Location: admin_signin.php');
            exit;
        }
        return $admin;
    }
}
?>
