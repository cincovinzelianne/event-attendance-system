<?php
require_once 'includes/admin_auth.php';

$adminAuth = new AdminAuth();
$adminAuth->logout();

header('Location: admin_signin.php?logged_out=1');
exit;
?>






