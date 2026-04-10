<?php
require_once 'config/database.php';

$db = (new Database())->getConnection();
$courses = $db->query("SELECT department, course_name, major FROM courses WHERE is_active = 1")->fetchAll(PDO::FETCH_ASSOC);
$courseMap = [];
foreach ($courses as $course) {
    $fullLabel = trim($course['course_name'] . (!empty($course['major']) ? ' - ' . $course['major'] : ''));
    $courseMap[$fullLabel] = $course['department'];
    if (!isset($courseMap[$course['course_name']])) {
        $courseMap[$course['course_name']] = $course['department'];
    }
}

$rows = $db->query("SELECT course FROM students WHERE email LIKE '%@llcc.edu.ph'")->fetchAll(PDO::FETCH_ASSOC);

$summary = [];
foreach ($rows as $row) {
    $courseLabel = (string)($row['course'] ?? '');
    $normalizedCourse = trim(preg_replace('/\s*\[[^\]]+\]\s*$/', '', $courseLabel));
    $department = $courseMap[$normalizedCourse] ?? 'Unassigned';
    $section = 'No Section';
    if (preg_match('/\[([^\]]+)\]\s*$/', (string)($row['course'] ?? ''), $matches)) {
        $section = trim($matches[1]);
    }
    if (!isset($summary[$department])) {
        $summary[$department] = [];
    }
    if (!isset($summary[$department][$section])) {
        $summary[$department][$section] = 0;
    }
    $summary[$department][$section]++;
}

ksort($summary);
foreach ($summary as $department => $sections) {
    ksort($sections);
    foreach ($sections as $section => $count) {
        echo $department . ' | ' . $section . ' | ' . $count . PHP_EOL;
    }
}
