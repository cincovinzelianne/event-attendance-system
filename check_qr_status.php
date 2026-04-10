<?php
// Check the actual status of QR codes in the database
require_once 'includes/auth.php';

$auth = new Auth();
$database = new Database();
$db = $database->getConnection();

echo "<h2>QR Code Status Check</h2>";

try {
    // Get all students with their QR code info
    $stmt = $db->prepare("
        SELECT id, student_id, first_name, last_name, email, qr_code, qr_code_path, created_at 
        FROM students 
        WHERE is_active = 1 
        ORDER BY created_at DESC
    ");
    $stmt->execute();
    $students = $stmt->fetchAll();
    
    echo "<h3>All Students QR Status</h3>";
    echo "Total students: " . count($students) . "<br><br>";
    
    foreach ($students as $student) {
        echo "<div style='border: 1px solid #ccc; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
        echo "<strong>" . $student['first_name'] . " " . $student['last_name'] . "</strong> (ID: " . $student['student_id'] . ")<br>";
        echo "Email: " . $student['email'] . "<br>";
        echo "QR Code: " . ($student['qr_code'] ?: '<span style="color: red;">NULL</span>') . "<br>";
        echo "QR Path: " . ($student['qr_code_path'] ?: '<span style="color: red;">NULL</span>') . "<br>";
        
        if ($student['qr_code_path']) {
            if (file_exists($student['qr_code_path'])) {
                echo "QR File: <span style='color: green;'>EXISTS</span> (" . filesize($student['qr_code_path']) . " bytes)<br>";
                echo "<img src='" . $student['qr_code_path'] . "' style='max-width: 100px; border: 1px solid #ccc; margin: 5px 0;'><br>";
            } else {
                echo "QR File: <span style='color: red;'>MISSING</span> (Path: " . $student['qr_code_path'] . ")<br>";
            }
        }
        
        echo "Created: " . $student['created_at'] . "<br>";
        echo "</div>";
    }
    
    // Check if we can generate QR codes for students with missing files
    echo "<h3>Fix Missing QR Files</h3>";
    $studentsToFix = [];
    
    foreach ($students as $student) {
        if ($student['qr_code'] && (!$student['qr_code_path'] || !file_exists($student['qr_code_path']))) {
            $studentsToFix[] = $student;
        }
    }
    
    if (count($studentsToFix) > 0) {
        echo "Found " . count($studentsToFix) . " students with missing QR files:<br>";
        
        $qrGenerator = new QRGenerator();
        $fixed = 0;
        
        foreach ($studentsToFix as $student) {
            echo "<br>Fixing: " . $student['first_name'] . " " . $student['last_name'] . "<br>";
            
            try {
                // Generate new QR code
                $qrResult = $qrGenerator->generateQRCode(
                    $student['student_id'], 
                    $student['first_name'], 
                    $student['last_name'], 
                    $student['email']
                );
                
                if (isset($qrResult['error'])) {
                    echo "Error: " . $qrResult['error'] . "<br>";
                } else {
                    // Update the database with new QR path
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
                        echo "✅ Fixed: " . $qrResult['qr_code'] . "<br>";
                        echo "✅ Path: " . $qrResult['qr_url'] . "<br>";
                        $fixed++;
                    } else {
                        echo "❌ Failed to update database<br>";
                    }
                }
            } catch (Exception $e) {
                echo "❌ Exception: " . $e->getMessage() . "<br>";
            }
        }
        
        echo "<br><strong>Fixed $fixed students</strong><br>";
    } else {
        echo "All students have valid QR codes and files!<br>";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "<br>";
}

echo "<br><a href='qr_code.php'>Check My QR Code</a> | <a href='dashboard.php'>Dashboard</a>";
?>
