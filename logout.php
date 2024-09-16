<?php
session_start();
session_unset(); // Clear all session variables
session_destroy(); // Destroy the session

// Optionally, clear cookies if needed
if (isset($_COOKIE['name'])) {
    setcookie('name', '', time() - 3600, '/'); // Adjust parameters as necessary
}

// Redirect to login page
header('Location: login.php');
exit();
?>
