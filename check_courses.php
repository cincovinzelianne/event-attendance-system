<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once 'config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $stmt = $db->query("SELECT * FROM courses");
    $courses = $stmt->fetchAll();
    
    echo "Current courses:\n";
    foreach ($courses as $course) {
        echo "- {$course['course_name']} ({$course['department']}, {$course['major']})\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
