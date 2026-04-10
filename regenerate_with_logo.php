<?php
// Regenerate all QR codes with LLCC logo embedded
require_once 'includes/auth.php';

$auth = new Auth();
$database = new Database();
$db = $database->getConnection();

echo "<h2>Regenerate QR Codes with LLCC Logo</h2>";

// Check if logo exists in multiple formats
$logoPaths = ['llcc-logo.jpeg', 'llcc-logo.png', 'llcc-logo.jpg'];
$logoPath = null;

foreach ($logoPaths as $path) {
    if (file_exists($path)) {
        $logoPath = $path;
        break;
    }
}

if (!$logoPath) {
    echo "❌ <strong>Error:</strong> LLCC logo not found. Tried: " . implode(', ', $logoPaths) . "<br>";
    echo "Please make sure the logo file is in the project root directory.<br>";
    echo "<br><a href='dashboard.php'>Back to Dashboard</a>";
    exit;
}

echo "✅ LLCC logo found: $logoPath<br>";
echo "Logo size: " . filesize($logoPath) . " bytes<br>";
echo "Logo format: " . strtoupper(pathinfo($logoPath, PATHINFO_EXTENSION)) . "<br><br>";

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
        echo "<div style='border: 1px solid #ddd; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
        echo "<strong>Processing:</strong> " . $student['first_name'] . " " . $student['last_name'] . " (ID: " . $student['student_id'] . ")<br>";
        
        try {
            // Generate new QR code with logo
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
                        echo "<h4>QR Code with LLCC Logo:</h4>";
                        echo "<img src='" . $qrResult['qr_url'] . "' style='max-width: 200px; border: 2px solid #ccc; margin: 10px 0;'><br>";
                        echo "<small>Try scanning this QR code to verify it works with the embedded logo.</small><br>";
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
    
    if ($successCount > 0) {
        echo "<br><h3>🎉 All QR codes now have the LLCC logo embedded!</h3>";
        echo "<p>Students can now view their QR codes with the LLCC logo at: <a href='qr_code.php'>My QR Code</a></p>";
    }
    
} catch (Exception $e) {
    echo "❌ <strong>Fatal Error:</strong> " . $e->getMessage() . "<br>";
}

echo "<br><a href='test_logo_qr.php'>Test Logo QR</a> | <a href='qr_code.php'>My QR Code</a> | <a href='dashboard.php'>Dashboard</a>";
?>
