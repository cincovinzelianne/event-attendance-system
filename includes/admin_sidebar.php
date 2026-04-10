<?php
require_once 'includes/admin_auth.php';

$adminAuth = new AdminAuth();
$currentAdmin = $adminAuth->getCurrentAdmin();

if (!$currentAdmin) {
    header('Location: admin_signin.php');
    exit;
}
?>

<div class="admin-sidebar">
    <div class="sidebar-header">
        <div class="institution-branding">
            <img src="assets/images/llcc-logo-transparent.png" alt="LapuLapu City College logo" class="institution-logo">
            <div class="brand-copy">
                <h2 class="institution-name">LapuLapu City College</h2>
            </div>
        </div>
    </div>
    <nav class="sidebar-nav">
        <ul class="nav-menu">
            <li class="nav-item">
                <a href="admin_dashboard.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'admin_dashboard.php' ? 'active' : ''; ?>">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a href="admin_create_event.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'admin_events.php' ? 'active' : ''; ?>">
                    <i class="fas fa-calendar-alt"></i>
                    <span>Events</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a href="admin_attendance.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'admin_attendance.php' ? 'active' : ''; ?>">
                    <i class="fas fa-users"></i>
                    <span>Attendance</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a href="admin_attendance_dashboard.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'admin_attendance_dashboard.php' ? 'active' : ''; ?>">
                    <i class="fas fa-chart-line"></i>
                    <span>Attendance Report</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a href="admin_students.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'admin_students.php' ? 'active' : ''; ?>">
                    <i class="fas fa-user-graduate"></i>
                    <span>Students</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a href="admin_courses.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'admin_courses.php' ? 'active' : ''; ?>">
                    <i class="fas fa-graduation-cap"></i>
                    <span>Courses</span>
                </a>
            </li>
            
          
            
            <li class="nav-item">
                <a href="admin_qr_scanner.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'admin_qr_scanner.php' ? 'active' : ''; ?>">
                    <i class="fas fa-camera"></i>
                    <span>QR Scanner</span>
                </a>
            </li>
            
           
            <?php if ($currentAdmin['role'] == 'super_admin'): ?>
            <li class="nav-item">
                <a href="admin_users.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'admin_users.php' ? 'active' : ''; ?>">
                    <i class="fas fa-users-cog"></i>
                    <span>Admin Users</span>
                </a>
            </li>
            <?php endif; ?>
            
           
        </ul>
    </nav>
    
    <div class="sidebar-footer">
        <div class="nav-item">
            <a href="admin_logout.php" class="nav-link logout-link">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
            </a>
        </div>
    </div>
</div>

<style>
.admin-sidebar {
    position: fixed;
    top: 24px;
    left: 24px;
    width: 250px;
    height: calc(100vh - 48px);
    background: linear-gradient(180deg, rgba(255,255,255,0.98) 0%, rgba(247,249,255,0.98) 100%);
    color: #243b67;
    z-index: 1000;
    display: flex;
    flex-direction: column;
    box-shadow: 0 28px 50px rgba(101, 116, 208, 0.16);
    transition: all 0.3s ease;
    overflow-y: auto;
    border: 1px solid rgba(203, 213, 225, 0.7);
    border-radius: 24px;
    backdrop-filter: blur(16px);
}

.admin-sidebar.collapsed {
    width: 92px;
}

html.admin-dark .admin-sidebar {
    background: linear-gradient(180deg, rgba(15,23,42,0.98) 0%, rgba(17,24,39,0.98) 100%);
    color: #d6e3ff;
    border-color: #263449;
    box-shadow: 0 28px 50px rgba(2, 6, 23, 0.42);
}

.sidebar-header {
    padding: 22px 20px 18px;
    border-bottom: 1px solid rgba(226, 232, 240, 0.9);
    background: linear-gradient(180deg, rgba(243, 246, 255, 0.9) 0%, rgba(255,255,255,0.72) 100%);
}

html.admin-dark .sidebar-header,
html.admin-dark .sidebar-footer {
    background: rgba(18, 28, 47, 0.86);
    border-color: #263449;
}

.institution-branding {
    display: flex;
    flex-direction: row;
    align-items: center;
    justify-content: flex-start;
    gap: 12px;
    text-align: left;
}

.institution-logo {
    width: 40px;
    height: 40px;
    flex-shrink: 0;
    display: block;
    filter: drop-shadow(0 10px 18px rgba(92, 110, 214, 0.22));
    object-fit: contain;
}

.brand-copy {
    display: flex;
    flex-direction: column;
    gap: 0;
}

.admin-sidebar.collapsed .brand-copy {
    display: none;
}

.institution-name {
    font-size: 0.98rem;
    font-weight: 800;
    color: #4169e1;
    letter-spacing: -0.02em;
    margin: 0;
    line-height: 1.1;
    max-width: 162px;
    text-transform: none;
}

html.admin-dark .institution-name {
    color: #dce6ff;
}

.admin-details p {
    margin: 4px 0 0 0;
    font-size: 13px;
    opacity: 0.9;
    text-transform: uppercase;
    letter-spacing: 0.8px;
    font-weight: 500;
}

.sidebar-nav {
    flex: 1;
    padding: 18px 0;
    overflow-y: auto;
}

.nav-menu {
    list-style: none;
    margin: 0;
    padding: 0;
}

.nav-item {
    margin: 0;
    padding: 0 14px;
}

