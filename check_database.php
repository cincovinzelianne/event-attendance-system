<?php
/**
 * Database Table Checker
 * Shows what tables exist and what's missing
 */

require_once 'config/database.php';

$requiredTables = [
    'students' => 'Student authentication and profiles',
    'admins' => 'Admin user accounts',
    'admin_sessions' => 'Admin session tracking',
    'events' => 'Event information',
    'attendance' => 'Attendance records',
    'user_sessions' => 'Student session tracking',
    'analytics' => 'Action logging',
    'courses' => 'Course/program information',
    'event_departments' => 'Event categorization',
    'student_notifications' => 'Notification tracking'
];

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Get list of existing tables
    $stmt = $db->query("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = '" . DB_NAME . "'");
    $existingTables = array_column($stmt->fetchAll(), 'TABLE_NAME');
    
    echo "=== DATABASE TABLE ANALYSIS ===\n\n";
    echo "Database: " . DB_NAME . "\n";
    echo "Host: " . DB_HOST . "\n\n";
    
    // Check each required table
    echo "REQUIRED TABLES STATUS:\n";
    echo str_repeat("-", 60) . "\n";
    
    $missing = [];
    $present = [];
    
    foreach ($requiredTables as $tableName => $description) {
        $exists = in_array($tableName, $existingTables);
        $status = $exists ? "✓ EXISTS" : "✗ MISSING";
        $color = $exists ? "\033[32m" : "\033[31m"; // Green/Red
        $reset = "\033[0m";
        
        echo sprintf("%s%-25s %s %s\n", $color, $tableName, $status, $reset);
        echo "  → $description\n\n";
        
        if ($exists) {
            $present[] = $tableName;
        } else {
            $missing[] = $tableName;
        }
    }
    
    echo str_repeat("-", 60) . "\n";
    echo "\nSUMMARY:\n";
    echo "✓ Present: " . count($present) . " tables\n";
    echo "✗ Missing: " . count($missing) . " tables\n\n";
    
    if (!empty($missing)) {
        echo "MISSING TABLES:\n";
        foreach ($missing as $table) {
            echo "  - $table\n";
        }
        echo "\nACTION REQUIRED: Import SQL files to create missing tables\n";
    } else {
        echo "✓ All required tables are present!\n";
    }
    
    // Show table row counts
    echo "\n" . str_repeat("=", 60) . "\n";
    echo "TABLE RECORD COUNTS:\n";
    echo str_repeat("-", 60) . "\n";
    
    foreach ($present as $table) {
        $countStmt = $db->query("SELECT COUNT(*) as count FROM $table");
        $count = $countStmt->fetch()['count'];
        echo sprintf("%-25s %s records\n", $table, $count);
    }
    
} catch (PDOException $e) {
    echo "❌ Database Connection Error:\n";
    echo $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "❌ Error:\n";
    echo $e->getMessage() . "\n";
}
?>
