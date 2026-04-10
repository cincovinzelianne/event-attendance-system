<?php
// Test QR code generation without logo (fallback)
require_once 'includes/auth.php';

echo "<h2>Test QR Code Generation (Without Logo)</h2>";

// Check GD extension
if (!extension_loaded('gd')) {
    echo "⚠️ <strong>Warning:</strong> GD extension not loaded. QR codes will be generated without logo embedding.<br>";
    echo "<a href='check_gd_extension.php'>Check GD Extension Status</a><br><br>";
}

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
            echo "<h3>Generated QR Code:</h3>";
            echo "<img src='" . $result['qr_url'] . "' style='max-width: 300px; border: 2px solid #ccc;'><br>";
            echo "<p><strong>Note:</strong> This QR code was generated without logo embedding due to GD extension not being available.</p>";
        } else {
            echo "❌ QR file does not exist<br>";
        }
    }
} catch (Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "<br>";
}

echo "<br><a href='check_gd_extension.php'>Check GD Extension</a> | <a href='dashboard.php'>Dashboard</a>";
?>
