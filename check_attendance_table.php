<?php
require_once 'config/database.php';

$db = (new Database())->getConnection();
echo "=== Attendance Table Structure ===\n";
$cols = $db->query('SHOW COLUMNS FROM attendance')->fetchAll(PDO::FETCH_ASSOC);
foreach($cols as $c) {
    echo $c['Field'] . ' (' . $c['Type'] . ')' . "\n";
}
?>
