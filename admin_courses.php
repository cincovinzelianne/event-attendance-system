<?php
require_once 'includes/admin_auth.php';

$adminAuth = new AdminAuth();
$currentAdmin = $adminAuth->requireAuth();

$message = '';
$messageType = '';

// Handle course actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        $database = new Database();
        $db = $database->getConnection();
        
        switch ($action) {
            case 'add_course':
                $department = trim($_POST['department']);
                $course_name = trim($_POST['course_name']);
                $major = trim($_POST['major']);
                $year_levels = json_encode($_POST['year_levels']);
                
                if (empty($department) || empty($course_name)) {
                    $message = 'Department and Course Name are required';
                    $messageType = 'error';
                } else {
                    $stmt = $db->prepare("INSERT INTO courses (department, course_name, major, year_levels) VALUES (?, ?, ?, ?)");
                    $result = $stmt->execute([$department, $course_name, $major, $year_levels]);
                    
                    if ($result) {
                        $message = 'Course added successfully!';
                        $messageType = 'success';
                    } else {
                        $message = 'Failed to add course';
                        $messageType = 'error';
                    }
                }
                break;
                
            case 'edit_course':
                $id = $_POST['course_id'];
                $department = trim($_POST['department']);
                $course_name = trim($_POST['course_name']);
                $major = trim($_POST['major']);
                $year_levels = json_encode($_POST['year_levels']);
                
                if (empty($department) || empty($course_name)) {
                    $message = 'Department and Course Name are required';
                    $messageType = 'error';
                } else {
                    $stmt = $db->prepare("UPDATE courses SET department = ?, course_name = ?, major = ?, year_levels = ? WHERE id = ?");
                    $result = $stmt->execute([$department, $course_name, $major, $year_levels, $id]);
                    
                    if ($result) {
                        $message = 'Course updated successfully!';
                        $messageType = 'success';
                    } else {
                        $message = 'Failed to update course';
                        $messageType = 'error';
                    }
                }
                break;
                
            case 'delete_course':
                $id = $_POST['course_id'];
                $stmt = $db->prepare("DELETE FROM courses WHERE id = ?");
                $result = $stmt->execute([$id]);
                
                if ($result) {
                    $message = 'Course deleted successfully!';
                    $messageType = 'success';
                } else {
                    $message = 'Failed to delete course';
                    $messageType = 'error';
                }
                break;
                
            case 'toggle_status':
                $id = $_POST['course_id'];
                $stmt = $db->prepare("UPDATE courses SET is_active = NOT is_active WHERE id = ?");
                $result = $stmt->execute([$id]);
                
                if ($result) {
                    $message = 'Course status updated successfully!';
                    $messageType = 'success';
                } else {
                    $message = 'Failed to update course status';
                    $messageType = 'error';
                }
                break;
        }
    } catch (Exception $e) {
        $message = 'Error: ' . $e->getMessage();
        $messageType = 'error';
    }
}

