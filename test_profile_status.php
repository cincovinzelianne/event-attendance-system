<?php
require_once 'includes/auth.php';

$auth = new Auth();
$currentUser = $auth->getCurrentUser();

if (!$currentUser) {
    header('Location: signin.php');
    exit;
}

echo "<h2>Profile Status Debug</h2>";
echo "<p><strong>Profile Completed:</strong> " . (isset($currentUser['profile_completed']) ? $currentUser['profile_completed'] : 'Not set') . "</p>";
echo "<p><strong>Course:</strong> " . ($currentUser['course'] ?: 'Not set') . "</p>";
echo "<p><strong>Year Level:</strong> " . ($currentUser['year_level'] ?: 'Not set') . "</p>";
echo "<p><strong>Profile Completed At:</strong> " . (isset($currentUser['profile_completed_at']) ? $currentUser['profile_completed_at'] : 'Not set') . "</p>";

$profileCompleted = isset($currentUser['profile_completed']) ? $currentUser['profile_completed'] : 0;
$hasCourse = !empty($currentUser['course']);
$hasYearLevel = !empty($currentUser['year_level']);

echo "<h3>Status Check:</h3>";
echo "<p>Profile Completed: " . ($profileCompleted ? 'YES' : 'NO') . "</p>";
echo "<p>Has Course: " . ($hasCourse ? 'YES' : 'NO') . "</p>";
echo "<p>Has Year Level: " . ($hasYearLevel ? 'YES' : 'NO') . "</p>";

if (!$profileCompleted || !$hasCourse || !$hasYearLevel) {
    echo "<p style='color: red;'><strong>Profile is INCOMPLETE - Banner should show</strong></p>";
} else {
    echo "<p style='color: green;'><strong>Profile is COMPLETE - Banner should be hidden</strong></p>";
}

echo "<br><a href='dashboard.php'>Go to Dashboard</a>";
?>