.nav-link {
    display: flex;
    align-items: center;
    gap: 13px;
    padding: 13px 16px;
    color: #4f628a;
    text-decoration: none;
    transition: all 0.3s ease;
    border-left: 0;
    border-radius: 14px;
    margin: 6px 0;
    font-weight: 600;
    position: relative;
    overflow: hidden;
}

html.admin-dark .nav-link {
    color: #9fb0cf;
}

.nav-link::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.55), transparent);
    transition: left 0.5s;
}

.nav-link:hover::before {
    left: 100%;
}

.nav-link:hover {
    background: rgba(87, 126, 255, 0.08);
    color: #4169e1;
    transform: translateX(2px);
    box-shadow: 0 12px 24px rgba(106, 130, 251, 0.12);
}

html.admin-dark .nav-link:hover {
    background: rgba(95, 124, 255, 0.12);
    color: #eff4ff;
    box-shadow: 0 12px 24px rgba(37, 99, 235, 0.16);
}

.nav-link.active {
    background: linear-gradient(135deg, #4f7cff 0%, #5f8bff 100%);
    color: white;
    font-weight: 600;
    box-shadow: 0 16px 28px rgba(79, 124, 255, 0.28);
}

html.admin-dark .logout-link {
    color: #9fb0cf !important;
}

.nav-link i {
    width: 20px;
    text-align: center;
    font-size: 15px;
    transition: all 0.3s ease;
}

.nav-link:hover i {
    transform: scale(1.1);
}

.nav-link span {
    font-size: 14px;
    font-weight: 600;
}

.admin-sidebar.collapsed .nav-link {
    justify-content: center;
    padding: 13px 12px;
}

.admin-sidebar.collapsed .nav-link span {
    display: none;
}

.admin-sidebar.collapsed .nav-link i {
    width: auto;
    font-size: 17px;
}

.admin-sidebar.collapsed .institution-branding {
    justify-content: center;
}

.admin-sidebar.collapsed .sidebar-footer .nav-item {
    padding: 0;
}

.sidebar-footer {
    padding: 18px 14px;
    border-top: 1px solid rgba(226, 232, 240, 0.9);
    background: rgba(248, 250, 255, 0.88);
}

.logout-link {
    color: #5a6d95 !important;
    border-radius: 14px !important;
    margin: 2px 0 !important;
}

.logout-link:hover {
    background: rgba(255,107,107,0.12) !important;
    color: #ef4444 !important;
    transform: translateX(2px) !important;
}

.logout-link i {
    color: #ef4444 !important;
}

/* Mobile responsiveness */
@media (max-width: 768px) {
    .admin-sidebar {
        transform: translateX(-100%);
        width: 250px;
        height: calc(100vh - 24px);
        top: 12px;
        left: 12px;
        box-shadow: 0 24px 42px rgba(58, 74, 152, 0.24);
    }
    
    .admin-sidebar.open {
        transform: translateX(0);
    }
    
    .sidebar-toggle {
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

    .institution-branding {
        justify-content: center;
    }
}

/* Main content adjustment */
.admin-content {
    margin-left: 280px;
    min-height: 100vh;
    background: #f8f9fa;
    transition: margin-left 0.3s ease;
}

@media (max-width: 768px) {
    .admin-content {
        margin-left: 0;
    }
}

/* Scrollbar styling for sidebar */
.admin-sidebar::-webkit-scrollbar {
    width: 6px;
}

.admin-sidebar::-webkit-scrollbar-track {
    background: rgba(148,163,184,0.18);
}

.admin-sidebar::-webkit-scrollbar-thumb {
    background: rgba(148,163,184,0.35);
    border-radius: 3px;
}

.admin-sidebar::-webkit-scrollbar-thumb:hover {
    background: rgba(148,163,184,0.5);
}

/* Animation for sidebar items */
@keyframes slideInLeft {
    from {
        opacity: 0;
        transform: translateX(-20px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

.nav-item {
    animation: slideInLeft 0.5s ease-out;
    animation-fill-mode: both;
}

.nav-item:nth-child(1) { animation-delay: 0.1s; }
.nav-item:nth-child(2) { animation-delay: 0.2s; }
.nav-item:nth-child(3) { animation-delay: 0.3s; }
.nav-item:nth-child(4) { animation-delay: 0.4s; }
.nav-item:nth-child(5) { animation-delay: 0.5s; }
.nav-item:nth-child(6) { animation-delay: 0.6s; }
.nav-item:nth-child(7) { animation-delay: 0.7s; }
.nav-item:nth-child(8) { animation-delay: 0.8s; }
.nav-item:nth-child(9) { animation-delay: 0.9s; }

/* Hover effects for better UX */
.admin-sidebar:hover {
    box-shadow: 0 28px 52px rgba(101, 116, 208, 0.18);
}

html.admin-dark .admin-sidebar:hover {
    box-shadow: 0 28px 52px rgba(2, 6, 23, 0.48);
}

/* Focus styles for accessibility */
.nav-link:focus {
    outline: 2px solid rgba(255,255,255,0.5);
    outline-offset: 2px;
}

/* High contrast mode support */
@media (prefers-contrast: high) {
    .admin-sidebar {
        background: #2d3748;
        border-right: 2px solid #000;
    }
    
    .nav-link {
        border: 1px solid transparent;
    }
    
    .nav-link:hover,
    .nav-link.active {
        border: 1px solid #fff;
    }
}
</style>
