<?php
require_once 'config/database.php';
$db = (new Database())->getConnection();
$sql = "SELECT COALESCE(c.department,'Unassigned') AS department_name, s.course, COUNT(*) AS total
FROM students s
LEFT JOIN courses c ON (s.course = c.course_name OR s.course LIKE CONCAT(c.course_name, ' - %'))
GROUP BY COALESCE(c.department,'Unassigned'), s.course
ORDER BY department_name, s.course";
foreach ($db->query($sql) as $row) {
    preg_match('/\[([^\]]+)\]\s*$/', (string)$row['course'], $m);
    $section = $m[1] ?? 'No Section';
    echo $row['department_name'] . " | " . $section . " | " . $row['total'] . PHP_EOL;
}
?>
