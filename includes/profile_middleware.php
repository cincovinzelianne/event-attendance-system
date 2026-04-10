<?php
/**
 * Profile Middleware
 * Redirects students with incomplete profiles to profile completion page
 */

function checkProfileCompletion($currentUser) {
    $profileCompleted = !empty($currentUser['profile_completed']);
    $hasCourse = !empty($currentUser['course']);
    $hasYearLevel = !empty($currentUser['year_level']);

    // Check if profile is incomplete
    if ((!$profileCompleted && (!$hasCourse || !$hasYearLevel)) || !$hasCourse || !$hasYearLevel) {
        // Don't redirect if already on profile completion page
        $currentPage = basename($_SERVER['PHP_SELF']);
        if ($currentPage !== 'student_profile_completion.php') {
            header('Location: student_profile_completion.php');
            exit;
        }
    }
}

/**
 * Check if student can access a specific page
 * @param string $pageName - Name of the page to check
 * @return bool - True if access is allowed, false otherwise
 */
function canAccessPage($pageName, $currentUser) {
    $restrictedPages = [
        'qr_code.php',
        'analytics.php',
        'verify_qr_uniqueness.php',
        'qr_scanner_test.php'
    ];
    
    if (in_array($pageName, $restrictedPages)) {
        return (!empty($currentUser['profile_completed']) || (!empty($currentUser['course']) && !empty($currentUser['year_level'])))
            && !empty($currentUser['course'])
            && !empty($currentUser['year_level']);
    }
    
    return true;
}
?>





