<?php
/**
 * Create Admin Accounts Directly
 * Adds default admin accounts to database with correct password hashing
 */

require_once 'config/database.php';

$result = [
    'success' => false,
    'message' => '',
    'admins_created' => [],
    'errors' => [],
    'debug' => []
];

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Check if admins table exists
    $stmt = $db->query("SHOW TABLES LIKE 'admins'");
    if ($stmt->rowCount() === 0) {
        $result['errors'][] = "Admins table does not exist. Please run create_admin_tables.sql first.";
        header('Content-Type: application/json');
        echo json_encode($result);
        exit;
    }
    
    // Admin accounts to create
    $admins = [
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
    
    foreach ($admins as $adminData) {
        try {
            // Check if admin already exists
            $checkStmt = $db->prepare("SELECT id FROM admins WHERE username = ? OR email = ?");
            $checkStmt->execute([$adminData['username'], $adminData['email']]);
            
            if ($checkStmt->fetch()) {
                $result['debug'][] = "Admin already exists: " . $adminData['username'];
                continue;
            }
            
            // Hash password using PASSWORD_DEFAULT
            $passwordHash = password_hash($adminData['password'], PASSWORD_DEFAULT);
            
            // Insert admin
            $insertStmt = $db->prepare("
                INSERT INTO admins (username, email, password_hash, full_name, role, is_active) 
                VALUES (?, ?, ?, ?, 'admin', 1)
            ");
            
            $insertStmt->execute([
                $adminData['username'],
                $adminData['email'],
                $passwordHash,
                $adminData['full_name']
            ]);
            
            // Verify the insert
            $verifyStmt = $db->prepare("SELECT id, password_hash FROM admins WHERE username = ?");
            $verifyStmt->execute([$adminData['username']]);
            $adminRecord = $verifyStmt->fetch();
            
            if ($adminRecord) {
                // Test password verification
                $isPasswordValid = password_verify($adminData['password'], $adminRecord['password_hash']);
                
                $result['admins_created'][] = [
                    'username' => $adminData['username'],
                    'email' => $adminData['email'],
                    'password' => $adminData['password'],
                    'full_name' => $adminData['full_name'],
                    'password_verified' => $isPasswordValid,
                    'hash' => substr($adminRecord['password_hash'], 0, 20) . '...'
                ];
            }
            
        } catch (Exception $e) {
            $result['errors'][] = "Error creating " . $adminData['username'] . ": " . $e->getMessage();
        }
    }
    
    $result['success'] = count($result['admins_created']) > 0;
    $result['message'] = count($result['admins_created']) . ' admin account(s) created successfully!';
    
} catch (Exception $e) {
    $result['errors'][] = "Database Error: " . $e->getMessage();
}

// Output results
header('Content-Type: application/json');
echo json_encode($result, JSON_PRETTY_PRINT);
?>
