<?php
/**
 * Direct Database Table Creation
 * This script immediately creates missing admin tables
 */

require_once 'config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "Creating admin tables...\n";
    
    // Drop tables if they exist (for fresh start if needed)
    // Commented out - uncomment only if you need to reset
    // $db->exec("DROP TABLE IF EXISTS admin_sessions");
    // $db->exec("DROP TABLE IF EXISTS admins");
    
    // Create admins table
    $adminsSQL = "
    CREATE TABLE IF NOT EXISTS admins (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) UNIQUE NOT NULL,
        email VARCHAR(100) UNIQUE NOT NULL,
        password_hash VARCHAR(255) NOT NULL,
        full_name VARCHAR(100) NOT NULL,
        role VARCHAR(50) DEFAULT 'admin',
        is_active BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_username (username),
        INDEX idx_email (email)
    )";
    
    $db->exec($adminsSQL);
    echo "✓ admins table created\n";
    
    // Create admin_sessions table
    $sessionsSQL = "
    CREATE TABLE IF NOT EXISTS admin_sessions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        admin_id INT NOT NULL,
        session_token VARCHAR(255) NOT NULL UNIQUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        expires_at TIMESTAMP NOT NULL,
        is_active BOOLEAN DEFAULT TRUE,
        FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE CASCADE,
        INDEX idx_session_token (session_token),
        INDEX idx_admin_id (admin_id)
    )";
    
    $db->exec($sessionsSQL);
    echo "✓ admin_sessions table created\n";
    
    // Verify tables exist
    $stmt = $db->prepare("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA='event_attendance' AND TABLE_NAME IN ('admins', 'admin_sessions')");
    $stmt->execute();
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (count($tables) === 2) {
        echo "\n✓ SUCCESS! All admin tables are now in place.\n";
        echo "  - admins table: EXISTS\n";
        echo "  - admin_sessions table: EXISTS\n";
    } else {
        echo "\n✕ Error: Not all tables were created successfully\n";
        exit(1);
    }
    
} catch (Exception $e) {
    echo "✕ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
