<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once 'config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $year_levels = json_encode(["1st Year", "2nd Year", "3rd Year", "4th Year"]);
    
    $courses_to_add = [
        [
            'department' => 'Technology',
            'course_name' => 'BIT',
            'major' => 'BIT Major',
            'year_levels' => $year_levels
        ],
        [
            'department' => 'Hospitality and Tourism Management',
            'course_name' => 'CoHTM',
            'major' => 'HTM Major',
            'year_levels' => $year_levels
        ]
    ];
    
    $stmt = $db->prepare("INSERT INTO courses (department, course_name, major, year_levels) VALUES (?, ?, ?, ?)");
    
    foreach ($courses_to_add as $course) {
        $stmt->execute([
            $course['department'],
            $course['course_name'],
            $course['major'],
            $course['year_levels']
        ]);
        echo "Added course: {$course['course_name']} ({$course['department']})\n";
    }
    
    echo "Success!\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
