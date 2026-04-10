<?php
// Debug script to test QR code generation
require_once 'includes/auth.php';

echo "<h2>QR Code Generation Debug</h2>";

// Test 1: Check if directories exist and are writable
echo "<h3>1. Directory Check</h3>";
$qrDir = 'assets/qr_codes/';
echo "QR Directory: $qrDir<br>";
echo "Directory exists: " . (file_exists($qrDir) ? 'YES' : 'NO') . "<br>";
echo "Directory writable: " . (is_writable($qrDir) ? 'YES' : 'NO') . "<br>";

if (!file_exists($qrDir)) {
    echo "Creating directory...<br>";
    if (mkdir($qrDir, 0755, true)) {
        echo "Directory created successfully!<br>";
    } else {
        echo "Failed to create directory!<br>";
    }
}

// Test 2: Check database connection
echo "<h3>2. Database Connection</h3>";
try {
    $auth = new Auth();
    echo "Database connection: OK<br>";
} catch (Exception $e) {
    echo "Database connection failed: " . $e->getMessage() . "<br>";
}

// Test 3: Check if students table has QR columns
echo "<h3>3. Database Structure Check</h3>";
try {
    $database = new Database();
    $db = $database->getConnection();
    
    $stmt = $db->prepare("DESCRIBE students");
    $stmt->execute();
    $columns = $stmt->fetchAll();
    
    $hasQrCode = false;
    $hasQrPath = false;
    
    foreach ($columns as $column) {
        if ($column['Field'] === 'qr_code') {
            $hasQrCode = true;
        }
        if ($column['Field'] === 'qr_code_path') {
            $hasQrPath = true;
        }
    }
    
    echo "qr_code column exists: " . ($hasQrCode ? 'YES' : 'NO') . "<br>";
    echo "qr_code_path column exists: " . ($hasQrPath ? 'YES' : 'NO') . "<br>";
    
} catch (Exception $e) {
    echo "Database structure check failed: " . $e->getMessage() . "<br>";
}

// Test 4: Test QR code generation
echo "<h3>4. QR Code Generation Test</h3>";
try {
    $qrGenerator = new QRGenerator();
    $result = $qrGenerator->generateQRCode('TEST123', 'John', 'Doe', 'john.doe@llcc.edu.ph');
    
    if (isset($result['error'])) {
        echo "QR Generation failed: " . $result['error'] . "<br>";
    } else {
        echo "QR Code generated: " . $result['qr_code'] . "<br>";
        echo "QR Image path: " . ($result['qr_url'] ?: 'NULL') . "<br>";
        
        if ($result['qr_url'] && file_exists($result['qr_url'])) {
            echo "QR Image file exists: YES<br>";
            echo "QR Image size: " . filesize($result['qr_url']) . " bytes<br>";
        } else {
            echo "QR Image file exists: NO<br>";
        }
    }
} catch (Exception $e) {
    echo "QR Generation test failed: " . $e->getMessage() . "<br>";
}

// Test 5: Check existing students
echo "<h3>5. Existing Students Check</h3>";
try {
    $stmt = $db->prepare("SELECT student_id, first_name, last_name, qr_code, qr_code_path FROM students LIMIT 5");
    $stmt->execute();
    $students = $stmt->fetchAll();
    
    echo "Found " . count($students) . " students:<br>";
    foreach ($students as $student) {
        echo "- " . $student['first_name'] . " " . $student['last_name'] . " (ID: " . $student['student_id'] . ")<br>";
        echo "  QR Code: " . ($student['qr_code'] ?: 'NULL') . "<br>";
        echo "  QR Path: " . ($student['qr_code_path'] ?: 'NULL') . "<br>";
        if ($student['qr_code_path'] && file_exists($student['qr_code_path'])) {
            echo "  QR File exists: YES<br>";
        } else {
            echo "  QR File exists: NO<br>";
        }
        echo "<br>";
    }
} catch (Exception $e) {
    echo "Students check failed: " . $e->getMessage() . "<br>";
}

echo "<h3>6. Recommendations</h3>";
echo "1. Make sure the 'assets/qr_codes/' directory exists and is writable<br>";
echo "2. Run the SQL ALTER statements to add QR code columns<br>";
echo "3. Check your internet connection (Google Charts API needs internet)<br>";
echo "4. Check PHP error logs for more details<br>";
?>