// Get courses with search and filter
$search = $_GET['search'] ?? '';
$department = $_GET['department'] ?? '';
$status = $_GET['status'] ?? '';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Build query conditions
    $whereConditions = [];
    $params = [];
    
    if (!empty($search)) {
        $whereConditions[] = "(department LIKE ? OR course_name LIKE ? OR major LIKE ?)";
        $searchTerm = "%$search%";
        $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm]);
    }
    
    if (!empty($department)) {
        $whereConditions[] = "department = ?";
        $params[] = $department;
    }
    
    if ($status !== '') {
        $whereConditions[] = "is_active = ?";
        $params[] = (int)$status;
    }
    
    $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
    
    // Get courses
    $query = "SELECT * FROM courses $whereClause ORDER BY department, course_name, major";
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get unique departments for filter
    $departmentsStmt = $db->query("SELECT DISTINCT department FROM courses ORDER BY department");
    $departments = $departmentsStmt->fetchAll(PDO::FETCH_COLUMN);
    
} catch (Exception $e) {
    $courses = [];
    $departments = [];
    $message = 'Error loading courses: ' . $e->getMessage();
    $messageType = 'error';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Course Management - Admin Panel</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
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
                <h1><i class="fas fa-graduation-cap"></i> Course Management</h1>
                <div class="header-actions">
                    <button class="btn btn-primary" onclick="openAddCourseModal()">
                        <i class="fas fa-plus"></i> Add Course
                    </button>
                </div>
            </div>
        </div>
        
        <div class="admin-main">
            <?php if ($message): ?>
                <div class="message <?php echo $messageType; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>
            
            <!-- Filters and Search -->
            <div class="filters-container">
                <form method="GET" class="filters-form">
                    <div class="filter-row">
                        <div class="filter-group">
                            <label for="search">Search Courses</label>
                            <input type="text" id="search" name="search" placeholder="Search by department, course, or major..." 
                                   value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        
                        <div class="filter-group">
                            <label for="department">Department</label>
                            <select id="department" name="department">
                                <option value="">All Departments</option>
                                <?php foreach ($departments as $dept): ?>
                                    <option value="<?php echo htmlspecialchars($dept); ?>" 
                                            <?php echo $department === $dept ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($dept); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <label for="status">Status</label>
                            <select id="status" name="status">
                                <option value="">All Status</option>
                                <option value="1" <?php echo $status === '1' ? 'selected' : ''; ?>>Active</option>
                                <option value="0" <?php echo $status === '0' ? 'selected' : ''; ?>>Inactive</option>
                            </select>
                        </div>
                        
                        <div class="filter-actions">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search"></i> Filter
                            </button>
                            <a href="admin_courses.php" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Clear
                            </a>
                        </div>
                    </div>
                </form>
            </div>
            
            <!-- Courses List -->
            <div class="table-container">
                <div class="table-header">
                    <h3>Courses List (<?php echo count($courses); ?> total)</h3>
                </div>
                
                <div class="courses-grid">
                    <?php if (empty($courses)): ?>
                        <div class="no-data">
                            <i class="fas fa-graduation-cap"></i>
                            <p>No courses found</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($courses as $course): ?>
                            <?php 
                            $yearLevels = json_decode($course['year_levels'], true);
                            $yearLevelsText = is_array($yearLevels) ? implode(', ', $yearLevels) : $course['year_levels'];
                            ?>
                            <div class="course-card">
                                <div class="course-header">
                                    <div class="course-title">
                                        <h4><?php echo htmlspecialchars($course['course_name']); ?></h4>
                                        <span class="course-department"><?php echo htmlspecialchars($course['department']); ?></span>
                                    </div>
                                    <div class="course-status">
                                        <span class="status-badge <?php echo $course['is_active'] ? 'active' : 'inactive'; ?>">
                                            <?php echo $course['is_active'] ? 'Active' : 'Inactive'; ?>
                                        </span>
                                    </div>
                                </div>
                                
                                <div class="course-details">
                                    <?php if ($course['major']): ?>
                                        <div class="course-major">
                                            <i class="fas fa-book"></i>
                                            <span><?php echo htmlspecialchars($course['major']); ?></span>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="course-years">
                                        <i class="fas fa-calendar-alt"></i>
                                        <span><?php echo htmlspecialchars($yearLevelsText); ?></span>
                                    </div>
                                </div>
                                
                                <div class="course-actions">
                                    <button class="btn-action edit"
                                            onclick='editCourse(<?php echo json_encode([
                                                'id' => (int)$course['id'],
                                                'department' => $course['department'],
                                                'course_name' => $course['course_name'],
                                                'major' => $course['major'],
                                                'year_levels' => json_decode($course['year_levels'], true) ?: []
                                            ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>)'
                                            title="Edit Course">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn-action toggle" 
                                            onclick="toggleCourseStatus(<?php echo $course['id']; ?>, <?php echo $course['is_active']; ?>)" 
                                            title="<?php echo $course['is_active'] ? 'Deactivate' : 'Activate'; ?>">
                                        <i class="fas fa-<?php echo $course['is_active'] ? 'ban' : 'check'; ?>"></i>
                                    </button>
                                    <button class="btn-action delete" onclick="deleteCourse(<?php echo $course['id']; ?>)" 
                                            title="Delete Course">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Add/Edit Course Modal -->
    <div id="courseModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalTitle">Add New Course</h3>
                <button class="modal-close" onclick="closeModal()">&times;</button>
            </div>
            
            <form id="courseForm" method="POST">
                <input type="hidden" name="action" id="formAction" value="add_course">
                <input type="hidden" name="course_id" id="courseId">
                
                <div class="modal-body">
                    <div class="form-group">
                        <label for="modalDepartment">Department *</label>
                        <input type="text" id="modalDepartment" name="department" required 
                               placeholder="e.g., DEPARTMENT OF EDUCATION">
                    </div>
                    
                    <div class="form-group">
                        <label for="modalCourseName">Course Name *</label>
                        <input type="text" id="modalCourseName" name="course_name" required 
                               placeholder="e.g., COED, BSIT, BSN">
                    </div>
                    
                    <div class="form-group">
                        <label for="modalMajor">Major/Specialization</label>
                        <input type="text" id="modalMajor" name="major" 
                               placeholder="e.g., EDUCATION MAJOR IN ENGLISH">
                    </div>
                    
                    <div class="form-group">
                        <label>Year Levels *</label>
                        <div class="year-levels-container">
                            <label class="checkbox-label">
                                <input type="checkbox" name="year_levels[]" value="1st Year">
                                <span>1st Year</span>
                            </label>
                            <label class="checkbox-label">
                                <input type="checkbox" name="year_levels[]" value="2nd Year">
                                <span>2nd Year</span>
                            </label>
                            <label class="checkbox-label">
                                <input type="checkbox" name="year_levels[]" value="3rd Year">
                                <span>3rd Year</span>
                            </label>
                            <label class="checkbox-label">
                                <input type="checkbox" name="year_levels[]" value="4th Year">
                                <span>4th Year</span>
                            </label>
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Course</button>
                </div>
            </form>
        </div>
    </div>
    
    <style>
    /* Admin Layout Styles */
    body {
        background: #f8f9fa;
        font-family: 'Roboto', sans-serif;
        margin: 0;
        padding: 0;
        overflow-x: hidden;
    }
    
    .admin-content {
        margin-left: 280px;
        min-height: 100vh;
        background: #f8f9fa;
        transition: margin-left 0.3s ease;
        width: calc(100% - 280px);
    }
    
    .admin-header {
        background: white;
        padding: 20px 30px;
        border-bottom: 1px solid #e9ecef;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        position: sticky;
        top: 0;
        z-index: 100;
    }
    
    .header-content {
        display: flex;
        justify-content: space-between;
        align-items: center;
        max-width: 1200px;
        margin: 0 auto;
    }
    
    .header-content h1 {
        margin: 0;
        color: #333;
        font-size: 28px;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 12px;
    }
    
    .header-content h1 i {
        color: #667eea;
        font-size: 24px;
    }
    
    .header-actions {
        display: flex;
        gap: 15px;
        align-items: center;
    }
    
    .admin-main {
        padding: 30px;
        max-width: 1400px;
        margin: 0 auto;
        width: 100%;
    }
    
    /* Filters */
    .filters-container {
        background: white;
        border-radius: 12px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        margin-bottom: 30px;
        overflow: hidden;
    }
    
    .filters-form {
        padding: 25px;
    }
    
    .filter-row {
        display: grid;
        grid-template-columns: 2fr 1fr 1fr auto;
        gap: 20px;
        align-items: end;
        width: 100%;
    }
    
    .filter-group {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }
    
    .filter-group label {
        font-weight: 600;
        color: #2d3748;
        font-size: 14px;
    }
    
    .filter-group input,
    .filter-group select {
        padding: 12px 16px;
        border: 2px solid #e2e8f0;
        border-radius: 8px;
        font-size: 14px;
        font-family: 'Roboto', sans-serif;
        transition: all 0.3s ease;
        background: white;
    }
    
    .filter-group input:focus,
    .filter-group select:focus {
        outline: none;
        border-color: #667eea;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }
    
    .filter-actions {
        display: flex;
        gap: 10px;
        align-items: end;
        flex-shrink: 0;
    }
    
    /* Table Container */
    .table-container {
        background: white;
        border-radius: 12px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        overflow: hidden;
    }
    
    .table-header {
        padding: 25px;
        border-bottom: 1px solid #f1f3f4;
    }
    
    .table-header h3 {
        margin: 0;
        color: #2d3748;
        font-size: 20px;
        font-weight: 600;
    }
    
    /* Courses Grid */
    .courses-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
        gap: 20px;
        padding: 25px;
    }
    
    .course-card {
        background: #f8f9fa;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 20px;
        transition: all 0.3s ease;
        position: relative;
    }
    
    .course-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        border-color: #667eea;
    }
    
    .course-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 15px;
    }
    
    .course-title h4 {
        margin: 0 0 5px 0;
        color: #2d3748;
        font-size: 18px;
        font-weight: 600;
    }
    
    .course-department {
        color: #718096;
        font-size: 14px;
        font-weight: 500;
    }
    
    .course-status {
        flex-shrink: 0;
    }
    
    .status-badge {
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .status-badge.active {
        background: #d4edda;
        color: #155724;
    }
    
    .status-badge.inactive {
        background: #f8d7da;
        color: #721c24;
    }
    
    .course-details {
        margin-bottom: 20px;
    }
    
    .course-major,
    .course-years {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 8px;
        color: #4a5568;
        font-size: 14px;
    }
    
    .course-major i,
    .course-years i {
        color: #667eea;
        width: 16px;
    }
    
    .course-actions {
        display: flex;
        gap: 8px;
        justify-content: flex-end;
    }
    
    .btn-action {
        width: 32px;
        height: 32px;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 12px;
        transition: all 0.3s ease;
    }
    
    .btn-action.edit {
        background: #fff3e0;
        color: #f57c00;
    }
    
    .btn-action.edit:hover {
        background: #ffe0b2;
    }
    
    .btn-action.toggle {
        background: #f3e5f5;
        color: #7b1fa2;
    }
    
    .btn-action.toggle:hover {
        background: #e1bee7;
    }
    
    .btn-action.delete {
        background: #ffebee;
        color: #d32f2f;
    }
    
    .btn-action.delete:hover {
        background: #ffcdd2;
    }
    
    .no-data {
        grid-column: 1 / -1;
        text-align: center;
        padding: 40px;
        color: #a0aec0;
    }
    
    .no-data i {
        font-size: 48px;
        margin-bottom: 15px;
        display: block;
    }
    
    .no-data p {
        margin: 0;
        font-size: 16px;
        font-weight: 500;
    }
    
    /* Modal Styles */
    .modal {
        display: none;
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0,0,0,0.5);
    }
    
    .modal-content {
        background-color: white;
        margin: 5% auto;
        border-radius: 12px;
        width: 90%;
        max-width: 600px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.3);
    }
    
    .modal-header {
        padding: 20px 25px;
        border-bottom: 1px solid #e2e8f0;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .modal-header h3 {
        margin: 0;
        color: #2d3748;
        font-size: 20px;
        font-weight: 600;
    }
    
    .modal-close {
        background: none;
        border: none;
        font-size: 24px;
        cursor: pointer;
        color: #a0aec0;
        padding: 0;
        width: 30px;
        height: 30px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .modal-close:hover {
        color: #718096;
    }
    
    .modal-body {
        padding: 25px;
    }
    
    .modal-body .form-group {
        margin-bottom: 20px;
    }
    
    .modal-body .form-group label {
        display: block;
        margin-bottom: 8px;
        font-weight: 600;
        color: #2d3748;
        font-size: 14px;
    }
    
    .modal-body .form-group input {
        width: 100%;
        padding: 12px 16px;
        border: 2px solid #e2e8f0;
        border-radius: 8px;
        font-size: 14px;
        font-family: 'Roboto', sans-serif;
        transition: all 0.3s ease;
    }
    
    .modal-body .form-group input:focus {
        outline: none;
        border-color: #667eea;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }
    
    .year-levels-container {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 10px;
    }
    
    .checkbox-label {
        display: flex;
        align-items: center;
        gap: 8px;
        cursor: pointer;
        padding: 8px;
        border-radius: 6px;
        transition: background-color 0.3s ease;
    }
    
    .checkbox-label:hover {
        background: #f8f9fa;
    }
    
    .checkbox-label input[type="checkbox"] {
        width: auto;
        margin: 0;
    }
    
    .modal-footer {
        padding: 20px 25px;
        border-top: 1px solid #e2e8f0;
        display: flex;
        gap: 15px;
        justify-content: flex-end;
    }
    
    /* Buttons */
    .btn {
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
        font-family: 'Roboto', sans-serif;
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
            width: 100%;
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
        
        .filter-row {
            grid-template-columns: 1fr;
            gap: 15px;
        }
        
        .filter-actions {
            justify-content: stretch;
        }
        
        .filter-actions .btn {
            flex: 1;
            justify-content: center;
        }
        
        .courses-grid {
            grid-template-columns: 1fr;
            padding: 20px;
        }
        
        .year-levels-container {
            grid-template-columns: 1fr;
        }
        
        .modal-content {
            width: 95%;
            margin: 10% auto;
        }
    }
    
    @media (max-width: 480px) {
        .admin-main {
            padding: 15px;
        }
        
        .courses-grid {
            padding: 15px;
        }
        
        .course-card {
            padding: 15px;
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
    
    // Modal functions
    function openAddCourseModal() {
        document.getElementById('modalTitle').textContent = 'Add New Course';
        document.getElementById('formAction').value = 'add_course';
        document.getElementById('courseForm').reset();
        document.getElementById('courseId').value = '';
        document.getElementById('modalDepartment').value = '';
        document.getElementById('modalCourseName').value = '';
        document.getElementById('modalMajor').value = '';
        document.getElementById('courseModal').style.display = 'block';
    }
    
    function editCourse(course) {
        document.getElementById('modalTitle').textContent = 'Edit Course';
        document.getElementById('formAction').value = 'edit_course';
        document.getElementById('courseId').value = course.id || '';
        document.getElementById('modalDepartment').value = course.department || '';
        document.getElementById('modalCourseName').value = course.course_name || '';
        document.getElementById('modalMajor').value = course.major || '';

        document.querySelectorAll('input[name="year_levels[]"]').forEach(function(checkbox) {
            checkbox.checked = Array.isArray(course.year_levels) && course.year_levels.includes(checkbox.value);
        });

        document.getElementById('courseModal').style.display = 'block';
    }
    
    function toggleCourseStatus(id, currentStatus) {
        if (confirm('Are you sure you want to ' + (currentStatus ? 'deactivate' : 'activate') + ' this course?')) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="action" value="toggle_status">
                <input type="hidden" name="course_id" value="${id}">
            `;
            document.body.appendChild(form);
            form.submit();
        }
    }
    
    function deleteCourse(id) {
        if (confirm('Are you sure you want to delete this course? This action cannot be undone.')) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="action" value="delete_course">
                <input type="hidden" name="course_id" value="${id}">
            `;
            document.body.appendChild(form);
            form.submit();
        }
    }
    
    function closeModal() {
        document.getElementById('courseModal').style.display = 'none';
    }
    
    // Close modal when clicking outside
    window.onclick = function(event) {
        const modal = document.getElementById('courseModal');
        if (event.target === modal) {
            closeModal();
        }
    }
    </script>
    
    <script src="assets/js/script.js"></script>
</body>
</html>





