<?php
// Force generate QR codes for all students
require_once 'includes/auth.php';

$auth = new Auth();
$database = new Database();
$db = $database->getConnection();

echo "<h2>Force Generate QR Codes</h2>";

try {
    // Get all active students
    $stmt = $db->prepare("
        SELECT id, student_id, first_name, last_name, email, qr_code, qr_code_path 
        FROM students 
        WHERE is_active = 1 
        ORDER BY id
    ");
    $stmt->execute();
    $students = $stmt->fetchAll();
    
    echo "Found " . count($students) . " students to process.<br><br>";
    
    $qrGenerator = new QRGenerator();
    $successCount = 0;
    $errorCount = 0;
    
    foreach ($students as $student) {
        echo "<div style='border: 1px solid #ddd; padding: 10px; margin: 5px 0;'>";
        echo "<strong>Processing:</strong> " . $student['first_name'] . " " . $student['last_name'] . " (ID: " . $student['student_id'] . ")<br>";
        
        try {
            // Generate new QR code
            $qrResult = $qrGenerator->generateQRCode(
                $student['student_id'], 
                $student['first_name'], 
                $student['last_name'], 
                $student['email']
            );
            
            if (isset($qrResult['error'])) {
                echo "❌ <span style='color: red;'>Error:</span> " . $qrResult['error'] . "<br>";
                $errorCount++;
            } else {
                // Update student record
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
                    echo "✅ <span style='color: green;'>Success:</span> " . $qrResult['qr_code'] . "<br>";
                    echo "✅ <span style='color: green;'>Path:</span> " . ($qrResult['qr_url'] ?: 'NULL') . "<br>";
                    
                    if ($qrResult['qr_url'] && file_exists($qrResult['qr_url'])) {
                        echo "✅ <span style='color: green;'>File exists:</span> " . filesize($qrResult['qr_url']) . " bytes<br>";
                        echo "<img src='" . $qrResult['qr_url'] . "' style='max-width: 100px; border: 1px solid #ccc; margin: 5px 0;'><br>";
                    } else {
                        echo "⚠️ <span style='color: orange;'>File missing:</span> " . $qrResult['qr_url'] . "<br>";
                    }
                    
                    $successCount++;
                } else {
                    echo "❌ <span style='color: red;'>Database update failed</span><br>";
                    $errorCount++;
                }
            }
        } catch (Exception $e) {
            echo "❌ <span style='color: red;'>Exception:</span> " . $e->getMessage() . "<br>";
            $errorCount++;
        }
        
        echo "</div>";
    }
    
    echo "<h3>Summary</h3>";
    echo "✅ Successfully processed: $successCount<br>";
    echo "❌ Errors: $errorCount<br>";
    echo "📊 Total students: " . count($students) . "<br>";
    
} catch (Exception $e) {
    echo "❌ <strong>Fatal Error:</strong> " . $e->getMessage() . "<br>";
}

echo "<br><a href='check_qr_status.php'>Check QR Status</a> | <a href='qr_code.php'>My QR Code</a> | <a href='dashboard.php'>Dashboard</a>";
?>
