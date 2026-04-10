<?php
require_once 'includes/auth.php';

$auth = new Auth();
$currentUser = $auth->getCurrentUser();

if (!$currentUser) {
    header('Location: signin.php');
    exit;
}

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['qr_code'])) {
    $qrCode = trim($_POST['qr_code']);
    $student = $auth->getQRCodeData($qrCode);
    
    if ($student) {
        $message = "QR Code validated for: " . $student['first_name'] . " " . $student['last_name'];
        $messageType = 'success';
    } else {
        $message = "Invalid QR Code";
        $messageType = 'error';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Code Scanner - Event Attendance</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .scanner-container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .scanner-card {
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            text-align: center;
        }
        
        .scanner-header {
            margin-bottom: 30px;
        }
        
        .scanner-header h1 {
            font-size: 2.5rem;
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 10px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .scanner-header p {
            color: #718096;
            font-size: 1.1rem;
        }
        
        .scanner-area {
            background: #f7fafc;
            border: 2px dashed #e2e8f0;
            border-radius: 15px;
            padding: 40px;
            margin: 30px 0;
            position: relative;
        }
        
        .scanner-icon {
            font-size: 4rem;
            color: #667eea;
            margin-bottom: 20px;
        }
        
        .scanner-text {
            color: #4a5568;
            font-size: 1.1rem;
            margin-bottom: 20px;
        }
        
        .manual-input {
            margin-top: 30px;
        }
        
        .manual-input h3 {
            color: #2d3748;
            margin-bottom: 15px;
        }
        
        .input-group {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .input-group input {
            flex: 1;
            padding: 15px;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            font-size: 1rem;
            font-family: 'Roboto', sans-serif;
        }
        
        .input-group input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .btn {
            padding: 15px 30px;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            font-family: 'Roboto', sans-serif;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);
        }
        
        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }
        
        .back-link:hover {
            color: #5a67d8;
        }
        
        .header-links {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .signout-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 14px;
            border-radius: 10px;
            text-decoration: none;
            color: #b91c1c;
            font-weight: 600;
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.2);
        }

        .signout-link:hover {
            background: rgba(239, 68, 68, 0.16);
        }
        
        .camera-preview {
            width: 100%;
            max-width: 400px;
            height: 300px;
            background: #000;
            border-radius: 10px;
            margin: 20px auto;
            display: none;
        }
        
        .scanner-overlay {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 200px;
            height: 200px;
            border: 2px solid #667eea;
            border-radius: 10px;
            pointer-events: none;
        }
        
        .scanner-overlay::before,
        .scanner-overlay::after {
            content: '';
            position: absolute;
            width: 20px;
            height: 20px;
            border: 3px solid #667eea;
        }
        
        .scanner-overlay::before {
            top: -3px;
            left: -3px;
            border-right: none;
            border-bottom: none;
        }
        
        .scanner-overlay::after {
            bottom: -3px;
            right: -3px;
            border-left: none;
            border-top: none;
        }
        
        @media (max-width: 768px) {
            .scanner-container {
                padding: 15px;
            }
            
            .scanner-card {
                padding: 25px 20px;
            }
            
            .scanner-header h1 {
                font-size: 2rem;
            }
            
            .input-group {
                flex-direction: column;
            }
        }
    </style>
</head>
<body style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh;">
    <div class="scanner-container">
        <div class="header-links">
            <a href="dashboard.php" class="back-link">← Back to Dashboard</a>
            <a href="signout.php" class="signout-link"><i class="fas fa-right-from-bracket"></i> Sign out</a>
        </div>
        
        <div class="scanner-card">
            <div class="scanner-header">
                <h1>QR Code Scanner</h1>
                <p>Scan or manually enter QR codes for attendance tracking</p>
            </div>
            
            <?php if ($message): ?>
                <div class="message <?php echo $messageType; ?>" style="margin-bottom: 20px;">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>
            
            <div class="scanner-area">
                <div class="scanner-icon">📱</div>
                <div class="scanner-text">Position QR code within the camera view</div>
                <button id="startCamera" class="btn btn-primary">Start Camera</button>
                <button id="stopCamera" class="btn btn-primary" style="display: none; background: #e53e3e;">Stop Camera</button>
                <video id="cameraPreview" class="camera-preview" autoplay></video>
                <div class="scanner-overlay" style="display: none;"></div>
            </div>
            
            <div class="manual-input">
                <h3>Manual QR Code Entry</h3>
                <form method="POST" action="">
                    <div class="input-group">
                        <input type="text" name="qr_code" placeholder="Enter QR code manually" required>
                        <button type="submit" class="btn btn-primary">Validate</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script>
        let stream = null;
        const startBtn = document.getElementById('startCamera');
        const stopBtn = document.getElementById('stopCamera');
        const preview = document.getElementById('cameraPreview');
        const overlay = document.querySelector('.scanner-overlay');
        const scannerText = document.querySelector('.scanner-text');
        
        startBtn.addEventListener('click', startCamera);
        stopBtn.addEventListener('click', stopCamera);
        
        async function startCamera() {
            try {
                stream = await navigator.mediaDevices.getUserMedia({ 
                    video: { 
                        facingMode: 'environment',
                        width: { ideal: 1280 },
                        height: { ideal: 720 }
                    } 
                });
                
                preview.srcObject = stream;
                preview.style.display = 'block';
                overlay.style.display = 'block';
                startBtn.style.display = 'none';
                stopBtn.style.display = 'inline-block';
                scannerText.textContent = 'Scanning for QR codes...';
                
                // Start QR code detection
                detectQRCode();
                
            } catch (err) {
                console.error('Error accessing camera:', err);
                alert('Unable to access camera. Please check permissions.');
            }
        }
        
        function stopCamera() {
            if (stream) {
                stream.getTracks().forEach(track => track.stop());
                stream = null;
            }
            
            preview.style.display = 'none';
            overlay.style.display = 'none';
            startBtn.style.display = 'inline-block';
            stopBtn.style.display = 'none';
            scannerText.textContent = 'Position QR code within the camera view';
        }
        
        function detectQRCode() {
            // Simple QR code detection simulation
            // In a real implementation, you would use a library like QuaggaJS or ZXing
            const canvas = document.createElement('canvas');
            const ctx = canvas.getContext('2d');
            
            setInterval(() => {
                if (preview.videoWidth && preview.videoHeight) {
                    canvas.width = preview.videoWidth;
                    canvas.height = preview.videoHeight;
                    ctx.drawImage(preview, 0, 0);
                    
                    // Simulate QR code detection
                    // In reality, you would process the canvas image here
                    const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
                    
                    // This is a placeholder - replace with actual QR detection
                    // For now, we'll just show a message that detection is active
                }
            }, 100);
        }
        
        // Handle page visibility change
        document.addEventListener('visibilitychange', () => {
            if (document.hidden && stream) {
                stopCamera();
            }
        });
        
        // Clean up on page unload
        window.addEventListener('beforeunload', () => {
            if (stream) {
                stopCamera();
            }
        });
    </script>
</body>
</html>
