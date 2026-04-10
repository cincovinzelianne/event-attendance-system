<?php
/**
 * Fix Missing Database Tables
 * Creates admin and admin_sessions tables if they don't exist
 */

require_once 'config/database.php';

$response = [
    'success' => false,
    'message' => '',
    'tables_created' => []
];

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Read the SQL file containing admin table definitions
    $sqlFile = __DIR__ . '/create_admin_tables.sql';
    
    if (!file_exists($sqlFile)) {
        throw new Exception("SQL file not found: $sqlFile");
    }
    
    // Read SQL content
    $sqlContent = file_get_contents($sqlFile);
    
    // Remove comments and split into individual statements
    $statements = array_filter(array_map('trim', explode(';', $sqlContent)));
    
    foreach ($statements as $statement) {
        if (empty($statement) || strpos($statement, '--') === 0) {
            continue;
        }
        
        // Skip SHOW TABLES statement
        if (strpos(strtoupper($statement), 'SHOW TABLES') !== false) {
            continue;
        }
        
        try {
            $db->exec($statement);
            
            // Track created tables
            if (strpos(strtoupper($statement), 'CREATE TABLE') !== false) {
                if (preg_match('/CREATE TABLE IF NOT EXISTS\s+(\w+)/i', $statement, $matches)) {
                    $response['tables_created'][] = $matches[1];
                }
            }
        } catch (Exception $e) {
            // Continue if table already exists
            if (strpos($e->getMessage(), 'already exists') === false) {
                throw $e;
            }
        }
    }
    
    // Verify tables exist
    $stmt = $db->prepare("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA='event_attendance' AND TABLE_NAME IN ('admins', 'admin_sessions')");
    $stmt->execute();
    $existingTables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (in_array('admins', $existingTables) && in_array('admin_sessions', $existingTables)) {
        $response['success'] = true;
        $response['message'] = 'All required admin tables are now present in the database!';
        $response['tables_verified'] = $existingTables;
    } else {
        throw new Exception('Failed to verify table creation');
    }
    
} catch (Exception $e) {
    $response['message'] = 'Error: ' . $e->getMessage();
}

// If AJAX request, return JSON
if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// Otherwise, output HTML
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fix Missing Database Tables</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 500px;
            width: 100%;
            padding: 40px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .header h1 {
            color: #333;
            font-size: 24px;
            margin-bottom: 10px;
        }
        .header p {
            color: #666;
            font-size: 14px;
        }
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            gap: 12px;
            align-items: flex-start;
        }
        .alert.success {
            background: #d4edda;
            border: 2px solid #28a745;
            color: #155724;
        }
        .alert.error {
            background: #f8d7da;
            border: 2px solid #f5c6cb;
            color: #721c24;
        }
        .alert-icon {
            font-size: 20px;
            flex-shrink: 0;
        }
        .alert h3 {
            margin-bottom: 5px;
            font-size: 14px;
        }
        .alert p {
            font-size: 13px;
            line-height: 1.5;
        }
        .info-box {
            background: #f8f9ff;
            border-left: 4px solid #667eea;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .info-box h4 {
            color: #667eea;
            font-size: 13px;
            margin-bottom: 8px;
        }
        .info-box ul {
            margin-left: 20px;
            font-size: 13px;
            color: #666;
        }
        .info-box li {
            margin-bottom: 5px;
        }
        .button-group {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
        .btn {
            flex: 1;
            padding: 12px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: bold;
            font-size: 14px;
            transition: all 0.3s;
            text-decoration: none;
            text-align: center;
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        .btn-secondary {
            background: #f0f0f0;
            color: #333;
        }
        .btn-secondary:hover {
            background: #e0e0e0;
        }
        .code {
            background: #f5f5f5;
            padding: 8px;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
            font-size: 12px;
        }
        .loading {
            text-align: center;
            padding: 20px;
        }
        .spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #667eea;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto 15px;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔧 Database Repair</h1>
            <p>Fixing missing admin tables...</p>
        </div>

        <div id="result">
            <div class="loading">
                <div class="spinner"></div>
                <p style="color: #666; font-size: 14px;">Creating missing tables...</p>
            </div>
        </div>
    </div>

    <script>
        // Auto-run on page load
        window.addEventListener('load', function() {
            fixTables();
        });

        function fixTables() {
            fetch(window.location.href, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                displayResult(data);
            })
            .catch(error => {
                displayResult({
                    success: false,
                    message: 'Error: ' + error.message
                });
            });
        }

        function displayResult(data) {
            let html = '';
            
            if (data.success) {
                html = `
                    <div class="alert success">
                        <div class="alert-icon">✓</div>
                        <div>
                            <h3>Success!</h3>
                            <p>${data.message}</p>
                        </div>
                    </div>
                    
                    <div class="info-box">
                        <h4>✓ Tables Created:</h4>
                        <ul>
                            <li><strong>admins</strong> - Stores admin user accounts</li>
                            <li><strong>admin_sessions</strong> - Tracks admin login sessions</li>
                        </ul>
                    </div>
                    
                    <div class="button-group">
                        <a href="consolidation_summary.php" class="btn btn-primary">Next: Review Changes</a>
                        <a href="login.php" class="btn btn-secondary">Go to Login</a>
                    </div>
                `;
            } else {
                html = `
                    <div class="alert error">
                        <div class="alert-icon">✕</div>
                        <div>
                            <h3>Error</h3>
                            <p>${data.message}</p>
                        </div>
                    </div>
                    
                    <div class="info-box">
                        <h4>💡 What to do:</h4>
                        <ul>
                            <li>Check your database connection in <span class="code">config/database.php</span></li>
                            <li>Ensure the <span class="code">event_attendance</span> database exists</li>
                            <li>Try refreshing this page</li>
                        </ul>
                    </div>
                    
                    <div class="button-group">
                        <button onclick="location.reload()" class="btn btn-primary">Retry</button>
                        <a href="login.php" class="btn btn-secondary">Go to Login</a>
                    </div>
                `;
            }
            
            document.getElementById('result').innerHTML = html;
        }
    </script>
</body>
</html>
