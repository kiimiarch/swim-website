<?php
session_start();

// If user is already logged in, redirect to dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit;
}

// Redirect to index.php which now contains both login and signup forms
header("Location: index.php");
exit;
?>