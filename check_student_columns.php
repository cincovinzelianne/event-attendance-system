<?php
require_once 'config/database.php';
$db=(new Database())->getConnection();
foreach($db->query("SHOW COLUMNS FROM students") as $row){ echo $row['Field'].PHP_EOL; }
?>
