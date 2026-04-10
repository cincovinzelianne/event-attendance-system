<?php
// Simple QR code test
echo "<h2>Simple QR Code Test</h2>";

// Test 1: Direct API test
$testData = "STU_test123";
$size = "300x300";

$apis = [
    "Google Charts" => "https://chart.googleapis.com/chart?chs={$size}&cht=qr&chl={$testData}",
    "QR Server" => "https://api.qrserver.com/v1/create-qr-code/?size={$size}&data={$testData}",
    "QuickChart" => "https://quickchart.io/qr?text={$testData}&size={$size}"
];

foreach ($apis as $name => $url) {
    echo "<h3>Testing $name</h3>";
    echo "URL: <a href='$url' target='_blank'>$url</a><br>";
    
    $context = stream_context_create([
        'http' => [
            'timeout' => 10,
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
        ]
    ]);
    
    $imageData = @file_get_contents($url, false, $context);
    
    if ($imageData !== false && strlen($imageData) > 0) {
        echo "✅ Success: Got " . strlen($imageData) . " bytes<br>";
        
        // Save test image
        $filename = "test_qr_" . strtolower(str_replace(' ', '_', $name)) . ".png";
        if (file_put_contents($filename, $imageData)) {
            echo "✅ Saved as: $filename<br>";
            echo "<img src='$filename' style='max-width: 200px; border: 1px solid #ccc;'><br>";
        } else {
            echo "❌ Failed to save file<br>";
        }
    } else {
        echo "❌ Failed to get image data<br>";
    }
    echo "<br>";
}

// Test 2: Check if we can create the directory and write files
echo "<h3>Directory Test</h3>";
$qrDir = 'assets/qr_codes/';
if (!file_exists($qrDir)) {
    if (mkdir($qrDir, 0755, true)) {
        echo "✅ Created directory: $qrDir<br>";
    } else {
        echo "❌ Failed to create directory: $qrDir<br>";
    }
} else {
    echo "✅ Directory exists: $qrDir<br>";
}

if (is_writable($qrDir)) {
    echo "✅ Directory is writable<br>";
} else {
    echo "❌ Directory is not writable<br>";
}

// Test 3: Try to generate a QR code using our class
echo "<h3>Class Test</h3>";
try {
    require_once 'includes/qr_generator.php';
    $qrGenerator = new QRGenerator();
    $result = $qrGenerator->generateQRCode('TEST123', 'John', 'Doe', 'john.doe@llcc.edu.ph');
    
    if (isset($result['error'])) {
        echo "❌ Error: " . $result['error'] . "<br>";
    } else {
        echo "✅ QR Code: " . $result['qr_code'] . "<br>";
        echo "✅ QR URL: " . ($result['qr_url'] ?: 'NULL') . "<br>";
        
        if ($result['qr_url'] && file_exists($result['qr_url'])) {
            echo "✅ QR file exists and is " . filesize($result['qr_url']) . " bytes<br>";
            echo "<img src='" . $result['qr_url'] . "' style='max-width: 200px; border: 1px solid #ccc;'><br>";
        } else {
            echo "❌ QR file does not exist<br>";
        }
    }
} catch (Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "<br>";
}

echo "<br><a href='debug_qr.php'>Back to Debug</a>";
?>
