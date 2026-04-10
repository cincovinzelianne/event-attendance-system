<?php
/**
 * Direct Database Import
 * Automatically imports all SQL files directly to the database without user interaction
 */

require_once 'config/database.php';

set_time_limit(120);

$result = [
    'success' => false,
    'total_files' => 0,
    'imported_files' => [],
    'failed_files' => [],
    'statements_executed' => 0,
    'tables_created' => 0,
    'errors' => [],
    'start_time' => microtime(true)
];

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // List of SQL files to import in order
    $sqlFiles = [
        'database.sql',
        'create_admin_tables.sql',
        'create_courses_table.sql',
        'create_event_departments_table.sql',
        'student_notifications.sql',
        'add_attendance_locked_column.sql',
        'add_qr_code_locked_field.sql',
        'sample_accounts.sql',
    ];
    
    $result['total_files'] = count($sqlFiles);
    
    // Import each file
    foreach ($sqlFiles as $file) {
        $filePath = __DIR__ . '/' . $file;
        
        if (!file_exists($filePath)) {
            $result['failed_files'][] = $file;
            $result['errors'][] = "File not found: $file";
            continue;
        }
        
        try {
            $sqlContent = file_get_contents($filePath);
            
            // Remove comments and split statements
            $lines = explode("\n", $sqlContent);
            $statements = [];
            $currentStatement = '';
            
            foreach ($lines as $line) {
                // Remove comments
                $line = preg_replace('/--.*$/', '', $line);
                $line = trim($line);
                
                if (empty($line)) continue;
                
                $currentStatement .= ' ' . $line;
                
                if (substr($line, -1) === ';') {
                    $statements[] = trim($currentStatement);
                    $currentStatement = '';
                }
            }
            
            // Execute each statement
            foreach ($statements as $statement) {
                if (!empty(trim($statement))) {
                    try {
                        $db->exec($statement);
                        $result['statements_executed']++;
                    } catch (PDOException $e) {
                        // Some statements might fail if table already exists, that's ok
                        if (strpos($e->getMessage(), 'already exists') === false) {
                            throw $e;
                        }
                    }
                }
            }
            
            $result['imported_files'][] = $file;
            
        } catch (Exception $e) {
            $result['failed_files'][] = $file;
            $result['errors'][] = "$file: " . $e->getMessage();
        }
    }
    
    // Get final table count
    $stmt = $db->query("SELECT COUNT(*) as count FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = '" . DB_NAME . "'");
    $result['tables_created'] = $stmt->fetch()['count'];
    
    // Check if essential tables exist
    $essentialTables = ['students', 'admins', 'events', 'attendance', 'courses'];
    $existingTables = [];
    $stmt = $db->query("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = '" . DB_NAME . "'");
    $existingTables = array_column($stmt->fetchAll(), 'TABLE_NAME');
    
    $missingTables = array_diff($essentialTables, $existingTables);
    
    $result['success'] = (count($result['imported_files']) > 0) && (count($missingTables) === 0);
    $result['missing_tables'] = $missingTables;
    $result['existing_tables'] = $existingTables;
    
} catch (Exception $e) {
    $result['errors'][] = "Database Error: " . $e->getMessage();
}

$result['execution_time'] = round(microtime(true) - $result['start_time'], 2);

// Output JSON response
header('Content-Type: application/json');
echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
?>
