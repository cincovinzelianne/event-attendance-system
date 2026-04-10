<?php
// Bottom Navigation Bar for Mobile View
// This file can be included in any page that needs mobile navigation
// Usage: include 'includes/bottom_navbar.php';

// Get current page name for active state
$currentPage = basename($_SERVER['PHP_SELF']);
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
    /* Bottom Navigation Bar Styles */
    .bottom-navbar {
        position: fixed;
        bottom: 0;
        left: 0;
        right: 0;
        background: white;
        border-top-left-radius: 20px;
        border-top-right-radius: 20px;
        box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.1);
        z-index: 1000;
        display: none; /* Hidden by default, shown on mobile */
        padding-bottom: env(safe-area-inset-bottom);
    }
    
    .bottom-navbar-content {
        display: flex;
        justify-content: space-around;
        align-items: center;
        padding: 8px 0 12px 0;
        max-width: 100%;
    }
    
    .bottom-nav-item {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        text-decoration: none;
        flex: 1;
        padding: 8px 4px;
        transition: all 0.3s ease;
        min-width: 0;
        position: relative;
    }
    
    .bottom-nav-item:active {
        transform: scale(0.95);
    }
    
    .bottom-nav-item i {
        font-size: 22px;
        margin-bottom: 4px;
        color: #6b7280;
        transition: all 0.3s ease;
    }
    
    .bottom-nav-item span {
        font-size: 11px;
        color: #6b7280;
        font-weight: 500;
        transition: all 0.3s ease;
        text-align: center;
        line-height: 1.2;
    }
    
    .bottom-nav-item.active i,
    .bottom-nav-item.active span {
        color: #3b82f6;
    }
    
    .bottom-nav-item.active::before {
        content: '';
        position: absolute;
        top: -2px;
        left: 50%;
        transform: translateX(-50%);
        width: 40px;
        height: 3px;
        background: #3b82f6;
        border-radius: 0 0 3px 3px;
    }
    
    /* Home Indicator (for modern phones) */
    .home-indicator {
        width: 134px;
        height: 5px;
        background: #000;
        border-radius: 3px;
        margin: 8px auto 4px;
    }
    
    /* Mobile View - Show bottom navbar */
    @media (max-width: 768px) {
        .bottom-navbar {
            display: block;
        }
        
        /* Hide desktop buttons on mobile */
        .desktop-buttons {
            display: none;
        }
        
        /* Add padding to body to prevent content from being hidden behind navbar */
        body {
            padding-bottom: 80px;
        }
    }
    
    /* Desktop View - Hide bottom navbar */
    @media (min-width: 769px) {
        .bottom-navbar {
            display: none;
        }
        
        .desktop-buttons {
            display: block;
        }
        
        body {
            padding-bottom: 0;
        }
    }
    
    /* Adjust for very small screens */
    @media (max-width: 360px) {
        .bottom-nav-item span {
            font-size: 10px;
        }
        
        .bottom-nav-item i {
            font-size: 20px;
        }
    }
</style>

<nav class="bottom-navbar">
    <div class="bottom-navbar-content">
        <a href="dashboard.php" class="bottom-nav-item <?php echo $currentPage == 'dashboard.php' ? 'active' : ''; ?>">
            <i class="fas fa-home"></i>
            <span>Home</span>
        </a>
        <a href="qr_code.php" class="bottom-nav-item <?php echo $currentPage == 'qr_code.php' ? 'active' : ''; ?>">
            <i class="fas fa-qrcode"></i>
            <span>QR Code</span>
        </a>
        <a href="analytics.php" class="bottom-nav-item <?php echo $currentPage == 'analytics.php' ? 'active' : ''; ?>">
            <i class="fas fa-comment-dots"></i>
            <span>Feedback</span>
        </a>
        <a href="notifications.php" class="bottom-nav-item <?php echo $currentPage == 'notifications.php' ? 'active' : ''; ?>">
            <i class="fas fa-bell"></i>
            <span>Notifications</span>
        </a>
        
    </div>
    <div class="home-indicator"></div>
</nav>
