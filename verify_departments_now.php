<?php
require_once 'config/database.php';
$db=(new Database())->getConnection();
foreach($db->query("SELECT DISTINCT department FROM courses ORDER BY department") as $row){ echo $row['department'].PHP_EOL; }
?>
