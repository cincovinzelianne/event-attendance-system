<?php
require_once 'includes/admin_auth.php';
require_once 'includes/google_calendar.php';

$adminAuth = new AdminAuth();
$currentAdmin = $adminAuth->requireAuth();

$message = '';
$messageType = '';

// Handle Google Calendar OAuth callback
if (isset($_GET['code'])) {
    $googleCalendar = new GoogleCalendar();
    if ($googleCalendar->handleCallback($_GET['code'])) {
        $message = 'Google Calendar integration successful!';
        $messageType = 'success';
    } else {
        $message = 'Failed to connect to Google Calendar';
        $messageType = 'error';
    }
}

// Check if already connected
$isConnected = file_exists('config/token.json');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Google Calendar Setup - Admin Panel</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <!-- Include Admin Sidebar -->
    <?php include 'includes/admin_sidebar.php'; ?>
    
    <!-- Main Content -->
    <div class="admin-content">
        <div class="admin-header">
            <div class="header-content">
                <h1><i class="fas fa-calendar-plus"></i> Google Calendar Integration</h1>
                <div class="header-actions">
                    <a href="admin_events.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Events
                    </a>
                </div>
            </div>
        </div>
        
        <div class="admin-main">
            <div class="setup-container">
                <?php if ($message): ?>
                    <div class="message <?php echo $messageType; ?>">
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>
                
                <div class="setup-card">
                    <div class="setup-header">
                        <div class="setup-icon">
                            <i class="fab fa-google"></i>
                        </div>
                        <h2>Google Calendar Integration</h2>
                        <p>Connect your Google Calendar to automatically create events and sync with your calendar.</p>
                    </div>
                    
                    <?php if ($isConnected): ?>
                        <div class="setup-status connected">
                            <i class="fas fa-check-circle"></i>
                            <h3>Connected to Google Calendar</h3>
                            <p>Your Google Calendar is successfully connected. Events will be automatically created in your calendar.</p>
                            
                            <div class="setup-actions">
                                <button class="btn btn-primary" onclick="testConnection()">
                                    <i class="fas fa-test-tube"></i> Test Connection
                                </button>
                                <button class="btn btn-danger" onclick="disconnectCalendar()">
                                    <i class="fas fa-unlink"></i> Disconnect
                                </button>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="setup-status not-connected">
                            <i class="fas fa-exclamation-triangle"></i>
                            <h3>Not Connected</h3>
                            <p>Connect your Google Calendar to enable automatic event creation and synchronization.</p>
                            
                            <div class="setup-requirements">
                                <h4>Setup Requirements:</h4>
                                <ol>
                                    <li>Create a Google Cloud Project</li>
                                    <li>Enable Google Calendar API</li>
                                    <li>Create OAuth 2.0 credentials</li>
                                    <li>Download credentials JSON file</li>
                                    <li>Place credentials in <code>config/google_credentials.json</code></li>
                                </ol>
                            </div>
                            
                            <div class="setup-actions">
                                <?php
                                $googleCalendar = new GoogleCalendar();
                                $authUrl = $googleCalendar->getAuthUrl();
                                ?>
                                <a href="<?php echo $authUrl; ?>" class="btn btn-primary">
                                    <i class="fab fa-google"></i> Connect to Google Calendar
                                </a>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="setup-info">
                    <h3><i class="fas fa-info-circle"></i> How it Works</h3>
                    <div class="info-grid">
                        <div class="info-item">
                            <i class="fas fa-plus"></i>
                            <h4>Create Events</h4>
                            <p>When you create an event in the system, it will automatically be added to your Google Calendar.</p>
                        </div>
                        <div class="info-item">
                            <i class="fas fa-sync"></i>
                            <h4>Sync Updates</h4>
                            <p>Any changes to events will be reflected in your Google Calendar in real-time.</p>
                        </div>
                        <div class="info-item">
                            <i class="fas fa-share"></i>
                            <h4>Share Events</h4>
                            <p>Events can be shared with attendees through Google Calendar invitations.</p>
                        </div>
                        <div class="info-item">
                            <i class="fas fa-bell"></i>
                            <h4>Notifications</h4>
                            <p>Attendees will receive calendar notifications and reminders automatically.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <style>
    .setup-container {
        max-width: 800px;
        margin: 0 auto;
        padding: 20px;
    }
    
    .setup-card {
        background: white;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        overflow: hidden;
        margin-bottom: 30px;
    }
    
    .setup-header {
        padding: 30px;
        text-align: center;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }
    
    .setup-icon {
        font-size: 48px;
        margin-bottom: 15px;
    }
    
    .setup-header h2 {
        margin: 0 0 10px 0;
        font-size: 24px;
    }
    
    .setup-header p {
        margin: 0;
        opacity: 0.9;
    }
    
    .setup-status {
        padding: 30px;
        text-align: center;
    }
    
    .setup-status.connected {
        background: #d4edda;
        border: 1px solid #c3e6cb;
    }
    
    .setup-status.not-connected {
        background: #fff3cd;
        border: 1px solid #ffeaa7;
    }
    
    .setup-status i {
        font-size: 48px;
        margin-bottom: 15px;
        display: block;
    }
    
    .setup-status.connected i {
        color: #28a745;
    }
    
    .setup-status.not-connected i {
        color: #ffc107;
    }
    
    .setup-status h3 {
        margin: 0 0 10px 0;
        color: #333;
    }
    
    .setup-status p {
        margin: 0 0 20px 0;
        color: #666;
    }
    
    .setup-requirements {
        text-align: left;
        background: #f8f9fa;
        padding: 20px;
        border-radius: 6px;
        margin: 20px 0;
    }
    
    .setup-requirements h4 {
        margin: 0 0 15px 0;
        color: #333;
    }
    
    .setup-requirements ol {
        margin: 0;
        padding-left: 20px;
    }
    
    .setup-requirements li {
        margin-bottom: 8px;
        color: #666;
    }
    
    .setup-requirements code {
        background: #e9ecef;
        padding: 2px 6px;
        border-radius: 3px;
        font-family: monospace;
    }
    
    .setup-actions {
        display: flex;
        gap: 15px;
        justify-content: center;
        flex-wrap: wrap;
    }
    
    .setup-info {
        background: white;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        padding: 30px;
    }
    
    .setup-info h3 {
        margin: 0 0 20px 0;
        color: #333;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .info-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
    }
    
    .info-item {
        text-align: center;
        padding: 20px;
        background: #f8f9fa;
        border-radius: 8px;
    }
    
    .info-item i {
        font-size: 32px;
        color: #667eea;
        margin-bottom: 15px;
        display: block;
    }
    
    .info-item h4 {
        margin: 0 0 10px 0;
        color: #333;
    }
    
    .info-item p {
        margin: 0;
        color: #666;
        font-size: 14px;
    }
    
    .btn {
        padding: 12px 24px;
        border: none;
        border-radius: 6px;
        font-size: 14px;
        font-weight: 500;
        cursor: pointer;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: all 0.3s ease;
    }
    
    .btn-primary {
        background: #667eea;
        color: white;
    }
    
    .btn-primary:hover {
        background: #5a6fd8;
        transform: translateY(-1px);
    }
    
    .btn-secondary {
        background: #6c757d;
        color: white;
    }
    
    .btn-danger {
        background: #dc3545;
        color: white;
    }
    
    .btn-danger:hover {
        background: #c82333;
    }
    
    .message {
        padding: 15px;
        border-radius: 6px;
        margin-bottom: 20px;
        font-weight: 500;
    }
    
    .message.success {
        background: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }
    
    .message.error {
        background: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }
    </style>
    
    <script>
    function testConnection() {
        // Implement test connection functionality
        alert('Testing Google Calendar connection...');
    }
    
    function disconnectCalendar() {
        if (confirm('Are you sure you want to disconnect Google Calendar? This will stop automatic event creation.')) {
            // Implement disconnect functionality
            window.location.href = 'admin_disconnect_calendar.php';
        }
    }
    </script>
    
    <script src="assets/js/script.js"></script>
</body>
</html>






