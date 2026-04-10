<?php
require_once 'config/database.php';
require_once 'includes/qr_generator.php';

class Auth {
    private $db;
    private $qrGenerator;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->qrGenerator = new QRGenerator();
    }
    
    public function register($studentId, $firstName, $lastName, $email, $password, $phone = '', $course = '', $yearLevel = '') {
        try {
            // Validate email domain
            if (!preg_match('/@llcc\.edu\.ph$/', $email)) {
                return ['success' => false, 'message' => 'Only @llcc.edu.ph email addresses are allowed'];
            }
            
            // Check if email already exists
            $stmt = $this->db->prepare("SELECT id FROM students WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                return ['success' => false, 'message' => 'Email already registered'];
            }
            
            // Check if student ID already exists
            $stmt = $this->db->prepare("SELECT id FROM students WHERE student_id = ?");
            $stmt->execute([$studentId]);
            if ($stmt->fetch()) {
                return ['success' => false, 'message' => 'Student ID already registered'];
            }
            
            // Hash password
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            // Generate QR code
            $qrResult = $this->qrGenerator->generateQRCode($studentId, $firstName, $lastName, $email);
            if (isset($qrResult['error'])) {
                return ['success' => false, 'message' => $qrResult['error']];
            }
            
            // Insert new student with QR code
            $stmt = $this->db->prepare("
                INSERT INTO students (student_id, first_name, last_name, email, password, phone, course, year_level, qr_code, qr_code_path) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $result = $stmt->execute([
                $studentId, $firstName, $lastName, $email, $hashedPassword, 
                $phone, $course, $yearLevel, $qrResult['qr_code'], $qrResult['qr_url']
            ]);
            
            if ($result) {
                return ['success' => true, 'message' => 'Registration successful'];
            } else {
                return ['success' => false, 'message' => 'Registration failed'];
            }
            
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }
    
    public function login($email, $password) {
        try {
            $stmt = $this->db->prepare("SELECT id, student_id, first_name, last_name, email, password FROM students WHERE email = ? AND is_active = 1");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password'])) {
                // Create session
                $sessionToken = bin2hex(random_bytes(32));
                $expiresAt = date('Y-m-d H:i:s', strtotime('+24 hours'));
                
                $stmt = $this->db->prepare("INSERT INTO user_sessions (student_id, session_token, expires_at) VALUES (?, ?, ?)");
                $stmt->execute([$user['id'], $sessionToken, $expiresAt]);
                
                // Set session cookie
                setcookie('session_token', $sessionToken, time() + (24 * 60 * 60), '/', '', false, true);
                
                return ['success' => true, 'message' => 'Login successful', 'user' => $user];
            } else {
                return ['success' => false, 'message' => 'Invalid email or password'];
            }
            
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }
    
    public function logout() {
        if (isset($_COOKIE['session_token'])) {
            $stmt = $this->db->prepare("UPDATE user_sessions SET is_active = 0 WHERE session_token = ?");
            $stmt->execute([$_COOKIE['session_token']]);
        }
        setcookie('session_token', '', time() - 3600, '/');
    }
    
    public function getCurrentUser() {
        if (!isset($_COOKIE['session_token'])) {
            return null;
        }
        
        try {
            $stmt = $this->db->prepare("
                SELECT s.id, s.student_id, s.first_name, s.last_name, s.email, s.phone, s.course, s.year_level, s.qr_code, s.qr_code_path 
                FROM students s 
                INNER JOIN user_sessions us ON s.id = us.student_id 
                WHERE us.session_token = ? AND us.is_active = 1 AND us.expires_at > NOW()
            ");
            $stmt->execute([$_COOKIE['session_token']]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            return null;
        }
    }
    
    public function getQRCodeData($studentId) {
        try {
            $stmt = $this->db->prepare("
                SELECT qr_code, qr_code_path, first_name, last_name, student_id 
                FROM students 
                WHERE student_id = ? AND is_active = 1
            ");
            $stmt->execute([$studentId]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            return null;
        }
    }
    
    public function getAnalyticsData() {
        return [
            'qr_stats' => $this->qrGenerator->getQRCodeStats(),
            'attendance_analytics' => $this->qrGenerator->getAttendanceAnalytics(),
            'course_distribution' => $this->qrGenerator->getCourseDistribution(),
            'year_level_distribution' => $this->qrGenerator->getYearLevelDistribution()
        ];
    }
}
?>
