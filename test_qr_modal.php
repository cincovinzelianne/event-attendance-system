<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Modal Test</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background: #f8f9fa;
            padding: 50px;
            text-align: center;
        }
        
        .test-container {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            max-width: 600px;
            margin: 0 auto;
        }
        
        h1 {
            color: #333;
            margin-bottom: 30px;
        }
        
        .test-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
            margin-bottom: 30px;
        }
        
        .test-btn {
            padding: 12px 24px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .test-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        }
        
        /* QR Code Modal Styles */
        .qr-modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(5px);
            animation: fadeIn 0.3s ease;
        }
        
        .qr-modal.show {
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .qr-modal-content {
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            max-width: 500px;
            width: 90%;
            max-height: 90vh;
            overflow: hidden;
            animation: slideIn 0.3s ease;
            position: relative;
        }
        
        .qr-modal-header {
            padding: 20px 25px;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #f8f9fa;
        }
        
        .qr-modal-header h3 {
            margin: 0;
            color: #2d3748;
            font-size: 20px;
            font-weight: 600;
        }
        
        .qr-modal-close {
            background: none;
            border: none;
            font-size: 20px;
            color: #718096;
            cursor: pointer;
            padding: 8px;
            border-radius: 6px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
        }
        
        .qr-modal-close:hover {
            background: #e2e8f0;
            color: #2d3748;
        }
        
        .qr-modal-body {
            padding: 25px;
            text-align: center;
        }
        
        .qr-image-container {
            margin-bottom: 25px;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 200px;
            background: #f8f9fa;
            border-radius: 12px;
            padding: 20px;
        }
        
        .qr-image {
            max-width: 100%;
            max-height: 300px;
            border-radius: 8px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }
        
        .qr-modal-actions {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .qr-modal-actions .btn {
            flex: 1;
            min-width: 140px;
            justify-content: center;
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }
        
        .btn-primary:hover {
            background: linear-gradient(135deg, #5a6fd8, #6a4190);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
            box-shadow: 0 4px 15px rgba(108, 117, 125, 0.3);
        }
        
        .btn-secondary:hover {
            background: #5a6268;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(108, 117, 125, 0.4);
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        @keyframes slideIn {
            from { 
                opacity: 0;
                transform: scale(0.9) translateY(-20px);
            }
            to { 
                opacity: 1;
                transform: scale(1) translateY(0);
            }
        }
    </style>
</head>
<body>
    <div class="test-container">
        <h1><i class="fas fa-qrcode"></i> QR Modal Test</h1>
        <p>Click the buttons below to test the QR code modal functionality:</p>
        
        <div class="test-buttons">
            <button class="test-btn" onclick="showQRModal('assets/qr_codes/qr_1234_1760496967.png', 'John Doe')">
                <i class="fas fa-qrcode"></i> Test QR 1
            </button>
            <button class="test-btn" onclick="showQRModal('assets/qr_codes/qr_TEST123_1760497369.png', 'Jane Smith')">
                <i class="fas fa-qrcode"></i> Test QR 2
            </button>
        </div>
        
        <p><strong>Features:</strong></p>
        <ul style="text-align: left; max-width: 400px; margin: 0 auto;">
            <li>✅ Modal opens with smooth animation</li>
            <li>✅ QR code displays in a centered modal</li>
            <li>✅ Download QR code functionality</li>
            <li>✅ Print QR code functionality</li>
            <li>✅ Close with X button, Escape key, or clicking outside</li>
            <li>✅ Responsive design for mobile devices</li>
        </ul>
    </div>

    <!-- QR Code Modal -->
    <div id="qrModal" class="qr-modal">
        <div class="qr-modal-content">
            <div class="qr-modal-header">
                <h3 id="qrModalTitle">QR Code</h3>
                <button class="qr-modal-close" onclick="closeQRModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="qr-modal-body">
                <div class="qr-image-container">
                    <img id="qrModalImage" src="" alt="QR Code" class="qr-image">
                </div>
                <div class="qr-modal-actions">
                    <button onclick="downloadQRCode()" class="btn btn-primary">
                        <i class="fas fa-download"></i> Download QR Code
                    </button>
                    <button onclick="printQRCode()" class="btn btn-secondary">
                        <i class="fas fa-print"></i> Print QR Code
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // QR Modal functions
        let currentQRImage = '';
        
        function showQRModal(qrImagePath, studentName) {
            currentQRImage = qrImagePath;
            document.getElementById('qrModalTitle').textContent = `${studentName}'s QR Code`;
            document.getElementById('qrModalImage').src = qrImagePath;
            document.getElementById('qrModal').classList.add('show');
            document.body.style.overflow = 'hidden'; // Prevent background scrolling
        }
        
        function closeQRModal() {
            document.getElementById('qrModal').classList.remove('show');
            document.body.style.overflow = 'auto'; // Restore scrolling
            // Clear the image source after animation
            setTimeout(() => {
                document.getElementById('qrModalImage').src = '';
            }, 300);
        }
        
        function downloadQRCode() {
            if (currentQRImage) {
                const link = document.createElement('a');
                link.href = currentQRImage;
                link.download = `qr_code_${Date.now()}.png`;
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            }
        }
        
        function printQRCode() {
            if (currentQRImage) {
                const printWindow = window.open('', '_blank');
                printWindow.document.write(`
                    <html>
                        <head>
                            <title>QR Code Print</title>
                            <style>
                                body { 
                                    text-align: center; 
                                    font-family: Arial, sans-serif;
                                    padding: 20px;
                                }
                                img { 
                                    max-width: 100%; 
                                    height: auto;
                                    border: 2px solid #333;
                                }
                                .print-title {
                                    margin-bottom: 20px;
                                    font-size: 18px;
                                    font-weight: bold;
                                }
                            </style>
                        </head>
                        <body>
                            <div class="print-title">Student QR Code</div>
                            <img src="${currentQRImage}" alt="QR Code">
                        </body>
                    </html>
                `);
                printWindow.document.close();
                printWindow.print();
            }
        }
        
        // Close modal when clicking outside
        document.addEventListener('click', function(e) {
            const modal = document.getElementById('qrModal');
            if (e.target === modal) {
                closeQRModal();
            }
        });
        
        // Close modal with Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeQRModal();
            }
        });
    </script>
</body>
</html>






