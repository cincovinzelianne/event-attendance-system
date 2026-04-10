<?php
require_once 'config/database.php';

class QRGenerator {
    private $db;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }
    
    /**
     * Generate a unique QR code for a student
     */
    public function generateQRCode($studentId, $firstName, $lastName, $email) {
        try {
            // Create unique QR data
            $qrData = [
                'student_id' => $studentId,
                'name' => $firstName . ' ' . $lastName,
                'email' => $email,
                'timestamp' => time(),
                'type' => 'student_attendance'
            ];
            
            $qrString = base64_encode(json_encode($qrData));
            $qrCode = 'STU_' . substr(md5($qrString . uniqid()), 0, 16);
            
            // Ensure QR code is unique
            while ($this->isQRCodeExists($qrCode)) {
                $qrCode = 'STU_' . substr(md5($qrString . uniqid() . rand()), 0, 16);
            }
            
            return [
                'qr_code' => $qrCode,
                'qr_data' => $qrString,
                'qr_url' => $this->generateQRImage($qrCode, $studentId)
            ];
            
        } catch (Exception $e) {
            return ['error' => 'Failed to generate QR code: ' . $e->getMessage()];
        }
    }
    
    /**
     * Check if QR code already exists
     */
    private function isQRCodeExists($qrCode) {
        $stmt = $this->db->prepare("SELECT id FROM students WHERE qr_code = ?");
        $stmt->execute([$qrCode]);
        return $stmt->fetch() !== false;
    }
    
    /**
     * Generate QR code image with LLCC logo embedded
     */
    private function generateQRImage($qrCode, $studentId) {
        return $this->buildRemoteQRCodeUrl($qrCode);
    }

    public function getQRCodeImagePath($qrCode) {
        return $this->buildRemoteQRCodeUrl($qrCode);
    }

    private function buildRemoteQRCodeUrl($qrCode) {
        $qrData = urlencode($qrCode);
        return "https://api.qrserver.com/v1/create-qr-code/?size=300x300&data={$qrData}&ecc=H";
    }
    
    /**
     * Embed LLCC logo in the center of QR code
     */
    private function embedLogoInQR($qrImagePath, $studentId) {
        try {
            // Check for LLCC logo in multiple formats
            $logoPaths = ['llcc-logo.jpeg', 'llcc-logo.png', 'llcc-logo.jpg'];
            $logoPath = null;
            
            foreach ($logoPaths as $path) {
                if (file_exists($path)) {
                    $logoPath = $path;
                    break;
                }
            }
            
            if (!$logoPath) {
                error_log("LLCC logo not found. Tried: " . implode(', ', $logoPaths));
                return $qrImagePath; // Return original QR code without logo
            }
            
            // Load QR code image
            $qrImage = imagecreatefrompng($qrImagePath);
            if (!$qrImage) {
                error_log("Failed to load QR code image: $qrImagePath");
                return $qrImagePath;
            }
            
            // Load LLCC logo (handle both JPEG and PNG)
            $logoImage = null;
            $extension = strtolower(pathinfo($logoPath, PATHINFO_EXTENSION));
            
            if ($extension === 'png') {
                $logoImage = imagecreatefrompng($logoPath);
            } elseif (in_array($extension, ['jpg', 'jpeg'])) {
                $logoImage = imagecreatefromjpeg($logoPath);
            } else {
                error_log("Unsupported logo format: $extension");
                imagedestroy($qrImage);
                return $qrImagePath;
            }
            
            if (!$logoImage) {
                error_log("Failed to load logo image: $logoPath");
                imagedestroy($qrImage);
                return $qrImagePath;
            }
            
            // Get dimensions
            $qrWidth = imagesx($qrImage);
            $qrHeight = imagesy($qrImage);
            $logoWidth = imagesx($logoImage);
            $logoHeight = imagesy($logoImage);
            
            // Calculate logo size (about 20% of QR code size)
            $logoSize = min($qrWidth, $qrHeight) * 0.2;
            $logoNewWidth = $logoSize;
            $logoNewHeight = $logoSize;
            
            // Resize logo to fit in QR code center
            $resizedLogo = imagecreatetruecolor($logoNewWidth, $logoNewHeight);
            imagecopyresampled($resizedLogo, $logoImage, 0, 0, 0, 0, $logoNewWidth, $logoNewHeight, $logoWidth, $logoHeight);
            
            // Calculate position to center the logo
            $logoX = ($qrWidth - $logoNewWidth) / 2;
            $logoY = ($qrHeight - $logoNewHeight) / 2;
            
            // Create a white background for the logo area
            $white = imagecolorallocate($qrImage, 255, 255, 255);
            $logoAreaSize = $logoNewWidth + 10; // Add some padding
            $logoAreaX = ($qrWidth - $logoAreaSize) / 2;
            $logoAreaY = ($qrHeight - $logoAreaSize) / 2;
            
            // Fill the logo area with white background
            imagefilledrectangle($qrImage, $logoAreaX, $logoAreaY, $logoAreaX + $logoAreaSize, $logoAreaY + $logoAreaSize, $white);
            
            // Copy the resized logo onto the QR code
            imagecopy($qrImage, $resizedLogo, $logoX, $logoY, 0, 0, $logoNewWidth, $logoNewHeight);
            
            // Save the modified QR code
            $finalPath = str_replace('.png', '_with_logo.png', $qrImagePath);
            if (imagepng($qrImage, $finalPath)) {
                // Clean up memory
                imagedestroy($qrImage);
                imagedestroy($logoImage);
                imagedestroy($resizedLogo);
                
                // Remove original QR code and rename the new one
                unlink($qrImagePath);
                rename($finalPath, $qrImagePath);
                
                return $qrImagePath;
            } else {
                error_log("Failed to save QR code with logo");
                imagedestroy($qrImage);
                imagedestroy($logoImage);
                imagedestroy($resizedLogo);
                return $qrImagePath;
            }
            
        } catch (Exception $e) {
            error_log("Error embedding logo in QR code: " . $e->getMessage());
            return $qrImagePath;
        }
    }
    
    /**
     * Create a fallback QR code representation
     */
    private function createFallbackQR($qrCode, $filepath) {
        $svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="300" height="300" viewBox="0 0 300 300">
  <rect width="300" height="300" fill="#ffffff"/>
  <rect x="18" y="18" width="264" height="264" rx="16" fill="#f8fafc" stroke="#0f172a" stroke-width="6"/>
  <text x="150" y="110" text-anchor="middle" font-family="Arial, sans-serif" font-size="20" font-weight="700" fill="#0f172a">LLCC QR</text>
  <text x="150" y="155" text-anchor="middle" font-family="Courier New, monospace" font-size="14" fill="#334155">{$qrCode}</text>
  <text x="150" y="195" text-anchor="middle" font-family="Arial, sans-serif" font-size="12" fill="#64748b">QR fallback preview</text>
</svg>
SVG;
        
        $svgFile = str_replace('.png', '.svg', $filepath);
        if (file_put_contents($svgFile, $svg)) {
            return $svgFile;
        }
        
        return null;
    }
    
    /**
     * Validate QR code
     */
    public function validateQRCode($qrCode) {
        try {
            $stmt = $this->db->prepare("
                SELECT s.id, s.student_id, s.first_name, s.last_name, s.email, s.qr_code 
                FROM students s 
                WHERE s.qr_code = ? AND s.is_active = 1
            ");
            $stmt->execute([$qrCode]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            return false;
        }
    }
    
    /**
     * Get QR code statistics
     */
    public function getQRCodeStats() {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    COUNT(*) as total_students,
                    COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 END) as new_this_week,
                    COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as new_this_month
                FROM students 
                WHERE is_active = 1
            ");
            $stmt->execute();
            return $stmt->fetch();
        } catch (PDOException $e) {
            return false;
        }
    }
    
    /**
     * Get attendance analytics
     */
    public function getAttendanceAnalytics() {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    DATE(attendance_time) as date,
                    COUNT(*) as attendance_count,
                    COUNT(CASE WHEN status = 'present' THEN 1 END) as present_count,
                    COUNT(CASE WHEN status = 'late' THEN 1 END) as late_count
                FROM attendance 
                WHERE attendance_time >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                GROUP BY DATE(attendance_time)
                ORDER BY date DESC
            ");
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }
    
    /**
     * Get course distribution
     */
    public function getCourseDistribution() {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    course,
                    COUNT(*) as student_count
                FROM students 
                WHERE is_active = 1 AND course IS NOT NULL AND course != ''
                GROUP BY course
                ORDER BY student_count DESC
            ");
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }
    
    /**
     * Get year level distribution
     */
    public function getYearLevelDistribution() {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    year_level,
                    COUNT(*) as student_count
                FROM students 
                WHERE is_active = 1 AND year_level IS NOT NULL AND year_level != ''
                GROUP BY year_level
                ORDER BY 
                    CASE year_level
                        WHEN '1st Year' THEN 1
                        WHEN '2nd Year' THEN 2
                        WHEN '3rd Year' THEN 3
                        WHEN '4th Year' THEN 4
                        WHEN '5th Year' THEN 5
                        ELSE 6
                    END
            ");
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }
}
?>
