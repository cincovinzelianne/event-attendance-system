<?php
// Test script to generate QR code with LLCC logo
require_once 'includes/auth.php';

echo "<h2>Test QR Code with LLCC Logo</h2>";

// Check if logo exists in multiple formats
$logoPaths = ['llcc-logo.jpeg', 'llcc-logo.png', 'llcc-logo.jpg'];
$logoPath = null;

foreach ($logoPaths as $path) {
    if (file_exists($path)) {
        $logoPath = $path;
        break;
    }
}

if ($logoPath) {
    echo "✅ LLCC logo found: $logoPath<br>";
    echo "Logo size: " . filesize($logoPath) . " bytes<br>";
    echo "Logo format: " . strtoupper(pathinfo($logoPath, PATHINFO_EXTENSION)) . "<br>";
} else {
    echo "❌ LLCC logo not found. Tried: " . implode(', ', $logoPaths) . "<br>";
    echo "Please make sure the logo file is in the project root directory.<br>";
}

// Test QR code generation with logo
try {
    $qrGenerator = new QRGenerator();
    $result = $qrGenerator->generateQRCode('TEST123', 'John', 'Doe', 'john.doe@llcc.edu.ph');
    
    if (isset($result['error'])) {
        echo "❌ Error: " . $result['error'] . "<br>";
    } else {
        echo "✅ QR Code generated: " . $result['qr_code'] . "<br>";
        echo "✅ QR Path: " . ($result['qr_url'] ?: 'NULL') . "<br>";
        
        if ($result['qr_url'] && file_exists($result['qr_url'])) {
            echo "✅ QR file exists: " . filesize($result['qr_url']) . " bytes<br>";
            echo "<h3>QR Code with LLCC Logo:</h3>";
            echo "<img src='" . $result['qr_url'] . "' style='max-width: 300px; border: 2px solid #ccc;'><br>";
            
            // Test if the QR code is still scannable
            echo "<h3>QR Code Test:</h3>";
            echo "Try scanning this QR code with your phone to verify it still works with the logo embedded.<br>";
        } else {
            echo "❌ QR file does not exist<br>";
        }
    }
} catch (Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "<br>";
}

echo "<br><a href='force_generate_qr.php'>Regenerate All QR Codes with Logo</a> | <a href='qr_code.php'>My QR Code</a>";
?>
