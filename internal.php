<?php
include 'connection.php';
session_start();

if (!isset($_SESSION['user_id']) || substr($_SESSION['user_id'], 3, 2) !== '11') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Internal Panel</title>
    <link rel="stylesheet" href="admin_style.css">
</head>
<body>
<header class="site-header">
    <nav>
        <ul class="nav-list">
            <li><a href="internal_notification.php">Notification</a></li>
            <li><a href="internal_assessment.php">Assessment</a></li>
            <li class="btn"><a href="logout.php">Log Out</a></li>
        </ul>
    </nav>
</header>
<div class="admin-content">
    <h1>Welcome to the Internal Panel</h1>
    <p>Select an option from the navigation bar to manage different sections.</p>
</div>
</body>
</html>