<?php
require_once 'includes/admin_auth.php';
require_once 'config/database.php';

$adminAuth = new AdminAuth();
$currentAdmin = $adminAuth->requireAuth();

$database = new Database();
$db = $database->getConnection();

$date = $_GET['date'] ?? date('Y-m-d');

// Fetch attendance records for the selected date
$query = "
    SELECT 
        s.student_id, 
        s.first_name, 
        s.last_name, 
        e.event_name,
        a.attendance_time,
        a.status
    FROM attendance a
    JOIN students s ON a.student_id = s.id
    JOIN events e ON a.event_id = e.id
    WHERE e.event_date = ?
    ORDER BY a.attendance_time ASC
";
$stmt = $db->prepare($query);
$stmt->execute([$date]);
$records = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate summary stats
$totalScans = count($records);
$presentCount = 0;
$lateCount = 0;
foreach ($records as $r) {
    if (($r['status'] ?? '') === 'present') {
        $presentCount++;
    } elseif (($r['status'] ?? '') === 'late') {
        $lateCount++;
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Report - Lapu-Lapu City College</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .report-container { padding: 30px; max-width: 1200px; margin: 0 auto; }
        .report-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .date-picker { display: flex; align-items: center; gap: 10px; }
        .summary-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .summary-card { background: white; padding: 20px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); border-left: 4px solid #4f46e5; }
        .summary-card h3 { font-size: 0.85rem; color: #718096; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 8px; }
        .summary-card .value { font-size: 1.5rem; font-weight: 700; color: #1a202c; }
        .attendance-table { width: 100%; border-collapse: collapse; background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
        .attendance-table th, .attendance-table td { padding: 15px; text-align: left; border-bottom: 1px solid #edf2f7; }
        .attendance-table th { background: #f7fafc; font-weight: 600; color: #4a5568; }
        .hours-badge { padding: 4px 8px; border-radius: 6px; background: #ebf8ff; color: #2b6cb0; font-weight: 600; font-size: 0.85rem; }
    </style>
</head>
<body class="admin-body">
    <?php include 'includes/admin_sidebar.php'; ?>
    
    <div class="admin-content">
        <div class="report-container">
            <div class="report-header">
                <h1><i class="fas fa-chart-line"></i> Daily Attendance Report</h1>
                <form class="date-picker" method="GET">
                    <label for="date">Select Date:</label>
                    <input type="date" id="date" name="date" value="<?php echo htmlspecialchars($date); ?>" onchange="this.form.submit()">
                </form>
            </div>

            <div class="summary-grid">
                <div class="summary-card">
                    <h3>Total Employees/Students</h3>
                    <div class="value"><?php echo count(array_unique(array_column($records, 'student_id'))); ?></div>
                </div>
                <div class="summary-card" style="border-left-color: #10b981;">
                    <h3>Total Attendance Logs</h3>
                    <div class="value"><?php echo $totalScans; ?></div>
                </div>
                <div class="summary-card" style="border-left-color: #f59e0b;">
                    <h3>Present</h3>
                    <div class="value"><?php echo $presentCount; ?></div>
                </div>
                <div class="summary-card" style="border-left-color: #ef4444;">
                    <h3>Late</h3>
                    <div class="value"><?php echo $lateCount; ?></div>
                </div>
            </div>

            <table class="attendance-table">
                <thead>
                    <tr>
                        <th>Student/Employee ID</th>
                        <th>Name</th>
                        <th>Event</th>
                        <th>Attendance Time</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($records)): ?>
                        <tr>
                            <td colspan="5" style="text-align: center; padding: 40px; color: #718096;">No attendance records found for this date.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($records as $r): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($r['student_id']); ?></strong></td>
                                <td><?php echo htmlspecialchars($r['first_name'] . ' ' . $r['last_name']); ?></td>
                                <td><?php echo htmlspecialchars($r['event_name']); ?></td>
                                <td>
                                    <span class="hours-badge">
                                        <?php echo $r['attendance_time'] ? date('g:i A', strtotime($r['attendance_time'])) : '-'; ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="hours-badge">
                                        <?php echo htmlspecialchars(ucfirst($r['status'] ?: 'unknown')); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
