<?php
/**
 * Event Attendance System - Health Check
 * Verifies database, PHP extensions, file permissions, and configuration
 */

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>System Health Check</title>";
echo "<style>body { font-family: Arial; margin: 20px; background: #f5f5f5; }";
echo ".check { margin: 10px 0; padding: 10px; border-radius: 5px; }";
echo ".pass { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }";
echo ".fail { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }";
echo ".warn { background: #fff3cd; color: #856404; border: 1px solid #ffeaa7; }";
echo ".section { margin-top: 20px; font-size: 18px; font-weight: bold; color: #333; }";
echo "</style></head><body>";

echo "<h1>📋 Event Attendance System - Health Check</h1>";
echo "<p>Generated: " . date('Y-m-d H:i:s') . "</p>";

// 1. PHP VERSION
echo "<div class='section'>🔧 PHP Configuration</div>";
$phpVersion = phpversion();
$phpStatus = version_compare($phpVersion, '7.4.0', '>=') ? 'pass' : 'fail';
echo "<div class='check $phpStatus'>PHP Version: $phpVersion</div>";

// 2. CHECK EXTENSIONS
echo "<div class='section'>📦 Required PHP Extensions</div>";
$extensions = ['pdo', 'pdo_mysql', 'gd', 'mbstring', 'curl'];
foreach ($extensions as $ext) {
    $status = extension_loaded($ext) ? 'pass' : 'fail';
    $icon = $status === 'pass' ? '✓' : '✗';
    echo "<div class='check $status'>$icon $ext</div>";
}

// 3. DATABASE CONNECTION
echo "<div class='section'>🗄️ Database Connection</div>";
require_once 'config/database.php';
try {
    $database = new Database();
    $db = $database->getConnection();
    echo "<div class='check pass'>✓ Database Connected Successfully</div>";
    echo "<div class='check pass'>Host: " . DB_HOST . " | Database: " . DB_NAME . "</div>";
    
    // Check tables
    echo "<div class='section'>📊 Database Tables</div>";
    $tables = ['students', 'events', 'attendance', 'user_sessions', 'admin_users', 'event_departments', 'courses'];
    
    $stmt = $db->prepare("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = ?");
    $stmt->execute([DB_NAME]);
    $existingTables = array_column($stmt->fetchAll(), 'TABLE_NAME');
    
    foreach ($tables as $table) {
        $status = in_array($table, $existingTables) ? 'pass' : 'fail';
        $icon = $status === 'pass' ? '✓' : '✗';
        echo "<div class='check $status'>$icon Table: $table</div>";
    }
    
    // Count records
    echo "<div class='section'>📈 Database Records</div>";
    foreach (['students', 'events', 'attendance', 'admin_users'] as $table) {
        if (in_array($table, $existingTables)) {
            $stmt = $db->prepare("SELECT COUNT(*) as count FROM $table");
            $stmt->execute();
            $count = $stmt->fetch()['count'];
            echo "<div class='check pass'>$table: <strong>$count</strong> records</div>";
        }
    }
    
} catch (PDOException $e) {
    echo "<div class='check fail'>✗ Database Connection Failed</div>";
    echo "<div class='check fail'>Error: " . $e->getMessage() . "</div>";
} catch (Exception $e) {
    echo "<div class='check fail'>✗ Error: " . $e->getMessage() . "</div>";
}

// 4. FILE PERMISSIONS
echo "<div class='section'>🔐 File Permissions</div>";
$paths = [
    'assets/qr_codes/' => 'QR Codes Directory',
    'uploads/approval_files/' => 'Uploads Directory',
    'config/database.php' => 'Database Config',
];

foreach ($paths as $path => $label) {
    $fullPath = __DIR__ . '/' . $path;
    if (file_exists($fullPath)) {
        $writable = is_writable($fullPath) ? 'pass' : 'warn';
        $icon = $writable === 'pass' ? '✓' : '⚠';
        $msg = $writable === 'pass' ? 'Writable' : 'Not writable';
        echo "<div class='check $writable'>$icon $label: $msg</div>";
    } else {
        echo "<div class='check fail'>✗ $label: <strong>Does not exist</strong></div>";
    }
}

// 5. CONFIGURATION CHECK
echo "<div class='section'>⚙️ Configuration</div>";
$config = [
    'max_upload_size' => ini_get('upload_max_filesize'),
    'post_max_size' => ini_get('post_max_size'),
    'memory_limit' => ini_get('memory_limit'),
];
foreach ($config as $key => $value) {
    echo "<div class='check pass'>$key: $value</div>";
}

// 6. SESSION CHECK
echo "<div class='section'>🔒 Session Status</div>";
$sessionStatus = session_status() === PHP_SESSION_ACTIVE ? 'pass' : 'warn';
echo "<div class='check $sessionStatus'>" . (session_status() === PHP_SESSION_ACTIVE ? '✓' : '⚠') . " Sessions: " . (session_status() === PHP_SESSION_ACTIVE ? 'Active' : 'Inactive') . "</div>";

echo "</body></html>";
?>
