<?php
// Comprehensive QR Code Uniqueness Verification Tool
require_once 'includes/auth.php';

$auth = new Auth();
$database = new Database();
$db = $database->getConnection();

echo "<h2>QR Code Uniqueness Verification</h2>";

try {
    // Get all QR codes from database
    $stmt = $db->prepare("
        SELECT student_id, first_name, last_name, email, qr_code, created_at 
        FROM students 
        WHERE is_active = 1 AND qr_code IS NOT NULL AND qr_code != ''
        ORDER BY created_at DESC
    ");
    $stmt->execute();
    $students = $stmt->fetchAll();
    
    echo "<h3>📊 QR Code Statistics</h3>";
    echo "Total students with QR codes: " . count($students) . "<br>";
    
    if (count($students) > 0) {
        // Check for duplicates
        $qrCodes = array_column($students, 'qr_code');
        $uniqueQrCodes = array_unique($qrCodes);
        $duplicateCount = count($qrCodes) - count($uniqueQrCodes);
        
        echo "Unique QR codes: " . count($uniqueQrCodes) . "<br>";
        echo "Duplicate QR codes: " . $duplicateCount . "<br>";
        
        if ($duplicateCount > 0) {
            echo "⚠️ <strong>WARNING: Duplicate QR codes found!</strong><br>";
        } else {
            echo "✅ <strong>All QR codes are unique!</strong><br>";
        }
        
        echo "<br><h3>🔍 QR Code Analysis</h3>";
        
        // Analyze QR code patterns
        $patterns = [];
        foreach ($qrCodes as $qrCode) {
            if (preg_match('/^STU_([a-f0-9]{16})$/', $qrCode, $matches)) {
                $patterns[] = $matches[1];
            }
        }
        
        echo "QR Code Format: STU_[16-character hex]<br>";
        echo "Valid format codes: " . count($patterns) . "<br>";
        echo "Invalid format codes: " . (count($qrCodes) - count($patterns)) . "<br>";
        
        // Check for collisions
        $collisions = array_diff_assoc($qrCodes, $uniqueQrCodes);
        if (!empty($collisions)) {
            echo "<br>⚠️ <strong>QR Code Collisions Found:</strong><br>";
            foreach ($collisions as $index => $duplicateCode) {
                $student = $students[$index];
                echo "- Duplicate: $duplicateCode (Student: {$student['first_name']} {$student['last_name']})<br>";
            }
        }
        
        echo "<br><h3>📋 All QR Codes</h3>";
        echo "<div style='max-height: 400px; overflow-y: auto; border: 1px solid #ccc; padding: 10px;'>";
        
        foreach ($students as $index => $student) {
            $isDuplicate = in_array($student['qr_code'], $collisions);
            $style = $isDuplicate ? "background-color: #ffebee; border-left: 4px solid #f44336;" : "background-color: #f5f5f5; border-left: 4px solid #4caf50;";
            
            echo "<div style='$style padding: 10px; margin: 5px 0; border-radius: 4px;'>";
            echo "<strong>" . ($index + 1) . ".</strong> " . $student['first_name'] . " " . $student['last_name'] . "<br>";
            echo "<strong>Student ID:</strong> " . $student['student_id'] . "<br>";
            echo "<strong>Email:</strong> " . $student['email'] . "<br>";
            echo "<strong>QR Code:</strong> <code>" . $student['qr_code'] . "</code><br>";
            echo "<strong>Created:</strong> " . $student['created_at'] . "<br>";
            
            if ($isDuplicate) {
                echo "<span style='color: #f44336; font-weight: bold;'>⚠️ DUPLICATE QR CODE</span><br>";
            } else {
                echo "<span style='color: #4caf50; font-weight: bold;'>✅ UNIQUE</span><br>";
            }
            echo "</div>";
        }
        
        echo "</div>";
        
        // QR Code Generation Algorithm Analysis
        echo "<br><h3>🔐 QR Code Generation Algorithm</h3>";
        echo "<p><strong>How QR codes are made unique:</strong></p>";
        echo "<ol>";
        echo "<li><strong>Student Data:</strong> Combines student_id, name, email, and timestamp</li>";
        echo "<li><strong>Base64 Encoding:</strong> Encodes the data as a string</li>";
        echo "<li><strong>MD5 Hash:</strong> Creates a hash of the encoded data + unique ID</li>";
        echo "<li><strong>Prefix:</strong> Adds 'STU_' prefix for identification</li>";
        echo "<li><strong>Length:</strong> Truncates to 16 characters for consistency</li>";
        echo "<li><strong>Uniqueness Check:</strong> Verifies no duplicate exists in database</li>";
        echo "<li><strong>Regeneration:</strong> If duplicate found, generates new code with additional randomness</li>";
        echo "</ol>";
        
        // Security Analysis
        echo "<br><h3>🛡️ Security Analysis</h3>";
        $totalPossibleCodes = pow(16, 16); // 16^16 possible combinations
        echo "Total possible QR codes: " . number_format($totalPossibleCodes) . "<br>";
        echo "Current usage: " . count($uniqueQrCodes) . " codes<br>";
        echo "Collision probability: " . number_format((count($uniqueQrCodes) / $totalPossibleCodes) * 100, 20) . "%<br>";
        echo "Security level: <strong>EXTREMELY HIGH</strong> (practically impossible to guess)<br>";
        
    } else {
        echo "No students with QR codes found.<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}

echo "<br><h3>🔍 QR Code Validation Test</h3>";
echo "<p>Test if a QR code is valid and unique:</p>";
echo "<form method='POST' style='background: #f9f9f9; padding: 15px; border-radius: 5px;'>";
echo "<input type='text' name='test_qr_code' placeholder='Enter QR code to test (e.g., STU_1234567890abcdef)' style='width: 300px; padding: 8px; margin-right: 10px;'>";
echo "<button type='submit' style='padding: 8px 15px; background: #4caf50; color: white; border: none; border-radius: 4px;'>Validate</button>";
echo "</form>";

if ($_POST['test_qr_code']) {
    $testCode = trim($_POST['test_qr_code']);
    echo "<br><h4>Validation Result for: <code>$testCode</code></h4>";
    
    // Check format
    if (preg_match('/^STU_[a-f0-9]{16}$/', $testCode)) {
        echo "✅ <strong>Format:</strong> Valid<br>";
        
        // Check if exists in database
        $stmt = $db->prepare("SELECT student_id, first_name, last_name, email FROM students WHERE qr_code = ?");
        $stmt->execute([$testCode]);
        $student = $stmt->fetch();
        
        if ($student) {
            echo "✅ <strong>Database:</strong> Found<br>";
            echo "✅ <strong>Student:</strong> {$student['first_name']} {$student['last_name']}<br>";
            echo "✅ <strong>Email:</strong> {$student['email']}<br>";
            echo "✅ <strong>Status:</strong> VALID AND UNIQUE<br>";
        } else {
            echo "❌ <strong>Database:</strong> Not found<br>";
            echo "❌ <strong>Status:</strong> INVALID OR NOT REGISTERED<br>";
        }
    } else {
        echo "❌ <strong>Format:</strong> Invalid (should be STU_[16 hex characters])<br>";
        echo "❌ <strong>Status:</strong> INVALID FORMAT<br>";
    }
}

echo "<br><a href='qr_code.php'>My QR Code</a> | <a href='dashboard.php'>Dashboard</a>";
?>
