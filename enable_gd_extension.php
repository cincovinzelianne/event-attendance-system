<?php
// Script to help enable GD extension in XAMPP
echo "<h2>Enable GD Extension in XAMPP</h2>";

$phpIniPath = 'C:\\xampp\\php\\php.ini';
$phpIniPathAlt = 'C:\\xampp\\php\\php.ini';

echo "<h3>Current PHP Configuration:</h3>";
echo "PHP Version: " . phpversion() . "<br>";
echo "PHP SAPI: " . php_sapi_name() . "<br>";
echo "Loaded Extensions: " . (extension_loaded('gd') ? 'GD is loaded' : 'GD is NOT loaded') . "<br>";

echo "<h3>Steps to Enable GD Extension:</h3>";
echo "<ol>";
echo "<li><strong>Method 1 - XAMPP Control Panel:</strong><br>";
echo "   • Open XAMPP Control Panel<br>";
echo "   • Click 'Config' next to Apache<br>";
echo "   • Select 'PHP (php.ini)'<br>";
echo "   • Find the line: <code>;extension=gd</code><br>";
echo "   • Remove the semicolon: <code>extension=gd</code><br>";
echo "   • Save the file<br>";
echo "   • Restart Apache</li><br>";

echo "<li><strong>Method 2 - Manual Edit:</strong><br>";
echo "   • Navigate to: <code>$phpIniPath</code><br>";
echo "   • Open php.ini in a text editor<br>";
echo "   • Find: <code>;extension=gd</code><br>";
echo "   • Change to: <code>extension=gd</code><br>";
echo "   • Save the file<br>";
echo "   • Restart Apache in XAMPP</li><br>";

echo "<li><strong>Method 3 - Command Line (if you have access):</strong><br>";
echo "   • Open Command Prompt as Administrator<br>";
echo "   • Navigate to XAMPP directory<br>";
echo "   • Edit php.ini file</li>";
echo "</ol>";

echo "<h3>Alternative: Use QR Codes Without Logo</h3>";
echo "<p>If you can't enable GD extension, the system will still work but QR codes won't have the logo embedded.</p>";
echo "<p>You can still use the QR codes for attendance - they just won't have the LLCC logo in the center.</p>";

echo "<h3>Test Current Status:</h3>";
if (extension_loaded('gd')) {
    echo "✅ GD Extension is enabled!<br>";
    echo "<a href='test_logo_qr.php'>Test QR Code with Logo</a><br>";
} else {
    echo "❌ GD Extension is not enabled<br>";
    echo "<a href='test_qr_without_logo.php'>Test QR Code without Logo</a><br>";
}

echo "<br><a href='check_gd_extension.php'>Check GD Extension Status</a> | <a href='dashboard.php'>Dashboard</a>";
?>
