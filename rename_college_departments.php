<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/database.php';

try {
    $db = (new Database())->getConnection();

    $mapping = [
        'DEPARTMENT OF INFORMATION TECHNOLOGY' => 'COLLEGE OF TECHNOLOGY',
        'DEPARTMENT OF EDUCATION' => 'COLLEGE OF EDUCATION',
        'DEPARTMENT OF BUSINESS ADMINISTRATION' => 'COLLEGE OF HOSPITALITY AND TOURISM MANAGEMENT',
        'DEPARTMENT OF NURSING' => 'COLLEGE OF HOSPITALITY AND TOURISM MANAGEMENT',
        'COLLEGE OF HOSPITALITY MANANGEMENT' => 'COLLEGE OF HOSPITALITY AND TOURISM MANAGEMENT',
        'COLLEGE OF HOSPITALITY MANAGEMENT' => 'COLLEGE OF HOSPITALITY AND TOURISM MANAGEMENT',
        'COLLEGE OF TOURISM MANAGEMENT' => 'COLLEGE OF HOSPITALITY AND TOURISM MANAGEMENT',
        'COLLEGE OF TOURISM AND HOSPITALITY MANAGEMENT' => 'COLLEGE OF HOSPITALITY AND TOURISM MANAGEMENT',
    ];

    $stmt = $db->prepare("UPDATE courses SET department = ? WHERE department = ?");
    $updated = 0;

    foreach ($mapping as $oldName => $newName) {
        $stmt->execute([$newName, $oldName]);
        $updated += $stmt->rowCount();
    }

    echo "Updated course department rows: {$updated}\n";
    echo "Current departments:\n";
    foreach ($db->query("SELECT DISTINCT department FROM courses ORDER BY department") as $row) {
        echo '- ' . $row['department'] . "\n";
    }
} catch (Throwable $e) {
    echo 'Error: ' . $e->getMessage() . "\n";
    exit(1);
}
