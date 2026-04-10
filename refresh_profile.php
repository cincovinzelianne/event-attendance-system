<?php
require_once 'includes/auth.php';

$auth = new Auth();
$currentUser = $auth->getCurrentUser();

if (!$currentUser) {
    header('Location: signin.php');
    exit;
}

// Force refresh user data from database
try {
    $database = new Database();
    $db = $database->getConnection();
    $stmt = $db->prepare("SELECT * FROM students WHERE id = ?");
    $stmt->execute([$currentUser['id']]);
    $freshUserData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($freshUserData) {
        // Update session with fresh data
        $_SESSION['user'] = $freshUserData;
        echo "Profile data refreshed successfully!<br>";
        echo "Profile Completed: " . ($freshUserData['profile_completed'] ? 'YES' : 'NO') . "<br>";
        echo "Course: " . ($freshUserData['course'] ?: 'Not set') . "<br>";
        echo "Year Level: " . ($freshUserData['year_level'] ?: 'Not set') . "<br>";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}

echo "<br><a href='dashboard.php'>Go to Dashboard</a>";
?>
