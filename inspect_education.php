<?php
require_once 'config/database.php';
$db=(new Database())->getConnection();
$stmt=$db->query("SELECT s.course, COUNT(*) AS total FROM students s LEFT JOIN courses c ON (s.course = c.course_name OR s.course LIKE CONCAT(c.course_name, ' - %')) WHERE c.department = 'DEPARTMENT OF EDUCATION' AND s.email LIKE '%@llcc.edu.ph' GROUP BY s.course ORDER BY s.course");
foreach($stmt as $row){ echo $row['course'].' | '.$row['total'].PHP_EOL; }
?>
