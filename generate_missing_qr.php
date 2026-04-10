<?php
// Script to generate QR codes for students who don't have them
require_once 'includes/auth.php';

$auth = new Auth();
$database = new Database();
$db = $database->getConnection();

echo "<h2>Generate Missing QR Codes</h2>";

try {
    // Find students without QR codes
    $stmt = $db->prepare("
        SELECT id, student_id, first_name, last_name, email 
        FROM students 
        WHERE (qr_code IS NULL OR qr_code = '') 
        AND is_active = 1
    ");
    $stmt->execute();
    $students = $stmt->fetchAll();
    
    echo "Found " . count($students) . " students without QR codes.<br><br>";
    
    if (count($students) > 0) {
        $qrGenerator = new QRGenerator();
        $successCount = 0;
        $errorCount = 0;
        
        foreach ($students as $student) {
            echo "Processing: " . $student['first_name'] . " " . $student['last_name'] . " (ID: " . $student['student_id'] . ")<br>";
            
            try {
                // Generate QR code
                $qrResult = $qrGenerator->generateQRCode(
                    $student['student_id'], 
                    $student['first_name'], 
                    $student['last_name'], 
                    $student['email']
                );
                
                if (isset($qrResult['error'])) {
                    echo "  Error: " . $qrResult['error'] . "<br>";
                    $errorCount++;
                } else {
                    // Update student record with QR code
                    $updateStmt = $db->prepare("
                        UPDATE students 
                        SET qr_code = ?, qr_code_path = ? 
                        WHERE id = ?
                    ");
                    
                    $result = $updateStmt->execute([
                        $qrResult['qr_code'],
                        $qrResult['qr_url'],
                        $student['id']
                    ]);
                    
                    if ($result) {
                        echo "  Success: QR code generated and saved<br>";
                        echo "  QR Code: " . $qrResult['qr_code'] . "<br>";
                        echo "  QR Path: " . $qrResult['qr_url'] . "<br>";
                        $successCount++;
                    } else {
                        echo "  Error: Failed to update database<br>";
                        $errorCount++;
                    }
                }
            } catch (Exception $e) {
                echo "  Exception: " . $e->getMessage() . "<br>";
                $errorCount++;
            }
            
            echo "<br>";
        }
        
        echo "<h3>Summary</h3>";
        echo "Successfully processed: $successCount<br>";
        echo "Errors: $errorCount<br>";
        
    } else {
        echo "All students already have QR codes!<br>";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "<br>";
}

echo "<br><a href='dashboard.php'>Back to Dashboard</a>";
?>
