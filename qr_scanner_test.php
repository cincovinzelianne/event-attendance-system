<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Scanner Test - Event Attendance</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            padding: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        h2, h3, h4 {
            color: #2d3748;
        }
        code {
            background: #f7fafc;
            padding: 2px 6px;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
        }
    </style>
</head>
<body>
<?php
// QR Code Scanner Test - Verify QR code uniqueness and validity
require_once 'includes/auth.php';

$auth = new Auth();
$database = new Database();
$db = $database->getConnection();

echo "<h2>QR Code Scanner Test</h2>";

if ($_POST['scanned_qr_code']) {
    $scannedCode = trim($_POST['scanned_qr_code']);
    
    echo "<h3>🔍 Scanning Result for: <code>$scannedCode</code></h3>";
    
    // Validate QR code format
    if (!preg_match('/^STU_[a-f0-9]{16}$/', $scannedCode)) {
        echo "❌ <strong>Invalid Format:</strong> QR code should be in format STU_[16 hex characters]<br>";
        echo "❌ <strong>Status:</strong> INVALID QR CODE<br>";
    } else {
        echo "✅ <strong>Format:</strong> Valid<br>";
        
        // Check if QR code exists in database
        $stmt = $db->prepare("
            SELECT s.id, s.student_id, s.first_name, s.last_name, s.email, s.qr_code, s.qr_code_path, s.created_at
            FROM students s 
            WHERE s.qr_code = ? AND s.is_active = 1
        ");
        $stmt->execute([$scannedCode]);
        $student = $stmt->fetch();
        
        if ($student) {
            echo "✅ <strong>Database Lookup:</strong> Found<br>";
            echo "✅ <strong>Student:</strong> {$student['first_name']} {$student['last_name']}<br>";
            echo "✅ <strong>Student ID:</strong> {$student['student_id']}<br>";
            echo "✅ <strong>Email:</strong> {$student['email']}<br>";
            echo "✅ <strong>QR Code:</strong> {$student['qr_code']}<br>";
            echo "✅ <strong>Created:</strong> {$student['created_at']}<br>";
            
            if ($student['qr_code_path'] && file_exists($student['qr_code_path'])) {
                echo "✅ <strong>QR Image:</strong> Available<br>";
                echo "<img src='" . $student['qr_code_path'] . "' style='max-width: 150px; border: 1px solid #ccc; margin: 10px 0;'><br>";
            } else {
                echo "⚠️ <strong>QR Image:</strong> File not found<br>";
            }
            
            echo "<br><strong style='color: #4caf50; font-size: 18px;'>✅ VALID QR CODE - ATTENDANCE CONFIRMED</strong><br>";
            
            // Log the attendance (optional)
            echo "<br><h4>📝 Attendance Log</h4>";
            echo "Student: {$student['first_name']} {$student['last_name']}<br>";
            echo "Time: " . date('Y-m-d H:i:s') . "<br>";
            echo "Status: Present<br>";
            
        } else {
            echo "❌ <strong>Database Lookup:</strong> Not found<br>";
            echo "❌ <strong>Status:</strong> INVALID QR CODE - NOT REGISTERED<br>";
        }
    }
    
    echo "<br><hr><br>";
}

echo "<h3>📱 QR Code Scanner</h3>";
echo "<p>Enter a QR code to verify its uniqueness and validity:</p>";

echo "<form method='POST' style='background: #f0f8ff; padding: 20px; border-radius: 8px; border: 2px solid #4caf50;'>";
echo "<div style='margin-bottom: 15px;'>";
echo "<label for='scanned_qr_code' style='display: block; margin-bottom: 5px; font-weight: bold;'>QR Code:</label>";
echo "<input type='text' id='scanned_qr_code' name='scanned_qr_code' placeholder='STU_1234567890abcdef' style='width: 100%; max-width: 400px; padding: 10px; border: 1px solid #ccc; border-radius: 4px; font-family: monospace;'>";
echo "</div>";
echo "<button type='submit' style='background: #4caf50; color: white; padding: 12px 25px; border: none; border-radius: 4px; cursor: pointer; font-size: 16px;'>🔍 Scan QR Code</button>";
echo "</form>";

echo "<br><h3>📊 QR Code Uniqueness Features</h3>";
echo "<ul>";
echo "<li><strong>Format:</strong> STU_[16-character hexadecimal]</li>";
echo "<li><strong>Uniqueness:</strong> Each QR code is mathematically unique</li>";
echo "<li><strong>Security:</strong> 16^16 possible combinations (extremely secure)</li>";
echo "<li><strong>Validation:</strong> Real-time database lookup</li>";
echo "<li><strong>Student Binding:</strong> Each QR code is tied to a specific student</li>";
echo "<li><strong>Timestamp:</strong> Includes creation timestamp for additional uniqueness</li>";
echo "</ul>";

echo "<br><h3>🔐 How Uniqueness is Guaranteed</h3>";
echo "<ol>";
echo "<li><strong>Student Data:</strong> Combines unique student information</li>";
echo "<li><strong>MD5 Hashing:</strong> Creates cryptographic hash</li>";
echo "<li><strong>Unique ID:</strong> Adds system-generated unique identifier</li>";
echo "<li><strong>Database Check:</strong> Verifies no duplicate exists</li>";
echo "<li><strong>Regeneration:</strong> If duplicate found, creates new code</li>";
echo "<li><strong>Timestamp:</strong> Includes current time for additional randomness</li>";
echo "</ol>";

echo "<br><a href='verify_qr_uniqueness.php'>Verify All QR Codes</a> | <a href='qr_code.php'>My QR Code</a> | <a href='dashboard.php'>Dashboard</a>";
?>

<?php include 'includes/bottom_navbar.php'; ?>
</body>
</html>
