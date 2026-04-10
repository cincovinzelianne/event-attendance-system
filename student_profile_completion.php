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

function studentProfileColumns(PDO $db) {
    static $columns = null;

    if ($columns !== null) {
        return $columns;
    }

    $columns = [
        'profile_completed' => false,
        'profile_completed_at' => false,
    ];

    try {
        $stmt = $db->query("SHOW COLUMNS FROM students");
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $column) {
            if (isset($columns[$column['Field']])) {
                $columns[$column['Field']] = true;
            }
        }
    } catch (Exception $e) {
        // Keep defaults when schema inspection is unavailable.
    }

    return $columns;
}

// Check if profile is already completed
$profileCompleted = !empty($currentUser['profile_completed']) || (!empty($currentUser['course']) && !empty($currentUser['year_level']));
if ($profileCompleted) {
    header('Location: dashboard.php');
    exit;
}

// Handle profile completion
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $course = trim($_POST['course']);
    $year_level = trim($_POST['year_level']);
    
    if (empty($course) || empty($year_level)) {
        $message = 'Please select both course and year level';
        $messageType = 'error';
    } else {
        try {
            $database = new Database();
            $db = $database->getConnection();
            $availableColumns = studentProfileColumns($db);
            
            $setClauses = ['course = ?', 'year_level = ?'];
            $params = [$course, $year_level];

            if ($availableColumns['profile_completed']) {
                $setClauses[] = 'profile_completed = 1';
            }

            if ($availableColumns['profile_completed_at']) {
                $setClauses[] = 'profile_completed_at = NOW()';
            }

            $params[] = $currentUser['id'];

            $sql = "UPDATE students SET " . implode(', ', $setClauses) . " WHERE id = ?";
            $stmt = $db->prepare($sql);
            $result = $stmt->execute($params);
            
            if ($result) {
                // Update the current user session data
                $_SESSION['user']['course'] = $course;
                $_SESSION['user']['year_level'] = $year_level;
                $_SESSION['user']['profile_completed'] = 1;

                if ($availableColumns['profile_completed_at']) {
                    $_SESSION['user']['profile_completed_at'] = date('Y-m-d H:i:s');
                }
                
                // Force immediate redirect to dashboard
                header('Location: dashboard.php?profile_completed=1');
                exit;
            } else {
                $message = 'Failed to update profile';
                $messageType = 'error';
            }
        } catch (Exception $e) {
            $message = 'Error: ' . $e->getMessage();
            $messageType = 'error';
        }
    }
}

