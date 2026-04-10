<?php
session_start();
require_once 'includes/admin_auth.php';

$adminAuth = new AdminAuth();
$currentAdmin = $adminAuth->requireAuth();

$message = '';
$messageType = '';

// Fetch active courses for invitation
try {
    $database = new Database();
    $db = $database->getConnection();
    $coursesStmt = $db->prepare("SELECT id, department, course_name, major FROM courses WHERE is_active = 1 ORDER BY department, course_name, major");
    $coursesStmt->execute();
    $availableCourses = $coursesStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get unique departments for department selection
    $departmentsStmt = $db->query("SELECT DISTINCT department FROM courses WHERE is_active = 1 ORDER BY department");
    $availableDepartments = $departmentsStmt->fetchAll(PDO::FETCH_COLUMN);
} catch (Exception $e) {
    $availableCourses = [];
    $availableDepartments = [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $eventDate = $_POST['event_date'];
    $startTime = $_POST['start_time'] ?? '';
    $endTime = $_POST['end_time'] ?? '';
    $location = trim($_POST['location']);
    $maxAttendees = !empty($_POST['max_attendees']) ? (int)$_POST['max_attendees'] : null;
    
    if (empty($title) || empty($eventDate) || empty($startTime) || empty($endTime)) {
        $message = 'Please fill in all required fields';
        $messageType = 'error';
    } elseif (strtotime($eventDate . ' ' . $endTime) <= strtotime($eventDate . ' ' . $startTime)) {
        $message = 'End time must be after start time';
        $messageType = 'error';
    } else {
        try {
            $database = new Database();
            $db = $database->getConnection();
            
            // Handle file upload
            $approvalFilePath = null;
            $approvalFileName = null;
            
            if (isset($_FILES['approval_file']) && $_FILES['approval_file']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = 'uploads/approval_files/';
                if (!file_exists($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                
                $fileExtension = pathinfo($_FILES['approval_file']['name'], PATHINFO_EXTENSION);
                $fileName = 'approval_' . time() . '_' . uniqid() . '.' . $fileExtension;
                $filePath = $uploadDir . $fileName;
                
                if (move_uploaded_file($_FILES['approval_file']['tmp_name'], $filePath)) {
                    $approvalFilePath = $filePath;
                    $approvalFileName = $_FILES['approval_file']['name'];
                }
            }
            
            // Insert event
            $stmt = $db->prepare("
                INSERT INTO events (event_name, event_description, event_date, event_time, location, created_by) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            
            $result = $stmt->execute([
                $title, $description, $eventDate, $startTime, $location ?: null, $currentAdmin['id']
            ]);
            
            if ($result) {
                $eventId = $db->lastInsertId();
                
                // Google Calendar integration removed
                
                // Save selected departments if any
                if (!empty($_POST['department_names']) && is_array($_POST['department_names'])) {
                    // Ensure event_departments table exists
                    $db->exec("CREATE TABLE IF NOT EXISTS event_departments (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        event_id INT NOT NULL,
                        department VARCHAR(100) NOT NULL,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        UNIQUE KEY unique_event_department (event_id, department),
                        FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
                    
                    $insertDeptStmt = $db->prepare("INSERT IGNORE INTO event_departments (event_id, department) VALUES (?, ?)");
                    foreach ($_POST['department_names'] as $dept) {
                        $dept = trim($dept);
                        if (!empty($dept)) {
                            $insertDeptStmt->execute([$eventId, $dept]);
                        }
                    }
                }
                
                // Save invited courses if any (for backward compatibility)
                if (isset($_POST['select_all_courses'])) {
                    // If select all is checked, invite all active courses
                    $allActive = $db->query("SELECT id FROM courses WHERE is_active = 1")->fetchAll(PDO::FETCH_COLUMN);
                    if ($allActive) {
                        $db->exec("CREATE TABLE IF NOT EXISTS event_courses (\n                            id INT AUTO_INCREMENT PRIMARY KEY,\n                            event_id INT NOT NULL,\n                            course_id INT NOT NULL,\n                            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,\n                            UNIQUE KEY unique_event_course (event_id, course_id),\n                            FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,\n                            FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE\n                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
                        $insertCourseStmt = $db->prepare("INSERT IGNORE INTO event_courses (event_id, course_id) VALUES (?, ?)");
                        foreach ($allActive as $courseId) {
                            $insertCourseStmt->execute([$eventId, (int)$courseId]);
                        }
                    }
                } elseif (!empty($_POST['course_ids']) && is_array($_POST['course_ids'])) {
                    // Ensure pivot table exists
                    $db->exec("CREATE TABLE IF NOT EXISTS event_courses (\n                        id INT AUTO_INCREMENT PRIMARY KEY,\n                        event_id INT NOT NULL,\n                        course_id INT NOT NULL,\n                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,\n                        UNIQUE KEY unique_event_course (event_id, course_id),\n                        FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,\n                        FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE\n                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

                    $insertCourseStmt = $db->prepare("INSERT IGNORE INTO event_courses (event_id, course_id) VALUES (?, ?)");
                    foreach ($_POST['course_ids'] as $courseId) {
                        $courseId = (int)$courseId;
                        if ($courseId > 0) {
                            $insertCourseStmt->execute([$eventId, $courseId]);
                        }
                    }
                }

                // Send notifications to all students
                try {
                    // First, ensure student_notifications table exists
                    $db->exec("
                        CREATE TABLE IF NOT EXISTS student_notifications (
                            id INT(11) NOT NULL AUTO_INCREMENT,
                            student_id INT(11) NOT NULL,
                            event_id INT(11) NULL DEFAULT NULL,
                            action_key VARCHAR(32) NOT NULL,
                            title VARCHAR(255) NOT NULL,
                            body TEXT NOT NULL,
                            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                            read_at DATETIME NULL DEFAULT NULL,
                            PRIMARY KEY (id),
                            KEY idx_sn_student_created (student_id, created_at),
                            KEY idx_sn_event (event_id),
                            CONSTRAINT fk_sn_student FOREIGN KEY (student_id) REFERENCES students (id) ON DELETE CASCADE,
                            CONSTRAINT fk_sn_event FOREIGN KEY (event_id) REFERENCES events (id) ON DELETE SET NULL
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
                    ");
                    
                    // Get all active students
                    $studentsStmt = $db->query("SELECT id FROM students WHERE is_active = 1");
                    $students = $studentsStmt->fetchAll(PDO::FETCH_COLUMN);
                    
                    if (!empty($students)) {
                        // Prepare notification insert statement
                        $notifStmt = $db->prepare("
                            INSERT INTO student_notifications (student_id, event_id, action_key, title, body) 
                            VALUES (?, ?, ?, ?, ?)
                        ");
                        
                        // Create notification message
                        $notificationTitle = "📅 New Event: " . $title;
                        $eventDateTime = date('M d, Y \a\t g:i A', strtotime($eventDate . ' ' . $startTime));
                        $notificationBody = $description . "\n\n📍 Location: " . ($location ?: 'TBA') . "\n📅 Date & Time: " . $eventDateTime;
                        
                        // Insert notification for each student
                        foreach ($students as $studentId) {
                            $notifStmt->execute([
                                $studentId,
                                $eventId,
                                'new_event',
                                $notificationTitle,
                                $notificationBody
                            ]);
                        }
                    }
                } catch (Exception $notifError) {
                    // Log error but don't fail the event creation
                    error_log("Failed to send notifications: " . $notifError->getMessage());
                }

                $message = 'Event created successfully! 🎉 All students have been notified.';
                $messageType = 'success';
                
                // Stay on page to show success modal
                // Set a flag to show success modal
                $_SESSION['event_created_success'] = true;
            } else {
                $message = 'Failed to create event';
                $messageType = 'error';
            }
        } catch (Exception $e) {
            $message = 'Error: ' . $e->getMessage();
            $messageType = 'error';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Event - Admin Panel</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.css" rel="stylesheet">
</head>
<body class="admin-body">
    <!-- Include Admin Sidebar -->
    <?php include 'includes/admin_sidebar.php'; ?>
    
    <!-- Mobile Sidebar Toggle -->
    <button class="sidebar-toggle" onclick="toggleSidebar()">
        <i class="fas fa-bars"></i>
    </button>
    
    <!-- Main Content -->
    <div class="admin-content">
        <div class="admin-header">
            <div class="header-content">
                <h1><i class="fas fa-plus-circle"></i> Create New Event</h1>
                <div class="header-actions">
                    <a href="admin_events.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Events
                    </a>
                </div>
            </div>
        </div>
        
        <div class="admin-main">
            <div class="form-container">
                <?php if ($message): ?>
                    <div class="message <?php echo $messageType; ?>">
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>
                
                <!-- Success Modal -->
                <div id="successModal" class="success-modal <?php echo (isset($_SESSION['event_created_success']) && $_SESSION['event_created_success']) ? 'show' : ''; ?>">
                    <div class="success-modal-content">
                        <div class="success-icon-wrapper">
                            <svg class="checkmark" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 52 52">
                                <circle class="checkmark-circle" cx="26" cy="26" r="25" fill="none"/>
                                <path class="checkmark-check" fill="none" d="M14.1 27.2l7.1 7.2 16.7-16.8"/>
                            </svg>
                        </div>
                        <h2 class="success-title">Event Created Successfully!</h2>
                        <p class="success-message">Your event has been created and is now available in the events list.</p>
                        <button class="success-button" onclick="closeSuccessModal()">
                            <i class="fas fa-check"></i> Continue
                        </button>
                    </div>
                </div>
                
                <form method="POST" enctype="multipart/form-data" class="event-form">
                    <div class="form-section">
                        <h3><i class="fas fa-info-circle"></i> Event Information</h3>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="title">Event Title *</label>
                                <input type="text" id="title" name="title" required 
                                       placeholder="Enter event title"
                                       value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : ''; ?>">
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="description">Description</label>
                                <textarea id="description" name="description" rows="4" 
                                          placeholder="Enter event description"><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="location">Location</label>
                                <input type="text" id="location" name="location" 
                                       placeholder="Enter event location"
                                       value="<?php echo isset($_POST['location']) ? htmlspecialchars($_POST['location']) : ''; ?>">
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-section">
                        <h3><i class="fas fa-calendar-alt"></i> Date & Time</h3>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="event_date">Event Date *</label>
                                <div class="inline-calendar-container">
                                    <div class="calendar-section">
                                        <div id="fullcalendar"></div>
                                    </div>
                                    <div class="time-panel">
                                        <div class="time-section">
                                            <label>Start time:</label>
                                            <div class="time-inputs">
                                                <input type="time" id="start_time" name="start_time" required>
                                            </div>
                                        </div>
                                        <div class="time-section">
                                            <label>End time:</label>
                                            <div class="time-inputs">
                                                <input type="time" id="end_time" name="end_time" required>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <input type="hidden" id="event_date" name="event_date" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-section">
                        <h3><i class="fas fa-users"></i> Attendance Settings</h3>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="max_attendees">Maximum Attendees</label>
                                <input type="number" id="max_attendees" name="max_attendees" min="1" 
                                       placeholder="Leave empty for unlimited"
                                       value="<?php echo isset($_POST['max_attendees']) ? $_POST['max_attendees'] : ''; ?>">
                                <small class="form-help">Leave empty for unlimited attendees</small>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>Select Departments *</label>
                                <small class="form-help" style="margin-bottom:8px;">Only students from selected departments will appear in the attendance sheet</small>
                                <div class="departments-checkbox-grid" id="departments-checkbox-grid">
                                    <?php foreach ($availableDepartments as $dept): ?>
                                        <?php 
                                            $isChecked = (isset($_POST['department_names']) && is_array($_POST['department_names']) && in_array($dept, $_POST['department_names']));
                                        ?>
                                        <label class="department-check">
                                            <input type="checkbox" class="department-checkbox" name="department_names[]" value="<?php echo htmlspecialchars($dept); ?>" <?php echo $isChecked ? 'checked' : ''; ?>>
                                            <span><?php echo htmlspecialchars($dept); ?></span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                                <small class="form-help">Select one or more departments. Only students from these departments will be included in the attendance sheet.</small>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>Invite Specific Courses (Optional)</label>
                                <small class="form-help" style="margin-bottom:8px;">You can also select specific courses for additional filtering</small>
                                <label class="checkbox-container" style="padding:0; gap:8px; align-items:center;">
                                    <input type="checkbox" id="select_all_courses" name="select_all_courses" <?php echo isset($_POST['select_all_courses']) ? 'checked' : ''; ?>>
                                    <span>Select all courses</span>
                                </label>
                                <div class="courses-checkbox-grid" id="courses-checkbox-grid">
                                    <?php foreach ($availableCourses as $c): ?>
                                        <?php 
                                            $label = $c['department'] . ' - ' . $c['course_name'] . (!empty($c['major']) ? (' (' . $c['major'] . ')') : '');
                                            $isChecked = (isset($_POST['course_ids']) && is_array($_POST['course_ids']) && in_array((string)$c['id'], array_map('strval', $_POST['course_ids'])));
                                            if (!$isChecked && isset($_POST['select_all_courses'])) { $isChecked = true; }
                                        ?>
                                        <label class="course-check">
                                            <input type="checkbox" class="course-checkbox" name="course_ids[]" value="<?php echo (int)$c['id']; ?>" <?php echo $isChecked ? 'checked' : ''; ?>>
                                            <span><?php echo htmlspecialchars($label); ?></span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                                <small class="form-help">Optional: Select specific courses for additional filtering. If departments are selected, this will further narrow down the list.</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-section">
                        <h3><i class="fas fa-file-upload"></i> Approval Documents</h3>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="approval_file">Upload Approval Document</label>
                                <div class="file-upload-container">
                                    <input type="file" id="approval_file" name="approval_file" 
                                           accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                                    <div class="file-upload-display">
                                        <i class="fas fa-cloud-upload-alt"></i>
                                        <span>Click to upload or drag and drop</span>
                                        <small>PDF, DOC, DOCX, JPG, PNG (Max 10MB)</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    
                    
                    <div class="form-actions">
                        <button type="button" class="btn btn-secondary" id="open-events-modal">
                            <i class="fas fa-list"></i> View All Events
                        </button>
                        <button type="button" class="btn btn-secondary" onclick="history.back()">
                            <i class="fas fa-times"></i> Cancel
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Create Event
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Events Modal -->
    <div id="eventsModal" class="modal" style="display:none;">
        <div class="modal-backdrop"></div>
        <div class="modal-container" role="dialog" aria-modal="true" aria-labelledby="eventsModalTitle">
            <div class="modal-content">
                <button class="modal-close" id="close-events-modal" aria-label="Close">
                    <i class="fas fa-times"></i>
                </button>
                <div class="modal-body" id="events-modal-body">
                    <div class="modal-loading">
                        <i class="fas fa-spinner fa-spin"></i>
                        <span>Loading events…</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <style>
    /* Admin Layout Styles - SaaS theme */
    .admin-body {
        background: radial-gradient(circle at top left, #eef2ff 0, #f6f8fc 40%, #f6f7fb 100%);
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        margin: 0;
        padding: 0;
        color: #0f172a;
    }
    
    .admin-content {
        margin-left: 280px;
        min-height: 100vh;
        background: transparent;
        transition: margin-left 0.24s ease;
    }
    
    .admin-header {
        padding: 18px 28px;
        position: sticky;
        top: 0;
        z-index: 120;
        backdrop-filter: blur(18px);
        background: linear-gradient(135deg, rgba(248,250,252,0.96), rgba(238,242,255,0.98));
        border-bottom: 1px solid rgba(148,163,184,0.3);
        box-shadow: 0 16px 40px rgba(15,23,42,0.14);
    }
    
    .header-content {
        display: flex;
        align-items: center;
        justify-content: space-between;
        max-width: 1180px;
        margin: 0 auto;
        gap: 12px;
    }
    
    .header-content h1 {
        margin: 0;
        display: flex;
        align-items: center;
        gap: 10px;
        font-family: 'Poppins', system-ui, sans-serif;
        font-size: 22px;
        font-weight: 700;
        letter-spacing: -0.02em;
        color: #0f172a;
    }
    
    .header-content h1 i {
        color: #4f46e5;
        font-size: 20px;
    }
    
    .header-actions {
        display: flex;
        gap: 10px;
        align-items: center;
    }
    
    .admin-main {
        padding: 22px 28px 32px;
        max-width: 1180px;
        margin: 0 auto;
    }
    
    .form-container {
        background: rgba(248,250,252,0.96);
        border-radius: 18px;
        box-shadow: 0 18px 44px rgba(15,23,42,0.18);
        border: 1px solid rgba(148,163,184,0.32);
        overflow: hidden;
        margin-bottom: 24px;
    }
    
    .event-form {
        background: transparent;
    }
    
    .form-section {
        padding: 20px 20px 18px;
        border-bottom: 1px solid rgba(226,232,240,0.9);
        transition: background 0.18s ease;
    }
    
    .form-section:hover {
        background: rgba(249,250,251,0.9);
    }
    
    .form-section:last-child {
        border-bottom: none;
    }
    
    .form-section h3 {
        margin: 0 0 18px 0;
        color: #111827;
        font-size: 15px;
        font-weight: 700;
        display: flex;
        align-items: center;
        gap: 10px;
        padding-bottom: 8px;
        border-bottom: 1px solid rgba(226,232,240,0.9);
        text-transform: uppercase;
        letter-spacing: 0.12em;
        font-family: 'Poppins', system-ui, sans-serif;
    }
    
    .form-section h3 i {
        color: #6366f1;
        font-size: 15px;
    }
    
    .form-row {
        display: grid;
        grid-template-columns: 1fr;
        gap: 18px;
        margin-bottom: 18px;
    }
    
    .form-row:last-child {
        margin-bottom: 0;
    }
    
    @media (min-width: 768px) {
        .form-row {
            grid-template-columns: 1fr 1fr;
        }
    }
    
    .form-group {
        display: flex;
        flex-direction: column;
        gap: 6px;
    }
    
    .form-group label {
        font-weight: 600;
        color: #1f2937;
        font-size: 13px;
        margin-bottom: 2px;
    }
    
    .form-group input,
    .form-group textarea,
    .form-group select {
        padding: 12px 13px;
        border: 1.8px solid #e2e8f0;
        border-radius: 10px;
        font-size: 13px;
        font-family: 'Inter', sans-serif;
        transition: all 0.18s ease;
        background: #ffffff;
    }
    .form-group select[multiple] {
        min-height: 180px;
    }
    .departments-checkbox-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 10px 16px;
        margin-top: 8px;
        padding: 12px;
        border: 2px solid #e2e8f0;
        border-radius: 8px;
        background: #fff;
        max-height: 220px;
        overflow: auto;
    }
    @media (max-width: 768px) {
        .departments-checkbox-grid { grid-template-columns: 1fr; }
    }
    .department-check { display:flex; align-items:center; gap:10px; cursor:pointer; }
    .department-check input[type="checkbox"] { width:18px; height:18px; }
    .courses-checkbox-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 10px 16px;
        margin-top: 8px;
        padding: 12px;
        border: 2px solid #e2e8f0;
        border-radius: 8px;
        background: #fff;
        max-height: 220px;
        overflow: auto;
    }
    @media (max-width: 768px) {
        .courses-checkbox-grid { grid-template-columns: 1fr; }
    }
    .course-check { display:flex; align-items:center; gap:10px; cursor:pointer; }
    .course-check input[type="checkbox"] { width:18px; height:18px; }
    
    .form-group input:focus,
    .form-group textarea:focus,
    .form-group select:focus {
        outline: none;
        border-color: #6366f1;
        box-shadow: 0 0 0 3px rgba(129, 140, 248, 0.2);
        transform: translateY(-1px);
    }
    
    .form-group textarea {
        resize: vertical;
        min-height: 100px;
    }
    
    .form-help {
        margin-top: 4px;
        color: #6b7280;
        font-size: 11px;
        font-style: italic;
    }
    
    .file-upload-container {
        position: relative;
        margin-top: 8px;
    }
    
    .file-upload-container input[type="file"] {
        position: absolute;
        opacity: 0;
        width: 100%;
        height: 100%;
        cursor: pointer;
        z-index: 2;
    }
    
    .file-upload-display {
        border: 2px dashed #cbd5e0;
        border-radius: 8px;
        padding: 40px 20px;
        text-align: center;
        background: #f7fafc;
        transition: all 0.3s ease;
        cursor: pointer;
    }
    
    .file-upload-container:hover .file-upload-display {
        border-color: #667eea;
        background: #f0f2ff;
        transform: translateY(-2px);
    }
    
    .file-upload-display i {
        font-size: 28px;
        color: #667eea;
        margin-bottom: 12px;
        display: block;
    }
    
    .file-upload-display span {
        display: block;
        margin-bottom: 8px;
        color: #2d3748;
        font-weight: 500;
        font-size: 16px;
    }
    
    .file-upload-display small {
        color: #718096;
        font-size: 12px;
    }
    
    .checkbox-container {
        display: flex;
        align-items: center;
        gap: 12px;
        cursor: pointer;
        padding: 12px 0;
    }
    
    .checkbox-container input[type="checkbox"] {
        width: 18px;
        height: 18px;
        margin: 0;
        cursor: pointer;
    }
    
    .checkbox-container span {
        font-weight: 500;
        color: #2d3748;
    }
    
    .form-actions {
        padding: 18px 20px 20px;
        background: rgba(248,250,252,0.9);
        display: flex;
        gap: 10px;
        justify-content: flex-end;
        border-top: 1px solid rgba(226,232,240,0.9);
    }
    
    .btn {
        padding: 11px 20px;
        border: none;
        border-radius: 999px;
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: all 0.2s ease;
        font-family: 'Inter', sans-serif;
    }
    
    .btn-primary {
        background: linear-gradient(135deg, #4f46e5, #7c3aed);
        color: white;
        box-shadow: 0 10px 26px rgba(79, 70, 229, 0.4);
    }
    
    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 16px 40px rgba(79, 70, 229, 0.55);
    }
    
    .btn-secondary {
        background: rgba(148,163,184,0.2);
        color: #111827;
        border: 1px solid rgba(148,163,184,0.5);
    }
    
    .btn-secondary:hover {
        background: rgba(148,163,184,0.32);
        transform: translateY(-1px);
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
    
    /* Success Modal Styles */
    .success-modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.6);
        z-index: 10000;
        align-items: center;
        justify-content: center;
        opacity: 0;
        transition: opacity 0.3s ease;
    }
    
    .success-modal.show {
        display: flex;
        opacity: 1;
        animation: fadeIn 0.3s ease;
    }
    
    @keyframes fadeIn {
        from {
            opacity: 0;
        }
        to {
            opacity: 1;
        }
    }
    
    .success-modal-content {
        background: white;
        border-radius: 20px;
        padding: 50px 40px;
        max-width: 500px;
        width: 90%;
        text-align: center;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        transform: scale(0.8);
        animation: modalSlideIn 0.4s ease forwards;
    }
    
    @keyframes modalSlideIn {
        from {
            transform: scale(0.8) translateY(-20px);
            opacity: 0;
        }
        to {
            transform: scale(1) translateY(0);
            opacity: 1;
        }
    }
    
    .success-icon-wrapper {
        margin-bottom: 30px;
        display: flex;
        justify-content: center;
        align-items: center;
    }
    
    .checkmark {
        width: 100px;
        height: 100px;
        border-radius: 50%;
        display: block;
        stroke-width: 3;
        stroke: #4caf50;
        stroke-miterlimit: 10;
        box-shadow: inset 0px 0px 0px #4caf50;
        animation: fill 0.4s ease-in-out 0.4s forwards, scale 0.3s ease-in-out 0.9s both;
    }
    
    .checkmark-circle {
        stroke-dasharray: 166;
        stroke-dashoffset: 166;
        stroke-width: 3;
        stroke-miterlimit: 10;
        stroke: #4caf50;
        fill: none;
        animation: stroke 0.6s cubic-bezier(0.65, 0, 0.45, 1) forwards;
    }
    
    .checkmark-check {
        transform-origin: 50% 50%;
        stroke-dasharray: 48;
        stroke-dashoffset: 48;
        animation: stroke 0.3s cubic-bezier(0.65, 0, 0.45, 1) 0.8s forwards;
    }
    
    @keyframes stroke {
        100% {
            stroke-dashoffset: 0;
        }
    }
    
    @keyframes scale {
        0%, 100% {
            transform: none;
        }
        50% {
            transform: scale3d(1.1, 1.1, 1);
        }
    }
    
    @keyframes fill {
        100% {
            box-shadow: inset 0px 0px 0px 30px #4caf50;
        }
    }
    
    .success-title {
        font-size: 28px;
        font-weight: 700;
        color: #2d3748;
        margin: 0 0 15px 0;
        animation: fadeInUp 0.5s ease 0.3s both;
    }
    
    .success-message {
        font-size: 16px;
        color: #718096;
        margin: 0 0 30px 0;
        line-height: 1.6;
        animation: fadeInUp 0.5s ease 0.4s both;
    }
    
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    .success-button {
        background: linear-gradient(135deg, #667eea, #764ba2);
        color: white;
        border: none;
        padding: 14px 35px;
        border-radius: 10px;
        font-size: 16px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        display: inline-flex;
        align-items: center;
        gap: 10px;
        animation: fadeInUp 0.5s ease 0.5s both;
    }
    
    .success-button:hover {
        background: linear-gradient(135deg, #5a6fd8, #6a4190);
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
    }
    
    .success-button:active {
        transform: translateY(0);
    }
    
    /* Modal styles */
    .modal { position: fixed; inset: 0; z-index: 1100; }
    .modal-backdrop { position:absolute; inset:0; background: rgba(0,0,0,0.45); opacity:0; transition: opacity .2s ease; }
    .modal-container { position: relative; height:100%; display:flex; align-items:center; justify-content:center; padding: 20px; }
    .modal-content { width: min(100%, 900px); background:#fff; border-radius:12px; box-shadow: 0 10px 40px rgba(0,0,0,0.2); transform: translateY(10px); opacity:0; transition: all .2s ease; position:relative; overflow:hidden; }
    .modal.show .modal-backdrop { opacity:1; }
    .modal.show .modal-content { transform: translateY(0); opacity:1; }
    .modal-close { position:absolute; top:10px; right:10px; background:transparent; border:none; cursor:pointer; font-size:18px; color:#4a5568; }
    .modal-body { max-height: 70vh; overflow:auto; }
    .modal-loading { display:flex; align-items:center; gap:10px; padding:20px; color:#4a5568; }
    
    /* Inline Calendar Styles */
    .inline-calendar-container {
        background: white;
        border: 2px solid #e2e8f0;
        border-radius: 12px;
        padding: 25px;
        margin-top: 12px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        display: flex;
        flex-wrap: wrap;
        gap: 30px;
        align-items: flex-start;
    }
    
    .calendar-section {
        flex: 1;
        min-width: 300px;
        max-width: 100%;
        overflow: hidden;
    }
    
    .calendar-section #fullcalendar {
        width: 100%;
        max-width: 100%;
    }
    
    .time-panel {
        display: flex;
        flex-direction: column;
        gap: 16px;
        min-width: 260px;
        flex-shrink: 0;
    }
    
    .time-section {
        display: flex;
        flex-direction: column;
        gap: 10px;
        padding: 18px;
        background: #f8f9fa;
        border-radius: 10px;
        border: 1px solid #e2e8f0;
    }
    
    .time-section label {
        font-size: 14px;
        font-weight: 600;
        color: #2d3748;
        margin: 0;
        display: block;
    }
    
    .time-inputs {
        display: flex;
        align-items: center;
        gap: 8px;
        width: 100%;
    }
    
    .time-inputs input[type="time"] {
        flex: 1;
        padding: 10px 12px;
        border: 2px solid #e2e8f0;
        border-radius: 6px;
        font-size: 14px;
        font-weight: 500;
        color: #2d3748;
        transition: all 0.3s ease;
        background: white;
        min-width: 0;
    }
    
    .time-inputs input[type="time"]:focus {
        outline: none;
        border-color: #667eea;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }
    
    .time-inputs span {
        font-size: 18px;
        color: #718096;
        font-weight: 600;
    }
    
    /* FullCalendar customizations */
    .fc {
        font-family: 'Roboto', sans-serif;
        width: 100% !important;
    }
    
    .fc-toolbar {
        margin-bottom: 1em !important;
    }
    
    .fc-toolbar-title {
        font-size: 18px !important;
        font-weight: 600 !important;
        color: #2d3748 !important;
    }
    
    .fc-button {
        background: #667eea !important;
        border: none !important;
        border-radius: 6px !important;
        font-size: 12px !important;
        padding: 8px 16px !important;
        font-weight: 500 !important;
        transition: all 0.3s ease !important;
    }
    
    .fc-button:hover {
        background: #5a6fd8 !important;
        transform: translateY(-1px) !important;
    }
    
    .fc-button-primary:not(:disabled):active,
    .fc-button-primary:not(:disabled).fc-button-active {
        background: #5a6fd8 !important;
    }
    
    .fc-daygrid-day {
        cursor: pointer;
        transition: all 0.3s ease;
    }
    
    .fc-daygrid-day:hover {
        background: #f7fafc !important;
    }
    
    .fc-daygrid-day.fc-day-selected {
        background: #667eea !important;
    }
    
    .fc-daygrid-day.fc-day-selected .fc-daygrid-day-number {
        color: white !important;
        font-weight: 600 !important;
    }
    
    /* Custom selected date highlight */
    .fc-day-selected-custom {
        background: #667eea !important;
        border-radius: 50% !important;
    }
    
    .fc-day-selected-custom .fc-daygrid-day-number {
        color: white !important;
        font-weight: 600 !important;
        background: #667eea !important;
        border-radius: 50% !important;
        width: 28px !important;
        height: 28px !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        margin: 2px auto !important;
    }
    
    .fc-day-selected-custom:hover {
        background: #5a6fd8 !important;
    }
    
    .fc-day-selected-custom:hover .fc-daygrid-day-number {
        background: #5a6fd8 !important;
    }
    
    .fc-daygrid-day-number {
        padding: 4px !important;
        font-size: 14px !important;
    }
    
    .fc-col-header-cell {
        padding: 8px 4px !important;
    }
    
    .fc-col-header-cell-cushion {
        font-size: 12px !important;
        font-weight: 600 !important;
        color: #4a5568 !important;
    }
    
    .fc-daygrid-day:hover {
        background: #f0f2ff !important;
    }
    
    .fc-daygrid-day.fc-day-today {
        background: #e8f0fe !important;
        font-weight: 600;
    }
    
    .fc-daygrid-day.fc-day-selected {
        background: #667eea !important;
        color: white !important;
        font-weight: 600;
    }
    
    /* Smooth hover/selection animations */
    .fc-daygrid-day {
        transition: background-color 120ms ease, color 120ms ease;
    }
    
    /* Mobile Sidebar Toggle */
    .sidebar-toggle {
        display: none;
        position: fixed;
        top: 20px;
        left: 20px;
        z-index: 1001;
        background: linear-gradient(135deg, #667eea, #764ba2);
        color: white;
        border: none;
        padding: 12px 15px;
        border-radius: 8px;
        cursor: pointer;
        font-size: 18px;
        box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        transition: all 0.3s ease;
    }
    
    .sidebar-toggle:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
    }
    
    /* Mobile Responsiveness */
    @media (max-width: 768px) {
        .sidebar-toggle {
            display: block;
        }
        
        .admin-content {
            margin-left: 0;
        }
        
        .admin-header {
            padding: 15px 20px;
        }
        
        .header-content h1 {
            font-size: 24px;
        }
        
        .admin-main {
            padding: 20px;
        }
        
        .form-section {
            padding: 20px;
        }
        
        .form-actions {
            flex-direction: column;
            padding: 20px;
        }
        
        .btn {
            width: 100%;
            justify-content: center;
        }
        
        .inline-calendar-container {
            padding: 15px;
            flex-direction: column;
        }
        
        .calendar-section {
            min-width: 100%;
            width: 100%;
        }
        
        .time-panel {
            min-width: 100%;
            width: 100%;
        }
        
        .time-section {
            flex-direction: column;
            align-items: flex-start;
            gap: 10px;
        }
        
        .time-inputs input[type="time"] {
            width: 100%;
        }
    }
    
    @media (max-width: 480px) {
        .admin-main {
            padding: 15px;
        }
        
        .form-section {
            padding: 15px;
        }
        
        .form-section h3 {
            font-size: 18px;
        }
    }
    </style>
    
    <script>
    // Mobile sidebar toggle function
    function toggleSidebar() {
        const sidebar = document.querySelector('.admin-sidebar');
        sidebar.classList.toggle('open');
    }
    
    // Close sidebar when clicking outside on mobile
    document.addEventListener('click', function(e) {
        const sidebar = document.querySelector('.admin-sidebar');
        const toggle = document.querySelector('.sidebar-toggle');
        
        if (window.innerWidth <= 768 && 
            !sidebar.contains(e.target) && 
            !toggle.contains(e.target) && 
            sidebar.classList.contains('open')) {
            sidebar.classList.remove('open');
        }
    });
    
    // Inline FullCalendar
    document.addEventListener('DOMContentLoaded', function() {
        let selectedDate = null;
        let calendar = null;
        let selectedDateStr = null;
        
        // Initialize FullCalendar
        function initCalendar() {
            const calendarEl = document.getElementById('fullcalendar');
            calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: ''
                },
                selectable: true,
                selectMirror: true,
                dayMaxEvents: true,
                height: 320,
                fixedWeekCount: false,
                showNonCurrentDates: false,
                validRange: { start: new Date().toISOString().split('T')[0] },
                selectOverlap: false,
                selectAllow: function(selectInfo) {
                    // Only allow single day selection
                    const start = selectInfo.start;
                    const end = selectInfo.end;
                    const diffTime = end - start;
                    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
                    return diffDays === 1;
                },
                select: function(info) {
                    selectedDate = info.start;
                    // Format date in local timezone to avoid timezone shifts
                    selectedDateStr = formatDateLocal(selectedDate);
                    updateHiddenInputs();
                    calendar.unselect(); // Clear the selection overlay
                    setTimeout(function() {
                        highlightSelectedDate();
                    }, 50);
                },
                dateClick: function(info) {
                    selectedDate = info.date;
                    // Format date in local timezone to avoid timezone shifts
                    selectedDateStr = formatDateLocal(selectedDate);
                    updateHiddenInputs();
                    setTimeout(function() {
                        highlightSelectedDate();
                    }, 50);
                },
                datesSet: function() {
                    // Re-apply highlight when calendar view changes
                    setTimeout(function() {
                        if (selectedDateStr) {
                            highlightSelectedDate();
                        }
                    }, 100);
                }
            });
            calendar.render();
            // Ensure calendar resizes if container width changes
            window.addEventListener('resize', function() {
                calendar.updateSize();
            });
        }
        
        // Format date in local timezone (YYYY-MM-DD)
        function formatDateLocal(date) {
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            return `${year}-${month}-${day}`;
        }
        
        // Highlight the selected date
        function highlightSelectedDate() {
            if (!selectedDateStr || !calendar) return;
            
            // Remove previous selection highlight
            const prevSelected = document.querySelectorAll('.fc-day-selected-custom');
            prevSelected.forEach(el => {
                el.classList.remove('fc-day-selected-custom');
            });
            
            // Find and highlight the selected date
            // FullCalendar uses data-date attribute in format YYYY-MM-DD
            const allDays = calendar.el.querySelectorAll('.fc-daygrid-day');
            allDays.forEach(dayEl => {
                const dayDate = dayEl.getAttribute('data-date');
                if (dayDate === selectedDateStr) {
                    dayEl.classList.add('fc-day-selected-custom');
                }
            });
        }
        
        // Update hidden inputs
        function updateHiddenInputs() {
            if (selectedDate) {
                // Use local date formatting to avoid timezone issues
                const dateStr = formatDateLocal(selectedDate);
                selectedDateStr = dateStr;
                document.getElementById('event_date').value = dateStr;
            }
        }
        
        // Initialize calendar immediately
        initCalendar();
        
        // Time input changes ensure a date is selected before submit
    });
    
    // File upload preview
    document.getElementById('approval_file').addEventListener('change', function(e) {
        const file = e.target.files[0];
        const display = document.querySelector('.file-upload-display');
        
        if (file) {
            display.innerHTML = `
                <i class="fas fa-file"></i>
                <span>${file.name}</span>
                <small>${(file.size / 1024 / 1024).toFixed(2)} MB</small>
            `;
        }
    });
    
    // Form validation
    document.querySelector('.event-form').addEventListener('submit', function(e) {
        const eventDate = document.getElementById('event_date').value;
        const startTime = document.getElementById('start_time').value;
        const endTime = document.getElementById('end_time').value;
        const today = new Date().toISOString().split('T')[0];
        
        if (eventDate < today) {
            e.preventDefault();
            alert('Event date cannot be in the past');
            return false;
        }
        if (!startTime || !endTime) {
            e.preventDefault();
            alert('Please set both start and end times');
            return false;
        }
        if (endTime <= startTime) {
            e.preventDefault();
            alert('End time must be after start time');
            return false;
        }
        if (!eventDate) {
            e.preventDefault();
            alert('Please select a date');
            return false;
        }
        
        // Validate at least one department is selected
        const deptCheckboxes = document.querySelectorAll('.department-checkbox:checked');
        if (deptCheckboxes.length === 0) {
            e.preventDefault();
            alert('Please select at least one department');
            return false;
        }
    });

    // Events Modal logic
    (function(){
        const openBtn = document.getElementById('open-events-modal');
        const modal = document.getElementById('eventsModal');
        const closeBtn = document.getElementById('close-events-modal');
        const bodyEl = document.getElementById('events-modal-body');
        if (!openBtn || !modal) return;
        function openModal() {
            modal.style.display = 'block';
            setTimeout(()=> modal.classList.add('show'), 10);
            bodyEl.innerHTML = '<div class="modal-loading"><i class="fas fa-spinner fa-spin"></i><span>Loading events…</span></div>';
            fetch('admin_events_modal.php', { credentials: 'same-origin' })
                .then(r => r.text())
                .then(html => { bodyEl.innerHTML = html; })
                .catch(() => { bodyEl.innerHTML = '<div class="modal-loading">Failed to load events.</div>'; });
        }
        function closeModal() {
            modal.classList.remove('show');
            setTimeout(()=> { modal.style.display = 'none'; }, 200);
        }
        openBtn.addEventListener('click', openModal);
        closeBtn.addEventListener('click', closeModal);
        modal.addEventListener('click', function(e){ if (e.target.classList.contains('modal-backdrop')) closeModal(); });
        document.addEventListener('keydown', function(e){ if (e.key === 'Escape' && modal.classList.contains('show')) closeModal(); });
    })();
    
    // Success Modal Functions
    function closeSuccessModal() {
        const modal = document.getElementById('successModal');
        if (modal) {
            modal.classList.remove('show');
            setTimeout(function() {
                modal.style.display = 'none';
                // Clear the session flag
                fetch('admin_create_event.php?clear_success=1', { method: 'GET' });
            }, 300);
        }
    }
    
    // Show success modal if flag is set
    document.addEventListener('DOMContentLoaded', function() {
        <?php if (isset($_SESSION['event_created_success']) && $_SESSION['event_created_success']): ?>
            const modal = document.getElementById('successModal');
            if (modal) {
                modal.style.display = 'flex';
                setTimeout(function() {
                    modal.classList.add('show');
                }, 10);
                
                // Reset form after showing success
                const form = document.querySelector('.event-form');
                if (form) {
                    form.reset();
                    // Clear calendar selection
                    if (typeof calendar !== 'undefined' && calendar) {
                        calendar.unselect();
                    }
                }
            }
            <?php 
            // Clear the session flag after showing
            unset($_SESSION['event_created_success']);
            ?>
        <?php endif; ?>
        
        // Handle clear_success parameter
        <?php if (isset($_GET['clear_success'])): ?>
            // Already handled by PHP clearing session
        <?php endif; ?>
    });
    
    // Close modal when clicking outside
    document.addEventListener('click', function(e) {
        const modal = document.getElementById('successModal');
        if (modal && e.target === modal) {
            closeSuccessModal();
        }
    });
    
    // Close modal with Escape key
    document.addEventListener('keydown', function(e) {
        const modal = document.getElementById('successModal');
        if (e.key === 'Escape' && modal && modal.classList.contains('show')) {
            closeSuccessModal();
        }
    });
    
    // Courses select-all behavior
    (function(){
        const selectAll = document.getElementById('select_all_courses');
        const grid = document.getElementById('courses-checkbox-grid');
        if (!grid || !selectAll) return;
        function updateFromMaster() {
            const checked = selectAll.checked;
            grid.querySelectorAll('input.course-checkbox').forEach(cb => { cb.checked = checked; });
        }
        function updateMaster() {
            const boxes = Array.from(grid.querySelectorAll('input.course-checkbox'));
            const allChecked = boxes.length > 0 && boxes.every(cb => cb.checked);
            selectAll.checked = allChecked;
        }
        selectAll.addEventListener('change', updateFromMaster);
        grid.addEventListener('change', function(e){ if (e.target.classList.contains('course-checkbox')) updateMaster(); });
        // Initialize master state on load
        updateMaster();
    })();
    </script>
    
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js"></script>
    <script src="assets/js/script.js"></script>
</body>
</html>
