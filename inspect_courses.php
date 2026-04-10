<?php
require_once 'config/database.php';
$db=(new Database())->getConnection();
$stmt=$db->query("SELECT course, COUNT(*) c FROM students WHERE email LIKE '%@llcc.edu.ph' AND (course LIKE 'BSBA%' OR course LIKE 'COED%') GROUP BY course ORDER BY course");
foreach($stmt as $row){ echo $row['course'].' | '.$row['c'].PHP_EOL; }
?>
