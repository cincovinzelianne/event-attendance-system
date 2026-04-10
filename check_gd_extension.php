<?php
// Check GD extension status and provide instructions
echo "<h2>PHP GD Extension Check</h2>";

// Check if GD extension is loaded
if (extension_loaded('gd')) {
    echo "✅ <strong>GD Extension:</strong> Loaded<br>";
    
    // Get GD version
    $gdInfo = gd_info();
    echo "✅ <strong>GD Version:</strong> " . $gdInfo['GD Version'] . "<br>";
    
    // Check specific functions
    $functions = [
        'imagecreatefrompng' => 'PNG Support',
        'imagecreatefromjpeg' => 'JPEG Support',
        'imagecreatefromgif' => 'GIF Support',
        'imagepng' => 'PNG Creation',
        'imagejpeg' => 'JPEG Creation',
        'imagecopy' => 'Image Copy',
        'imagecopyresampled' => 'Image Resize'
    ];
    
    echo "<h3>GD Functions Available:</h3>";
    foreach ($functions as $function => $description) {
        if (function_exists($function)) {
            echo "✅ $description: Available<br>";
        } else {
            echo "❌ $description: Not Available<br>";
        }
    }
    
    echo "<br><strong>🎉 GD Extension is working! QR codes with logos should work.</strong><br>";
    
} else {
    echo "❌ <strong>GD Extension:</strong> NOT LOADED<br>";
    echo "<br><h3>How to Enable GD Extension in XAMPP:</h3>";
    echo "<ol>";
    echo "<li>Open XAMPP Control Panel</li>";
    echo "<li>Click 'Config' next to Apache</li>";
    echo "<li>Select 'PHP (php.ini)'</li>";
    echo "<li>Find the line: <code>;extension=gd</code></li>";
    echo "<li>Remove the semicolon to make it: <code>extension=gd</code></li>";
    echo "<li>Save the file</li>";
    echo "<li>Restart Apache in XAMPP Control Panel</li>";
    echo "</ol>";
    
    echo "<br><h3>Alternative: Manual php.ini Edit</h3>";
    echo "<p>1. Navigate to: <code>C:\\xampp\\php\\php.ini</code></p>";
    echo "<p>2. Find: <code>;extension=gd</code></p>";
    echo "<p>3. Change to: <code>extension=gd</code></p>";
    echo "<p>4. Save and restart Apache</p>";
}

// Check PHP version
echo "<br><h3>PHP Information:</h3>";
echo "PHP Version: " . phpversion() . "<br>";
echo "PHP SAPI: " . php_sapi_name() . "<br>";

// Test basic image functions if GD is available
if (extension_loaded('gd')) {
    echo "<br><h3>GD Function Test:</h3>";
    
    try {
        // Test creating a simple image
        $testImage = imagecreate(100, 100);
        if ($testImage) {
            echo "✅ Basic image creation: Working<br>";
            imagedestroy($testImage);
        } else {
            echo "❌ Basic image creation: Failed<br>";
        }
        
        // Test PNG functions
        if (function_exists('imagecreatefrompng') && function_exists('imagepng')) {
            echo "✅ PNG functions: Available<br>";
        } else {
            echo "❌ PNG functions: Not available<br>";
        }
        
        // Test JPEG functions
        if (function_exists('imagecreatefromjpeg') && function_exists('imagejpeg')) {
            echo "✅ JPEG functions: Available<br>";
        } else {
            echo "❌ JPEG functions: Not available<br>";
        }
        
    } catch (Exception $e) {
        echo "❌ GD Test Error: " . $e->getMessage() . "<br>";
    }
}

echo "<br><a href='test_logo_qr.php'>Test QR Code with Logo</a> | <a href='dashboard.php'>Dashboard</a>";
?>
