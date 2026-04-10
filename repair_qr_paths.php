<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/database.php';
require_once 'includes/qr_generator.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    $qrGenerator = new QRGenerator();

    $stmt = $db->query("SELECT id, qr_code FROM students WHERE qr_code IS NOT NULL AND qr_code != '' AND (qr_code_path IS NULL OR qr_code_path = '')");
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $updateStmt = $db->prepare("UPDATE students SET qr_code_path = ? WHERE id = ?");
    $updated = 0;

    foreach ($students as $student) {
        $updateStmt->execute([
            $qrGenerator->getQRCodeImagePath($student['qr_code']),
            $student['id']
        ]);
        $updated++;
    }

    echo "Updated QR paths: {$updated}\n";
} catch (Throwable $e) {
    echo 'Error: ' . $e->getMessage() . "\n";
    exit(1);
}