// Get available courses
try {
    $database = new Database();
    $db = $database->getConnection();
    
    $coursesStmt = $db->query("SELECT * FROM courses WHERE is_active = 1 ORDER BY department, course_name, major");
    $courses = $coursesStmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    $courses = [];
    $message = 'Error loading courses: ' . $e->getMessage();
    $messageType = 'error';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Complete Your Profile - Event Attendance</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="profile-completion-container">
        <div class="profile-completion-card">
            <div class="completion-header">
                <div class="completion-icon">
                    <i class="fas fa-user-graduate"></i>
                </div>
                <h1>Complete Your Profile</h1>
                <p>Please select your department, course, and year level to continue</p>
            </div>
            
            <?php if ($message): ?>
                <div class="message <?php echo $messageType; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" class="profile-form">
                <div class="form-section">
                    <h3><i class="fas fa-graduation-cap"></i> Academic Information</h3>
                    
                    <div class="form-group">
                        <label for="course">Course & Department *</label>
                        <select id="course" name="course" required>
                            <option value="">Select your course...</option>
                            <?php 
                            $currentDepartment = '';
                            foreach ($courses as $courseOption): 
                                if ($currentDepartment !== $courseOption['department']):
                                    if ($currentDepartment !== ''):
                                        echo '</optgroup>';
                                    endif;
                                    echo '<optgroup label="' . htmlspecialchars($courseOption['department']) . '">';
                                    $currentDepartment = $courseOption['department'];
                                endif;
                                
                                $courseValue = $courseOption['course_name'];
                                if ($courseOption['major']) {
                                    $courseValue .= ' - ' . $courseOption['major'];
                                }
                                
                                $displayText = $courseOption['course_name'];
                                if ($courseOption['major']) {
                                    $displayText .= ' - ' . $courseOption['major'];
                                }
                            ?>
                                <option value="<?php echo htmlspecialchars($courseValue); ?>" 
                                        <?php echo (isset($_POST['course']) && $_POST['course'] === $courseValue) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($displayText); ?>
                                </option>
                            <?php endforeach; 
                            if ($currentDepartment !== ''):
                                echo '</optgroup>';
                            endif;
                            ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="year_level">Year Level *</label>
                        <select id="year_level" name="year_level" required>
                            <option value="">Select your year level...</option>
                            <option value="1st Year" <?php echo (isset($_POST['year_level']) && $_POST['year_level'] === '1st Year') ? 'selected' : ''; ?>>1st Year</option>
                            <option value="2nd Year" <?php echo (isset($_POST['year_level']) && $_POST['year_level'] === '2nd Year') ? 'selected' : ''; ?>>2nd Year</option>
                            <option value="3rd Year" <?php echo (isset($_POST['year_level']) && $_POST['year_level'] === '3rd Year') ? 'selected' : ''; ?>>3rd Year</option>
                            <option value="4th Year" <?php echo (isset($_POST['year_level']) && $_POST['year_level'] === '4th Year') ? 'selected' : ''; ?>>4th Year</option>
                        </select>
                    </div>
                </div>
                
                <div class="completion-notice">
                    <div class="notice-icon">
                        <i class="fas fa-info-circle"></i>
                    </div>
                    <div class="notice-content">
                        <h4>Important Notice</h4>
                        <p>You can only set your academic information <strong>once</strong>. After completion, this information cannot be changed. Please make sure to select the correct course and year level.</p>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-check"></i> Complete Profile
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <style>
    body {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 20px;
        font-family: 'Roboto', sans-serif;
    }
    
    .profile-completion-container {
        width: 100%;
        max-width: 600px;
    }
    
    .profile-completion-card {
        background: white;
        border-radius: 20px;
        padding: 40px;
        box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        border: 1px solid rgba(255,255,255,0.2);
    }
    
    .completion-header {
        text-align: center;
        margin-bottom: 30px;
    }
    
    .completion-icon {
        width: 80px;
        height: 80px;
        background: linear-gradient(135deg, #667eea, #764ba2);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 20px;
        color: white;
        font-size: 32px;
    }
    
    .completion-header h1 {
        margin: 0 0 10px 0;
        color: #2d3748;
        font-size: 28px;
        font-weight: 700;
    }
    
    .completion-header p {
        margin: 0;
        color: #718096;
        font-size: 16px;
    }
    
    .form-section {
        margin-bottom: 30px;
    }
    
    .form-section h3 {
        margin: 0 0 20px 0;
        color: #2d3748;
        font-size: 18px;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .form-section h3 i {
        color: #667eea;
    }
    
    .form-group {
        margin-bottom: 20px;
    }
    
    .form-group label {
        display: block;
        margin-bottom: 8px;
        font-weight: 600;
        color: #2d3748;
        font-size: 14px;
    }
    
    .form-group select {
        width: 100%;
        padding: 15px 20px;
        border: 2px solid #e2e8f0;
        border-radius: 12px;
        font-size: 16px;
        font-family: 'Roboto', sans-serif;
        transition: all 0.3s ease;
        background: white;
    }
    
    .form-group select:focus {
        outline: none;
        border-color: #667eea;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }
    
    .completion-notice {
        background: #e8f4fd;
        border: 1px solid #bee5eb;
        border-radius: 12px;
        padding: 20px;
        margin-bottom: 30px;
        display: flex;
        gap: 15px;
    }
    
    .notice-icon {
        color: #0c5460;
        font-size: 24px;
        flex-shrink: 0;
    }
    
    .notice-content h4 {
        margin: 0 0 8px 0;
        color: #0c5460;
        font-size: 16px;
        font-weight: 600;
    }
    
    .notice-content p {
        margin: 0;
        color: #0c5460;
        font-size: 14px;
        line-height: 1.5;
    }
    
    .form-actions {
        text-align: center;
    }
    
    .btn {
        padding: 15px 30px;
        border: none;
        border-radius: 12px;
        font-size: 16px;
        font-weight: 600;
        cursor: pointer;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 10px;
        transition: all 0.3s ease;
        font-family: 'Roboto', sans-serif;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .btn-primary {
        background: linear-gradient(135deg, #667eea, #764ba2);
        color: white;
        box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);
    }
    
    .btn-primary:hover {
        background: linear-gradient(135deg, #5a6fd8, #6a4190);
        transform: translateY(-2px);
        box-shadow: 0 12px 25px rgba(102, 126, 234, 0.4);
    }
    
    .message {
        padding: 16px 20px;
        border-radius: 8px;
        margin-bottom: 25px;
        font-weight: 500;
        border-left: 4px solid;
    }
    
    .message.success {
        background: #d4edda;
        color: #155724;
        border-left-color: #28a745;
    }
    
    .message.error {
        background: #f8d7da;
        color: #721c24;
        border-left-color: #dc3545;
    }
    
    @media (max-width: 768px) {
        .profile-completion-card {
            padding: 30px 25px;
        }
        
        .completion-header h1 {
            font-size: 24px;
        }
        
        .completion-icon {
            width: 60px;
            height: 60px;
            font-size: 24px;
        }
    }
    </style>
    
    <script>
    // Form validation
    document.querySelector('.profile-form').addEventListener('submit', function(e) {
        const course = document.getElementById('course').value;
        const yearLevel = document.getElementById('year_level').value;
        
        if (!course || !yearLevel) {
            e.preventDefault();
            alert('Please select both course and year level');
            return false;
        }
        
        if (!confirm('Are you sure you want to set your academic information? This cannot be changed later.')) {
            e.preventDefault();
            return false;
        }
    });
    </script>
</body>
</html>
